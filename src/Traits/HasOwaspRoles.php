<?php

namespace Growats\OkNicOwaspSecurity\Traits;

use Growats\OkNicOwaspSecurity\Models\OwaspRole;

trait HasOwaspRoles
{
    public function owaspRoles()
    {
        return $this->belongsToMany(
            OwaspRole::class,
            'owasp_user_role',
            'user_id',
            'role_id'
        )->withTimestamps();
    }

    public function assignOwaspRole(string $roleName): void
    {
        $role = OwaspRole::where('name', $roleName)->firstOrFail();
        $this->owaspRoles()->syncWithoutDetaching([$role->id]);
    }

    public function removeOwaspRole(string $roleName): void
    {
        $role = OwaspRole::where('name', $roleName)->first();
        if ($role) {
            $this->owaspRoles()->detach($role->id);
        }
    }

    public function hasOwaspRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        return $this->owaspRoles()->whereIn('name', $roles)->exists();
    }

    public function isOwaspSuperAdmin(): bool
    {
        return $this->hasOwaspRole('super_admin');
    }

    public function hasOwaspPermission(string $permission): bool
    {
        if ($this->isOwaspSuperAdmin()) {
            return true;
        }

        foreach ($this->owaspRoles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
