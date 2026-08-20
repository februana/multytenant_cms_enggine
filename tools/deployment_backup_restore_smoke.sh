#!/usr/bin/env bash
set -euo pipefail

if [ "$EUID" -ne 0 ]; then
    exec sudo -n bash "$0" "$@"
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FIXTURE="$(mktemp -d /tmp/webserver_undangan_deployment_smoke.XXXXXX)"
cleanup_fixture() { [ "${KEEP_DEPLOYMENT_SMOKE:-0}" = 1 ] || rm -rf -- "$FIXTURE"; }
trap cleanup_fixture EXIT

PASS_COUNT=0
fail() { echo "FAIL: $*" >&2; exit 1; }
pass() { printf 'PASS: %s\n' "$*"; PASS_COUNT=$((PASS_COUNT + 1)); }

make_base_app() {
    local app="$1"
    mkdir -p "$app"
    cp -a "$ROOT_DIR/." "$app/"
    rm -rf -- "$app/.git" "$app/backups" "$app/uploads" "$app/webdav" "$app/storage"
    mkdir -p "$app/uploads/gallery" "$app/uploads/theme-assets/pawiwahan" "$app/webdav" "$app/backups" "$app/storage"
    printf 'user-config' > "$app/config.json"
    printf 'user-css' > "$app/custom.css"
    printf 'user-guests' > "$app/guest-links.json"
    python3 - "$app/database.sqlite" <<'PY'
import sqlite3, sys
path = sys.argv[1]
con = sqlite3.connect(path)
con.executescript('''
CREATE TABLE tenants (id INTEGER PRIMARY KEY AUTOINCREMENT, domain TEXT NOT NULL UNIQUE, status TEXT NOT NULL DEFAULT 'active', created_at DATETIME DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NULL, username TEXT NOT NULL, password_hash TEXT NOT NULL, visible_password TEXT NOT NULL DEFAULT '', role TEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE tenant_configs (tenant_id INTEGER PRIMARY KEY, config_json TEXT NOT NULL, custom_css TEXT NOT NULL DEFAULT '', event_ics TEXT NOT NULL DEFAULT '');
CREATE TABLE guest_links (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, guest_name TEXT NOT NULL, invitation_url TEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE tamu (id INTEGER PRIMARY KEY AUTOINCREMENT, nama TEXT NOT NULL, status TEXT NOT NULL, ucapan TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);
''')
con.commit()
con.close()
PY
    printf 'UNDANGAN_MAIN_DOMAIN=restore.example\nADMIN_USER=restore-admin\nADMIN_PASS=restore-password\nUNDANGAN_PASSWORD_KEY=restore-key\n' > "$app/.env"
    printf 'user-ics' > "$app/event.ics"
    printf 'gallery media' > "$app/uploads/gallery/photo with spaces.webp"
    printf 'theme media' > "$app/uploads/theme-assets/pawiwahan/user-theme.webp"
    printf 'hidden media' > "$app/uploads/theme-assets/pawiwahan/.hidden-theme"
    printf 'webdav data' > "$app/webdav/.htaccess"
    printf 'legacy storage' > "$app/storage/legacy.txt"
}

make_source() {
    local source="$1"
    mkdir -p "$source"
    cp -a "$ROOT_DIR/." "$source/"
    rm -rf -- "$source/.git" "$source/uploads" "$source/backups" "$source/webdav" "$source/storage"
    rm -f -- "$source/config.json" "$source/custom.css" "$source/guest-links.json" "$source/database.sqlite" "$source/.env" "$source/event.ics"
    printf 'updated source marker' > "$source/deployment-source-marker.txt"
}

make_mocks() {
    local bin="$1"
    mkdir -p "$bin"
    cat > "$bin/git" <<'MOCKGIT'
#!/usr/bin/env bash
set -euo pipefail
if [ "${1:-}" = "clone" ]; then
    printf '%s\n' "$@" >> "${MOCK_GIT_LOG:-/tmp/mock-git.log}"
    if [ "${MOCK_GIT_FAIL:-0}" = "1" ]; then
        echo 'mock git: simulated SSH authentication failure' >&2
        exit 77
    fi
    source_dir="${MOCK_GIT_SOURCE:?MOCK_GIT_SOURCE is required}"
    target="${@: -1}"
    mkdir -p "$target"
    cp -a "$source_dir/." "$target/"
    exit 0
fi
exec /usr/bin/git "$@"
MOCKGIT
    chmod 755 "$bin/git"
    cat > "$bin/rsync" <<'MOCKRSYNC'
#!/usr/bin/env bash
set -euo pipefail
positional=()
delete_mode=0
for arg in "$@"; do
    case "$arg" in
        --delete) delete_mode=1 ;;
        --) ;;
        -*) ;;
        *) positional+=("$arg")
    esac
