#!/usr/bin/env bash
set -euo pipefail

# deploy/restore.sh
# Usage: ./deploy/restore.sh <backup_file.tar.gz>

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="${DEPLOY_DIR:-$(dirname "$SCRIPT_DIR")}"
DATA_ROOT="${UNDANGAN_DATA_DIR:-$ROOT_DIR}"
DAV_PASSWORD_FILE="${APACHE_DAV_PASSWORD_PATH:-/etc/apache2/.davpasswd}"
RUNTIME_DIRECTORIES_SCRIPT="$SCRIPT_DIR/runtime-directories.sh"

if [ ! -r "$RUNTIME_DIRECTORIES_SCRIPT" ]; then
    echo "[ERROR] Missing runtime directory contract: $RUNTIME_DIRECTORIES_SCRIPT" >&2
    exit 2
fi
. "$RUNTIME_DIRECTORIES_SCRIPT"

if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <backup_file.tar.gz>" >&2
    exit 1
fi

BACKUP_FILE="$1"
if [ ! -f "$BACKUP_FILE" ]; then
    echo "[ERROR] Backup file not found: $BACKUP_FILE" >&2
    exit 1
fi

RESTORE_TEMP=""
cleanup_restore() {
    if [ -n "$RESTORE_TEMP" ] && [ -d "$RESTORE_TEMP" ]; then
        rm -rf -- "$RESTORE_TEMP"
    fi
}
trap cleanup_restore EXIT

restore_error() {
    echo "[ERROR] Restore failed: $*" >&2
    exit 1
}

normalize_archive_entry() {
    local entry="$1"
    while [[ "$entry" == ./* ]]; do entry="${entry#./}"; done
    while [[ "$entry" == */ ]]; do entry="${entry%/}"; done
    printf '%s' "$entry"
}

