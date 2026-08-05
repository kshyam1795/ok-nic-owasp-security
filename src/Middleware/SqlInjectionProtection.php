<?php

namespace Growats\OkNicOwaspSecurity\Middleware;

use Closure;
use Growats\OkNicOwaspSecurity\Services\SecuritySettingsService;
use Illuminate\Http\Request;

class SqlInjectionProtection
{
    public function __construct(
        protected SecuritySettingsService $settings
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if (!$this->settings->isEnabled('protection.sql_injection', true)) {
            return $next($request);
        }

        $patterns = [
            '/(\b(union|select|insert|update|delete|drop|alter|create|rename|truncate|exec|execute)\b.*\b(from|into|table|database|sleep|benchmark)\b)/i',
            '/(--|#|\/\*|\*\/)/',
            '/(\b(or|and)\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+)/i',
            '/(\bxp_cmdshell\b|\binformation_schema\b)/i',
            '/(\b(load_file|outfile|dumpfile)\b)/i',
        ];

        foreach ($this->flatten($request->all()) as $value) {
            if (!is_string($value)) {
                continue;
            }
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return response()->json([
                        'error' => 'Potential SQL injection detected.',
                        'owasp' => 'A03:2021 Injection',
                    ], 400);
                }
            }
        }

        return $next($request);
    }

    protected function flatten(array $input, array $carry = []): array
    {
        foreach ($input as $value) {
            if (is_array($value)) {
                $carry = $this->flatten($value, $carry);
            } else {
                $carry[] = $value;
            }
        }
        return $carry;
    }
}
