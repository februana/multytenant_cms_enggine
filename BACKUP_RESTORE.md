# Backup and Restore

This guide reflects the current backup model used by the repository.

## What is backed up

The current backup script preserves runtime and user data while excluding application source code. The protected data includes:

- `config.json`
- `guest-links.json`
- `database.sqlite`
- `uploads/`
- `event.ics`
- `custom.css` when present
- `webdav/` when enabled
- `/etc/apache2/.davpasswd` when WebDAV is active

## Create a backup

```bash
cd /var/www/wedding
./deploy/backup.sh
```

The script creates a timestamped archive in `backups/` and keeps only the most recent 10 archives.

## Restore a backup

```bash
cd /var/www/wedding
./deploy/restore.sh /path/to/backup.tar.gz
```

The restore process restores the runtime files while leaving the source code in place.

## Validate backup contents

```bash
tar -tzf /var/www/wedding/backups/wedding_YYYYMMDD_HHMMSS.tar.gz
```

## Permission notes

After restore, ensure runtime data remains owned by the web server user and remains restricted from public access:

```bash
sudo chown -R www-data:www-data /var/www/wedding/uploads
sudo chmod 600 /var/www/wedding/config.json
sudo chmod 600 /var/www/wedding/database.sqlite
sudo chmod 600 /var/www/wedding/guest-links.json
```

## Disaster recovery workflow

1. Provision the server with the same runtime dependencies.
2. Deploy the current repository code.
3. Copy or restore the backup archive.
4. Run `deploy/restore.sh`.
5. Run `deploy/health-check.sh`.

## Optional WebDAV

WebDAV is optional. If it is enabled in Apache, the backup script includes the WebDAV credentials file; otherwise, the deployment is still considered healthy if the critical root files and runtime data are intact.

## Canonical media policy

New image uploads are processed through the shared role-aware media pipeline before their configuration or Gallery reference is persisted. The final stored asset is a verified WebP; source JPG/PNG/GIF files are removed only after the WebP exists, can be decoded, and passes the declared dimension policy. Gallery ownership remains explicit in `config.json`; placing an image in storage does not add it to Gallery.

Before cleaning legacy source files, create an inventory with:

```bash
php tools/media_inventory.php
```

The inventory reports MIME type, dimensions, byte size, references, and verified WebP status. The optional cleanup mode removes only an unreferenced JPG/JPEG/PNG/GIF when a verified sibling WebP exists:

```bash
php tools/media_inventory.php --cleanup
```

Review the dry-run output first. Unique, referenced, or unverified media is preserved. Backups include the canonical `uploads/` tree, including preset-scoped `uploads/theme-assets/` files, so restore remains WebP-only for newly processed media and does not depend on original uploads.

## Deployment bootstrap and health contract

The application uses `deploy/runtime-directories.sh` as the shared runtime directory contract. Native installation, native updates, and the Docker entrypoint create the upload roots, `uploads/theme-assets/`, and preset-scoped Theme Assets directories for `dewankl`, `elix`, `rainier`, `archak`, `parang`, `pawiwahan`, and `custom`. These directories are storage boundaries only; they do not contain demo wedding media.

After restoring runtime data, run the health check:

```bash
sudo /var/www/wedding/deploy/health-check.sh
```

The health check now validates all six built-in theme adapters, the active preset, required upload and Theme Assets directories, runtime state, writable permissions, and ImageMagick or PHP GD WebP availability. Missing optional user media remains a warning, while missing asset directories, unsupported presets, unavailable processing capability, missing runtime state, or unsafe public exposure remain deployment failures.

The update path preserves the entire `uploads/` tree, including `uploads/theme-assets/<preset>/`, and then recreates any missing empty directories without replacing user media. This makes upgrading safe for installations created before the Theme Assets directory contract existed.
