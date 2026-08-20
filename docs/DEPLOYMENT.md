# Deployment Guide

[`multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine) is a Multi-Tenant CMS Engine targeting a **single Apache instance behind a Cloudflare Tunnel**. The application uses one PHP runtime, one shared SQLite database, one shared schema, and tenant-specific configuration and media namespaces. Docker Compose is retained as an optional packaging and test path for the same Apache/PHP application; it is not a per-tenant architecture.

## Runtime model

| Namespace | Default location | Persistence role |
|---|---|---|
| Application code | `/var/www/wedding` | Deployed PHP, theme, and validator files |
| Shared SQLite database | `/var/www/wedding/database.sqlite` or `UNDANGAN_DB_PATH` | Tenants, users, tenant configuration, guest links, and RSVP rows |
| Tenant media | `uploads/tenant_<id>/...` | Cover, gallery, background, love-story, music, and theme assets |
| Backups | `/var/www/wedding/backups` | Validated database, environment, and media archives |
| Optional WebDAV | `/var/www/wedding/webdav` | Operator-provisioned integration data |

The installer and Docker entrypoint create shared runtime roots, while tenant-specific media directories are created on demand during provisioning. Existing tenant files are never merged into another tenant's namespace.

## Native Apache deployment

### Prerequisites

Run the installer from a trusted checkout as root. It follows the Apache + PHP-FPM flow from the foundation repository and can install the required native packages through Debian/Ubuntu `apt-get`. The package set includes Apache, Apache utilities, PHP-FPM, PHP CLI, SQLite3, GD, mbstring, zip, Composer, ImageMagick, rsync, OpenSSL, CA certificates, curl, and unzip. `SKIP_APACHE_PACKAGE_INSTALL=1` is available only when the operator has already provisioned and verified those requirements.

| Requirement | Purpose |
|---|---|
| Debian/Ubuntu `apt-get` and `systemd` | Native package and service lifecycle used by the source installer |
| Apache with `mod_rewrite`, `headers`, `expires`, and `proxy_fcgi` | Catch-all HTTP serving, security headers, caching, and PHP-FPM |
| PHP-FPM socket under `/run/php` | FastCGI application execution without mod_php |
| Composer | Installation of the locked `chillerlan/php-qrcode` dependency graph |
| ImageMagick CLI and PHP GD | Foundation media conversion with GD fallback |
| Cloudflare Tunnel (`cloudflared`) | Intended public ingress |

The installer does not use `rsync --delete`, does not delete `.env`, the database, tenant media, backups, or WebDAV data, and does not create a per-tenant VirtualHost. It renders one catch-all vhost and leaves tenant resolution to the application.

### Install the application

Run the installer from a trusted repository checkout:

```bash
cd /path/to/multytenant_cms_enggine
sudo bash deploy/install.sh
sudo /var/www/wedding/deploy/health-check.sh
```

The installer copies application code without `--delete`, preserves an existing `.env`, initializes the runtime directory contract, runs `composer install --no-dev --prefer-dist --optimize-autoloader`, creates the database file when needed, and runs [`deploy/migrate.php`](../deploy/migrate.php). The migration is the only schema bootstrap path; normal requests do not execute `CREATE TABLE` or `ALTER TABLE` operations. Use `PRIMARY_TENANT_ADMIN_PASS` only as an optional protected process environment override; otherwise a secure random password is generated and printed once when the primary admin is first created.

The first installation requires `UNDANGAN_MAIN_DOMAIN` or prompts for a valid FQDN. This primary domain is inserted as the initial **normal tenant**: it has its own public invitation, tenant-scoped configuration/media/data, and Tenant Admin behavior. The migration then creates a separate Primary Tenant Admin with `role = tenant_admin` and that tenant ID, followed by the global Super Admin account with `role = super_admin` and `tenant_id IS NULL`. The Super Admin role, not the hostname, authorizes cross-tenant management. Newly generated Primary Tenant Admin credentials and Super Admin credentials are printed once; repeat migrations preserve existing accounts and passwords.

The installer is deliberately non-destructive toward application data, but it does configure the native Apache service as part of the foundation deployment contract. It enables `rewrite`, `headers`, `expires`, `proxy_fcgi`, and `setenvif`, starts the detected PHP-FPM service, renders `/etc/apache2/sites-available/wedding.conf` from [`deploy/templates/apache/apache-http.conf.template`](../deploy/templates/apache/apache-http.conf.template), runs `apache2ctl configtest`, enables the site, disables `000-default.conf`, and only then starts or reloads Apache. Set `APACHE_ENABLE_SSL=1` to use the existing SSL template with an already-provisioned certificate. Set `APACHE_WEBDAV_ENABLE=1` to provision `/etc/apache2/.davpasswd` and the optional source WebDAV modules. The installer does not introduce Nginx or create per-tenant VirtualHosts.

The generated catch-all uses the deployed application as its document root and permits overrides. Its PHP handler is the source foundation's PHP-FPM Unix-socket handler, and its `/uploads/` boundary remains application-owned through `media.php`:

```apache
<VirtualHost *:80>
    ServerName example.com
    ServerAlias *.example.com
    DocumentRoot /var/www/wedding

    <Directory /var/www/wedding>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost/"
    </FilesMatch>
</VirtualHost>
```

Do not add a per-tenant VirtualHost. The Cloudflare Tunnel should route all invitation domains to this one catch-all origin, and the origin firewall or network policy should prevent direct public access that bypasses Cloudflare.

### Existing installations and updates

Use the guarded updater for application changes:

```bash
sudo /var/www/wedding/deploy/update.sh
```

The updater backs up runtime data before replacing code, preserves `.env`, the shared database, the complete tenant-prefixed `uploads/` tree, backups, WebDAV data, and compatible legacy storage, then reruns the runtime directory contract, standalone migration, and health validation. Do not use a source checkout as the live runtime directory when the deployment path is `/var/www/wedding`.

## Cloudflare Tunnel and onboarding

Set the following environment values in the deployed `.env`:

```dotenv
UNDANGAN_MAIN_DOMAIN=example.com
UNDANGAN_DB_PATH=/var/www/wedding/database.sqlite
UNDANGAN_PASSWORD_KEY=<64-hex-characters>
UNDANGAN_AUTO_PROVISION=1
```

For an unknown hostname, auto-provisioning requires all of the following:

1. `UNDANGAN_AUTO_PROVISION=1`.
2. `REMOTE_ADDR` is `127.0.0.1` or `::1`.
3. `CF-RAY` is present.
4. `CF-Connecting-IP` contains a valid IP address.

A request that fails these checks receives `403` and does not create a tenant. An invalid or suspended tenant domain receives `404`. A validated unknown Cloudflare hostname is provisioned transactionally with an active tenant, `tenant_configs`, a tenant-admin account, and `uploads/tenant_<id>/` subdirectories.

Super Admin may also create tenants manually at `/admin/super-admin.php`. The DNS record and Cloudflare Tunnel route must exist before a custom domain is expected to serve an invitation. Auto-provisioning can be disabled by setting `UNDANGAN_AUTO_PROVISION=0` when the operator requires manual onboarding only.

## Media and static delivery

All uploads are tenant-scoped. The media pipeline is:

```text
Upload -> validate -> WebP conversion where applicable -> preset resize
       -> delete original after successful conversion -> tenant namespace
```

Apache rewrites `/uploads/<path>` to [`media.php`](../media.php). The endpoint resolves the current host tenant, validates the requested path against the current tenant's approved media roots, verifies MIME type, and serves only the authorized file. Do not expose the `uploads/` directory through an alternate static alias.

## Docker Compose

Docker Compose provides a repeatable Apache/PHP packaging path with persistent named volumes:

```bash
git clone https://github.com/februana/multytenant_cms_enggine.git
cd multytenant_cms_enggine
cp .env.example .env
chmod 600 .env
# Set ADMIN_PASS, UNDANGAN_MAIN_DOMAIN, and UNDANGAN_PASSWORD_KEY as appropriate.
docker compose build
docker compose up -d
docker compose exec wedding-cms /var/www/wedding/deploy/health-check.sh
```

The container uses Apache and PHP 8.3. Persist the database, tenant media, backups, and optional WebDAV volume. The entrypoint initializes directories and invokes the standalone migration when a main domain is configured. Do not run `docker compose down -v` except when intentionally destroying a disposable test installation.

## Backup, restore, and health checks

Use the dedicated guide [`BACKUP_RESTORE.md`](../BACKUP_RESTORE.md) for archive semantics. The routine commands are:

```bash
sudo /var/www/wedding/deploy/backup.sh
sudo /var/www/wedding/deploy/restore.sh /path/to/backup.tar.gz
sudo /var/www/wedding/deploy/health-check.sh
```

The health check validates application files, theme adapters, the shared database, tenant media root, WebP support, permissions, sensitive-file blocking, and HTTP reachability. Missing optional administrator media is reported as a warning; missing runtime, schema, or security requirements fails the check.

## Deployment safety checklist

| Check | Expected state |
|---|---|
| Web server | One Apache catch-all VirtualHost with `.htaccess` overrides |
| Public ingress | Cloudflare Tunnel only; origin not directly Internet-exposed |
| Database | One shared SQLite file with migrated tenant-aware schema |
| Tenant routing | Hostname lookup through `tenants.domain` |
| Auto-provisioning | Enabled only with localhost and Cloudflare header validation |
| Media | `uploads/tenant_<id>/` and `media.php` authorization boundary |
| Secrets | `.env` and `UNDANGAN_PASSWORD_KEY` outside Git, mode `600` |
| Runtime migration | Performed by deployment scripts, never by normal requests |
| Validation | `health-check.sh`, `tools/validate.php`, and dependency audit pass |

## Related documentation

- [`README.md`](../README.md) — project overview and quick start.
- [`docs/ARCHITECTURE.md`](ARCHITECTURE.md) — ownership and rendering boundaries.
- [`docs/MULTI_TENANT.md`](MULTI_TENANT.md) — tenant resolution and isolation.
- [`docs/PASSWORD_MANAGEMENT.md`](PASSWORD_MANAGEMENT.md) — credentials and AES-256-GCM recovery behavior.
- [`BACKUP_RESTORE.md`](../BACKUP_RESTORE.md) — backup and disaster recovery.
- [`SECURITY.md`](../SECURITY.md) — security policy and reporting.
- [`docs/ATTRIBUTIONS.md`](ATTRIBUTIONS.md) — source provenance and license status.
