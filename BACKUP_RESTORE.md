# Backup & Restore Guide

This guide explains the current backup and restore process for the wedding invitation application.

## What Gets Backed Up

The backup script preserves user data and configuration while excluding source code.

### Included files

- `config.json`
- `custom.css`
- `guest-links.json`
- `database.sqlite`
- `uploads/`
- `webdav/` (if present)
- `event.ics`
- `/etc/apache2/.davpasswd` (if present)

### Excluded files

- Application source code (`admin/`, `app/`, root PHP files, `deploy/`, documentation)
- Backup archive files themselves
- Temporary files and logs

## Creating a Backup

Run:

```bash
cd /var/www/wedding
./deploy/backup.sh
```

The script creates a timestamped `tar.gz` archive in `backups/`, applies secure permissions, and keeps the archive owner-readable.

### Backup retention

The backup script retains the most recent 10 archives and removes older files automatically.

## Verifying a Backup

Inspect the archive contents:

```bash
tar -tzf backups/wedding_YYYYMMDD_HHMMSS.tar.gz
```

Expected entries include:

- `config.json`
- `custom.css`
- `guest-links.json`
- `database.sqlite`
- `uploads/`
- `webdav/` (if present)
- `event.ics`

## Restoring from Backup

### Full restore

```bash
cd /var/www/wedding
./deploy/restore.sh /path/to/backup.tar.gz
```

This restores backed-up user data and configuration, preserving the application source code.

### Selective restore

To restore individual files from a backup archive:

```bash
tar -xzf backup.tar.gz config.json
tar -xzf backup.tar.gz database.sqlite
tar -xzf backup.tar.gz uploads/
```

Then restore permissions:

```bash
sudo chown www-data:www-data config.json database.sqlite
sudo chmod 600 config.json database.sqlite
sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 uploads/
```

## Disaster Recovery

### Server failure

1. Provision a new server with the same OS and PHP version
2. Clone the repository
3. Retrieve the latest backup
4. Run the installer if needed
5. Run `deploy/restore.sh` with the backup archive
6. Verify with `deploy/health-check.sh`

### Accidental deletion

1. Stop the application if possible
2. Locate the latest good backup
3. Restore the affected files
4. Verify the site and data

## Backup Schedule Recommendations

- Daily full backups with 7-day retention
- Weekly backups with 4-week retention
- Monthly backups with 12-month retention
- Always create a backup before updates

## Monitoring

Verify backups are created successfully and stored off-server whenever possible.
