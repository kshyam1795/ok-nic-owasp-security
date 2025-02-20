<?php

namespace Sam\OkNicOwaspSecurity\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;

class RateLimiting
{
    public function handle(Request $request, Closure $next)
    {
        $limiter = app(RateLimiter::class);
        $key = $request->ip();
        $maxAttempts = config('owasp-security.rate_limit', 60); // Default 60 requests per minute

        if ($limiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json(['error' => 'Too many requests'], 429);
        }

        $limiter->hit($key, 60); // 1-minute lockout
        return $next($request);
    }
}
