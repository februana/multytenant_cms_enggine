#!/usr/bin/env bash
set -Eeuo pipefail

# Non-destructive installer for the application only.
# It never enables/disables sites/modules, restarts services, installs OS packages,
# or overwrites live Apache/Nginx configuration.

CANONICAL_TARGET="${CANONICAL_TARGET:-/var/www/wedding}"
SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUNTIME_DIRECTORIES_SCRIPT="$SOURCE_DIR/deploy/runtime-directories.sh"
NEW_INSTALL_CREDENTIALS=0

if [[ ! -r "$RUNTIME_DIRECTORIES_SCRIPT" ]]; then
  echo "ERROR: runtime directory contract not found: $RUNTIME_DIRECTORIES_SCRIPT" >&2
  exit 2
fi
. "$RUNTIME_DIRECTORIES_SCRIPT"

if [[ "$EUID" -ne 0 ]]; then
  echo "Jalankan sebagai root agar installer dapat menyiapkan $CANONICAL_TARGET: sudo bash deploy/install.sh" >&2
  exit 2
fi

check_dependencies() {
  local missing=()
  local command_name
  for command_name in php openssl; do
    command -v "$command_name" >/dev/null 2>&1 || missing+=("$command_name")
  done
  if [[ ${#missing[@]} -gt 0 ]]; then
    printf 'ERROR: dependency wajib belum tersedia: %s\n' "${missing[*]}" >&2
    printf '%s\n' 'Installer tidak mengubah package manager. Install dependency tersebut melalui prosedur server Anda, lalu jalankan ulang.' >&2
    exit 2
  fi
  php -m 2>/dev/null | grep -Fxq 'SQLite3' || { echo 'ERROR: PHP extension SQLite3 tidak tersedia.' >&2; exit 2; }
  php -m 2>/dev/null | grep -Fxq 'openssl' || { echo 'ERROR: PHP extension openssl tidak tersedia.' >&2; exit 2; }
  if ! command -v rsync >/dev/null 2>&1; then
    echo 'WARNING: rsync tidak tersedia; installer menggunakan cp tanpa menghapus file existing.' >&2
  fi
}

normalize_domain() {
  local value="$1"
  value="${value#http://}"
  value="${value#https://}"
  value="${value%%/*}"
  value="${value%%:*}"
  value="${value,,}"
  value="${value%.}"
  printf '%s' "$value"
}

validate_domain() {
  [[ "$1" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$ ]]
}

read_main_domain() {
  local value
  value="${UNDANGAN_MAIN_DOMAIN:-${MAIN_DOMAIN:-}}"
  while ! validate_domain "$(normalize_domain "$value")"; do
    read -r -p "Main Domain untuk Super Admin, contoh example.com: " value
    value="$(normalize_domain "$value")"
    if validate_domain "$value"; then
      break
    fi
    echo 'Domain tidak valid. Gunakan hostname FQDN tanpa path atau port.' >&2
  done
  MAIN_DOMAIN="$(normalize_domain "$value")"
}

read_admin_username() {
  ADMIN_USERNAME="${ADMIN_USER:-admin}"
  read -r -p "Username Super Admin [$ADMIN_USERNAME]: " input_user
  [[ -n "$input_user" ]] && ADMIN_USERNAME="$input_user"
  [[ "$ADMIN_USERNAME" =~ ^[A-Za-z0-9_.-]{3,64}$ ]] || { echo 'Username Super Admin tidak valid.' >&2; exit 2; }
}

generate_install_credentials() {
  ADMIN_PASSWORD="$(openssl rand -hex 16)"
  PASSWORD_KEY="$(openssl rand -hex 32)"
}

write_initial_env() {
  local env_file="$CANONICAL_TARGET/.env"
  if [[ -e "$env_file" ]]; then
    echo "Mempertahankan .env existing: $env_file"
    chmod 600 "$env_file" 2>/dev/null || true
    return
  fi
  read_main_domain
  read_admin_username
  generate_install_credentials
  cat > "$env_file" <<EOF
ADMIN_USER=$ADMIN_USERNAME
ADMIN_PASS=$ADMIN_PASSWORD
UNDANGAN_MAIN_DOMAIN=$MAIN_DOMAIN
UNDANGAN_DB_PATH=$CANONICAL_TARGET/database.sqlite
UNDANGAN_PASSWORD_KEY=$PASSWORD_KEY
UNDANGAN_AUTO_PROVISION=1
SESSION_TIMEOUT=3600
EOF
  chmod 600 "$env_file"
  NEW_INSTALL_CREDENTIALS=1
}

copy_application() {
  mkdir -p "$CANONICAL_TARGET"
  # No --delete: existing application files and operator data are never removed.
  if command -v rsync >/dev/null 2>&1; then
    rsync -a \
      --exclude='.git/' --exclude='.env' --exclude='database.sqlite' \
      --exclude='config.json' --exclude='guest-links.json' --exclude='custom.css' \
      --exclude='event.ics' --exclude='uploads/' --exclude='backups/' --exclude='webdav/' \
      "$SOURCE_DIR/" "$CANONICAL_TARGET/"
  else
    tar -C "$SOURCE_DIR" \
      --exclude='.git' --exclude='.env' --exclude='database.sqlite' \
      --exclude='config.json' --exclude='guest-links.json' --exclude='custom.css' \
      --exclude='event.ics' --exclude='uploads' --exclude='backups' --exclude='webdav' \
      -cf - . | tar -C "$CANONICAL_TARGET" -xf -
  fi
}

check_dependencies
copy_application
write_initial_env
ensure_runtime_directories "$CANONICAL_TARGET"
touch "$CANONICAL_TARGET/database.sqlite"

# Migrations are the only database bootstrap operation and run once during setup.
(
  cd "$CANONICAL_TARGET"
  php deploy/migrate.php
)

if id -u www-data >/dev/null 2>&1 && getent group www-data >/dev/null 2>&1; then
  chown -R www-data:www-data "$CANONICAL_TARGET"
fi
find "$CANONICAL_TARGET" -type d -exec chmod 755 {} \;
find "$CANONICAL_TARGET" -type f -name '*.php' -exec chmod 644 {} \;
chmod 600 "$CANONICAL_TARGET/.env" "$CANONICAL_TARGET/database.sqlite"

cat <<EOF

Instalasi aplikasi multi-tenant selesai tanpa mengubah konfigurasi web server.

Document root       : $CANONICAL_TARGET
Database            : $CANONICAL_TARGET/database.sqlite
Sample Apache vhost : $CANONICAL_TARGET/deploy/apache-catchall.conf.example

Terapkan sample VirtualHost secara manual setelah meninjau isinya, kemudian reload
web server melalui prosedur operasional Anda. Installer tidak menjalankan a2dissite,
a2ensite, a2enmod, systemctl, apt-get, atau menulis /etc/apache2 maupun /etc/nginx.
EOF

if [[ "$NEW_INSTALL_CREDENTIALS" -eq 1 ]]; then
  printf 'Super Admin username : %s\n' "$ADMIN_USERNAME"
  printf 'Super Admin password : %s\n' "$ADMIN_PASSWORD"
  printf 'UNDANGAN_PASSWORD_KEY: %s\n' "$PASSWORD_KEY"
  echo 'Simpan credential dan key tersebut sebelum terminal ditutup.'
else
  echo 'Credential existing dipertahankan; tidak ada password baru yang dicetak.'
fi
