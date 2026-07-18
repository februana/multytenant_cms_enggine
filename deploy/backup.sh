#!/usr/bin/env bash
set -euo pipefail

# deploy/backup.sh
# Creates timestamped backup of all user data
# Backs up: config.json, guest-links.json, database.sqlite, uploads/, event.ics

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
tar -czf "$BACKUP_FILE" \
    config.json \
    guest-links.json \
    database.sqlite \
    uploads/ \
    event.ics \
    2>/dev/null || {
        # Some files might not exist yet, that's OK
        echo "Warning: Some files were not found (this is normal for new installations)."
        tar -czf "$BACKUP_FILE" \
            $(ls config.json guest-links.json database.sqlite event.ics uploads/ 2>/dev/null) \
            || true
    }

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
