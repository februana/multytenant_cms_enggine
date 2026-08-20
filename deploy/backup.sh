#!/usr/bin/env bash
set -euo pipefail

# deploy/backup.sh
# Creates a validated, timestamped archive of runtime and user data.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="${DEPLOY_DIR:-$(dirname "$SCRIPT_DIR")}"
DATA_ROOT="${UNDANGAN_DATA_DIR:-$ROOT_DIR}"
BACKUP_DIR="${BACKUP_DIR:-$ROOT_DIR/backups}"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_FILE="$BACKUP_DIR/wedding_${TIMESTAMP}_$$.tar.gz"
DAV_PASSWORD_FILE="${APACHE_DAV_PASSWORD_PATH:-/etc/apache2/.davpasswd}"
STAGE_DIR=""

cleanup() {
    if [ -n "$STAGE_DIR" ] && [ -d "$STAGE_DIR" ]; then
        rm -rf -- "$STAGE_DIR"
    fi
}
trap cleanup EXIT

fail_backup() {
    echo "[ERROR] Backup failed: $*" >&2
    rm -f -- "$BACKUP_FILE"
    exit 1
}

copy_file_if_present() {
    local source="$1" archive_name="$2"
    if [ -f "$source" ]; then
        mkdir -p -- "$STAGE_DIR/$(dirname "$archive_name")"
        cp -a -- "$source" "$STAGE_DIR/$archive_name" || fail_backup "Could not stage $source"
        printf '[INFO] Included %s\n' "$archive_name"
    fi
}

copy_directory_if_present() {
    local source="$1" archive_name="$2"
    if [ -d "$source" ]; then
        mkdir -p -- "$STAGE_DIR/$archive_name"
        if command -v rsync >/dev/null 2>&1; then
            rsync -a -- "$source/" "$STAGE_DIR/$archive_name/" || fail_backup "Could not stage $source"
        else
            cp -a -- "$source/." "$STAGE_DIR/$archive_name/" || fail_backup "Could not stage $source"
        fi
        printf '[INFO] Included %s/\n' "$archive_name"
    fi
}

archive_has_entry() {
    local expected="$1"
    tar -tzf "$BACKUP_FILE" | sed 's#^\./##; s#/$##' | grep -Fx -- "$expected" >/dev/null 2>&1
}

printf '%s\n' 'Starting backup...'
mkdir -p -- "$BACKUP_DIR" || fail_backup "Cannot create backup directory: $BACKUP_DIR"
STAGE_DIR="$(mktemp -d "${TMPDIR:-/tmp}/webserver_undangan_backup.XXXXXX")" || fail_backup "Cannot create secure staging directory"

# Shared runtime state. All tenant settings, guest links, CSS, and calendar data live in SQLite.
copy_file_if_present "${UNDANGAN_DB_PATH:-$DATA_ROOT/database.sqlite}" "database.sqlite"
copy_file_if_present "${UNDANGAN_ENV_PATH:-$DATA_ROOT/.env}" ".env"

# Preserve the complete canonical user-media namespaces without scanning or pruning.
copy_directory_if_present "$ROOT_DIR/uploads" "uploads"
copy_directory_if_present "$ROOT_DIR/webdav" "webdav"

# The credential is staged outside the application tree and stored only under a
# backup-private namespace. restore.sh also understands the older archive path.
if [ -f "$DAV_PASSWORD_FILE" ]; then
    mkdir -p -- "$STAGE_DIR/.webdav"
    cp -a -- "$DAV_PASSWORD_FILE" "$STAGE_DIR/.webdav/davpasswd" || fail_backup "Could not stage WebDAV credentials"
    chmod 600 -- "$STAGE_DIR/.webdav/davpasswd"
    printf '%s\n' '[INFO] Included Apache WebDAV credentials in protected archive namespace.'
fi

printf '[INFO] Creating %s...\n' "$BACKUP_FILE"
if ! tar -czf "$BACKUP_FILE" -C "$STAGE_DIR" .; then
    fail_backup "tar archive creation failed"
fi

if [ ! -s "$BACKUP_FILE" ]; then
    fail_backup "archive is empty"
fi
if ! tar -tzf "$BACKUP_FILE" >/dev/null 2>&1; then
    fail_backup "archive validation failed"
fi

# Verify entries for every shared runtime object that existed before staging.
for entry in database.sqlite .env; do
    source_path="$DATA_ROOT/$entry"
    case "$entry" in
        database.sqlite) source_path="${UNDANGAN_DB_PATH:-$DATA_ROOT/database.sqlite}" ;;
        .env) source_path="${UNDANGAN_ENV_PATH:-$DATA_ROOT/.env}" ;;
    esac
    if [ -f "$source_path" ] && ! archive_has_entry "$entry"; then
        fail_backup "validated archive is missing $entry"
    fi
done
for entry in uploads webdav; do
    if [ -d "$ROOT_DIR/$entry" ] && ! archive_has_entry "$entry"; then
        fail_backup "validated archive is missing $entry/"
    fi
done
if [ -f "$DAV_PASSWORD_FILE" ] && ! archive_has_entry '.webdav/davpasswd'; then
    fail_backup "validated archive is missing WebDAV credentials"
fi

chmod 600 -- "$BACKUP_FILE" || fail_backup "Cannot restrict archive permissions"
if id -u www-data >/dev/null 2>&1 && getent group www-data >/dev/null 2>&1; then
    chown www-data:www-data "$BACKUP_FILE" 2>/dev/null || printf '%s\n' '[WARN] Could not apply www-data ownership to backup.' >&2
fi

# Retention runs only after the newest archive has been created and validated.
# Backup filenames contain no spaces, while media filenames inside the archive
# are handled by tar/rsync without shell expansion.
printf '%s\n' 'Cleaning old backups (keeping latest 10 valid archives)...'
find "$BACKUP_DIR" -maxdepth 1 -type f -name 'wedding_*.tar.gz' -printf '%T@ %p\n' \
    | sort -rn \
    | awk 'NR > 10 {sub(/^[^ ]+ /, ""); print}' \
    | while IFS= read -r old_backup; do
        [ -n "$old_backup" ] && rm -f -- "$old_backup"
      done

printf 'Backup complete: %s\n' "$BACKUP_FILE"
printf 'Size: %s\n' "$(du -h -- "$BACKUP_FILE" | cut -f1)"
