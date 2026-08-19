#!/usr/bin/env bash
set -Eeuo pipefail

DB_PATH="${UNDANGAN_DB_PATH:-/var/www/wedding/database.sqlite}"
APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
HOST_NAME=""
BASE_URL="http://127.0.0.1"
FAILURES=0

usage() {
  echo "Usage: sudo $0 --host tenant.example.com [--db /path/database.sqlite] [--root /var/www/wedding]"
}
while [[ $# -gt 0 ]]; do
  case "$1" in
    --host) HOST_NAME="${2:-}"; shift 2;;
    --db) DB_PATH="${2:-}"; shift 2;;
    --root) APP_ROOT="${2:-}"; shift 2;;
    -h|--help) usage; exit 0;;
    *) echo "Unknown option: $1" >&2; usage; exit 2;;
  esac
done

if [[ -z "$HOST_NAME" ]]; then
  echo "ERROR: --host wajib diisi untuk verifikasi Host header." >&2
  usage
  exit 2
fi

pass() { printf 'PASS: %s\n' "$1"; }
fail() { printf 'FAIL: %s\n' "$1" >&2; FAILURES=$((FAILURES + 1)); }
warn() { printf 'WARN: %s\n' "$1"; }

if ! command -v apache2ctl >/dev/null 2>&1; then fail 'apache2ctl tidak ditemukan'; else pass 'apache2ctl tersedia'; fi
if ! apache2ctl configtest >/tmp/undangan-apache-configtest.log 2>&1; then fail "Apache configtest gagal: $(tr '\n' ' ' </tmp/undangan-apache-configtest.log)"; else pass 'Apache configuration valid'; fi

if systemctl is-active --quiet apache2; then pass 'Apache aktif'; else fail 'Apache tidak aktif'; fi
if ss -ltn 2>/dev/null | grep -Eq '(^|[[:space:]])(0\.0\.0\.0|\[::\]|\*):80([[:space:]]|$)'; then pass 'Apache/listener ditemukan pada port 80'; else fail 'Tidak ada listener port 80'; fi

DEFAULT_CONF="/etc/apache2/sites-enabled/000-default.conf"
if [[ ! -r "$DEFAULT_CONF" ]]; then fail "Catch-all config tidak ditemukan: $DEFAULT_CONF"; else
  if grep -Eq '^\s*<VirtualHost \*:80>\s*$' "$DEFAULT_CONF"; then pass '000-default.conf menggunakan <VirtualHost *:80>'; else fail '000-default.conf bukan VirtualHost *:80'; fi
  if grep -Eq '^\s*Server(Name|Alias)\b' "$DEFAULT_CONF"; then fail 'Catch-all masih memiliki ServerName/ServerAlias yang membatasi domain'; else pass 'Catch-all tidak memiliki ServerName/ServerAlias'; fi
  if grep -Eq 'AllowOverride[[:space:]]+All' "$DEFAULT_CONF"; then pass 'AllowOverride All aktif pada DocumentRoot'; else fail 'AllowOverride All tidak ditemukan'; fi
  if grep -Eq 'DocumentRoot[[:space:]]+' "$DEFAULT_CONF"; then pass 'DocumentRoot terdefinisi'; else fail 'DocumentRoot tidak ditemukan'; fi
fi

if [[ ! -r "$DB_PATH" ]]; then fail "Database tidak ditemukan: $DB_PATH"; else
  if [[ -z "${UNDANGAN_PASSWORD_KEY:-}" && -r "$APP_ROOT/.env" ]]; then
    UNDANGAN_PASSWORD_KEY="$(grep '^UNDANGAN_PASSWORD_KEY=' "$APP_ROOT/.env" | cut -d= -f2- || true)"
  fi
  if [[ -n "${UNDANGAN_PASSWORD_KEY:-}" ]]; then pass 'UNDANGAN_PASSWORD_KEY tersedia'; else fail 'UNDANGAN_PASSWORD_KEY belum dikonfigurasi'; fi
  if DB_PATH="$DB_PATH" HOST_NAME="$HOST_NAME" python3 - <<'PY'
import os, sqlite3, sys
path = os.environ['DB_PATH']
con = sqlite3.connect(path)
con.execute('PRAGMA foreign_keys = ON')
required = {'tenants', 'users', 'tenant_configs', 'guest_links', 'tamu'}
actual = {row[0] for row in con.execute("SELECT name FROM sqlite_master WHERE type='table'")}
missing = sorted(required - actual)
if missing:
    print('FAIL: tabel shared-schema hilang: ' + ', '.join(missing)); sys.exit(10)
print('PASS: tabel shared-schema lengkap')
for table in ('users', 'tenant_configs', 'guest_links', 'tamu'):
    cols = {row[1] for row in con.execute(f'PRAGMA table_info("{table}")')}
    if 'tenant_id' not in cols:
        print(f'FAIL: {table}.tenant_id tidak ada'); sys.exit(11)
    if table == 'users' and 'visible_password' not in cols:
        print('FAIL: users.visible_password tidak ada'); sys.exit(13)
    fk = {(row[3], row[2]) for row in con.execute(f'PRAGMA foreign_key_list("{table}")')}
    if ('tenant_id', 'tenants') not in fk:
        print(f'FAIL: {table}.tenant_id tidak memiliki FK ke tenants(id)'); sys.exit(12)
print('PASS: foreign key tenant_id terpasang pada seluruh tabel tenant')
if con.execute("SELECT COUNT(*) FROM tenants WHERE domain = ? AND status = 'active'", (os.environ.get('HOST_NAME', ''),)).fetchone()[0] == 0:
    print('WARN: host audit belum terdaftar sebagai tenant aktif')
else:
    print('PASS: host audit terdaftar aktif')
con.close()
PY
  then
    pass 'Validasi SQLite selesai'
  else
    fail 'Validasi SQLite gagal'
  fi
fi

if command -v curl >/dev/null 2>&1; then
  code="$(curl --max-time 10 -sS -o /tmp/undangan-host-response -w '%{http_code}' -H "Host: $HOST_NAME" "$BASE_URL/" || true)"
  case "$code" in
    200|301|302) pass "Host header diterima aplikasi (HTTP $code)";;
    404) fail 'Host header mencapai aplikasi tetapi domain tidak terdaftar/aktif (HTTP 404)';;
    *) fail "Aplikasi tidak merespons sebagaimana mestinya dengan Host header (HTTP $code)";;
  esac
  if grep -qiE 'Domain tidak terdaftar|ditangguhkan' /tmp/undangan-host-response 2>/dev/null; then
    fail 'Aplikasi menolak Host header; periksa tenants.domain dan Cloudflare Tunnel';
  else
    pass 'Respons tidak menunjukkan penolakan domain';
  fi
else
  warn 'curl tidak tersedia; verifikasi Host header dilewati'
fi

if [[ "$FAILURES" -gt 0 ]]; then
  echo "Audit selesai dengan $FAILURES kegagalan." >&2
  exit 1
fi
echo 'Audit deployment lulus.'
