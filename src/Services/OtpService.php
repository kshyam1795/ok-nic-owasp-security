<?php

namespace Growats\OkNicOwaspSecurity\Services;

use Growats\OkNicOwaspSecurity\Models\OtpToken;
use Growats\OkNicOwaspSecurity\Models\SecurityAuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OtpService
{
    public function __construct(
        protected SecuritySettingsService $settings
    ) {}

    public function isEnabled(): bool
    {
        return $this->settings->isEnabled('auth.otp', true);
    }

    public function requiredForUser($user): bool
    {
        if (!$this->isEnabled() || !$user) {
            return false;
        }

        $roles = config('owasp-security.authentication.otp.required_for_roles', []);
        if (empty($roles)) {
            return true;
        }

        if (method_exists($user, 'hasOwaspRole')) {
            return $user->hasOwaspRole($roles);
        }

        return false;
    }

    /**
     * Generate OTP and return plaintext once (for delivery). Hash is stored.
     */
    public function generate($user, string $purpose = 'login', string $channel = 'email'): array
    {
        $meta = $this->settings->all()['auth.otp']['meta'] ?? [];
        $length = (int) ($meta['length'] ?? config('owasp-security.authentication.otp.length', 6));
        $ttl = (int) ($meta['ttl_seconds'] ?? config('owasp-security.authentication.otp.ttl_seconds', 300));
        $maxAttempts = (int) ($meta['max_verify_attempts'] ?? config('owasp-security.authentication.otp.max_verify_attempts', 5));

        OtpToken::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        $code = $this->numericCode($length);

        $token = OtpToken::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'channel' => $channel,
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'expires_at' => now()->addSeconds($ttl),
            'ip_address' => request()?->ip(),
            'user_agent' => Str::limit((string) request()?->userAgent(), 500, ''),
        ]);

        $this->audit('otp.generated', "OTP generated for user {$user->id} purpose={$purpose}");

        return [
            'token_id' => $token->id,
            'code' => $code,
            'expires_at' => $token->expires_at,
            'channel' => $channel,
            'purpose' => $purpose,
        ];
    }

    public function verify($user, string $code, string $purpose = 'login'): bool
    {
        $token = OtpToken::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (!$token || $token->isExpired() || !$token->hasAttemptsLeft()) {
            $this->audit('otp.failed', "OTP verify failed for user {$user->id}");
            return false;
        }

        $token->increment('attempts');

        if (!Hash::check($code, $token->code_hash)) {
            $this->audit('otp.failed', "OTP invalid for user {$user->id}");
            return false;
        }

        $token->verified_at = now();
        $token->save();

        session(['owasp_otp_verified_at' => now()->toIso8601String(), 'owasp_otp_purpose' => $purpose]);
        $this->audit('otp.verified', "OTP verified for user {$user->id}");

        return true;
    }

    public function isSessionVerified(int $maxAgeMinutes = 30): bool
    {
        $at = session('owasp_otp_verified_at');
        if (!$at) {
            return false;
        }

        try {
            return now()->diffInMinutes(\Carbon\Carbon::parse($at)) <= $maxAgeMinutes;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function numericCode(int $length): string
    {
        $max = (10 ** $length) - 1;
        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    protected function audit(string $event, string $description): void
    {
        if (!config('owasp-security.audit.enabled')) {
            return;
        }

        try {
            if (!Schema::hasTable('owasp_security_audit_logs')) {
                return;
            }
            SecurityAuditLog::create([
                'user_id' => auth()->id(),
                'event' => $event,
                'category' => 'otp',
                'description' => $description,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
        }
    }
}
