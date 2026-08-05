<?php

namespace Growats\OkNicOwaspSecurity\Http\Controllers;

use Growats\OkNicOwaspSecurity\Models\OwaspRole;
use Growats\OkNicOwaspSecurity\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roles
    ) {}

    public function index()
    {
        $roles = OwaspRole::with('permissions')->orderBy('id')->get();
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $assignments = DB::table('owasp_user_role')
            ->join('owasp_roles', 'owasp_roles.id', '=', 'owasp_user_role.role_id')
            ->select('owasp_user_role.user_id', 'owasp_roles.name as role_name', 'owasp_roles.label as role_label')
            ->get()
            ->groupBy('user_id');

        $users = $userModel::query()->orderBy('id')->limit(200)->get();

        return view('owasp-security::roles', compact('roles', 'users', 'assignments'));
    }

    public function assign(Request $request)
    {
        $user = $request->user();
        if (method_exists($user, 'hasOwaspPermission') && !$user->hasOwaspPermission('users.assign_roles')) {
            abort(403);
        }

        $data = $request->validate([
            'user_id' => 'required|integer',
            'role' => 'required|string|exists:owasp_roles,name',
        ]);

        if ($data['role'] === 'super_admin' && method_exists($user, 'isOwaspSuperAdmin') && !$user->isOwaspSuperAdmin()) {
            abort(403, 'Only Super Admin can assign Super Admin role.');
        }

        $this->roles->assignRole($data['user_id'], $data['role'], $user->id);

        return back()->with('success', 'Role assigned successfully.');
    }

    public function revoke(Request $request)
    {
        $user = $request->user();
        if (method_exists($user, 'hasOwaspPermission') && !$user->hasOwaspPermission('users.assign_roles')) {
            abort(403);
        }

        $data = $request->validate([
            'user_id' => 'required|integer',
            'role' => 'required|string|exists:owasp_roles,name',
        ]);

        if ($data['role'] === 'super_admin' && method_exists($user, 'isOwaspSuperAdmin') && !$user->isOwaspSuperAdmin()) {
            abort(403, 'Only Super Admin can revoke Super Admin role.');
        }

        $this->roles->revokeRole($data['user_id'], $data['role'], $user->id);

        return back()->with('success', 'Role revoked successfully.');
    }
}
