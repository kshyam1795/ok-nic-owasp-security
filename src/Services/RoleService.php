<?php

namespace Growats\OkNicOwaspSecurity\Services;

use Growats\OkNicOwaspSecurity\Models\OwaspPermission;
use Growats\OkNicOwaspSecurity\Models\OwaspRole;
use Growats\OkNicOwaspSecurity\Models\SecurityAuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RoleService
{
    public function seedRolesAndPermissions(): void
    {
        $permissions = [
            ['name' => '*', 'label' => 'All Permissions', 'description' => 'Super Admin wildcard'],
            ['name' => 'settings.view', 'label' => 'View Settings', 'description' => 'View OWASP security settings'],
            ['name' => 'settings.update', 'label' => 'Update Settings', 'description' => 'Enable/disable security controls'],
            ['name' => 'roles.view', 'label' => 'View Roles', 'description' => 'View roles and permissions'],
            ['name' => 'roles.manage', 'label' => 'Manage Roles', 'description' => 'Create/update custom roles'],
            ['name' => 'users.view', 'label' => 'View Users', 'description' => 'View users and assigned roles'],
            ['name' => 'users.assign_roles', 'label' => 'Assign Roles', 'description' => 'Assign OWASP roles to users'],
            ['name' => 'audit.view', 'label' => 'View Audit Logs', 'description' => 'View security audit trail'],
            ['name' => 'otp.manage', 'label' => 'Manage OTP', 'description' => 'Configure OTP / MFA'],
        ];

        $permissionIds = [];
        foreach ($permissions as $perm) {
            $model = OwaspPermission::updateOrCreate(['name' => $perm['name']], $perm);
            $permissionIds[$perm['name']] = $model->id;
        }

        foreach (config('owasp-security.roles', []) as $name => $roleCfg) {
            $role = OwaspRole::updateOrCreate(
                ['name' => $name],
                [
                    'label' => $roleCfg['label'],
                    'description' => $roleCfg['description'] ?? null,
                    'is_system' => true,
                ]
            );

            $perms = $roleCfg['permissions'] ?? [];
            $ids = [];
            foreach ($perms as $p) {
                if (isset($permissionIds[$p])) {
                    $ids[] = $permissionIds[$p];
                }
            }
            $role->permissions()->sync($ids);
        }
    }

    public function ensureSuperAdmin(): array
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $email = config('owasp-security.super_admin.email');
        $name = config('owasp-security.super_admin.name', 'Super Admin');
        $password = config('owasp-security.super_admin.password') ?: Str::password(16);

        $user = $userModel::query()->where('email', $email)->first();
        $created = false;

        if (!$user) {
            $data = [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ];

            // Support apps that use email_verified_at
            if (Schema::hasColumn((new $userModel)->getTable(), 'email_verified_at')) {
                $data['email_verified_at'] = now();
            }

            $user = $userModel::create($data);
            $created = true;
        }

        if (method_exists($user, 'assignOwaspRole')) {
            $user->assignOwaspRole('super_admin');
        } else {
            $role = OwaspRole::where('name', 'super_admin')->first();
            if ($role) {
                \DB::table('owasp_user_role')->updateOrInsert(
                    ['user_id' => $user->id, 'role_id' => $role->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        $this->audit('role.super_admin_ensured', "Super Admin ensured for {$email}");

        return [
            'user' => $user,
            'email' => $email,
            'password' => $created ? $password : null,
            'created' => $created,
        ];
    }

    public function assignRole(int $userId, string $roleName, ?int $actorId = null): void
    {
        $role = OwaspRole::where('name', $roleName)->firstOrFail();
        \DB::table('owasp_user_role')->updateOrInsert(
            ['user_id' => $userId, 'role_id' => $role->id],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $this->audit('role.assigned', "Assigned role {$roleName} to user {$userId}", $actorId);
    }

    public function revokeRole(int $userId, string $roleName, ?int $actorId = null): void
    {
        $role = OwaspRole::where('name', $roleName)->first();
        if (!$role) {
            return;
        }

        \DB::table('owasp_user_role')
            ->where('user_id', $userId)
            ->where('role_id', $role->id)
            ->delete();

        $this->audit('role.revoked', "Revoked role {$roleName} from user {$userId}", $actorId);
    }

    protected function audit(string $event, string $description, ?int $userId = null): void
    {
        if (!config('owasp-security.audit.enabled') || !config('owasp-security.audit.log_role_changes')) {
            return;
        }

        try {
            if (!Schema::hasTable('owasp_security_audit_logs')) {
                return;
            }
            SecurityAuditLog::create([
                'user_id' => $userId ?? auth()->id(),
                'event' => $event,
                'category' => 'rbac',
                'description' => $description,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
        }
    }
}
