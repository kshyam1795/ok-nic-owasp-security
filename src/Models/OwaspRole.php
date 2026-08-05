<?php

namespace Growats\OkNicOwaspSecurity\Models;

use Illuminate\Database\Eloquent\Model;

class OwaspRole extends Model
{
    protected $table = 'owasp_roles';

    protected $fillable = [
        'name',
        'label',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function permissions()
    {
        return $this->belongsToMany(
            OwaspPermission::class,
            'owasp_role_permission',
            'role_id',
            'permission_id'
        );
    }

    public function users()
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        return $this->belongsToMany(
            $userModel,
            'owasp_user_role',
            'role_id',
            'user_id'
        );
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->name === 'super_admin') {
            return true;
        }

        return $this->permissions->contains('name', $permission)
            || $this->permissions->contains('name', '*');
    }
}
