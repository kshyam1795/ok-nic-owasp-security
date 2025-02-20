<?php

namespace Sam\OkNicOwaspSecurity\Middleware;

use Closure;
use Illuminate\Http\Request;

class XssSanitization
{
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$input) {
            $input = htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
        });

        $request->merge($input);
        return $next($request);
    }
}
