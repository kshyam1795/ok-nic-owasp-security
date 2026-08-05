<?php

namespace Growats\OkNicOwaspSecurity\Models;

use Illuminate\Database\Eloquent\Model;

class OtpToken extends Model
{
    protected $table = 'owasp_otp_tokens';

    protected $fillable = [
        'user_id',
        'purpose',
        'code_hash',
        'channel',
        'attempts',
        'max_attempts',
        'expires_at',
        'verified_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function hasAttemptsLeft(): bool
    {
        return $this->attempts < $this->max_attempts;
    }
}
