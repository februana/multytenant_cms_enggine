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
UNDANGAN_AUTO_PROVISION=1
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

# Initialize the shared schema and migrate the legacy single-tenant config into the
# main tenant. The command is deliberately idempotent.
(
  cd "$CANONICAL_TARGET"
  php -r 'require "config.php"; if (!init_database()) { fwrite(STDERR, "Database initialization failed\n"); exit(1); } $config = load_config(); if (!save_config($config)) { fwrite(STDERR, "Tenant config seed failed\n"); exit(1); }'
)

# Explicit SQL execution command required by the deployment contract. Values are
# still bound parameters; no domain or password is interpolated into SQL.
cat > "$CANONICAL_TARGET/deploy/.seed-multitenant.php" <<'PHP'
<?php
require __DIR__ . '/../config.php';
$db = tenant_database(false);
$tenantStmt = $db->prepare("INSERT OR IGNORE INTO tenants (domain, status) VALUES (:domain, 'active')");
$tenantStmt->bindValue(':domain', getenv('MAIN_DOMAIN'), SQLITE3_TEXT);
$tenantStmt->execute();
$userStmt = $db->prepare("INSERT OR IGNORE INTO users (tenant_id, username, password_hash, visible_password, role) VALUES (NULL, :username, :password_hash, :visible_password, 'super_admin')");
$userStmt->bindValue(':username', getenv('ADMIN_USERNAME'), SQLITE3_TEXT);
$userStmt->bindValue(':password_hash', password_hash((string)getenv('ADMIN_PASSWORD'), PASSWORD_DEFAULT), SQLITE3_TEXT);
$userStmt->bindValue(':visible_password', encrypt_visible_password((string)getenv('ADMIN_PASSWORD')), SQLITE3_TEXT);
if (!$userStmt->execute()) exit(1);
$db->close();
PHP
MAIN_DOMAIN="$MAIN_DOMAIN" ADMIN_USERNAME="$ADMIN_USERNAME" ADMIN_PASSWORD="$ADMIN_PASSWORD" php "$CANONICAL_TARGET/deploy/.seed-multitenant.php"
rm -f "$CANONICAL_TARGET/deploy/.seed-multitenant.php"

systemctl restart apache2

cat <<EOF

Instalasi multi-tenant selesai.

Catch-all Apache : $APACHE_SITE
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
