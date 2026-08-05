# Security Settings Reference (OWASP Mapping)

All toggles appear under `/owasp-security/settings`. Keys are stored in `owasp_security_settings.key`.

## Headers (`group = headers`)

| Key | Header | OWASP | Notes |
|-----|--------|-------|-------|
| `header.x_frame_options` | X-Frame-Options | A05 Clickjacking | Prefer `DENY` or `SAMEORIGIN` |
| `header.x_content_type_options` | X-Content-Type-Options | A05 MIME sniffing | Value `nosniff` |
| `header.x_xss_protection` | X-XSS-Protection | A03 XSS | Legacy; keep for older browsers |
| `header.strict_transport_security` | Strict-Transport-Security | A02 Crypto | Enable only when HTTPS is correct |
| `header.referrer_policy` | Referrer-Policy | A01 data leak | `strict-origin-when-cross-origin` |
| `header.content_security_policy` | Content-Security-Policy | A03 XSS | Tighten per app assets |
| `header.permissions_policy` | Permissions-Policy | A05 | Disable unused browser features |
| `header.cross_origin_opener_policy` | Cross-Origin-Opener-Policy | A05 | `same-origin` |
| `header.cross_origin_resource_policy` | Cross-Origin-Resource-Policy | A05 | `same-origin` |
| `header.cross_origin_embedder_policy` | Cross-Origin-Embedder-Policy | A05 | Often **OFF** until app tested |
| `header.cache_control` | Cache-Control | A01 | Applied on sensitive path fragments |

## Authentication (`group = authentication`)

| Key | Control | OWASP | Behavior when enabled |
|-----|---------|-------|------------------------|
| `auth.rate_limiting` | Rate limiting | A07 | Global + stricter login throttles → 429 |
| `auth.account_lockout` | Account lockout | A07 | Failed logins → temporary lock |
| `auth.otp` | OTP / MFA | A07 | Required for privileged roles |
| `auth.password_policy` | Password policy | A07 | Min length, complexity checks via service |
| `auth.session` | Session security | A07 | Idle + absolute timeout |
| `auth.csrf` | CSRF flag | A01 | Marker for host CSRF enforcement |
| `auth.force_https` | Force HTTPS | A02 | Redirect non-TLS (non-local) |
| `auth.login_anomaly` | Login anomaly | A07 | Audit new IP/device style events |

## Protections (`group = protections`)

| Key | Control | OWASP | Behavior when enabled |
|-----|---------|-------|------------------------|
| `protection.xss_sanitization` | XSS sanitization | A03 | Strip/escape request strings |
| `protection.sql_injection` | SQLi patterns | A03 | Block common injection payloads |
| `protection.cors` | CORS allow-list | A05 | Only configured origins |

## How Super Admin uses this after an audit

Example finding: *“Missing Content-Security-Policy”*

1. Settings → Headers
2. Ensure `header.content_security_policy` is **ON**
3. Paste auditor-approved CSP into Value
4. Save → verify with `curl -I`
5. Auditor closes finding

Example finding: *“No brute-force protection on login”*

1. Enable `auth.rate_limiting` and `auth.account_lockout`
2. Optionally enable `auth.otp` for admins
3. Verify 429 / lockout behavior

## Config defaults

Defaults ship in `config/owasp-security.php` and are copied into DB on `owasp:install`. UI overrides win at runtime (cached ~5 minutes; cleared on save).
