#!/usr/bin/env bash
set -euo pipefail

# deploy/backup.sh
# Creates timestamped backup of all user data
# Backs up: config.json, guest-links.json, database.sqlite, uploads/, webdav/, event.ics, .davpasswd

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="$ROOT_DIR/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="$BACKUP_DIR/wedding_${TIMESTAMP}.tar.gz"

echo "Starting backup..."

# Ensure backup directory exists
mkdir -p "$BACKUP_DIR"

# Change to root directory for relative paths in archive
cd "$ROOT_DIR"

# Create backup archive
echo "Creating $BACKUP_FILE..."

# Build list of files to backup
BACKUP_FILES=(
    "config.json"
    "custom.css"
    "guest-links.json"
    "database.sqlite"
    "uploads/"
    "webdav/"
    "event.ics"
)

# Include WebDAV password file if it exists
if [ -f "/etc/apache2/.davpasswd" ]; then
    echo "Including WebDAV password file in backup..."
    # Copy to temporary location for inclusion in archive
    mkdir -p "$BACKUP_DIR/_temp_backup"
    cp /etc/apache2/.davpasswd "$BACKUP_DIR/_temp_backup/davpasswd"
    BACKUP_FILES+=("_temp_backup/davpasswd")
fi

tar -czf "$BACKUP_FILE" "${BACKUP_FILES[@]}" 2>/dev/null || {
    # Some files might not exist yet, that's OK
    echo "Warning: Some files were not found (this is normal for new installations)."
    tar -czf "$BACKUP_FILE" $(ls "${BACKUP_FILES[@]}" 2>/dev/null) || true
}

# Cleanup temporary files
if [ -d "$BACKUP_DIR/_temp_backup" ]; then
    rm -rf "$BACKUP_DIR/_temp_backup"
fi

# Set secure permissions on backup
chmod 600 "$BACKUP_FILE"
chown www-data:www-data "$BACKUP_FILE" 2>/dev/null || true

# Keep only last 10 backups
echo "Cleaning old backups (keeping last 10)..."
cd "$BACKUP_DIR"
ls -t wedding_*.tar.gz 2>/dev/null | tail -n +11 | xargs -r rm --

echo "Backup complete: $BACKUP_FILE"
echo "Size: $(du -h "$BACKUP_FILE" | cut -f1)"

exit 0
