#!/usr/bin/env bash
set -euo pipefail

# deploy/restore.sh
# Restores data from a backup archive
# Usage: ./deploy/restore.sh <backup_file.tar.gz>

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"

if [ $# -lt 1 ]; then
    echo "Usage: $0 <backup_file.tar.gz>"
    echo "Available backups:"
    ls -lht "$ROOT_DIR/backups/"*.tar.gz 2>/dev/null | head -10 || echo "  No backups found."
    exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Error: Backup file not found: $BACKUP_FILE"
    exit 1
fi

echo "Starting restore from $BACKUP_FILE..."

# Verify archive is valid
if ! tar -tzf "$BACKUP_FILE" >/dev/null 2>&1; then
    echo "Error: Invalid or corrupted archive."
    exit 1
fi

# Change to root directory
cd "$ROOT_DIR"

# Extract backup
echo "Extracting files..."
tar -xzf "$BACKUP_FILE"

# Restore WebDAV password file if present in backup
if tar -tzf "$BACKUP_FILE" | grep -q "_temp_backup/davpasswd"; then
    echo "Restoring WebDAV password file..."
    # Extract only the davpasswd file to a temp location
    tar -xzf "$BACKUP_FILE" _temp_backup/davpasswd 2>/dev/null || true
    if [ -f "_temp_backup/davpasswd" ]; then
        cp "_temp_backup/davpasswd" /etc/apache2/.davpasswd
        chown root:www-data /etc/apache2/.davpasswd
        chmod 640 /etc/apache2/.davpasswd
        rm -rf "_temp_backup"
        echo "WebDAV password file restored to /etc/apache2/.davpasswd"
    fi
fi

# Set secure permissions
echo "Setting permissions..."
chmod 600 config.json 2>/dev/null || true
chmod 644 custom.css 2>/dev/null || true
chmod 600 guest-links.json 2>/dev/null || true
chown www-data:www-data config.json custom.css guest-links.json database.sqlite 2>/dev/null || true
chown -R www-data:www-data uploads/ 2>/dev/null || true
chmod -R 755 uploads/ 2>/dev/null || true
chown -R www-data:www-data webdav/ 2>/dev/null || true
chmod -R 755 webdav/ 2>/dev/null || true

# Verify critical files
if [ -f config.json ]; then
    echo "✓ config.json restored"
else
    echo "⚠ config.json not found in backup (may be normal)"
fi

if [ -f database.sqlite ]; then
    echo "✓ database.sqlite restored"
else
    echo "⚠ database.sqlite not found in backup (may be normal)"
fi

echo "Restore complete. Run deploy/health-check.sh to verify."

exit 0
