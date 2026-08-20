#!/usr/bin/env bash
set -Eeuo pipefail

# Pure multi-tenant installer for Apache + PHP on a single low-memory host.
# One Apache catch-all vhost serves every Cloudflare Tunnel hostname.

CANONICAL_TARGET="${CANONICAL_TARGET:-/var/www/wedding}"
SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APACHE_SITE="/etc/apache2/sites-available/000-default.conf"
RUNTIME_DIRECTORIES_SCRIPT="$SOURCE_DIR/deploy/runtime-directories.sh"
if [[ ! -r "$RUNTIME_DIRECTORIES_SCRIPT" ]]; then
  echo "ERROR: runtime directory contract not found: $RUNTIME_DIRECTORIES_SCRIPT" >&2
  exit 2
fi
. "$RUNTIME_DIRECTORIES_SCRIPT"

if [[ "$EUID" -ne 0 ]]; then
  echo "Jalankan sebagai root: sudo bash deploy/install.sh" >&2
  exit 2
fi

CONFIG_BACKUP_DIR="/var/backups/wedding-installer/$(date -u +%Y%m%dT%H%M%SZ)"
backup_existing_server_configs() {
  mkdir -p "$CONFIG_BACKUP_DIR"
  local source target
  for source in "$APACHE_SITE" /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default; do
    if [[ -e "$source" || -L "$source" ]]; then
      target="$CONFIG_BACKUP_DIR/$(echo "$source" | sed 's#^/##; s#/#__#g')"
      cp -a "$source" "$target"
      echo "Backup konfigurasi dibuat: $target"
    fi
  done
}
backup_existing_server_configs

normalize_domain() {
  local value="$1"
  value="${value#http://}"
  value="${value#https://}"
  value="${value%%/*}"
  value="${value,,}"
  value="${value%.}"
  printf '%s' "$value"
}

validate_domain() {
  [[ "$1" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$ ]]
}

read_main_domain() {
  local value
  while true; do
    read -r -p "Main Domain untuk Super Admin, contoh example.com: " value
    value="$(normalize_domain "$value")"
    if validate_domain "$value"; then
      MAIN_DOMAIN="$value"
      return
    fi
    echo "Domain tidak valid. Gunakan hostname FQDN tanpa path atau port." >&2
  done
}

read_admin_username() {
  ADMIN_USERNAME="${ADMIN_USER:-admin}"
  read -r -p "Username Super Admin [$ADMIN_USERNAME]: " input_user
  [[ -n "$input_user" ]] && ADMIN_USERNAME="$input_user"
}

generate_install_credentials() {
  ADMIN_PASSWORD="$(openssl rand -hex 16)"
  PASSWORD_KEY="$(openssl rand -hex 32)"
}

read_main_domain
read_admin_username

export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq apache2 php-cli libapache2-mod-php php-sqlite3 php-gd php-mbstring php-zip imagemagick rsync openssl curl ca-certificates
generate_install_credentials

mkdir -p "$CANONICAL_TARGET"
# Keep runtime data out of source control and do not overwrite existing tenant data.
rsync -a --delete \
  --exclude='.git/' --exclude='.env' --exclude='database.sqlite' --exclude='config.json' \
  --exclude='guest-links.json' --exclude='custom.css' --exclude='event.ics' --exclude='uploads/' \
  "$SOURCE_DIR/" "$CANONICAL_TARGET/"

ensure_runtime_directories "$CANONICAL_TARGET"
mkdir -p "$CANONICAL_TARGET/data"
cat > "$CANONICAL_TARGET/.env" <<EOF
ADMIN_USER=$ADMIN_USERNAME
ADMIN_PASS=$ADMIN_PASSWORD
UNDANGAN_MAIN_DOMAIN=$MAIN_DOMAIN
UNDANGAN_DB_PATH=$CANONICAL_TARGET/database.sqlite
UNDANGAN_PASSWORD_KEY=$PASSWORD_KEY
UNDANGAN_AUTO_PROVISION=0
SESSION_TIMEOUT=3600
EOF
chmod 600 "$CANONICAL_TARGET/.env"
touch "$CANONICAL_TARGET/database.sqlite"

# Apache is intentionally a single catch-all site: no ServerName/ServerAlias.
cat > "$APACHE_SITE" <<EOF
<VirtualHost *:80>
    DocumentRoot $CANONICAL_TARGET

    <Directory $CANONICAL_TARGET>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch "\\.php$">
        SetHandler application/x-httpd-php
    </FilesMatch>

    ErrorLog \\${APACHE_LOG_DIR}/wedding-error.log
    CustomLog \\${APACHE_LOG_DIR}/wedding-access.log combined
</VirtualHost>
EOF

# Disable all competing sites so Cloudflare reaches the same catch-all instance.
if [[ -d /etc/apache2/sites-enabled ]]; then
  while IFS= read -r enabled; do
    [[ -z "$enabled" ]] && continue
    a2dissite "$enabled" >/dev/null 2>&1 || true
  done < <(find /etc/apache2/sites-enabled -maxdepth 1 -type l -printf '%f\n')
fi

a2enmod rewrite headers >/dev/null
a2enmod php"$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')" >/dev/null 2>&1 || true
a2ensite 000-default.conf >/dev/null

# The application uses PHP's built-in session mechanism; prefork avoids a PHP-FPM pool.
a2dismod mpm_event >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true
apache2ctl configtest
systemctl enable apache2 >/dev/null
systemctl restart apache2

chown -R www-data:www-data "$CANONICAL_TARGET"
find "$CANONICAL_TARGET" -type d -exec chmod 755 {} \;
find "$CANONICAL_TARGET" -type f -name '*.php' -exec chmod 644 {} \;
chmod 600 "$CANONICAL_TARGET/.env" "$CANONICAL_TARGET/database.sqlite"

# Schema creation and legacy data migration run once through the standalone deploy script.
(
  cd "$CANONICAL_TARGET"
  php deploy/migrate.php
)

systemctl restart apache2

cat <<EOF

Instalasi multi-tenant selesai.

Catch-all Apache : $APACHE_SITE
Config backup    : $CONFIG_BACKUP_DIR
Document root    : $CANONICAL_TARGET
Main domain      : $MAIN_DOMAIN
Database         : $CANONICAL_TARGET/database.sqlite
Super Admin      : $ADMIN_USERNAME
Super Admin password (simpan sekarang): $ADMIN_PASSWORD

Tambahkan setiap domain tenant baru ke tabel tenants dengan status active.
Pastikan setiap hostname diarahkan melalui Cloudflare Tunnel yang sama.
Jalankan audit pascadeploy:
  sudo $CANONICAL_TARGET/deploy/audit.sh --host $MAIN_DOMAIN
EOF
