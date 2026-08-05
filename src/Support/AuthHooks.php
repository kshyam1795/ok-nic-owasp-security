<?php

namespace Growats\OkNicOwaspSecurity\Support;

use Growats\OkNicOwaspSecurity\Services\AuthSecurityService;
use Growats\OkNicOwaspSecurity\Services\OtpService;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Drop-in helpers for host-app login controllers.
 */
class AuthHooks
{
    public static function assertNotLocked(string $email): void
    {
        $service = app(AuthSecurityService::class);
        if ($service->isLocked($email, request()->ip())) {
            abort(response()->json([
                'error' => 'Account temporarily locked due to too many failed attempts.',
                'owasp' => 'A07:2021 Identification and Authentication Failures',
            ], 423));
        }
    }

    public static function recordLogin(string $email, bool $successful, ?Authenticatable $user = null): void
    {
        app(AuthSecurityService::class)->recordAttempt($email, $successful, $user?->getAuthIdentifier());

        if ($successful && $user) {
            if (config('owasp-security.authentication.session.regenerate_on_login', true)) {
                request()->session()->regenerate();
            }
            session([
                'owasp_session_started_at' => now()->timestamp,
                'owasp_last_activity_at' => now()->timestamp,
            ]);
        }
    }

    public static function maybeStartOtp(Authenticatable $user): ?array
    {
        $otp = app(OtpService::class);
        if (!$otp->requiredForUser($user)) {
            return null;
        }

        return $otp->generate($user, 'login', 'email');
    }
}
