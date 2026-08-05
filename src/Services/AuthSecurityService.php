<?php

namespace Growats\OkNicOwaspSecurity\Services;

use Growats\OkNicOwaspSecurity\Models\LoginAttempt;
use Growats\OkNicOwaspSecurity\Models\SecurityAuditLog;
use Illuminate\Support\Facades\Schema;

class AuthSecurityService
{
    public function __construct(
        protected SecuritySettingsService $settings
    ) {}

    public function isLocked(string $email, ?string $ip = null): bool
    {
        if (!$this->settings->isEnabled('auth.account_lockout', true)) {
            return false;
        }

        if (!$this->tableReady()) {
            return false;
        }

        $meta = $this->settings->all()['auth.account_lockout']['meta'] ?? [];
        $lockoutMinutes = (int) ($meta['lockout_minutes'] ?? 15);

        $query = LoginAttempt::query()
            ->where('email', strtolower($email))
            ->whereNotNull('locked_until')
            ->where('locked_until', '>', now());

        if ($ip) {
            $query->where(function ($q) use ($ip) {
                $q->where('ip_address', $ip)->orWhereNull('ip_address');
            });
        }

        return $query->exists() || $this->shouldLock($email, $ip, $lockoutMinutes);
    }

    public function recordAttempt(string $email, bool $successful, ?int $userId = null): void
    {
        if (!$this->tableReady()) {
            return;
        }

        $meta = $this->settings->all()['auth.account_lockout']['meta'] ?? [];
        $max = (int) ($meta['max_failed_attempts'] ?? 5);
        $lockoutMinutes = (int) ($meta['lockout_minutes'] ?? 15);

        $lockedUntil = null;
        if (!$successful && $this->settings->isEnabled('auth.account_lockout', true)) {
            $failures = LoginAttempt::where('email', strtolower($email))
                ->where('successful', false)
                ->where('created_at', '>=', now()->subMinutes($lockoutMinutes))
                ->count();

            if (($failures + 1) >= $max) {
                $lockedUntil = now()->addMinutes($lockoutMinutes);
            }
        }

        LoginAttempt::create([
            'email' => strtolower($email),
            'user_id' => $userId,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'successful' => $successful,
            'locked_until' => $lockedUntil,
            'created_at' => now(),
        ]);

        $this->audit(
            $successful ? 'auth.login_success' : 'auth.login_failed',
            $successful ? "Login success for {$email}" : "Login failed for {$email}" . ($lockedUntil ? ' (locked)' : '')
        );
    }

    public function validatePassword(string $password): array
    {
        if (!$this->settings->isEnabled('auth.password_policy', true)) {
            return ['valid' => true, 'errors' => []];
        }

        $meta = $this->settings->all()['auth.password_policy']['meta'] ?? [];
        $errors = [];

        $min = (int) ($meta['min_length'] ?? 12);
        if (strlen($password) < $min) {
            $errors[] = "Password must be at least {$min} characters.";
        }
        if (($meta['require_uppercase'] ?? true) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain an uppercase letter.';
        }
        if (($meta['require_lowercase'] ?? true) && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain a lowercase letter.';
        }
        if (($meta['require_number'] ?? true) && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain a number.';
        }
        if (($meta['require_special'] ?? true) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain a special character.';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    protected function shouldLock(string $email, ?string $ip, int $lockoutMinutes): bool
    {
        $meta = $this->settings->all()['auth.account_lockout']['meta'] ?? [];
        $max = (int) ($meta['max_failed_attempts'] ?? 5);

        return LoginAttempt::where('email', strtolower($email))
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes($lockoutMinutes))
            ->count() >= $max;
    }

    protected function tableReady(): bool
    {
        try {
            return Schema::hasTable('owasp_login_attempts');
        } catch (\Throwable) {
            return false;
        }
    }

    protected function audit(string $event, string $description): void
    {
        if (!config('owasp-security.audit.enabled') || !config('owasp-security.audit.log_auth_events')) {
            return;
        }

        try {
            if (!Schema::hasTable('owasp_security_audit_logs')) {
                return;
            }
            SecurityAuditLog::create([
                'user_id' => auth()->id(),
                'event' => $event,
                'category' => 'authentication',
                'description' => $description,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
        }
    }
}
