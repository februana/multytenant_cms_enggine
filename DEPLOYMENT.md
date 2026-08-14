# Deployment Guide

This document reflects the current single-root CMS-first repository structure.

## Prerequisites

- Linux server with root or sudo access
- PHP 8.1+ with SQLite support
- Composer installed for QR support
- Nginx or Apache with PHP-FPM
- Git available for repository updates

## Repository layout used by deployment

The canonical public root is the repository root itself:

- `index.php`
- `admin.php`
- `save.php`
- `messages.php`
- `gallery.php`
- `style.css`
- `script.js`

Private and legacy implementation remains in `app/` and is not the canonical frontend.

## Fresh install

```bash
cd /path/to/repository
sudo ./deploy/install.sh
```

The installer deploys to `/var/www/wedding` by default and:

- installs required PHP packages
- creates database and config defaults when missing
- creates required upload directories
- sets secure permissions on configuration and SQLite files
- creates `.env` when needed
- configures Nginx or Apache from the templates in `deploy/templates/`

## Update existing install

```bash
sudo /var/www/wedding/deploy/update.sh
```

The updater should preserve runtime data and user config while updating the application source. The current protected files include:

- `config.json`
- `guest-links.json`
- `database.sqlite`
- `uploads/`
- `event.ics`
- `custom.css`
- `backups/`

## Health check

```bash
sudo /var/www/wedding/deploy/health-check.sh
```

The health check validates only the critical deployment requirements for the repository as it exists now:

- application root and public files exist
- config and database are readable and secure
- required upload directories exist and are writable
- public routes respond
- sensitive files remain blocked from public access

WebDAV is optional and should not fail a deployment when it is not enabled.

## Web server templates

- Nginx template: `deploy/templates/nginx/wedding.conf`
- Apache templates: `deploy/templates/apache/`

The templates must align with the current canonical paths and should not assume a legacy `app/` document root architecture.

## Backup and restore

```bash
sudo /var/www/wedding/deploy/backup.sh
sudo /var/www/wedding/deploy/restore.sh /path/to/backup.tar.gz
```

## Security and permissions

Minimum runtime permissions should remain aligned with the server configuration:

```bash
chmod 600 /var/www/wedding/config.json
chmod 600 /var/www/wedding/database.sqlite
chmod 600 /var/www/wedding/guest-links.json
chown -R www-data:www-data /var/www/wedding/uploads
```

Public access must remain blocked for sensitive files such as `config.json`, `database.sqlite`, and `guest-links.json`.

## Troubleshooting

### Site does not load

- verify the web server is active
- verify PHP-FPM is active
- run `deploy/health-check.sh`
- confirm the document root points to the repository root and not a legacy subdirectory

### Permissions problem

```bash
sudo chown -R www-data:www-data /var/www/wedding
sudo chmod 600 /var/www/wedding/config.json
sudo chmod 600 /var/www/wedding/database.sqlite
sudo chmod 600 /var/www/wedding/guest-links.json
```

### 403 on config or database

This is expected and is a required security condition.
