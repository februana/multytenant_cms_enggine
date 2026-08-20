# Backup and Restore

This guide documents the production backup and restore contract for the pure multi-tenant Wedding Invitation CMS. A backup contains shared application state for **all tenants**, so access must be restricted to trusted operators.

## Backup contents

`deploy/backup.sh` creates a timestamped, validated archive containing the runtime objects present at backup time:

| Category | Content |
|---|---|
| Shared application state | SQLite database, `.env`, and deployment metadata when present |
| Tenant configuration | Stored inside the shared SQLite database, including `tenant_configs`, custom CSS, calendar data, and guest links |
| Tenant media | Complete `uploads/tenant_<id>/` tree, including cover, gallery, background, love-story, music, and theme assets |
| Backups | Existing validated archives when included by the deployment contract |
| Optional WebDAV | `webdav/` data and protected Apache credential namespace when provisioned |

There is no supported global `config.json`, `guest-links.json`, or global upload namespace in the current runtime. Legacy files may be preserved only for migration compatibility and must not be treated as the active configuration source.

The backup process treats the complete tenant media tree as user data. It does not inspect gallery references, convert media, delete unreferenced files, or move a file between tenants. Missing optional runtime objects are omitted without failing the archive; staging, permission, or validation errors return non-zero.

## Create a backup

Run the command as the deployment operator:

```bash
cd /var/www/wedding
sudo ./deploy/backup.sh
```

Archives are written below:

```text
/var/www/wedding/backups/wedding_YYYYMMDD_HHMMSS_<process>.tar.gz
```

The script validates existence, non-zero size, archive listing, and staged content before retaining the new archive. The archive is protected from world-readable access. Retention removes old archives only after a new archive passes validation.

## Inspect an archive

Before restoring, inspect the archive and confirm that it contains only expected paths:

```bash
tar -tzf /var/www/wedding/backups/wedding_YYYYMMDD_HHMMSS_<process>.tar.gz
```

A normal archive contains the shared database, environment data, `uploads/tenant_<id>/`, optional `webdav/`, and the protected WebDAV credential namespace. Reject an archive that contains unexpected absolute paths, traversal components, links, or a second application root.

## Restore a backup

```bash
cd /var/www/wedding
sudo ./deploy/restore.sh /var/www/wedding/backups/wedding_YYYYMMDD_HHMMSS_<process>.tar.gz
sudo ./deploy/health-check.sh
```

The restore process:

1. Validates the argument and archive existence.
2. Lists and validates archive entries before changing the application root.
3. Rejects absolute paths, `../` traversal, unexpected top-level objects, symbolic links, and hard links.
4. Extracts into a private temporary directory.
5. Restores the shared database, `.env`, complete tenant media tree, and optional WebDAV data according to the deployment contract.
6. Recreates missing runtime directories without deleting existing tenant media.
7. Applies restrictive permissions to secrets and writable permissions only where required.
8. Runs the standalone migration when the restore procedure supplies the required environment values.

An invalid archive is rejected before the application root is modified. Temporary extraction data is removed by a cleanup trap.

## Legacy archive compatibility

Older archives may contain global media directories or legacy configuration files from before the pure multi-tenant migration. They are compatibility inputs for controlled restore and migration only. After restoration, run `deploy/migrate.php` with the correct `UNDANGAN_MAIN_DOMAIN`, verify tenant binding, and do not re-enable legacy files as runtime sources.

The migration deterministically assigns legacy RSVP rows to the tenant identified by `UNDANGAN_MAIN_DOMAIN` and converts legacy password ciphertext when the configured key permits it. Review the migration output before reopening public traffic.

## Permissions and secrets

Protect the following items explicitly:

| Item | Expected protection |
|---|---|
| `.env` | Mode `600`; contains database path and encryption key |
| SQLite database | Mode `600` or an equivalent private deployment permission |
| Backups | Operator-only access; contain every tenant |
| `uploads/tenant_<id>/` | Writable by the application where required, not a replacement for `media.php` authorization |
| WebDAV credentials | Protected archive namespace and restricted filesystem permissions |

Do not run a blanket `chmod -R 755` over the deployment. Do not commit `.env`, production SQLite data, backups, or uploaded media.

## Update behavior

`deploy/update.sh` creates and validates a backup before replacing application code. It preserves `.env`, the shared database, the complete tenant-prefixed media tree, backups, WebDAV data, and compatible legacy storage. It then recreates missing runtime directories, runs the standalone migration, and validates the installation.

A failed backup or source synchronization must abort the update and must not print a success message. Preserve the verified backup until the new installation passes health and tenant-isolation checks.

## Verification

After backup, restore, or update:

```bash
tar -tzf /var/www/wedding/backups/wedding_YYYYMMDD_HHMMSS_<process>.tar.gz
sudo /var/www/wedding/deploy/health-check.sh
php tools/validate.php
php tools/repo_contract_audit.php
php tools/dependency_graph_audit.php
```

The validators check the tenant-aware schema contract, public endpoint wrappers, media delivery boundary, runtime file protection, and dependency/orphan contracts. The application-level health check should pass before the service is exposed through Cloudflare.

## Disaster recovery workflow

Provision a host with the same PHP, SQLite3, OpenSSL, WebP, and Apache prerequisites. Apply the reviewed catch-all Apache configuration, restore the archive with `deploy/restore.sh`, confirm `UNDANGAN_MAIN_DOMAIN` and `UNDANGAN_PASSWORD_KEY`, run health and repository validation, then verify at least one active tenant and one tenant-media request through the Cloudflare route. Do not automatically roll back over newly uploaded media without preserving the current runtime state first.
