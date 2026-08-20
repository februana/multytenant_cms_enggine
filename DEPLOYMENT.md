# Deployment Quick Reference

The production architecture is one PHP/SQLite application served by one Apache catch-all VirtualHost behind a Cloudflare Tunnel. Tenant domains are resolved from `HTTP_HOST`; configuration and data are stored in a shared tenant-aware SQLite schema; media is stored below `uploads/tenant_<id>/` and delivered through `media.php`.

## Native Apache installation

The installer is application-only and non-destructive. It checks PHP, SQLite3, and OpenSSL, copies application code without deleting runtime data, creates runtime directories, and runs `deploy/migrate.php`. It does not install packages, modify `/etc/apache2` or `/etc/nginx`, enable or disable sites/modules, or restart services.

```bash
cd /path/to/multytenant_cms_enggine
sudo bash deploy/install.sh
sudo /var/www/wedding/deploy/health-check.sh
```

Review and apply [`deploy/apache-catchall.conf.example`](deploy/apache-catchall.conf.example) separately. The origin must not be directly exposed to the Internet.

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
