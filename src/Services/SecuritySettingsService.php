<?php

namespace Growats\OkNicOwaspSecurity\Services;

use Growats\OkNicOwaspSecurity\Models\SecurityAuditLog;
use Growats\OkNicOwaspSecurity\Models\SecuritySetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SecuritySettingsService
{
    public function all(?string $group = null): array
    {
        if (!$this->tableReady()) {
            return $this->defaultsFromConfig($group);
        }

        $cacheKey = config('owasp-security.cache.settings_key') . ($group ? ".{$group}" : '');

        return Cache::remember($cacheKey, config('owasp-security.cache.settings_ttl', 300), function () use ($group) {
            $query = SecuritySetting::query()->orderBy('group')->orderBy('key');
            if ($group) {
                $query->where('group', $group);
            }

            return $query->get()->keyBy('key')->map(fn ($s) => [
                'id' => $s->id,
                'group' => $s->group,
                'key' => $s->key,
                'label' => $s->label,
                'description' => $s->description,
                'owasp_ref' => $s->owasp_ref,
                'enabled' => (bool) $s->enabled,
                'value' => $s->value,
                'meta' => $s->meta,
            ])->toArray();
        });
    }

    public function isEnabled(string $key, bool $default = true): bool
    {
        if (!config('owasp-security.enabled', true)) {
            return false;
        }

        $settings = $this->all();
        if (isset($settings[$key])) {
            return (bool) $settings[$key]['enabled'];
        }

        return $this->defaultEnabled($key, $default);
    }

    public function value(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        if (isset($settings[$key])) {
            return $settings[$key]['value'] ?? $default;
        }

        return $this->defaultValue($key, $default);
    }

    public function toggle(string $key, bool $enabled, ?int $userId = null): SecuritySetting
    {
        $setting = SecuritySetting::where('key', $key)->firstOrFail();
        $old = ['enabled' => $setting->enabled, 'value' => $setting->value];

        $setting->enabled = $enabled;
        $setting->updated_by = $userId ?? Auth::id();
        $setting->save();

        $this->forgetCache();
        $this->audit('setting.toggle', 'settings', "Toggled {$key} to " . ($enabled ? 'enabled' : 'disabled'), $old, [
            'enabled' => $enabled,
            'value' => $setting->value,
        ]);

        return $setting->fresh();
    }

    public function update(string $key, array $data, ?int $userId = null): SecuritySetting
    {
        $setting = SecuritySetting::where('key', $key)->firstOrFail();
        $old = ['enabled' => $setting->enabled, 'value' => $setting->value, 'meta' => $setting->meta];

        if (array_key_exists('enabled', $data)) {
            $setting->enabled = (bool) $data['enabled'];
        }
        if (array_key_exists('value', $data)) {
            $setting->value = $data['value'];
        }
        if (array_key_exists('meta', $data)) {
            $setting->meta = $data['meta'];
        }

        $setting->updated_by = $userId ?? Auth::id();
        $setting->save();

        $this->forgetCache();
        $this->audit('setting.update', 'settings', "Updated setting {$key}", $old, [
            'enabled' => $setting->enabled,
            'value' => $setting->value,
            'meta' => $setting->meta,
        ]);

        return $setting->fresh();
    }

    public function bulkUpdate(array $items, ?int $userId = null): void
    {
        foreach ($items as $item) {
            if (empty($item['key'])) {
                continue;
            }
            $this->update($item['key'], $item, $userId);
        }
    }

    public function seedDefaults(): void
    {
        if (!$this->tableReady()) {
            return;
        }

        $definitions = $this->definitions();

        foreach ($definitions as $def) {
            SecuritySetting::updateOrCreate(
                ['key' => $def['key']],
                [
                    'group' => $def['group'],
                    'label' => $def['label'],
                    'description' => $def['description'],
                    'owasp_ref' => $def['owasp_ref'],
                    'enabled' => $def['enabled'],
                    'value' => $def['value'] ?? null,
                    'meta' => $def['meta'] ?? null,
                ]
            );
        }

        $this->forgetCache();
    }

    public function forgetCache(): void
    {
        $base = config('owasp-security.cache.settings_key', 'owasp.security.settings');
        Cache::forget($base);
        foreach (['headers', 'authentication', 'protections'] as $group) {
            Cache::forget("{$base}.{$group}");
        }
    }

    public function definitions(): array
    {
        $defs = [];

        foreach (config('owasp-security.headers', []) as $key => $cfg) {
            $defs[] = [
                'group' => 'headers',
                'key' => "header.{$key}",
                'label' => $this->labelize($key),
                'description' => "HTTP security header: {$key}",
                'owasp_ref' => $cfg['owasp'] ?? null,
                'enabled' => (bool) ($cfg['enabled'] ?? true),
                'value' => $cfg['value'] ?? null,
                'meta' => array_filter([
                    'paths' => $cfg['paths'] ?? null,
                    'header_name' => $this->headerName($key),
                ]),
            ];
        }

        $authMap = [
            'rate_limiting' => ['Rate Limiting', 'Throttle requests to mitigate brute force and DoS'],
            'account_lockout' => ['Account Lockout', 'Temporarily lock accounts after failed logins'],
            'otp' => ['OTP / MFA Protection', 'One-time password verification for sensitive roles'],
            'password_policy' => ['Password Policy', 'Enforce strong password requirements'],
            'session' => ['Session Security', 'Idle/absolute timeout, regenerate IDs, secure cookies'],
            'csrf' => ['CSRF Protection Flag', 'Require CSRF tokens on state-changing requests'],
            'force_https' => ['Force HTTPS', 'Redirect HTTP to HTTPS'],
            'login_anomaly' => ['Login Anomaly Detection', 'Flag new IP/device logins'],
        ];

        foreach (config('owasp-security.authentication', []) as $key => $cfg) {
            $defs[] = [
                'group' => 'authentication',
                'key' => "auth.{$key}",
                'label' => $authMap[$key][0] ?? $this->labelize($key),
                'description' => $authMap[$key][1] ?? "Authentication protection: {$key}",
                'owasp_ref' => 'A07:2021 Identification and Authentication Failures',
                'enabled' => (bool) ($cfg['enabled'] ?? true),
                'value' => null,
                'meta' => is_array($cfg) ? collect($cfg)->except('enabled')->all() : null,
            ];
        }

        $protMap = [
            'xss_sanitization' => ['XSS Sanitization', 'A03:2021 Injection'],
            'sql_injection' => ['SQL Injection Protection', 'A03:2021 Injection'],
            'cors' => ['CORS Protection', 'A05:2021 Security Misconfiguration'],
        ];

        foreach (config('owasp-security.protections', []) as $key => $cfg) {
            $defs[] = [
                'group' => 'protections',
                'key' => "protection.{$key}",
                'label' => $protMap[$key][0] ?? $this->labelize($key),
                'description' => "Input/output protection: {$key}",
                'owasp_ref' => $protMap[$key][1] ?? null,
                'enabled' => (bool) ($cfg['enabled'] ?? true),
                'value' => null,
                'meta' => is_array($cfg) ? collect($cfg)->except('enabled')->all() : null,
            ];
        }

        return $defs;
    }

    protected function tableReady(): bool
    {
        try {
            return Schema::hasTable('owasp_security_settings');
        } catch (\Throwable) {
            return false;
        }
    }

    protected function defaultsFromConfig(?string $group): array
    {
        $out = [];
        foreach ($this->definitions() as $def) {
            if ($group && $def['group'] !== $group) {
                continue;
            }
            $out[$def['key']] = $def;
        }
        return $out;
    }

    protected function defaultEnabled(string $key, bool $default): bool
    {
        foreach ($this->definitions() as $def) {
            if ($def['key'] === $key) {
                return (bool) $def['enabled'];
            }
        }
        return $default;
    }

    protected function defaultValue(string $key, mixed $default): mixed
    {
        foreach ($this->definitions() as $def) {
            if ($def['key'] === $key) {
                return $def['value'] ?? $default;
            }
        }
        return $default;
    }

    protected function labelize(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }

    protected function headerName(string $key): string
    {
        return match ($key) {
            'x_frame_options' => 'X-Frame-Options',
            'x_content_type_options' => 'X-Content-Type-Options',
            'x_xss_protection' => 'X-XSS-Protection',
            'strict_transport_security' => 'Strict-Transport-Security',
            'referrer_policy' => 'Referrer-Policy',
            'content_security_policy' => 'Content-Security-Policy',
            'permissions_policy' => 'Permissions-Policy',
            'cross_origin_opener_policy' => 'Cross-Origin-Opener-Policy',
            'cross_origin_resource_policy' => 'Cross-Origin-Resource-Policy',
            'cross_origin_embedder_policy' => 'Cross-Origin-Embedder-Policy',
            'cache_control' => 'Cache-Control',
            default => str_replace(' ', '-', ucwords(str_replace('_', ' ', $key))),
        };
    }

    protected function audit(string $event, string $category, string $description, ?array $old, ?array $new): void
    {
        if (!config('owasp-security.audit.enabled') || !config('owasp-security.audit.log_setting_changes')) {
            return;
        }

        try {
            if (!Schema::hasTable('owasp_security_audit_logs')) {
                return;
            }

            SecurityAuditLog::create([
                'user_id' => Auth::id(),
                'event' => $event,
                'category' => $category,
                'description' => $description,
                'old_value' => $old,
                'new_value' => $new,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // never break request flow for audit
        }
    }
}
