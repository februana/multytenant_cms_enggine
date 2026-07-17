# Backup & Restore Guide

## Overview

This guide explains how to backup and restore your wedding invitation application data. The backup system focuses on **user data** (configuration, database, uploads) while excluding version-controlled source code.

## What Gets Backed Up

### Included Files
- `config.json` - All application settings
- `guest-links.json` - Personalized guest link data
- `database.sqlite` - RSVP submissions and messages
- `uploads/` - All uploaded media (covers, music, gallery, backgrounds)
- `event.ics` - Generated calendar event

### Excluded Files
- Source code (`app/`, `assets/`)
- Deployment scripts (`deploy/`)
- Documentation
- Backup archives themselves
- Temporary files and logs

**Rationale**: Source code is managed via Git. Backups focus on irreplaceable user data.

## Automated Backups

### Daily Cron Job

Set up automated daily backups at 2 AM:

```bash
crontab -e
```

Add this line:

```cron
0 2 * * * cd /var/www/wedding && ./deploy/backup.sh
```

### Backup Retention

By default, backups accumulate in `backups/` directory. Set up cleanup for old backups:

```bash
# Delete backups older than 30 days
find /var/www/wedding/backups -name "*.tar.gz" -mtime +30 -delete
```

Add to crontab:

```cron
0 3 * * * find /var/www/wedding/backups -name "*.tar.gz" -mtime +30 -delete
```

## Manual Backup

### Create Backup

```bash
cd /var/www/wedding
./deploy/backup.sh
```

Output example:
```
Backup created: /var/www/wedding/backups/wedding_20240115_143022.tar.gz
Size: 15M
Files included: config.json, guest-links.json, database.sqlite, uploads/
```

### Verify Backup Integrity

Check the backup archive contents:

```bash
tar -tzf backups/wedding_YYYYMMDD_HHMMSS.tar.gz
```

Expected output:
```
config.json
guest-links.json
database.sqlite
uploads/
uploads/cover/
uploads/music/
uploads/gallery/
uploads/background/
```

### Off-Site Storage

**Critical**: Always store backups off-server. Examples:

#### SCP to Remote Server

```bash
scp backups/wedding_*.tar.gz user@backup-server:/backups/wedding/
```

#### Upload to Cloud Storage (AWS S3)

```bash
aws s3 cp backups/wedding_*.tar.gz s3://your-bucket/wedding-backups/
```

#### Rsync to NAS

```bash
rsync -avz backups/wedding_*.tar.gz user@nas:/volume1/backups/
```

## Restore from Backup

### Full Restore

**Warning**: This will overwrite all current data. Ensure you have a recent backup before proceeding.

```bash
cd /var/www/wedding
./deploy/restore.sh /path/to/backup.tar.gz
```

The restore script will:
1. Extract files to repository root
2. Restore correct file permissions
3. Set ownership to web server user
4. Verify critical files exist

### Selective Restore

To restore only specific files (e.g., just the database):

```bash
# Extract only database.sqlite
tar -xzf backup.tar.gz database.sqlite

# Extract only config.json
tar -xzf backup.tar.gz config.json

# Extract only uploads directory
tar -xzf backup.tar.gz uploads/
```

Then fix permissions:

```bash
chown www-www-data database.sqlite config.json
chmod 600 database.sqlite config.json
chown -R www-www-data uploads/
chmod -R 755 uploads/
```

### Restore to New Server

1. **Install Fresh Code**:
   ```bash
   git clone <repository-url> /var/www/wedding
   cd /var/www/wedding
   ./deploy/install.sh
   ```

2. **Restore Data**:
   ```bash
   ./deploy/restore.sh /path/to/backup.tar.gz
   ```

3. **Verify**:
   ```bash
   ./deploy/health-check.sh
   ```

## Disaster Recovery Plan

### Scenario: Server Failure

1. **Provision New Server** with same OS and PHP version
2. **Clone Repository**
3. **Retrieve Latest Backup** from off-site storage
4. **Run Installation Script**
5. **Restore from Backup**
6. **Update DNS** to point to new server IP
7. **Verify Functionality**

### Scenario: Accidental Data Deletion

1. **Stop Application** temporarily (maintenance mode)
2. **Identify Latest Good Backup** before deletion
3. **Restore Only Affected Files** (selective restore)
4. **Verify Data Integrity**
5. **Resume Application**

### Scenario: Corrupted Configuration

1. **Backup Current State** (even if corrupted)
2. **Extract config.json** from previous backup
3. **Review Configuration** for errors
4. **Replace Corrupted File**
5. **Clear Cache** (if using opcode cache)
6. **Test Admin Panel**

## Backup Schedule Recommendations

| Frequency | Type | Retention | Storage |
|-----------|------|-----------|---------|
| Daily | Full | 7 days | Local + Off-site |
| Weekly | Full | 4 weeks | Off-site |
| Monthly | Full | 12 months | Off-site + Archive |
| Before Updates | Full | Permanent | Off-site |

## Monitoring & Alerts

### Verify Backup Success

Add to monitoring system:

```bash
#!/bin/bash
# Check if backup was created in last 24 hours
if [ $(find /var/www/wedding/backups -name "*.tar.gz" -mtime -1 | wc -l) -eq 0 ]; then
    echo "ALERT: No recent backup found!"
    # Send email/slack notification
fi
```

### Test Restore Periodically

Quarterly, perform a test restore to a staging environment:

```bash
# Create test directory
mkdir -p /tmp/wedding-restore

# Extract backup
tar -xzf latest-backup.tar.gz -C /tmp/wedding-restore

# Verify files
test -f /tmp/wedding-restore/config.json && echo "Config OK"
test -f /tmp/wedding-restore/database.sqlite && echo "Database OK"
test -d /tmp/wedding-restore/uploads && echo "Uploads OK"

# Cleanup
rm -rf /tmp/wedding-restore
```

## Troubleshooting

### Backup Script Fails

1. **Check Permissions**:
   ```bash
   ls -la deploy/backup.sh
   chmod +x deploy/backup.sh
   ```

2. **Check Disk Space**:
   ```bash
   df -h
   ```

3. **Check Write Access**:
   ```bash
   touch backups/test && rm backups/test
   ```

### Restore Script Fails

1. **Verify Backup File**:
   ```bash
   tar -tzf backup.tar.gz > /dev/null && echo "Archive valid"
   ```

2. **Check Destination Permissions**:
   ```bash
   ls -la /var/www/wedding/
   ```

3. **Manual Extraction**:
   If script fails, try manual extract:
   ```bash
   tar -xzf backup.tar.gz -C /var/www/wedding/
   ```

## Best Practices

1. **Always Test Backups**: Periodically verify backups can be restored
2. **Off-Site Storage**: Never rely solely on local backups
3. **Encrypt Sensitive Data**: Use encrypted storage for backups containing guest information
4. **Document Procedures**: Keep this guide updated and accessible
5. **Automate Everything**: Reduce human error with cron jobs and scripts
6. **Monitor Backup Health**: Set up alerts for failed backups

## Related Documentation

- `DEPLOYMENT.md` - Initial setup and configuration
- `SECURITY.md` - Security policies and best practices
- `ARCHITECTURE.md` - System architecture overview
