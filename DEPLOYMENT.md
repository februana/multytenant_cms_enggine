# Deployment Quick Reference

The production architecture is one PHP/SQLite application served by one Apache catch-all VirtualHost behind a Cloudflare Tunnel. Tenant domains are resolved from `HTTP_HOST`; configuration and data are stored in a shared tenant-aware SQLite schema; media is stored below `uploads/tenant_<id>/` and delivered through `media.php`.

## Native Apache installation

The installer follows the foundation Apache + PHP-FPM deployment flow and remains non-destructive for application data. It installs/verifies Apache, PHP-FPM, Composer, ImageMagick, GD, mbstring, zip, and SQLite support; copies application code without `--delete`; runs Composer and `deploy/migrate.php`; detects the PHP-FPM socket; renders the repository Apache template; enables the required modules; runs `apache2ctl configtest`; and only then starts or reloads Apache. Existing `.env`, database, tenant media, backups, and WebDAV data are preserved. Set `SKIP_APACHE_PACKAGE_INSTALL=1` only when the operator has already provisioned the required packages.

```bash
cd /path/to/multytenant_cms_enggine
sudo bash deploy/install.sh
sudo /var/www/wedding/deploy/health-check.sh
```

The installer writes the source-adapted catch-all site to `/etc/apache2/sites-available/wedding.conf` and disables the default site after a successful configtest. Set `APACHE_ENABLE_SSL=1` with an existing certificate directory to enable the optional SSL template. Set `APACHE_WEBDAV_ENABLE=1` only when WebDAV is intentionally provisioned. The origin must not be directly exposed to the Internet.

## Routine operations

```bash
sudo /var/www/wedding/deploy/update.sh
sudo /var/www/wedding/deploy/backup.sh
sudo /var/www/wedding/deploy/restore.sh /path/to/backup.tar.gz
sudo /var/www/wedding/deploy/health-check.sh
```

The updater and restore flow preserve the shared database, `.env`, all tenant media, backups, and optional WebDAV data. See [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) and [`BACKUP_RESTORE.md`](BACKUP_RESTORE.md) for the complete procedures.

## Tenant onboarding

Set `UNDANGAN_MAIN_DOMAIN`, `UNDANGAN_DB_PATH`, `UNDANGAN_PASSWORD_KEY`, and `UNDANGAN_AUTO_PROVISION` in the protected `.env`. Unknown hosts are auto-provisioned only when auto-provisioning is enabled and the request is local Cloudflare Tunnel traffic with `CF-RAY` and a valid `CF-Connecting-IP`. Super Admin can also create tenants manually at `/admin/super-admin.php`.

## Validation

```bash
php tools/validate.php
php tools/repo_contract_audit.php
php tools/dependency_graph_audit.php
```

Related references are [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md), [`docs/MULTI_TENANT.md`](docs/MULTI_TENANT.md), [`docs/PASSWORD_MANAGEMENT.md`](docs/PASSWORD_MANAGEMENT.md), and [`SECURITY.md`](SECURITY.md).
