<?php

namespace Growats\OkNicOwaspSecurity\Models;

use Illuminate\Database\Eloquent\Model;

class OwaspPermission extends Model
{
    protected $table = 'owasp_permissions';

    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            OwaspRole::class,
            'owasp_role_permission',
            'permission_id',
            'role_id'
        );
    }
}
