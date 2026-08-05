<?php

namespace Growats\OkNicOwaspSecurity\Events;

class OtpGenerated
{
    public function __construct(
        public $user,
        public array $payload
    ) {}
}
