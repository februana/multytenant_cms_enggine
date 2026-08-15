# Deployment Guide

This document reflects the current single-root CMS-first repository structure.

## Prerequisites

- Linux server with root or sudo access
- PHP 8.1+ with SQLite support
- Composer installed for QR support
- Nginx or Apache with PHP-FPM
- Git available for repository updates

## Source and runtime

The Git working tree is separate from the deployed runtime:

```text
~/webserver_undangan
        ↓ deploy/install.sh
/var/www/wedding
```

`/var/www/wedding` is deployment output. Do not run Git operations there and do not manually create it for a fresh install; `deploy/install.sh` creates it.

## Fresh install

From the source repository:

```bash
sudo rm -rf /var/www/wedding
cd ~/webserver_undangan
sudo bash deploy/install.sh
sudo /var/www/wedding/deploy/health-check.sh
```

The installer deploys to `/var/www/wedding` by default and:

- installs required PHP packages
- creates the runtime directory
- creates database and config defaults when missing
- creates required upload directories
- sets secure permissions on configuration and SQLite files
- creates `.env` when needed
- configures Nginx or Apache from the templates in `deploy/templates/`

The `rm -rf /var/www/wedding` step is appropriate only for a deliberate fresh-install test because it removes the current runtime. Do not use it for ordinary updates when runtime data must be preserved.

## Update existing install

```bash
sudo /var/www/wedding/deploy/update.sh
```

The updater should preserve runtime data and user config while updating application source. Protected runtime data includes:

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

The health check validates critical deployment requirements, including:

- application root and public files exist
- active theme files exist
- config and database are readable and secure
- required upload directories exist and are writable
- public routes respond
- sensitive files remain blocked from public access

WebDAV is optional and should not fail a deployment when it is not enabled.

## Theme deployment

Theme presets live in the repository under:

```text
/themes/dewankl/
/themes/elix/
/themes/rainier/
/themes/archak/
```

`deploy/install.sh` and `deploy/update.sh` synchronize the `themes/` directory as application source. Runtime data remains protected separately.

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

### Site returns HTTP 500

Run the PHP entrypoint directly to expose the underlying fatal error:

```bash
sudo php -d display_errors=1 -d log_errors=1 /var/www/wedding/index.php >/tmp/test-output.html 2>/tmp/test-error.txt
cat /tmp/test-error.txt
```

Then run the full health check:

```bash
sudo /var/www/wedding/deploy/health-check.sh
```

### Site does not load

- verify the web server is active
- verify PHP-FPM is active when used by the selected web server
- verify the document root is `/var/www/wedding`
- run `health-check.sh`
- inspect the web server/PHP logs

### Permissions problem

```bash
sudo chown -R www-data:www-data /var/www/wedding/uploads
sudo chmod 600 /var/www/wedding/config.json
sudo chmod 600 /var/www/wedding/database.sqlite
sudo chmod 600 /var/www/wedding/guest-links.json
```

### 403 on config or database

This is expected and is a required security condition.
