# Deployment Guide

## Prerequisites

- **Web Server**: Nginx (recommended) or Apache
- **PHP**: Version 7.4 or higher
- **PHP Extensions**: `pdo`, `sqlite3`, `json`, `gd`, `fileinfo`
- **Database**: SQLite (built-in, no separate installation needed)
- **User**: Web server user (typically `www-data` on Ubuntu/Debian)

## Quick Start

### 1. Clone Repository

```bash
git clone <repository-url> /var/www/wedding
cd /var/www/wedding
```

### 2. Run Installation Script

```bash
chmod +x deploy/install.sh
sudo ./deploy/install.sh
```

This script will:
- Create required directories (`uploads/*`)
- Set correct file permissions
- Generate placeholder files
- Configure ownership for web server

### 3. Configure Web Server

#### Nginx Configuration

Copy the provided configuration template:

```bash
sudo cp deploy/nginx-site.conf /etc/nginx/sites-available/wedding
sudo ln -s /etc/nginx/sites-available/wedding /etc/nginx/sites-enabled/
```

Edit `/etc/nginx/sites-available/wedding`:
- Change `server_name` to your domain
- Verify `root` points to `/var/www/wedding`

Test and reload:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

#### Apache Configuration

Enable `.htaccess` support in your virtual host:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/wedding
    
    <Directory /var/www/wedding>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable required modules:

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

### 4. Verify Installation

Run the health check:

```bash
./deploy/health-check.sh
```

Expected output: All checks should pass with HTTP 200 status codes.

### 5. Access Application

- **Frontend**: `https://your-domain.com/`
- **Admin Panel**: `https://your-domain.com/admin.php`

## File Permissions

The installation script sets these permissions automatically:

| Path | Permission | Owner | Group |
|------|------------|-------|-------|
| `config.json` | `600` | www-data | www-data |
| `database.sqlite` | `600` | www-data | www-data |
| `guest-links.json` | `600` | www-data | www-data |
| `uploads/` | `755` | www-data | www-data |
| `app/` | `755` | root | root |
| `assets/` | `755` | root | root |

### Manual Permission Fix

If needed, manually fix permissions:

```bash
sudo chown -R www-www-data /var/www/wedding
sudo chmod 600 /var/www/wedding/config.json
sudo chmod 755 /var/www/wedding/uploads
```

## Configuration

### Initial Setup

1. Access `https://your-domain.com/admin.php`
2. Login with default credentials (if set) or create admin account
3. Configure wedding details, theme, and media uploads
4. Save settings (writes to `config.json`)

### Environment Variables (Optional)

Create `.env` file in root for custom settings:

```bash
APP_ENV=production
APP_DEBUG=false
```

## Backup & Restore

### Automated Backups

Add to crontab for daily backups:

```bash
0 2 * * * /var/www/wedding/deploy/backup.sh
```

Backups are stored in `/var/www/wedding/backups/`.

### Manual Backup

```bash
./deploy/backup.sh
```

### Restore from Backup

```bash
./deploy/restore.sh /path/to/backup.tar.gz
```

**Warning**: This overwrites current data. Ensure you have a recent backup before restoring.

## Security Hardening

### 1. Verify Protected Resources

Test that sensitive files are blocked:

```bash
curl -I https://your-domain.com/config.json
# Should return 403 Forbidden

curl -I https://your-domain.com/app/config.php
# Should return 403 Forbidden
```

### 2. Disable Directory Listing

Already configured in `.htaccess` and Nginx config. Verify:

```bash
curl -I https://your-domain.com/uploads/
# Should return 403 or 404, not directory listing
```

### 3. SSL/TLS Configuration

Use Let's Encrypt for free SSL:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

### 4. Regular Updates

Keep PHP and system packages updated:

```bash
sudo apt update && sudo apt upgrade
```

## Troubleshooting

### 500 Internal Server Error

1. Check PHP error log: `/var/log/php-fpm/error.log`
2. Check web server error log: `/var/log/nginx/error.log`
3. Verify file permissions
4. Ensure PHP extensions are installed

### 403 Forbidden on Valid Pages

1. Verify `index.php` exists in root
2. Check `.htaccess` syntax (Apache)
3. Verify Nginx configuration `try_files` directive

### Uploads Not Working

1. Check `uploads/` directory permissions: `ls -la uploads/`
2. Verify ownership: `chown -R www-www-data uploads/`
3. Check PHP `upload_max_filesize` and `post_max_size` in `php.ini`

### Database Errors

1. Verify `database.sqlite` exists and is writable
2. Check permissions: `chmod 600 database.sqlite`
3. Ensure PHP has SQLite extension enabled

## Upgrade from v1.x

If upgrading from the legacy dual-root architecture:

1. **Backup Current Data**:
   ```bash
   ./deploy/backup.sh
   ```

2. **Pull Latest Code**:
   ```bash
   git pull origin main
   ```

3. **Run Installation Script**:
   ```bash
   ./deploy/install.sh
   ```

4. **Update Web Server Config**:
   - Replace old Nginx/Apache config with new templates
   - Change document root to repository root (not `/app`)

5. **Verify Functionality**:
   ```bash
   ./deploy/health-check.sh
   ```

6. **Clean Old Files** (optional):
   - Remove old `/app/` references from configs
   - Delete legacy scripts if no longer needed

## Support

For issues or questions:
1. Check logs in `/var/log/`
2. Run health check script
3. Review `ARCHITECTURE.md` for system design
4. Consult `SECURITY.md` for security policies
