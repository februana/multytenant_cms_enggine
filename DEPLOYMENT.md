# Deployment Guide

This document describes how to deploy the wedding invitation application using the current repository architecture.

## Prerequisites

- Linux server with root or sudo access
- PHP 8.1 or higher with SQLite support
- Composer installed for QR code functionality
- Web server with PHP-FPM (Nginx or Apache)
- Git installed if using the update script

## Quick Installation

### Step 1: Clone the repository

```bash
git clone https://github.com/februana/webserver_undangan.git
cd webserver_undangan
```

### Step 2: Run the installer

```bash
sudo ./deploy/install.sh
```

The installer deploys the application to `/var/www/wedding` by default.

### What the installer does

- Verifies Composer is installed
- Creates or preserves `.env`
- Copies application files to `/var/www/wedding`
- Configures web server settings for Nginx or Apache
- Sets secure permissions on runtime files
- Initializes the database and default configuration
- Generates administrator credentials if `.env` does not already exist
- Optionally configures WebDAV for Apache

### Step 3: Verify the installation

```bash
sudo /var/www/wedding/deploy/health-check.sh
```

Expected checks include:

- Required files and directories
- `config.json` protection
- `database.sqlite` protection
- `.env` existence and password
- WebDAV configuration if enabled

### Step 4: Access the application

- Public site: `http://your-server-ip/`
- Admin panel: `http://your-server-ip/admin/`

## Updating an Existing Installation

Use `deploy/update.sh` for updates.

```bash
sudo /var/www/wedding/deploy/update.sh
```

The update script will:

- create a backup using `deploy/backup.sh`
- download the latest source code from GitHub
- run `composer install --no-dev --optimize-autoloader`
- preserve user data and configuration
- restart PHP-FPM and reload the web server
- run `deploy/health-check.sh`

### Files preserved during update

- `config.json`
- `guest-links.json`
- `database.sqlite`
- `.env`
- `event.ics`
- `uploads/`
- `backups/`
- `webdav/`

## Installer vs Updater

- `deploy/install.sh` is the recommended path for fresh installations.
- `deploy/update.sh` is the preferred path for existing installations.
- `deploy/install.sh` can detect and run from the canonical target `/var/www/wedding`, but `deploy/update.sh` remains the safer update workflow.

## Optional WebDAV

Apache can optionally provide WebDAV support. The installer prompts for WebDAV configuration and creates `/etc/apache2/.davpasswd` when enabled.

Nginx may serve static content and limited WebDAV methods but is not the recommended WebDAV platform.

## Manual Installation

### 1. Install dependencies

```bash
sudo apt update
sudo apt install -y nginx php-fpm php-sqlite3 php-gd php-mbstring php-curl git composer
```

### 2. Clone the repository

```bash
sudo mkdir -p /var/www/wedding
sudo git clone https://github.com/februana/webserver_undangan.git /var/www/wedding
```

### 3. Set permissions

```bash
sudo chown -R www-data:www-data /var/www/wedding
sudo find /var/www/wedding -type d -exec chmod 755 {} \;
sudo find /var/www/wedding -type f -exec chmod 644 {} \;
sudo chmod 600 /var/www/wedding/config.json
```

### 4. Install Composer dependencies

```bash
cd /var/www/wedding
composer install --no-dev --optimize-autoloader
```

### 5. Configure the web server

- For Nginx: use the templates in `deploy/templates/nginx/`
- For Apache: use the templates in `deploy/templates/apache/`

### 6. Test and reload

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## SSL/HTTPS Setup

For production, enable HTTPS with Certbot.

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

## Troubleshooting

### Application not loading

- Check Nginx or Apache status
- Check PHP-FPM status
- Inspect web server logs

### Permission denied

```bash
sudo chown -R www-data:www-data /var/www/wedding
sudo chmod 600 /var/www/wedding/config.json
```

### Database errors

Ensure `/var/www/wedding/database.sqlite` exists and is writable by the web server.

### 403 forbidden on config or database

This is expected. These files should be blocked from public access.

## Next Steps

- Review `ARCHITECTURE.md` for the current repository design.
- Review `BACKUP_RESTORE.md` for backup and restore procedures.
