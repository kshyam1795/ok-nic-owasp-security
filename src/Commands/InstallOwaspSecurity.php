<?php

namespace Growats\OkNicOwaspSecurity\Commands;

use Growats\OkNicOwaspSecurity\Services\RoleService;
use Growats\OkNicOwaspSecurity\Services\SecuritySettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallOwaspSecurity extends Command
{
    protected $signature = 'owasp:install
                            {--fresh : Re-seed settings and roles}
                            {--skip-middleware : Do not patch bootstrap/Kernel for global middleware}
                            {--skip-trait : Do not remind about HasOwaspRoles trait}
                            {--email= : Super admin email override}
                            {--password= : Super admin password override}';

    protected $description = 'Install OWASP security package: migrate, seed settings/roles, create Super Admin, wire middleware';

    public function handle(SecuritySettingsService $settings, RoleService $roles): int
    {
        $this->info('=== OWASP Security Installer ===');
        $this->newLine();

        if ($email = $this->option('email')) {
            config(['owasp-security.super_admin.email' => $email]);
        }
        if ($password = $this->option('password')) {
            config(['owasp-security.super_admin.password' => $password]);
        }

        $this->step('Publishing config');
        $this->callSilent('vendor:publish', [
            '--provider' => 'Growats\\OkNicOwaspSecurity\\OwaspSecurityServiceProvider',
            '--tag' => 'owasp-security-config',
            '--force' => true,
        ]);

        $this->step('Running migrations');
        $this->call('migrate', ['--force' => true]);

        $this->step('Seeding security settings (headers, auth, protections)');
        $settings->seedDefaults();

        $this->step('Seeding RBAC roles & permissions');
        $roles->seedRolesAndPermissions();

        $this->step('Ensuring Super Admin account');
        $admin = $roles->ensureSuperAdmin();

        if (!$this->option('skip-middleware')) {
            $this->step('Wiring global middleware');
            $this->wireMiddleware();
        }

        $this->step('Patching User model trait (if possible)');
        $this->wireUserTrait();

        $this->newLine();
        $this->info('Installation complete.');
        $this->table(
            ['Item', 'Value'],
            [
                ['Dashboard', url(config('owasp-security.route_prefix', 'owasp-security'))],
                ['Settings', url(config('owasp-security.route_prefix', 'owasp-security') . '/settings')],
                ['Super Admin email', $admin['email']],
                ['Super Admin password', $admin['created'] ? $admin['password'] : '(existing user — password unchanged)'],
                ['Created new admin?', $admin['created'] ? 'yes' : 'no'],
            ]
        );

        $this->warn('Store the Super Admin password securely. Change it after first login.');
        $this->line('Next: login → open Settings → enable/disable controls per auditor findings.');
        $this->line('Docs: docs/INSTALLATION.md · docs/SOP.md');

        return self::SUCCESS;
    }

    protected function step(string $message): void
    {
        $this->line("<fg=cyan>→</> {$message}");
    }

    protected function wireMiddleware(): void
    {
        $classes = [
            \Growats\OkNicOwaspSecurity\Middleware\SecurityHeaders::class,
            \Growats\OkNicOwaspSecurity\Middleware\XssSanitization::class,
            \Growats\OkNicOwaspSecurity\Middleware\SqlInjectionProtection::class,
            \Growats\OkNicOwaspSecurity\Middleware\RateLimiting::class,
            \Growats\OkNicOwaspSecurity\Middleware\CorsProtection::class,
            \Growats\OkNicOwaspSecurity\Middleware\SessionSecurity::class,
            \Growats\OkNicOwaspSecurity\Middleware\AuthenticationProtection::class,
        ];

        // Laravel 11+ bootstrap/app.php
        $bootstrap = base_path('bootstrap/app.php');
        if (File::exists($bootstrap)) {
            $contents = File::get($bootstrap);
            if (!str_contains($contents, 'OkNicOwaspSecurity\\Middleware\\SecurityHeaders')) {
                $this->comment('  Laravel 11+ detected. Middleware is auto-pushed by the service provider when OWASP_AUTO_MIDDLEWARE=true.');
                $this->comment('  Ensure .env has OWASP_AUTO_MIDDLEWARE=true (default).');
            }
            return;
        }

        // Laravel 9/10 Http/Kernel.php
        $kernel = app_path('Http/Kernel.php');
        if (!File::exists($kernel)) {
            $this->comment('  Could not find Kernel.php — provider will push middleware at runtime.');
            return;
        }

        $contents = File::get($kernel);
        $changed = false;

        foreach ($classes as $class) {
            if (!str_contains($contents, $class)) {
                // Insert into $middleware array if present
                if (preg_match('/\$middleware\s*=\s*\[/', $contents)) {
                    $contents = preg_replace(
                        '/(\$middleware\s*=\s*\[)/',
                        "$1\n        \\{$class}::class,",
                        $contents,
                        1
                    );
                    $changed = true;
                }
            }
        }

        if ($changed) {
            File::put($kernel, $contents);
            $this->info('  Patched app/Http/Kernel.php');
        } else {
            $this->comment('  Middleware already present or auto-registered by provider.');
        }
    }

    protected function wireUserTrait(): void
    {
        if ($this->option('skip-trait')) {
            return;
        }

        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        if (!class_exists($userModel)) {
            $this->warn("  User model {$userModel} not found. Manually add HasOwaspRoles trait.");
            return;
        }

        try {
            $ref = new \ReflectionClass($userModel);
            $file = $ref->getFileName();
            if (!$file || !File::exists($file)) {
                return;
            }

            $contents = File::get($file);
            $trait = 'Growats\\OkNicOwaspSecurity\\Traits\\HasOwaspRoles';

            if (str_contains($contents, 'HasOwaspRoles')) {
                $this->comment('  HasOwaspRoles already on User model.');
                return;
            }

            if (!str_contains($contents, "use {$trait};")) {
                $contents = preg_replace(
                    '/(namespace\s+[^;]+;)/',
                    "$1\n\nuse {$trait};",
                    $contents,
                    1
                );
            }

            if (preg_match('/class\s+User[^{]*\{/', $contents)) {
                $contents = preg_replace(
                    '/(class\s+User[^{]*\{)/',
                    "$1\n    use HasOwaspRoles;\n",
                    $contents,
                    1
                );
                File::put($file, $contents);
                $this->info('  Added HasOwaspRoles trait to User model.');
            }
        } catch (\Throwable $e) {
            $this->warn('  Could not patch User model automatically: ' . $e->getMessage());
            $this->line('  Add: use Growats\\OkNicOwaspSecurity\\Traits\\HasOwaspRoles;');
        }
    }
}
