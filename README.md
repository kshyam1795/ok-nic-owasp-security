# Laravel OWASP Security Package

Installable Laravel package that hardens applications against **OWASP Top 10** risks, with a **Super Admin** control plane to enable/disable every security control after auditor findings.

## What you get

| Area | Controls |
|------|----------|
| **HTTP Headers** | X-Frame-Options, CSP, HSTS, Referrer-Policy, Permissions-Policy, COOP/CORP/COEP, nosniff, cache-control |
| **Authentication** | Rate limiting, account lockout, OTP/MFA, password policy, session idle/absolute timeout, force HTTPS, login anomaly logging |
| **Protections** | XSS sanitization, SQL injection pattern blocking, CORS allow-list |
| **RBAC** | `super_admin`, `security_admin`, `auditor`, `user` |
| **Audit** | Setting changes, auth events, OTP, role assignments |
| **Admin UI** | Dashboard, Settings toggles, Roles, Audit logs, OTP challenge |

## One-command install (any Laravel 9–12 project)

```bash
composer require growats/ok-nic-owasp-security
php artisan owasp:install
```

Optional:

```bash
php artisan owasp:install --email=security@yourorg.gov --password="ChangeMe!2026"
```

Then open:

```
https://your-app.test/owasp-security
```

Login as Super Admin → **Settings** → toggle headers / authentication / protections per audit report.

## Path install (local / private package)

In the host app `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../ok-nic-owasp-security",
      "options": { "symlink": true }
    }
  ],
  "require": {
    "growats/ok-nic-owasp-security": "*"
  }
}
```

```bash
composer update growats/ok-nic-owasp-security
php artisan owasp:install
```

## Documentation & SOP

| Document | Purpose |
|----------|---------|
| [docs/INSTALLATION.md](docs/INSTALLATION.md) | Install, configure, verify |
| [docs/SOP.md](docs/SOP.md) | Standard Operating Procedure for auditors & Super Admin |
| [docs/SECURITY-SETTINGS.md](docs/SECURITY-SETTINGS.md) | Every toggle explained (OWASP mapping) |
| [docs/RBAC.md](docs/RBAC.md) | Roles, permissions, Super Admin duties |

## Quick architecture

```
Request
  → SecurityHeaders (toggleable)
  → SessionSecurity / Auth lockout
  → XSS / SQLi / Rate limit / CORS
  → App routes
  → /owasp-security/* (Super Admin UI)
```

Settings live in `owasp_security_settings` and are cached. Middleware reads them on every request so toggles apply **without redeploy**.

## Middleware aliases

| Alias | Class |
|-------|-------|
| `owasp.security.headers` | SecurityHeaders |
| `owasp.security.xss` | XssSanitization |
| `owasp.security.sql` | SqlInjectionProtection |
| `owasp.security.rate` | RateLimiting |
| `owasp.security.cors` | CorsProtection |
| `owasp.security.session` | SessionSecurity |
| `owasp.security.auth` | AuthenticationProtection |
| `owasp.otp` | OtpVerification |
| `owasp.role` | RoleMiddleware (`owasp.role:super_admin\|security_admin`) |

## Env keys

```env
OWASP_SECURITY_ENABLED=true
OWASP_AUTO_MIDDLEWARE=true
OWASP_SUPER_ADMIN_EMAIL=superadmin@example.com
OWASP_SUPER_ADMIN_NAME="Super Admin"
OWASP_SUPER_ADMIN_PASSWORD=
OWASP_ROUTE_PREFIX=owasp-security
OWASP_RATE_LIMIT=60
OWASP_LOGIN_RATE_LIMIT=5
OWASP_ALLOWED_ORIGINS=https://app.example.com
OWASP_FORCE_HTTPS=false
```

## License

MIT © Shyamendra Kushwaha / Growats
