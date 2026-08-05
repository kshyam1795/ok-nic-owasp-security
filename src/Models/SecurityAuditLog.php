<?php

namespace Growats\OkNicOwaspSecurity\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityAuditLog extends Model
{
    protected $table = 'owasp_security_audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'event',
        'category',
        'description',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];
}
