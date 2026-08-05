<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Package Master Switch
    |--------------------------------------------------------------------------
    */
    'enabled' => env('OWASP_SECURITY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    | Created/updated by: php artisan owasp:install
    */
    'super_admin' => [
        'email' => env('OWASP_SUPER_ADMIN_EMAIL', 'superadmin@example.com'),
        'name' => env('OWASP_SUPER_ADMIN_NAME', 'Super Admin'),
        'password' => env('OWASP_SUPER_ADMIN_PASSWORD', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard / Routes
    |--------------------------------------------------------------------------
    */
    'route_prefix' => env('OWASP_ROUTE_PREFIX', 'owasp-security'),
    'route_middleware' => ['web', 'auth', 'owasp.role:super_admin|security_admin|auditor'],
    'api_prefix' => env('OWASP_API_PREFIX', 'api/owasp-security'),

    /*
    |--------------------------------------------------------------------------
    | Auto-register global middleware (installer can enable this)
    |--------------------------------------------------------------------------
    */
    'auto_middleware' => env('OWASP_AUTO_MIDDLEWARE', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Security Headers (toggleable from Super Admin Settings)
    |--------------------------------------------------------------------------
    | Each key maps to owasp_security_settings.key
    */
    'headers' => [
        'x_frame_options' => [
            'enabled' => true,
            'value' => 'DENY',
            'owasp' => 'A05:2021 Security Misconfiguration / Clickjacking',
        ],
        'x_content_type_options' => [
            'enabled' => true,
            'value' => 'nosniff',
            'owasp' => 'A05:2021 MIME sniffing',
        ],
        'x_xss_protection' => [
            'enabled' => true,
            'value' => '1; mode=block',
            'owasp' => 'A03:2021 XSS (legacy browser header)',
        ],
        'strict_transport_security' => [
            'enabled' => true,
            'value' => 'max-age=31536000; includeSubDomains; preload',
            'owasp' => 'A02:2021 Cryptographic Failures / HTTPS',
        ],
        'referrer_policy' => [
            'enabled' => true,
            'value' => 'strict-origin-when-cross-origin',
            'owasp' => 'A01:2021 Broken Access Control / info leak',
        ],
        'content_security_policy' => [
            'enabled' => true,
            'value' => "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
            'owasp' => 'A03:2021 Injection / XSS',
        ],
        'permissions_policy' => [
            'enabled' => true,
            'value' => 'geolocation=(), microphone=(), camera=(), payment=()',
            'owasp' => 'A05:2021 Security Misconfiguration',
        ],
        'cross_origin_opener_policy' => [
            'enabled' => true,
            'value' => 'same-origin',
            'owasp' => 'A05:2021 Cross-origin isolation',
        ],
        'cross_origin_resource_policy' => [
            'enabled' => true,
            'value' => 'same-origin',
            'owasp' => 'A05:2021 Cross-origin isolation',
        ],
        'cross_origin_embedder_policy' => [
            'enabled' => false,
            'value' => 'require-corp',
            'owasp' => 'A05:2021 Cross-origin isolation',
        ],
        'cache_control' => [
            'enabled' => true,
            'value' => 'no-store, no-cache, must-revalidate, private',
            'owasp' => 'A01:2021 Sensitive data in cache',
            'paths' => ['login', 'password', 'otp', 'admin', 'owasp-security'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication & Session Protections
    |--------------------------------------------------------------------------
    */
    'authentication' => [
        'rate_limiting' => [
            'enabled' => true,
            'max_attempts' => (int) env('OWASP_RATE_LIMIT', 60),
            'decay_seconds' => 60,
            'login_max_attempts' => (int) env('OWASP_LOGIN_RATE_LIMIT', 5),
            'login_decay_seconds' => 300,
        ],
        'account_lockout' => [
            'enabled' => true,
            'max_failed_attempts' => 5,
            'lockout_minutes' => 15,
        ],
        'otp' => [
            'enabled' => true,
            'length' => 6,
            'ttl_seconds' => 300,
            'max_verify_attempts' => 5,
            'channels' => ['email'], // email|sms|both — host app implements delivery
            'required_for_roles' => ['super_admin', 'security_admin', 'auditor'],
        ],
        'password_policy' => [
            'enabled' => true,
            'min_length' => 12,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_number' => true,
            'require_special' => true,
            'prevent_reuse' => 5,
        ],
        'session' => [
            'enabled' => true,
            'regenerate_on_login' => true,
            'idle_timeout_minutes' => 30,
            'absolute_timeout_minutes' => 480,
            'secure_cookie' => true,
            'http_only' => true,
            'same_site' => 'lax',
        ],
        'csrf' => [
            'enabled' => true,
        ],
        'force_https' => [
            'enabled' => env('OWASP_FORCE_HTTPS', false),
        ],
        'login_anomaly' => [
            'enabled' => true,
            'alert_on_new_ip' => true,
            'alert_on_new_device' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Input / Output Protections
    |--------------------------------------------------------------------------
    */
    'protections' => [
        'xss_sanitization' => [
            'enabled' => true,
        ],
        'sql_injection' => [
            'enabled' => true,
        ],
        'cors' => [
            'enabled' => true,
            'allowed_origins' => array_filter(array_map('trim', explode(',', env('OWASP_ALLOWED_ORIGINS', '')))),
            'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN', 'X-OTP-TOKEN'],
            'supports_credentials' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles (seeded on install)
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'super_admin' => [
            'label' => 'Super Admin',
            'description' => 'Full control: enable/disable all OWASP settings per security audit findings.',
            'permissions' => ['*'],
        ],
        'security_admin' => [
            'label' => 'Security Admin',
            'description' => 'Manage security settings and review audit logs (cannot delete Super Admin).',
            'permissions' => [
                'settings.view',
                'settings.update',
                'roles.view',
                'users.view',
                'users.assign_roles',
                'audit.view',
                'otp.manage',
            ],
        ],
        'auditor' => [
            'label' => 'Auditor',
            'description' => 'Read-only access to settings and audit logs for security assessments.',
            'permissions' => [
                'settings.view',
                'roles.view',
                'users.view',
                'audit.view',
            ],
        ],
        'user' => [
            'label' => 'User',
            'description' => 'Standard authenticated user (protections apply, no admin UI).',
            'permissions' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit logging
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => true,
        'log_setting_changes' => true,
        'log_auth_events' => true,
        'log_role_changes' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'settings_ttl' => 300,
        'settings_key' => 'owasp.security.settings',
    ],
];