archive_entry_allowed() {
    local entry="$1"
    case "$entry" in
        ''|.|config.json|custom.css|guest-links.json|database.sqlite|.env|event.ics \
        |uploads|uploads/*|webdav|webdav/*|.webdav|.webdav/davpasswd|_temp_backup|_temp_backup/davpasswd)
            return 0
            ;;
        *)
            return 1
            ;;
    esac
}

copy_root_file_if_present() {
    local name="$1"
    if [ -f "$RESTORE_TEMP/$name" ]; then
        mkdir -p -- "$DATA_ROOT"
        cp -a -- "$RESTORE_TEMP/$name" "$DATA_ROOT/$name" || restore_error "could not restore $name"
        printf '[INFO] Restored %s\n' "$name"
    fi
}

sync_directory_if_present() {
    local name="$1"
    if [ -d "$RESTORE_TEMP/$name" ]; then
        mkdir -p -- "$ROOT_DIR/$name"
        if command -v rsync >/dev/null 2>&1; then
            rsync -a -- "$RESTORE_TEMP/$name/" "$ROOT_DIR/$name/" || restore_error "could not restore $name/"
        else
            cp -a -- "$RESTORE_TEMP/$name/." "$ROOT_DIR/$name/" || restore_error "could not restore $name/"
        fi
        printf '[INFO] Restored %s/\n' "$name"
    fi
}

printf '%s\n' "Starting restore from $BACKUP_FILE..."
if ! tar -tzf "$BACKUP_FILE" >/dev/null 2>&1; then
    restore_error "archive is corrupt or cannot be listed"
fi

# Reject links before extraction. The archive is extracted into a fresh private
# directory, but links are rejected as defense-in-depth against unsafe backups.
if tar -tvzf "$BACKUP_FILE" | grep -Eq '^[lh]'; then
    restore_error "archive contains symbolic or hard links"
fi

while IFS= read -r raw_entry; do
    entry="$(normalize_archive_entry "$raw_entry")"
    if [[ "$entry" == /* || "$entry" == *$'\n'* || "$entry" == ../* || "$entry" == */../* || "$entry" == *'/..' ]]; then
        restore_error "archive contains unsafe path: $raw_entry"
    fi
    archive_entry_allowed "$entry" || restore_error "archive contains unexpected entry: $raw_entry"
done < <(tar -tzf "$BACKUP_FILE")

RESTORE_TEMP="$(mktemp -d "${TMPDIR:-/tmp}/webserver_undangan_restore.XXXXXX")" || restore_error "could not create secure extraction directory"
if ! tar --no-same-owner --no-same-permissions -xzf "$BACKUP_FILE" -C "$RESTORE_TEMP"; then
    restore_error "archive extraction failed"
fi

# Extracted structure is checked again after tar normalization.
for entry in config.json custom.css guest-links.json database.sqlite .env event.ics; do
    [ -e "$RESTORE_TEMP/$entry" ] || continue
    [ -L "$RESTORE_TEMP/$entry" ] && restore_error "extracted root entry is a symlink: $entry"
done

for name in config.json custom.css guest-links.json database.sqlite .env event.ics; do
    copy_root_file_if_present "$name"
done
sync_directory_if_present uploads
sync_directory_if_present webdav

# Support both the new protected namespace and the previous archive format.
DAV_SOURCE=""
if [ -f "$RESTORE_TEMP/.webdav/davpasswd" ]; then
    DAV_SOURCE="$RESTORE_TEMP/.webdav/davpasswd"
elif [ -f "$RESTORE_TEMP/_temp_backup/davpasswd" ]; then
    DAV_SOURCE="$RESTORE_TEMP/_temp_backup/davpasswd"
fi
if [ -n "$DAV_SOURCE" ]; then
    dav_directory="$(dirname "$DAV_PASSWORD_FILE")"
    if [ -d "$dav_directory" ]; then
        if [ "$EUID" -eq 0 ] && getent group www-data >/dev/null 2>&1; then
            install -o root -g www-data -m 640 "$DAV_SOURCE" "$DAV_PASSWORD_FILE" || restore_error "could not restore $DAV_PASSWORD_FILE"
        elif [ "$EUID" -eq 0 ]; then
            install -o root -m 640 "$DAV_SOURCE" "$DAV_PASSWORD_FILE" || restore_error "could not restore $DAV_PASSWORD_FILE"
            printf '%s\n' '[WARN] www-data group is unavailable; restored WebDAV credentials with root ownership only.' >&2
        else
            install -m 640 "$DAV_SOURCE" "$DAV_PASSWORD_FILE" || restore_error "could not restore $DAV_PASSWORD_FILE"
            printf '%s\n' '[WARN] Restore is not running as root; WebDAV credential ownership was not changed.' >&2
        fi
        printf '[INFO] Restored WebDAV credentials to %s.\n' "$DAV_PASSWORD_FILE"
    else
        printf '[WARN] WebDAV credentials were preserved in the archive, but %s is unavailable; WebDAV-specific restoration was skipped.\n' "$dav_directory" >&2
    fi
fi

ensure_runtime_directories "$ROOT_DIR" || restore_error "could not create canonical runtime directories"

# Apply the existing security model without making every file world-readable.
if id -u www-data >/dev/null 2>&1 && getent group www-data >/dev/null 2>&1; then
    for file in config.json guest-links.json database.sqlite .env; do
        [ -f "$DATA_ROOT/$file" ] && chown www-data:www-data "$DATA_ROOT/$file" 2>/dev/null || true
        [ -f "$DATA_ROOT/$file" ] && chmod 600 "$DATA_ROOT/$file"
    done
    [ -f "$DATA_ROOT/custom.css" ] && chown www-data:www-data "$DATA_ROOT/custom.css" 2>/dev/null || true
    [ -f "$DATA_ROOT/custom.css" ] && chmod 644 "$DATA_ROOT/custom.css"
    [ -f "$DATA_ROOT/event.ics" ] && chown www-data:www-data "$DATA_ROOT/event.ics" 2>/dev/null || true
    [ -f "$DATA_ROOT/event.ics" ] && chmod 644 "$DATA_ROOT/event.ics"
    for directory in uploads webdav backups; do
        [ -d "$ROOT_DIR/$directory" ] && chown -R www-data:www-data "$ROOT_DIR/$directory" 2>/dev/null || true
        [ -d "$ROOT_DIR/$directory" ] && find "$ROOT_DIR/$directory" -type d -exec chmod 755 {} \;
        [ -d "$ROOT_DIR/$directory" ] && find "$ROOT_DIR/$directory" -type f -exec chmod 644 {} \;
    done
fi

printf '%s\n' 'Restore complete. Run deploy/health-check.sh to verify.'
