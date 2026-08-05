<?php

namespace Growats\OkNicOwaspSecurity\Middleware;

use Closure;
use Growats\OkNicOwaspSecurity\Services\SecuritySettingsService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;

class RateLimiting
{
    public function __construct(
        protected SecuritySettingsService $settings
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if (!$this->settings->isEnabled('auth.rate_limiting', true)) {
            return $next($request);
        }

        $meta = $this->settings->all()['auth.rate_limiting']['meta'] ?? [];
        $limiter = app(RateLimiter::class);

        $isLogin = $this->isAuthEndpoint($request);
        $max = $isLogin
            ? (int) ($meta['login_max_attempts'] ?? config('owasp-security.authentication.rate_limiting.login_max_attempts', 5))
            : (int) ($meta['max_attempts'] ?? config('owasp-security.authentication.rate_limiting.max_attempts', 60));

        $decay = $isLogin
            ? (int) ($meta['login_decay_seconds'] ?? config('owasp-security.authentication.rate_limiting.login_decay_seconds', 300))
            : (int) ($meta['decay_seconds'] ?? config('owasp-security.authentication.rate_limiting.decay_seconds', 60));

        $key = ($isLogin ? 'owasp-login:' : 'owasp-global:') . $request->ip() . ':' . strtolower((string) $request->input('email', ''));

        if ($limiter->tooManyAttempts($key, $max)) {
            $retry = $limiter->availableIn($key);
            return response()->json([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => $retry,
                'owasp' => 'A07:2021 Identification and Authentication Failures',
            ], 429)->header('Retry-After', $retry);
        }

        $limiter->hit($key, $decay);

        return $next($request);
    }

    protected function isAuthEndpoint(Request $request): bool
    {
        $path = strtolower($request->path());
        return str_contains($path, 'login')
            || str_contains($path, 'otp')
            || str_contains($path, 'password')
            || str_contains($path, 'register')
            || $request->is('*/auth/*');
    }
}
