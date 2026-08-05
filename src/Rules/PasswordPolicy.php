<?php

namespace Growats\OkNicOwaspSecurity\Rules;

use Growats\OkNicOwaspSecurity\Services\AuthSecurityService;
use Illuminate\Contracts\Validation\Rule;

class PasswordPolicy implements Rule
{
    protected array $errors = [];

    public function passes($attribute, $value): bool
    {
        $result = app(AuthSecurityService::class)->validatePassword((string) $value);
        $this->errors = $result['errors'];
        return $result['valid'];
    }

    public function message(): array|string
    {
        return $this->errors ?: 'Password does not meet OWASP policy.';
    }
}
