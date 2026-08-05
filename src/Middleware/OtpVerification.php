<?php

namespace Growats\OkNicOwaspSecurity\Middleware;

use Closure;
use Growats\OkNicOwaspSecurity\Services\OtpService;
use Illuminate\Http\Request;

class OtpVerification
{
    public function __construct(
        protected OtpService $otp
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$this->otp->requiredForUser($user)) {
            return $next($request);
        }

        // Allow OTP challenge endpoints themselves
        if ($request->is('*/owasp-security/otp*') || $request->routeIs('owasp.otp.*')) {
            return $next($request);
        }

        if ($this->otp->isSessionVerified()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'OTP verification required.',
                'otp_required' => true,
                'owasp' => 'A07:2021 Identification and Authentication Failures',
            ], 403);
        }

        return redirect()->route('owasp.otp.challenge');
    }
}
