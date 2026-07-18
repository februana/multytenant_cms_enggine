# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability in this project, please report it responsibly:

1. **Do NOT** create a public GitHub issue
2. **Email**: [Insert security contact email]
3. **Include**: 
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

We will respond within 48 hours and work with you to resolve the issue promptly.

## Security Architecture

### Defense in Depth

This application implements multiple layers of security:

1. **Web Server Level**: Access controls block sensitive files
2. **File System Level**: Restrictive permissions limit access
3. **Application Level**: Input validation, CSRF protection, and password hashing
4. **Network Level**: HTTPS/TLS encryption recommended

### Protected Resources

The following are **blocked** from direct web access:

| Resource | Reason | Protection Method |
|----------|--------|-------------------|
| `/app/` | Source code | Web server deny rule |
| `*.json` | Configuration data | Web server deny rule |
| `*.sqlite` | Database | Web server deny rule |
| `.env` | Environment variables | Web server deny rule |
| `/backups/` | Backup archives | Web server deny rule |
| `.*` | Hidden files | Web server deny rule |

### Upload Security

To prevent Remote Code Execution (RCE) attacks:

- **PHP execution disabled** in `/uploads/` directory
- Files served as static content only
- Original filenames sanitized to prevent directory traversal
- File types validated before acceptance

## Authentication System

### Password Storage

- Passwords are hashed using PHP's `password_hash()` with bcrypt algorithm
- Verification uses `password_verify()` for timing-safe comparison
- No plaintext passwords are stored

### Authentication Priority

The authentication system follows this priority order:

1. **Priority 1**: `config.json` → `admin.password_hash`
   - Takes precedence when set
   - Used after administrator changes password via admin panel

2. **Priority 2**: `.env` → `ADMIN_USER` + `ADMIN_PASS`
   - Used for initial installation login
   - Ignored once password_hash is set in config.json

3. **No Fallback**: Login rejected if neither credential source exists
   - No hardcoded fallback passwords
   - No silent defaults like "change-this-password"

### Password Migration

When an administrator changes their password through the admin panel:

1. The new password is hashed with `password_hash()`
2. The hash is stored in `config.json` under `admin.password_hash`
3. Subsequent logins use the hash, ignoring `.env` credentials
4. This provides secure migration from environment-based auth

### First Installation

During fresh installation:

1. If `.env` doesn't exist, `.env.example` is copied to `.env`
2. A cryptographically secure random password is generated using `openssl rand`
3. The password is written to `ADMIN_PASS` in `.env`
4. Credentials are displayed once at the end of installation
5. **Important**: Save these credentials immediately - they won't be shown again

## Configuration Guidelines

### File Permissions

Production servers should enforce these permissions:

```bash
# Configuration and database (owner read/write only)
chmod 600 config.json
chmod 600 guest-links.json
chmod 600 database.sqlite
chmod 600 .env

# Uploads directory (writable by web server)
chmod 755 uploads/
chown -R www-data:www-data uploads/

# Source code (read-only)
chmod 755 app/
chmod 644 app/*.php
```

### Web Server Hardening

#### Nginx

Use the provided `deploy/nginx-site.conf` which includes:
- Deny rules for sensitive paths
- PHP execution disabled in uploads
- Hidden file blocking
- Directory listing disabled

#### Apache

Ensure `.htaccess` is enabled with `AllowOverride All`. The included `.htaccess` file provides:
- Access denial for JSON/SQLite/.env files
- Upload directory protections
- Hidden file blocking

## Best Practices

### 1. Use HTTPS Always

Enable SSL/TLS for all traffic:

```bash
# Let's Encrypt installation
sudo certbot --nginx -d your-domain.com
```

**Never** run this application over plain HTTP in production.

### 2. Regular Updates

Keep all components updated:

```bash
# System packages
sudo apt update && sudo apt upgrade

# PHP extensions
sudo apt install php-gd php-sqlite3 php-pdo

# Application code
git pull origin main
```

### 3. Strong Admin Credentials

