# Release Notes — PR #89

**Branch:** `multy-tenant_februana`
**Final commit:** `01740bf`
**Status:** Ready for review
**Deployment target:** One Apache instance behind a Cloudflare Tunnel, with shared SQLite schema and tenant-prefixed media.

## Overview

This release finalizes the transition from a single-tenant runtime to a pure multi-tenant wedding-invitation CMS. One application instance serves multiple domains. The tenant is resolved from the normalized `Host` header, configuration is stored in `tenant_configs`, tenant-owned rows carry `tenant_id`, and tenant media is stored below `uploads/tenant_<id>/`.

The existing theme-adapter architecture is preserved. The seven built-in presets and the CMS-native Custom builder retain their source-compatible DOM, CSS, JavaScript, dependency, section, and capability boundaries.

## Security and deployment changes

The native installer is now explicitly non-destructive and application-only. It does not install operating-system packages, alter `/etc/apache2` or `/etc/nginx`, enable or disable sites/modules, or restart services. Apache catch-all configuration is reviewed and applied separately by the operator.

Unknown hostnames are auto-provisioned only for validated local Cloudflare Tunnel requests when `UNDANGAN_AUTO_PROVISION=1`, `REMOTE_ADDR` is localhost, `CF-RAY` is present, and `CF-Connecting-IP` is a valid IP address. Invalid direct-origin requests do not create tenants.

Schema creation and legacy-data migration are deployment operations performed by `deploy/migrate.php`. Normal web requests do not run database DDL or maintain global configuration files. Visible Tenant Admin passwords remain an intentional Super Admin recovery feature and are stored as AES-256-GCM ciphertext alongside a one-way login hash.

## Media isolation

All uploads use the tenant namespace `uploads/tenant_<id>/`. The existing upload, WebP conversion, preset resize, original cleanup, and tenant persistence pipeline remains intact. Apache rewrites `/uploads/...` to `media.php`, where the current host tenant, path containment, and MIME type are checked before a file is served.

## Confirmed fix and audit

The confirmed Pawiwahan CSS fallback defect was fixed by changing the asset reference from `hero-source.jpg` to `assets/hero-source.jpg`. Repository validation now requires all public endpoint wrappers, including `event.ics.php` and `media.php`.

The final audit passed 24 regression cases, 142 HTTP frontend matrix assertions, media end-to-end and traversal checks, RSVP/calendar/CSS isolation checks, and the dependency graph audit with zero failures and zero warnings.

## Upgrade procedure

Back up an existing installation before updating:

```bash
sudo /var/www/wedding/deploy/backup.sh
sudo /var/www/wedding/deploy/update.sh
sudo /var/www/wedding/deploy/health-check.sh
```

For Docker packaging, retain named volumes during rebuilds. Do not run `docker compose down -v` unless intentionally destroying a disposable test installation. Read [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) and [`BACKUP_RESTORE.md`](BACKUP_RESTORE.md) for the complete procedures.

## Related documents

- [`README.md`](README.md) — project overview and quick start.
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — architecture and ownership boundaries.
- [`docs/MULTI_TENANT.md`](docs/MULTI_TENANT.md) — tenant routing and isolation.
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) — installation and operations.
- [`BACKUP_RESTORE.md`](BACKUP_RESTORE.md) — backup and disaster recovery.
- [`SECURITY.md`](SECURITY.md) — security policy and reporting.
- [`docs/ATTRIBUTIONS.md`](docs/ATTRIBUTIONS.md) — source provenance and licensing.
