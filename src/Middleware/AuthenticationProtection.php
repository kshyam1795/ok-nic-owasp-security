<?php

namespace Growats\OkNicOwaspSecurity\Middleware;

use Closure;
use Growats\OkNicOwaspSecurity\Services\AuthSecurityService;
use Illuminate\Http\Request;

class AuthenticationProtection
{
    public function __construct(
        protected AuthSecurityService $authSecurity
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $email = (string) $request->input('email', '');

        if ($email !== '' && $this->authSecurity->isLocked($email, $request->ip())) {
            return response()->json([
                'error' => 'Account temporarily locked due to too many failed attempts.',
                'owasp' => 'A07:2021 Identification and Authentication Failures',
            ], 423);
        }

        return $next($request);
    }
}
