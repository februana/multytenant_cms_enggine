# Backup and Restore

This guide documents the production backup and restore contract for the Wedding Invitation CMS. The workflow preserves runtime state and the complete user-owned media tree without moving, pruning, or regenerating media.

## What is backed up

`deploy/backup.sh` creates a timestamped, validated archive containing every runtime object that exists at the time of the backup:

| Category | Content |
|---|---|
| Root/runtime state | `config.json`, `custom.css`, `guest-links.json`, `database.sqlite`, `.env`, and `event.ics` |
| User media | The complete `uploads/` tree, including Gallery, cover, background, music, love-story, and Theme Assets namespaces |
| WebDAV data | The complete `webdav/` directory when present |
| Apache credential | `/etc/apache2/.davpasswd`, stored in the protected archive namespace `.webdav/davpasswd` when present |

The canonical Theme Assets paths are defined by `deploy/runtime-directories.sh` and include:

```text
uploads/theme-assets/dewankl/
uploads/theme-assets/rainier/
uploads/theme-assets/archak/
uploads/theme-assets/parang/
uploads/theme-assets/pawiwahan/
uploads/theme-assets/shubh-vivah/
uploads/theme-assets/yami-buzzy/
uploads/theme-assets/custom/
```

The backup process treats the complete `uploads/` tree as user data. It does not inspect Gallery references, delete unreferenced files, convert media, or mix Theme Assets with Gallery entries.

Optional files that do not exist are omitted without causing a backup failure. A genuine staging, tar, permission, or archive-validation failure returns non-zero and never claims `Backup complete`.

## Create a backup

Run the command as the deployment user, normally `root`:

```bash
cd /var/www/wedding
sudo ./deploy/backup.sh
```

Archives are written to:

```text
/var/www/wedding/backups/wedding_YYYYMMDD_HHMMSS_<process>.tar.gz
```

The generated archive is checked for existence, non-zero size, successful `tar -tzf` listing, and the presence of every file or directory that existed before staging. The file mode is `600`; on a normal native installation its owner is `www-data:www-data`.

The script retains the latest ten valid `wedding_*.tar.gz` archives. Retention runs only after the newest archive has passed validation, so a failed new backup does not remove an older valid backup.

## Inspect an archive

Before restoring, inspect the archive and verify its content:

```bash
tar -tzf /var/www/wedding/backups/wedding_YYYYMMDD_HHMMSS_<process>.tar.gz
```

A valid archive should contain only the supported runtime files, `uploads/`, `webdav/`, and the protected WebDAV credential namespace. Backup staging directories are never created inside `backups/` and must not appear in the archive.

## Restore a backup

Restore syntax remains compatible with existing operations:

```bash
cd /var/www/wedding
sudo ./deploy/restore.sh /var/www/wedding/backups/wedding_YYYYMMDD_HHMMSS_<process>.tar.gz
```

The restore process follows this order:

1. It validates the argument and verifies that the archive exists.
2. It lists the archive with `tar -tzf`.
3. It rejects absolute paths, `../` traversal, unexpected top-level objects, and symbolic or hard links.
4. It extracts into a private `mktemp` directory rather than directly into the application root.
5. It restores the runtime files and synchronizes `uploads/` and `webdav/` without creating a second media store.
6. It restores WebDAV credentials only when the archive contains them.
7. It calls `ensure_runtime_directories` so older archives also receive missing canonical Theme Assets directories.
8. It reapplies restricted runtime-file permissions and writable media-directory permissions.

An invalid or unsafe archive is rejected before the application root is modified. Temporary extraction data is removed with a cleanup trap and is never left in the application tree.

## Legacy archive compatibility

Older archives that predate `uploads/theme-assets/` remain restorable. Existing archive content is restored as-is, and the canonical runtime directory contract creates missing empty directories afterward. The restore process does not fail merely because a legacy archive lacks newer Theme Assets directories.