- On fresh installation, a cryptographically secure password is auto-generated
- For manual setup, use complex passwords (12+ characters, mixed case, numbers, symbols)
- Change default credentials immediately after installation
- Consider implementing two-factor authentication if available

### 4. Limit File Uploads

Configure PHP to restrict upload sizes:

```ini
; In php.ini
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 5
```

### 5. Disable Error Display

In production, hide error details from users:

```ini
; In php.ini
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
```

### 6. Secure Backups

- Encrypt backup files containing sensitive data
- Store backups off-server
- Restrict access to backup directories
- Delete old backups securely

### 7. Monitor Access Logs

Regularly review logs for suspicious activity:

```bash
# Check for blocked access attempts
grep "403" /var/log/nginx/access.log

# Check for upload attempts
grep "POST.*save.php" /var/log/nginx/access.log
```

### 8. Network Segmentation

If possible:
- Place application behind a firewall
- Restrict database access to localhost
- Use separate user accounts for different services

## Known Limitations

### Shared Hosting Risks

On shared hosting environments:
- Other users on the same server may have more access
- `.htaccess` rules may not be fully enforced
- PHP execution disabling in uploads may not work with PHP-FPM

**Mitigation**: Use VPS or dedicated hosting for sensitive deployments.

### SQLite Limitations

SQLite does not provide:
- User authentication at database level
- Encrypted columns
- Audit logging

**Mitigation**: Rely on file system permissions and application-level security.

## Incident Response

### If You Suspect a Breach

1. **Isolate**: Take the application offline temporarily
2. **Assess**: Review logs for unauthorized access
3. **Contain**: Change all passwords and API keys
4. **Eradicate**: Remove any malicious files
5. **Recover**: Restore from known-good backup
6. **Learn**: Document lessons and update security measures

### Log Locations

| Log Type | Typical Location |
|----------|------------------|
| Web Server Access | `/var/log/nginx/access.log` |
| Web Server Error | `/var/log/nginx/error.log` |
| PHP Errors | `/var/log/php-fpm/error.log` |
| Application Logs | `logs/` (if configured) |

## Compliance Considerations

### GDPR (European Union)

If collecting EU citizen data:
- Obtain explicit consent for RSVP data collection
- Provide data export/deletion capabilities
- Document data processing activities
- Implement data retention policies

### Data Minimization

Only collect necessary information:
- Guest name
- Attendance status
- Message (optional)
- Contact info (optional)

**Do not** collect sensitive personal information unless absolutely required.

## Security Checklist

Before going live, verify:

- [ ] HTTPS enabled with valid certificate
- [ ] File permissions set correctly
- [ ] Web server config applied and tested
- [ ] Admin password changed from initial/generated value
- [ ] PHP error display disabled
- [ ] Upload size limits configured
- [ ] Backup system operational
- [ ] Logs accessible for monitoring
- [ ] Blocked paths verified (config.json, app/, .env, etc.)
- [ ] Database file not directly downloadable
- [ ] No hardcoded fallback passwords in codebase
- [ ] `password_hash()` and `password_verify()` used correctly

## Version-Specific Notes

### Version 2.0+

- Single-root architecture improves security isolation
- Enhanced web server rules block more attack vectors
- Removed insecure fallback passwords
- Cryptographically secure password generation during installation
- Password hashing with proper priority-based authentication

### Upgrading from 1.x

After upgrading:
1. Verify new `.htaccess` rules are active
2. Test that `/app/` directory is blocked
3. Confirm `config.json` returns 403 when accessed directly
4. Update admin password to use new hashing system
5. Remove any hardcoded passwords from configuration

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [Nginx Security Guide](https://www.nginx.com/resources/admin-guide/security-controls/)
- [PHP password_hash() Documentation](https://www.php.net/manual/en/function.password-hash.php)
- [PHP password_verify() Documentation](https://www.php.net/manual/en/function.password-verify.php)

## Contact

For security questions or concerns:
- Email: [security@example.com]
- Documentation: See `DEPLOYMENT.md` for setup guidance

---

*Last Updated: January 2024*
*Version: 2.0.0*
