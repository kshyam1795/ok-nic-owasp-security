# SOP — OWASP Security Package (Standard Operating Procedure)

**Document control**

| Field | Value |
|-------|-------|
| Title | OWASP Security Controls — Operation SOP |
| Audience | Super Admin, Security Admin, Auditor, DevOps |
| Package | `growats/ok-nic-owasp-security` |
| Review cycle | After every VAPT / security audit |

---

## 1. Purpose

Define how security audit findings are translated into **enabled/disabled runtime controls** by the Super Admin, with audit trail and least privilege.

## 2. Roles & responsibilities

| Role | Duties |
|------|--------|
| **Auditor** | Performs assessment; lists findings (missing headers, weak auth, etc.); reviews Settings UI (read-only); signs off after remediation |
| **Super Admin** | Enables/disables all settings; assigns Security Admin; owns break-glass access |
| **Security Admin** | Day-to-day toggles for approved controls; reviews audit logs; cannot create Super Admin |
| **DevOps** | Installs package, configures env, deploys, monitors 429/lockouts |
| **Application Developer** | Integrates OTP delivery, CORS origins, password validation hooks |

## 3. Lifecycle (happy path)

```
Audit report → Map findings to Settings keys → Super Admin toggles ON
  → Verify in staging → Sign-off → Promote to production → Monitor audit logs
```

### 3.1 Intake (Auditor)

1. Complete VAPT / OWASP checklist.
2. For each finding, note:
   - OWASP category (e.g. A05 Security Misconfiguration)
   - Evidence (missing `X-Frame-Options`, no rate limit, etc.)
   - Recommended Setting key (see SECURITY-SETTINGS.md)
3. Raise ticket to Super Admin with checklist.

### 3.2 Remediation (Super Admin)

1. Login → `/owasp-security`
2. Open **Settings** → filter by group (`headers` / `authentication` / `protections`)
3. Enable control(s); adjust header **value** if auditor specifies stricter CSP/HSTS
4. Click **Save settings**
5. Confirm success flash + Audit Logs entry (`setting.update` / `setting.toggle`)

### 3.3 Verification (Auditor + QA)

| Control type | Verification |
|--------------|--------------|
| Headers | `curl -I https://host/` or DevTools Response Headers |
| Rate limiting | > N login posts → HTTP 429 |
| Account lockout | Fail login 5× → HTTP 423 / locked |
| OTP | Login as Super Admin → OTP challenge required |
| XSS/SQLi | Submit known probe strings → blocked or sanitized |
| CORS | Cross-origin from unlisted origin → no ACAO |

### 3.4 Sign-off

Auditor marks finding **Closed** when evidence matches. Super Admin retains Audit Log screenshot/export in ticket.

## 4. Change control rules

1. **No production toggle without ticket** referencing audit ID.
2. Disabling a control requires justification + temporary expiry date in ticket.
3. Only Super Admin may assign/revoke `super_admin`.
4. OTP must remain **enabled** for `super_admin`, `security_admin`, `auditor` unless emergency break-glass (max 24h, logged).
5. After any bulk change, clear caches:

```bash
php artisan cache:clear
php artisan config:clear
```

## 5. Emergency break-glass

If Super Admin is locked out:

1. DevOps sets temporary password via tinker / password reset (host app process).
2. Optionally set `OWASP_SECURITY_ENABLED=false` **only** on staging to recover; never leave disabled in production.
3. Log incident in `owasp_security_audit_logs` (manual note) and re-enable within 24 hours.
4. Rotate Super Admin password + revoke sessions.

## 6. Periodic checks (monthly)

- [ ] Review disabled controls — still justified?
- [ ] Review Super Admin / Security Admin user list
- [ ] Sample audit logs for unexpected toggles
- [ ] Confirm HSTS/CSP still compatible with app releases
- [ ] Re-run header scan (securityheaders.com or internal scanner)

## 7. New project onboarding (few clicks / commands)

```bash
composer require growats/ok-nic-owasp-security
php artisan owasp:install --email=superadmin@org.gov
# Login → Settings → enable recommended baseline (all ON except COEP if app breaks)
# Auditor validates → production promote
```

Baseline recommendation for government / NIC apps:

| Group | Default |
|-------|---------|
| Headers | All **ON** except `cross_origin_embedder_policy` until tested |
| Authentication | All **ON**; tune rate limits for public portals |
| Protections | All **ON**; set explicit CORS origins |

## 8. Escalation

| Issue | Escalate to |
|-------|-------------|
| Package install failure | DevOps + package maintainer |
| False positive SQLi/XSS blocks | Security Admin (disable temporarily) + Developer fix |
| Suspected compromise of Super Admin | CISO — revoke roles, force password reset, review audit logs |

## 9. Related documents

- [INSTALLATION.md](INSTALLATION.md)
- [SECURITY-SETTINGS.md](SECURITY-SETTINGS.md)
- [RBAC.md](RBAC.md)
