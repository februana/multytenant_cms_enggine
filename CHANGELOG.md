# Changelog

## [Unreleased] — PR #89

This change set finalizes the pure multi-tenant architecture for the Wedding Invitation CMS on branch `multy-tenant_februana`.

### Added

- Added shared-schema tenant resolution from normalized `HTTP_HOST` through the `tenants` table.
- Added Cloudflare-authenticated auto-provisioning guarded by `REMOTE_ADDR`, `CF-RAY`, and a valid `CF-Connecting-IP`.
- Added transactional tenant initialization for `tenant_configs`, tenant-admin credentials, and tenant media directories.
- Added tenant-authorized `media.php` delivery and Apache rewrite coverage for all `/uploads/...` requests.
- Added the permanent `tools/dependency_graph_audit.php` build-time dependency, orphan, endpoint, asset, DOM, and tenant-isolation auditor.

### Changed

- Moved runtime configuration, custom CSS, calendar data, and guest links to tenant-scoped SQLite storage.
- Moved schema creation and legacy migration into `deploy/migrate.php`; normal requests no longer perform runtime DDL.
- Preserved and hardened the upload → WebP → preset resize → original cleanup → tenant namespace pipeline.
- Made the native installer non-destructive and Apache-only: it does not install packages, modify `/etc/apache2` or `/etc/nginx`, enable or disable sites/modules, or restart services.
- Documented AES-256-GCM storage for `visible_password` while retaining the intentional Super Admin recovery feature.
- Updated README, architecture, deployment, backup/restore, security, contributor, and password-management documentation to match the final implementation.

### Fixed

- Corrected the Pawiwahan fallback CSS asset path from `hero-source.jpg` to `assets/hero-source.jpg`.
- Extended repository validation to require all public endpoint wrappers, including `event.ics.php` and `media.php`.

### Validation

The final audit passed 24 regression cases, 142 HTTP frontend matrix assertions, media end-to-end and traversal checks, RSVP/calendar/CSS isolation checks, and the dependency graph audit with zero failures and zero warnings.

See [`README.md`](README.md), [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md), [`docs/MULTI_TENANT.md`](docs/MULTI_TENANT.md), [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md), and [`dependency_audit_final_report.md`](../dependency_audit_final_report.md) for the current operational and audit records.
