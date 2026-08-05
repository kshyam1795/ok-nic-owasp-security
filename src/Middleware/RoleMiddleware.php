<?php

namespace Growats\OkNicOwaspSecurity\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $roles)
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $required = array_filter(array_map('trim', explode('|', $roles)));

        if (method_exists($user, 'hasOwaspRole')) {
            if (!$user->hasOwaspRole($required)) {
                abort(403, 'Insufficient OWASP role.');
            }
            return $next($request);
        }

        // Fallback: check pivot table directly
        $has = \DB::table('owasp_user_role')
            ->join('owasp_roles', 'owasp_roles.id', '=', 'owasp_user_role.role_id')
            ->where('owasp_user_role.user_id', $user->id)
            ->whereIn('owasp_roles.name', $required)
            ->exists();

        if (!$has) {
            abort(403, 'Insufficient OWASP role. Add HasOwaspRoles trait to User model.');
        }

        return $next($request);
    }
}
