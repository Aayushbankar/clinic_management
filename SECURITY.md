# Security Policy

## Supported Versions

| Version | Supported             |
| ------- | --------------------- |
| 1.1.x   | ✅ Yes                 |
| 1.0.x   | ⚠️ Security fixes only |
| < 1.0   | ❌ No                  |

## Reporting a Vulnerability

If you discover a security vulnerability, please report it responsibly:

1. **Do NOT** open a public issue
2. Email: security@clinic.test (replace with actual)
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

We aim to respond within 48 hours.

## Security Measures

### Authentication
- **bcrypt** password hashing (cost factor 10)
- **HttpOnly + Secure** session cookies
- **Rate limiting**: 5 failed attempts → 15 min lockout
- **CSRF** double-submit cookie pattern

### Data Protection
- **PDO prepared statements** - SQL injection prevention
- **Input validation** - Server-side on all endpoints
- **Role-based access control** - Enforced at API level

### Headers
```
Content-Security-Policy: default-src 'none'
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: no-referrer
Strict-Transport-Security: max-age=31536000
```

### Password Requirements
- Minimum 8 characters
- At least 1 uppercase letter
- At least 1 number

## Best Practices for Deployment

1. **Always use HTTPS** in production
2. **Change default credentials** immediately
3. **Set strong `CMS_DB_PASS`**
4. **Disable `CMS_DEBUG`** in production
5. **Regular backups** of MySQL database
6. **Keep Docker images updated**

## Known Limitations

- Password reset emails require SMTP configuration (demo mode logs to console)
- No 2FA currently (planned for v2.0)
