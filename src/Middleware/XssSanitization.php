<?php

namespace Growats\OkNicOwaspSecurity\Middleware;

use Closure;
use Growats\OkNicOwaspSecurity\Services\SecuritySettingsService;
use Illuminate\Http\Request;

class XssSanitization
{
    public function __construct(
        protected SecuritySettingsService $settings
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if (!$this->settings->isEnabled('protection.xss_sanitization', true)) {
            return $next($request);
        }

        $input = $request->all();

        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                $value = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            }
        });

        $request->merge($input);

        return $next($request);
    }
}
