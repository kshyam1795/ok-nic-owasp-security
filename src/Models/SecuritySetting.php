<?php

namespace Growats\OkNicOwaspSecurity\Models;

use Illuminate\Database\Eloquent\Model;

class SecuritySetting extends Model
{
    protected $table = 'owasp_security_settings';

    protected $fillable = [
        'group',
        'key',
        'label',
        'description',
        'owasp_ref',
        'enabled',
        'value',
        'meta',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'meta' => 'array',
    ];

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
