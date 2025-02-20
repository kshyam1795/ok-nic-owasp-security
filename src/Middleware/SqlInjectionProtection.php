<?php

namespace Sam\OkNicOwaspSecurity\Middleware;

use Closure;
use Illuminate\Http\Request;

class SqlInjectionProtection
{
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();
        $patterns = [
            '/(\b(union|select|insert|update|delete|drop|alter|create|rename|truncate)\b)/i',
            '/(--|#|\/\*)/',
            '/(\b(or|and)\b\s*\d+\s*=\s*\d+)/i'
        ];

        foreach ($input as $key => $value) {
            if (is_string($value) && preg_match($patterns[0], $value)) {
                return response()->json(['error' => 'Potential SQL Injection detected'], 400);
            }
        }

        return $next($request);
    }
}
