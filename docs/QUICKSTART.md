# Quick Start (copy into host Laravel app)

## Linux / macOS

```bash
composer require growats/ok-nic-owasp-security
php artisan owasp:install --email=superadmin@yourorg.gov
```

## Windows (PowerShell)

```powershell
composer require growats/ok-nic-owasp-security
php artisan owasp:install --email=superadmin@yourorg.gov
```

## Local path package (this repo)

From your Laravel app:

```powershell
# composer.json repositories + require, then:
composer update growats/ok-nic-owasp-security
php artisan owasp:install
```

Open: `/owasp-security` → Settings → toggle controls per audit.

Full SOP: `docs/SOP.md`