Older WebDAV backups that contain `_temp_backup/davpasswd` remain supported. New backups use `.webdav/davpasswd` so a temporary credential staging directory is never confused with application data.

## WebDAV credential handling

When `/etc/apache2/.davpasswd` exists, backup copies it into the protected archive namespace only; no permanent copy is written into the application tree. During a root restore, it is written back to `/etc/apache2/.davpasswd` with:

```text
owner: root:www-data
mode: 640
```

If Apache is not installed, the credential remains preserved in the archive and the restore reports that Apache-specific restoration was skipped. The restore never leaves the extracted credential under the application root.

## Permissions

The scripts preserve the deployment security model without applying a blanket recursive `chmod 755`:

```text
config.json       600
 database.sqlite  600
guest-links.json  600
.env              600
custom.css       644
event.ics        644
backups/         writable by the deployment runtime and not world-readable as files
uploads/         writable by the application where required
webdav/          writable by the application where required
Theme Assets     writable by the application where Admin replacement is supported
```

After a native restore, verify the owner and mode explicitly:

```bash
stat -c '%U:%G %a %n' \
  config.json database.sqlite guest-links.json .env custom.css event.ics
find uploads webdav -maxdepth 2 -type d -printf '%U:%G %m %p\n'
```

Do not run `chmod -R 755` across the application. It can expose configuration, database, and secret files.

## Update and backup failure behavior

`deploy/update.sh` runs a verified backup before cloning or replacing application code:

```bash
if ! create_backup; then
    log_error "Backup failed. Update aborted."
    return 1
fi
```

The updater keeps the SSH transport unchanged:

```text
git@github.com:februana/webserver_undangan.git
```

Git stderr is not suppressed. SSH authentication, DNS, host-key, and repository errors are shown to the operator. A failed clone leaves the current application content untouched and does not print `UPDATE COMPLETE`.

Preserved user data is staged in a separate secure temporary directory, never inside the cloned source. The updater preserves hidden files, filenames containing spaces, nested Theme Assets, Gallery media, `storage/` from legacy installations, backups, WebDAV data, and all runtime files. After source synchronization it restores the preserved state, creates missing canonical runtime directories, applies permissions, validates the web-server configuration when applicable, runs the health check, and prints `UPDATE COMPLETE` only after all critical steps pass.

## Verification procedure

After backup:

```bash
tar -tzf /var/www/wedding/backups/wedding_YYYYMMDD_HHMMSS_<process>.tar.gz
```

After restore or update:

```bash
sudo /var/www/wedding/deploy/health-check.sh
```

The health check validates the seven built-in adapters, active preset, required runtime files, every canonical upload and Theme Assets directory, runtime writability, restricted config/database permissions, WebP processing capability, WebDAV requirements, and public blocking of sensitive files.

The repository includes an isolated deployment fixture test covering:

```bash
sudo bash tools/deployment_backup_restore_smoke.sh
```

The test verifies empty/populated runtime behavior, Theme Assets and Gallery preservation, hidden files and spaces, WebDAV credentials, legacy archives without Theme Assets, invalid archive rejection, successful SSH-shaped clone behavior, visible clone failures, backup gating, and `_preserve` non-leakage. It never targets the production installation.

## Disaster recovery workflow

1. Provision the server with the same PHP, WebP, web-server, and WebDAV dependencies.
2. Deploy the repository code without deleting the existing runtime directories.
3. Confirm the archive with `tar -tzf`.
4. Restore using `deploy/restore.sh`.
5. Run `deploy/health-check.sh`.
6. Review the health summary before reopening public traffic.

If restore or update fails after source replacement, the verified backup remains available under `backups/`. Do not perform an automatic rollback that could overwrite new user media without first preserving and reviewing the current runtime state.

## Canonical media policy

New image uploads pass through the role-aware media pipeline and are persisted as verified WebP files. Gallery ownership remains explicit in `config.json`; placing an image in storage does not add it to Gallery. Deployment backup, update, and restore preserve the complete `uploads/` tree exactly and do not move media between namespaces.
