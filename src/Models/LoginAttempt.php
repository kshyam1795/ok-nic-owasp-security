<?php

namespace Growats\OkNicOwaspSecurity\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $table = 'owasp_login_attempts';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'user_id',
        'ip_address',
        'user_agent',
        'successful',
        'locked_until',
        'created_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'locked_until' => 'datetime',
        'created_at' => 'datetime',
    ];
}
