# Deployment Guide

This guide provides step-by-step instructions for deploying the Wedding Invitation application on Ubuntu 24.04.

## Prerequisites

- Ubuntu 24.04 server (fresh installation recommended)
- Root or sudo access
- Domain name pointing to your server (optional, for production)
- At least 512MB RAM and 5GB disk space

## Quick Installation

### Step 1: Clone and Install

```bash
cd /tmp
git clone https://github.com/yourusername/wedding-invitation.git temp-wedding
cd temp-wedding
sudo bash deploy/install.sh
```

The installer will automatically:
- Install Nginx, PHP-FPM 8.x, and required extensions
- Create the deployment directory at `/var/www/wedding`
- Copy all application files
- Configure Nginx with security rules
- Set secure file permissions
- Initialize the database and configuration files
- Enable the site and reload Nginx

### Step 2: Verify Installation

```bash
sudo /var/www/wedding/deploy/health-check.sh
```

Expected output:
```
=== Deployment Health Check ===
✓ Deployment directory exists
✓ File exists: index.php
✓ File exists: admin.php
...
✓ config.json blocked from public access
✓ database.sqlite blocked from public access

DEPLOYMENT HEALTHY
```

### Step 3: Access the Application

Open your browser and navigate to:
- `http://your-server-ip/` for the invitation
- `http://your-server-ip/admin.php` for the admin panel

## Manual Installation (Alternative)

If you prefer manual control:

### Step 1: Install Dependencies

```bash
sudo apt update
sudo apt install -y nginx php-fpm php-sqlite3 php-gd php-mbstring php-curl jq git
```

### Step 2: Clone Repository

```bash
sudo mkdir -p /var/www/wedding
sudo git clone https://github.com/yourusername/wedding-invitation.git /var/www/wedding
```

### Step 3: Set Permissions

```bash
sudo chown -R www-www-data /var/www/wedding
sudo find /var/www/wedding -type d -exec chmod 755 {} \;
sudo find /var/www/wedding -type f -name "*.php" -exec chmod 644 {} \;
sudo chmod 600 /var/www/wedding/config.json
```

### Step 4: Configure Nginx

Copy the provided Nginx configuration:

```bash
sudo cp /var/www/wedding/deploy/nginx-site.conf /etc/nginx/sites-available/wedding
sudo ln -sf /etc/nginx/sites-available/wedding /etc/nginx/sites-enabled/wedding
sudo rm -f /etc/nginx/sites-enabled/default
```

Edit `/etc/nginx/sites-available/wedding` if you need to customize the server name.

### Step 5: Test and Reload

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Configuration

### Database

The application uses SQLite. The database file is created automatically at `/var/www/wedding/database.sqlite`.

### Configuration File

Edit `/var/www/wedding/config.json` to customize:
- Site title and description
- Wedding details (couple names, date, location)
- Media paths (cover image, music, background)
- Gallery images
- Gift/bank account information

### Uploads

User-uploaded media is stored in:
- `/var/www/wedding/uploads/cover/` - Cover images
- `/var/www/wedding/uploads/music/` - Background music
- `/var/www/wedding/uploads/gallery/` - Gallery photos
- `/var/www/wedding/uploads/background/` - Background images

## SSL/HTTPS Setup (Recommended)

For production deployments, enable HTTPS using Certbot:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

Certbot will automatically:
- Obtain a free SSL certificate
- Configure HTTPS redirect
- Set up automatic renewal

## Firewall Configuration

If UFW is enabled:

```bash
sudo ufw allow 'Nginx Full'
sudo ufw allow 'Nginx HTTPS'  # If using SSL
sudo ufw enable
```

## Troubleshooting

### Application Not Loading

1. Check Nginx status: `sudo systemctl status nginx`
2. Check PHP-FPM status: `sudo systemctl status php*-fpm`
3. Check logs: `sudo tail -f /var/log/nginx/wedding.error.log`

### Permission Denied Errors

```bash
sudo chown -R www-www-data /var/www/wedding
sudo find /var/www/wedding -type f -name "*.json" -exec chmod 600 {} \;
```

### Database Errors

Ensure the database file exists and is writable:

```bash
ls -la /var/www/wedding/database.sqlite
sudo chown www-www-data /var/www/wedding/database.sqlite
sudo chmod 600 /var/www/wedding/database.sqlite
```

### 403 Forbidden on Config/Database

This is expected and correct! These files should be blocked from public access.

### 502 Bad Gateway

Check that PHP-FPM is running and the socket path in Nginx config matches:

```bash
sudo systemctl status php*-fpm
find /run/php -name "*.sock"
```

Update the `fastcgi_pass` directive in `/etc/nginx/sites-available/wedding` if needed.

## Updating

To update from Git:

```bash
cd /var/www/wedding
sudo git pull
sudo systemctl reload nginx
```

## Next Steps

- [Architecture Overview](ARCHITECTURE.md)
- [Security Best Practices](SECURITY.md)
- [Backup & Restore Procedures](BACKUP_RESTORE.md)
