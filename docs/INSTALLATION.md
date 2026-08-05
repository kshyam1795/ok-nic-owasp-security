# Installation Guide — OWASP Security Package

## Prerequisites

- PHP 8.1+
- Laravel 9, 10, 11, or 12
- Database configured (`php artisan migrate` works)
- Working auth (`User` model + `login` flow)

## A. Composer install (recommended)

```bash
cd /path/to/your-laravel-app
composer require growats/ok-nic-owasp-security
```

If the package is not on Packagist yet, use a **path** or **VCS** repository (see README).

## B. One-command setup

```bash
php artisan owasp:install
```

This will:

1. Publish `config/owasp-security.php`
2. Run package migrations (`owasp_*` tables)
3. Seed all security settings (headers, auth, protections)
4. Seed roles & permissions
5. Create / ensure **Super Admin** user
6. Auto-register global middleware (`OWASP_AUTO_MIDDLEWARE=true`)
7. Add `HasOwaspRoles` trait to `App\Models\User` when possible

### Install options

```bash
php artisan owasp:install --email=ciso@org.gov --password="Str0ng!Passw0rd"
php artisan owasp:install --fresh
php artisan owasp:install --skip-middleware
php artisan owasp:install --skip-trait
```

**Save the printed Super Admin password** if one was generated.

## C. Environment

Add to `.env`:

```env
OWASP_SECURITY_ENABLED=true
OWASP_AUTO_MIDDLEWARE=true
OWASP_SUPER_ADMIN_EMAIL=superadmin@example.com
OWASP_ROUTE_PREFIX=owasp-security
OWASP_RATE_LIMIT=60
OWASP_LOGIN_RATE_LIMIT=5
OWASP_ALLOWED_ORIGINS=https://your-frontend.example.com
OWASP_FORCE_HTTPS=true
```

```bash
php artisan config:clear
php artisan cache:clear
```

## D. Manual User trait (if installer could not patch)

`app/Models/User.php`:

```php
use Growats\OkNicOwaspSecurity\Traits\HasOwaspRoles;

class User extends Authenticatable
{
    use HasOwaspRoles;
    // ...
}
```

## E. Verify installation

| Check | How |
|-------|-----|
| Tables exist | `owasp_security_settings`, `owasp_roles`, `owasp_user_role`, … |
| Config published | `config/owasp-security.php` |
| Login as Super Admin | Use printed credentials |
| Dashboard | `/owasp-security` |
| Settings toggles | `/owasp-security/settings` |
| Headers applied | Browser DevTools → Network → Response Headers |
| Rate limit | Burst login attempts → HTTP 429 |

### Artisan smoke checks

```bash
php artisan route:list --path=owasp-security
php artisan migrate:status
```

## F. Protect privileged routes with OTP (optional)

```php
Route::middleware(['auth', 'owasp.otp'])->group(function () {
    // sensitive admin area
});
```

## G. Uninstall / disable

Temporary disable:

```env
OWASP_SECURITY_ENABLED=false
```

Or disable auto middleware:

```env
OWASP_AUTO_MIDDLEWARE=false
```

Remove package:

```bash
composer remove growats/ok-nic-owasp-security
# optionally drop owasp_* tables manually
```

## H. Integrating into existing NIC / government projects

1. Install on **staging** first.
2. Run `owasp:install`.
3. Auditor reviews Settings against VAPT report.
4. Super Admin enables only approved controls.
5. Promote same config/settings to production (export DB settings or re-seed + toggles).
6. Follow [SOP.md](SOP.md) for change control.

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| 403 on `/owasp-security` | User missing `super_admin` / `security_admin` role |
| Settings empty | `php artisan owasp:install --fresh` |
| Headers missing | `OWASP_AUTO_MIDDLEWARE=true` + clear config cache |
| OTP email not received | Configure `MAIL_*`; in `local`/`debug` OTP is flashed on screen |
| CORS broken | Set `OWASP_ALLOWED_ORIGINS` to explicit origins (not `*`) when credentials needed |
| Trait missing methods | Add `HasOwaspRoles` to User model |
