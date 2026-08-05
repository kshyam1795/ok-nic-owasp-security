# RBAC — Role Based Access Control

## Roles (seeded)

| Role | Name key | Purpose |
|------|----------|---------|
| Super Admin | `super_admin` | Full control of all settings, roles, OTP, audit. Responsible for enabling controls per auditor findings. |
| Security Admin | `security_admin` | Manage settings & user role assignment (except Super Admin creation). |
| Auditor | `auditor` | Read-only settings, roles, audit logs. |
| User | `user` | Standard user; protections apply; no admin UI. |

## Permissions

| Permission | Description |
|------------|-------------|
| `*` | Wildcard (Super Admin) |
| `settings.view` | View security settings |
| `settings.update` | Enable/disable/update settings |
| `roles.view` | View roles |
| `roles.manage` | Manage custom roles |
| `users.view` | View users |
| `users.assign_roles` | Assign/revoke OWASP roles |
| `audit.view` | View audit logs |
| `otp.manage` | Manage OTP configuration |

## Super Admin duties (summary)

1. Install / receive credentials from `php artisan owasp:install`
2. Change password immediately
3. Map VAPT findings → Settings toggles (see SOP)
4. Assign `security_admin` and `auditor` to staff
5. Keep OTP enabled for privileged roles
6. Review audit logs weekly

## Using roles in application code

```php
if ($user->isOwaspSuperAdmin()) {
    // ...
}

if ($user->hasOwaspRole(['security_admin', 'auditor'])) {
    // ...
}

if ($user->hasOwaspPermission('settings.update')) {
    // ...
}
```

### Route middleware

```php
Route::middleware(['auth', 'owasp.role:super_admin|security_admin'])->group(function () {
    // ...
});
```

## Assign via UI

`/owasp-security/roles` → select user + role → Assign.

## Assign via code / tinker

```php
$user = User::find(1);
$user->assignOwaspRole('auditor');
```

## Database

- `owasp_roles`
- `owasp_permissions`
- `owasp_role_permission`
- `owasp_user_role`
