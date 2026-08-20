# Deployment Guide

This branch targets a **single Apache instance behind a Cloudflare Tunnel**. The application uses one PHP runtime, one shared SQLite database, one shared schema, and tenant-specific configuration and media namespaces. Docker Compose is retained as an optional packaging and test path for the same Apache/PHP application; it is not a per-tenant architecture.

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

Install or provision the following outside this repository before running the installer:

| Requirement | Purpose |
|---|---|
| PHP with `SQLite3` and `openssl` extensions | Application runtime and database/encryption support |
| `php` CLI | Standalone migration and validation |
| `openssl` | Initial credential and encryption-key generation |
| `rsync` | Preferred non-destructive synchronization; `cp` fallback is available |
| Apache with `mod_rewrite` and `AllowOverride All` | Catch-all HTTP serving and `/uploads/` media rewrite |
| Cloudflare Tunnel (`cloudflared`) | Intended public ingress |

The installer does not install packages or modify the operating system. It checks required dependencies and stops with an actionable error when PHP, SQLite3, or OpenSSL is missing.

### Install the application

Run the installer from a trusted repository checkout:

```bash
cd /path/to/webserver_undangan
sudo bash deploy/install.sh
sudo /var/www/wedding/deploy/health-check.sh
```

The installer copies application code without `--delete`, preserves an existing `.env`, initializes the runtime directory contract, creates the database file when needed, and runs [`deploy/migrate.php`](../deploy/migrate.php). The migration is the only schema bootstrap path; normal requests do not execute `CREATE TABLE` or `ALTER TABLE` operations.

The first installation requires `UNDANGAN_MAIN_DOMAIN` or prompts for a valid FQDN. It creates a Super Admin password and `UNDANGAN_PASSWORD_KEY`, prints them once, and stores only the password hash plus AES-256-GCM ciphertext in the database. Save the credentials before closing the terminal.

The installer is deliberately non-destructive. It does **not** run `apt-get`, `a2dissite`, `a2ensite`, `a2enmod`, `systemctl`, or equivalent commands, and it never writes to `/etc/apache2` or `/etc/nginx`. It prints the path to [`deploy/apache-catchall.conf.example`](../deploy/apache-catchall.conf.example); review and apply that Apache configuration through the operator's own change procedure, then reload Apache separately.

A suitable catch-all must use the deployed application as its document root and permit overrides:

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/wedding

    <Directory /var/www/wedding>
        AllowOverride All
        Require all granted
    </Directory>
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
git clone https://github.com/februana/webserver_undangan.git
cd webserver_undangan
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