done
[ "${#positional[@]}" -ge 2 ] || { echo 'mock rsync: source/destination missing' >&2; exit 2; }
src="${positional[${#positional[@]}-2]}"
dest="${positional[${#positional[@]}-1]}"
mkdir -p "$dest"
if [ "$delete_mode" -eq 1 ]; then
    for child in "$dest"/* "$dest"/.[!.]*; do
        [ -e "$child" ] || continue
        case "$(basename "$child")" in
            uploads|webdav|backups|storage|database.sqlite|.env) continue ;;
            *) rm -rf -- "$child" ;;
        esac
    done
fi
cp -a "$src/." "$dest/"
MOCKRSYNC
    chmod 755 "$bin/rsync"
}

# Empty/new installation: optional runtime files and directories may be absent, but the archive must still validate.
EMPTY_APP="$FIXTURE/empty-app"
mkdir -p "$EMPTY_APP/backups"
DEPLOY_DIR="$EMPTY_APP" UNDANGAN_DATA_DIR="$EMPTY_APP" BACKUP_DIR="$EMPTY_APP/backups" bash "$ROOT_DIR/deploy/backup.sh" >"$FIXTURE/empty-backup.log"
EMPTY_ARCHIVE="$(find "$EMPTY_APP/backups" -maxdepth 1 -type f -name '*.tar.gz' -print -quit)"
[ -s "$EMPTY_ARCHIVE" ] || fail 'empty-install backup archive missing or empty'
tar -tzf "$EMPTY_ARCHIVE" >/dev/null || fail 'empty-install backup archive is invalid'
grep -F 'Backup complete:' "$FIXTURE/empty-backup.log" >/dev/null || fail 'empty-install backup did not claim validated success'
pass 'empty installation backup succeeds with optional paths absent'

# A genuine backup-directory failure must return non-zero and retain old archives.
FAILED_BACKUP_ROOT="$FIXTURE/backup-failure"
mkdir -p "$FAILED_BACKUP_ROOT"
printf old > "$FAILED_BACKUP_ROOT/old-valid.tar.gz"
printf not-a-directory > "$FAILED_BACKUP_ROOT/backups"
if DEPLOY_DIR="$FAILED_BACKUP_ROOT" UNDANGAN_DATA_DIR="$FAILED_BACKUP_ROOT" BACKUP_DIR="$FAILED_BACKUP_ROOT/backups" bash "$ROOT_DIR/deploy/backup.sh" >"$FIXTURE/backup-failure.log" 2>&1; then
    fail 'backup failure returned success'
fi
! grep -F 'Backup complete:' "$FIXTURE/backup-failure.log" >/dev/null || fail 'failed backup claimed success'
[ -f "$FAILED_BACKUP_ROOT/old-valid.tar.gz" ] || fail 'failed backup deleted an older archive'
pass 'genuine backup failure returns non-zero and retains older archive'

# Backup/restore populated fixture, including canonical namespaces and hidden filenames.
APP="$FIXTURE/app"
make_base_app "$APP"
APACHE_DAV="$FIXTURE/davpasswd"
printf 'dav secret' > "$APACHE_DAV"
BACKUP_OUTPUT="$FIXTURE/backup.log"
DEPLOY_DIR="$APP" UNDANGAN_DATA_DIR="$APP" BACKUP_DIR="$APP/backups" APACHE_DAV_PASSWORD_PATH="$APACHE_DAV" bash "$ROOT_DIR/deploy/backup.sh" >"$BACKUP_OUTPUT"
ARCHIVE="$(find "$APP/backups" -maxdepth 1 -type f -name '*.tar.gz' -print -quit)"
[ -s "$ARCHIVE" ] || fail 'backup archive missing or empty'
tar -tzf "$ARCHIVE" >/dev/null || fail 'backup archive cannot be listed'
tar -tzf "$ARCHIVE" | grep -F './uploads/gallery/photo with spaces.webp' >/dev/null || fail 'Gallery filename with spaces missing'
tar -tzf "$ARCHIVE" | grep -F './uploads/theme-assets/pawiwahan/.hidden-theme' >/dev/null || fail 'hidden Theme Asset missing'
tar -tzf "$ARCHIVE" | grep -F './.webdav/davpasswd' >/dev/null || fail 'WebDAV credential missing from protected namespace'
[ "$(stat -c %a "$ARCHIVE")" = 600 ] || fail 'backup archive is not mode 600'
! tar -tzf "$ARCHIVE" | grep -E 'webserver_undangan_(backup|preserve)' >/dev/null || fail 'temporary staging leaked into backup'
grep -F 'Backup complete:' "$BACKUP_OUTPUT" >/dev/null || fail 'backup did not claim success after validation'
pass 'populated backup is valid, canonical, hidden-file-safe, and permission-restricted'

RESTORE_APP="$FIXTURE/restored"
mkdir -p "$RESTORE_APP/apache2"
DEPLOY_DIR="$RESTORE_APP" UNDANGAN_DATA_DIR="$RESTORE_APP" APACHE_DAV_PASSWORD_PATH="$RESTORE_APP/apache2/.davpasswd" bash "$ROOT_DIR/deploy/restore.sh" "$ARCHIVE" >"$FIXTURE/restore.log"
[ ! -f "$RESTORE_APP/config.json" ] || fail 'restore resurrected removed global config.json'
UNDANGAN_DB_PATH="$APP/database.sqlite" UNDANGAN_MAIN_DOMAIN=restore.example UNDANGAN_PASSWORD_KEY=restore-key ADMIN_USER=restore-admin ADMIN_PASS=restore-password php "$ROOT_DIR/deploy/migrate.php" >/dev/null
python3 - "$APP/database.sqlite" "$RESTORE_APP/database.sqlite" <<'PY'
import sqlite3, sys

def snapshot(path):
    con = sqlite3.connect(path)
    integrity = con.execute('PRAGMA integrity_check').fetchone()[0]
    assert integrity == 'ok', f'{path}: integrity check failed: {integrity}'
    tables = {row[0] for row in con.execute("SELECT name FROM sqlite_master WHERE type='table'")}
    required = {'tenants', 'users', 'tenant_configs', 'guest_links', 'tamu'}
    assert required.issubset(tables), f'{path}: migrated tables missing'
    tenants = con.execute('SELECT domain, status FROM tenants ORDER BY domain').fetchall()
    users = con.execute('SELECT username, role, tenant_id FROM users ORDER BY username').fetchall()
    configs = con.execute('SELECT tenant_id, config_json FROM tenant_configs ORDER BY tenant_id').fetchall()
    counts = {table: con.execute(f'SELECT COUNT(*) FROM {table}').fetchone()[0] for table in required}
    con.close()
    return tenants, [(u, r, t) for u, r, t in users], configs, counts
assert snapshot(sys.argv[1]) == snapshot(sys.argv[2]), 'restored database differs semantically after migration'
PY
cmp "$APP/uploads/gallery/photo with spaces.webp" "$RESTORE_APP/uploads/gallery/photo with spaces.webp"
cmp "$APP/uploads/theme-assets/pawiwahan/.hidden-theme" "$RESTORE_APP/uploads/theme-assets/pawiwahan/.hidden-theme"
cmp "$APACHE_DAV" "$RESTORE_APP/apache2/.davpasswd"
[ "$(stat -c %a "$RESTORE_APP/apache2/.davpasswd")" = 640 ] || fail 'restored WebDAV credential is not mode 640'
[ -d "$RESTORE_APP/uploads" ] || fail 'restore did not bootstrap the uploads root'
pass 'valid backup restores state, WebDAV credentials, hidden files, spaces, and database-only runtime directories'

# Legacy archive without Theme Assets remains restorable and is bootstrapped afterward.
LEGACY_STAGE="$FIXTURE/legacy-stage"
mkdir -p "$LEGACY_STAGE/uploads/gallery"
printf legacy > "$LEGACY_STAGE/config.json"
printf legacy-gallery > "$LEGACY_STAGE/uploads/gallery/legacy.webp"
tar -czf "$FIXTURE/legacy.tar.gz" -C "$LEGACY_STAGE" .
LEGACY_RESTORE="$FIXTURE/legacy-restore"
mkdir -p "$LEGACY_RESTORE"
DEPLOY_DIR="$LEGACY_RESTORE" UNDANGAN_DATA_DIR="$LEGACY_RESTORE" bash "$ROOT_DIR/deploy/restore.sh" "$FIXTURE/legacy.tar.gz" >"$FIXTURE/legacy-restore.log"
[ ! -f "$LEGACY_RESTORE/config.json" ] || fail 'legacy archive restored removed global config.json'
[ -f "$LEGACY_RESTORE/uploads/gallery/legacy.webp" ] || fail 'legacy Gallery media was not restored'
[ -d "$LEGACY_RESTORE/uploads" ] || fail 'legacy restore did not create the uploads root'
pass 'legacy archive without Theme Assets remains compatible with on-demand tenant media directories'

# Corrupt archives must be rejected before modifying the restore target.
CORRUPT_ROOT="$FIXTURE/corrupt-root"
mkdir -p "$CORRUPT_ROOT"
printf untouched > "$CORRUPT_ROOT/config.json"
printf not-a-tar > "$FIXTURE/corrupt.tar.gz"
if DEPLOY_DIR="$CORRUPT_ROOT" UNDANGAN_DATA_DIR="$CORRUPT_ROOT" bash "$ROOT_DIR/deploy/restore.sh" "$FIXTURE/corrupt.tar.gz" >"$FIXTURE/corrupt.log" 2>&1; then
    fail 'corrupt archive was accepted'
fi
[ "$(cat "$CORRUPT_ROOT/config.json")" = untouched ] || fail 'corrupt archive changed restore target'
pass 'corrupt archive is rejected before target modification'

# Invalid traversal archive must be rejected before modifying the restore target.
printf original > "$FIXTURE/traversal-target.txt"
printf safe > "$FIXTURE/safe.txt"
tar -czf "$FIXTURE/traversal.tar.gz" -C "$FIXTURE" --transform='s#safe.txt#../evil.txt#' safe.txt 2>/dev/null
TRAVERSAL_ROOT="$FIXTURE/traversal-root"
mkdir -p "$TRAVERSAL_ROOT"
printf untouched > "$TRAVERSAL_ROOT/config.json"
if DEPLOY_DIR="$TRAVERSAL_ROOT" UNDANGAN_DATA_DIR="$TRAVERSAL_ROOT" bash "$ROOT_DIR/deploy/restore.sh" "$FIXTURE/traversal.tar.gz" >"$FIXTURE/traversal.log" 2>&1; then
    fail 'path traversal archive was accepted'
fi
[ "$(cat "$TRAVERSAL_ROOT/config.json")" = untouched ] || fail 'path traversal changed restore target'
pass 'path traversal archive is rejected before target modification'

# Update fixture with a successful SSH-shaped clone and preservation checks.
UPDATE_APP="$FIXTURE/update-app"
SOURCE="$FIXTURE/source"
MOCK_BIN="$FIXTURE/mock-bin"
MOCK_LOG="$FIXTURE/mock-git.log"
make_base_app "$UPDATE_APP"
make_source "$SOURCE"
make_mocks "$MOCK_BIN"
printf '#!/usr/bin/env bash\nexit 0\n' > "$FIXTURE/health-pass.sh"
chmod 755 "$FIXTURE/health-pass.sh"
TERM=xterm PATH="$MOCK_BIN:$PATH" CANONICAL_TARGET="$UPDATE_APP" DEPLOY_DIR="$UPDATE_APP" UNDANGAN_DATA_DIR="$UPDATE_APP" BACKUP_SCRIPT="$UPDATE_APP/deploy/backup.sh" HEALTH_CHECK_SCRIPT="$FIXTURE/health-pass.sh" REPOSITORY_URL='git@github.com:februana/webserver_undangan.git' MOCK_GIT_SOURCE="$SOURCE" MOCK_GIT_LOG="$MOCK_LOG" bash "$ROOT_DIR/deploy/update.sh" <<< $'1\n\n4\n' >"$FIXTURE/update-success.log" 2>&1
[ -f "$UPDATE_APP/deployment-source-marker.txt" ] || fail 'successful update did not synchronize cloned source'
[ ! -f "$UPDATE_APP/config.json" ] || fail 'successful update retained removed global config.json'
[ -f "$UPDATE_APP/uploads/gallery/photo with spaces.webp" ] || fail 'successful update lost Gallery media'
[ -f "$UPDATE_APP/uploads/theme-assets/pawiwahan/.hidden-theme" ] || fail 'successful update lost hidden Theme Asset'
[ -f "$UPDATE_APP/storage/legacy.txt" ] || fail 'successful update lost legacy storage'
[ ! -d "$UPDATE_APP/_preserve" ] || fail '_preserve leaked into web root'
[ -d "$UPDATE_APP/uploads" ] || fail 'successful update did not bootstrap the uploads root'
grep -F 'git@github.com:februana/webserver_undangan.git' "$MOCK_LOG" >/dev/null || fail 'update did not retain SSH repository transport'
grep -F 'UPDATE COMPLETE' "$FIXTURE/update-success.log" >/dev/null || fail 'successful update omitted completion marker'
pass 'successful update preserves all user namespaces and does not leak staging data'

# Failed clone must expose the error and leave the application data/source unchanged.
FAILED_APP="$FIXTURE/failed-clone-app"
make_base_app "$FAILED_APP"
: > "$MOCK_LOG"
if TERM=xterm PATH="$MOCK_BIN:$PATH" CANONICAL_TARGET="$FAILED_APP" DEPLOY_DIR="$FAILED_APP" UNDANGAN_DATA_DIR="$FAILED_APP" BACKUP_SCRIPT="$FAILED_APP/deploy/backup.sh" HEALTH_CHECK_SCRIPT="$FIXTURE/health-pass.sh" REPOSITORY_URL='git@github.com:februana/webserver_undangan.git' MOCK_GIT_SOURCE="$SOURCE" MOCK_GIT_LOG="$MOCK_LOG" MOCK_GIT_FAIL=1 bash "$ROOT_DIR/deploy/update.sh" <<< $'1\n\n4\n' >"$FIXTURE/update-failed-clone.log" 2>&1; then
    :
fi
grep -F 'simulated SSH authentication failure' "$FIXTURE/update-failed-clone.log" >/dev/null || fail 'real clone error was hidden'
[ ! -f "$FAILED_APP/deployment-source-marker.txt" ] || fail 'failed clone modified application source'
[ "$(cat "$FAILED_APP/config.json")" = user-config ] || fail 'failed clone modified config'
[ ! -d "$FAILED_APP/_preserve" ] || fail 'failed clone leaked preservation staging'
! grep -F 'UPDATE COMPLETE' "$FIXTURE/update-failed-clone.log" >/dev/null || fail 'failed clone printed completion marker'
pass 'failed SSH-shaped clone exposes diagnostics and leaves application content unchanged'

# Failed backup must block clone and source replacement.
FAILED_BACKUP_APP="$FIXTURE/failed-backup-app"
make_base_app "$FAILED_BACKUP_APP"
FAIL_BACKUP="$FIXTURE/fail-backup.sh"
cat > "$FAIL_BACKUP" <<'FAILBACKUP'
#!/usr/bin/env bash
echo 'simulated backup failure' >&2
exit 9
FAILBACKUP
chmod 755 "$FAIL_BACKUP"
: > "$MOCK_LOG"
if TERM=xterm PATH="$MOCK_BIN:$PATH" CANONICAL_TARGET="$FAILED_BACKUP_APP" DEPLOY_DIR="$FAILED_BACKUP_APP" UNDANGAN_DATA_DIR="$FAILED_BACKUP_APP" BACKUP_SCRIPT="$FAIL_BACKUP" HEALTH_CHECK_SCRIPT="$FIXTURE/health-pass.sh" REPOSITORY_URL='git@github.com:februana/webserver_undangan.git' MOCK_GIT_SOURCE="$SOURCE" MOCK_GIT_LOG="$MOCK_LOG" bash "$ROOT_DIR/deploy/update.sh" <<< $'1\n\n4\n' >"$FIXTURE/update-failed-backup.log" 2>&1; then
    :
fi
grep -F 'Backup failed. Update aborted.' "$FIXTURE/update-failed-backup.log" >/dev/null || fail 'backup failure did not abort update'
[ ! -s "$MOCK_LOG" ] || fail 'clone started after backup failure'
[ "$(cat "$FAILED_BACKUP_APP/config.json")" = user-config ] || fail 'backup failure modified application'
pass 'backup failure blocks clone and application replacement'

printf 'PASS: deployment backup/restore/update smoke (%s assertions)\n' "$PASS_COUNT"
