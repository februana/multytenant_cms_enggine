#!/usr/bin/env bash
set -Eeuo pipefail

# Native Apache + PHP-FPM installer for the multi-tenant evolution of the CMS.
# The deployment flow is adapted from webserver_undangan/deploy/install.sh;
# application and tenant data remain owned by the target architecture.

CANONICAL_TARGET="${CANONICAL_TARGET:-/var/www/wedding}"
SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUNTIME_DIRECTORIES_SCRIPT="$SOURCE_DIR/deploy/runtime-directories.sh"
APACHE_SITE_NAME="${APACHE_SITE_NAME:-wedding.conf}"
APACHE_ENABLE_SSL="${APACHE_ENABLE_SSL:-0}"
APACHE_WEBDAV_ENABLE="${APACHE_WEBDAV_ENABLE:-0}"
APACHE_LETSENCRYPT_PATH="${APACHE_LETSENCRYPT_PATH:-}"
APACHE_HTTP_CONF="/etc/apache2/sites-available/$APACHE_SITE_NAME"
APACHE_SSL_CONF="/etc/apache2/sites-available/wedding-ssl.conf"
NEW_INSTALL_CREDENTIALS=0
ADMIN_PASSWORD=""
PASSWORD_KEY=""
MAIN_DOMAIN=""
PHP_FPM_SOCKET=""
APACHE_HTTP_BACKUP=""
APACHE_SSL_BACKUP=""
APACHE_HTTP_WAS_ENABLED=0
APACHE_SSL_WAS_ENABLED=0
APACHE_DEFAULT_WAS_ENABLED=0

if [[ ! -r "$RUNTIME_DIRECTORIES_SCRIPT" ]]; then
  echo "ERROR: runtime directory contract not found: $RUNTIME_DIRECTORIES_SCRIPT" >&2
  exit 2
fi
. "$RUNTIME_DIRECTORIES_SCRIPT"

if [[ "$EUID" -ne 0 ]]; then
  echo "Jalankan sebagai root: sudo bash deploy/install.sh" >&2
  exit 2
fi

if [[ ! "$APACHE_SITE_NAME" =~ ^[A-Za-z0-9._-]+\.conf$ ]]; then
  echo "ERROR: APACHE_SITE_NAME tidak valid." >&2
  exit 2
fi

check_base_commands() {
  local command_name
  for command_name in apt-get systemctl; do
    command -v "$command_name" >/dev/null 2>&1 || { echo "ERROR: command host wajib tidak tersedia: $command_name" >&2; exit 2; }
  done
}

verify_php_runtime() {
  local command_name
  for command_name in php openssl; do
    command -v "$command_name" >/dev/null 2>&1 || { echo "ERROR: command runtime tidak tersedia: $command_name" >&2; exit 2; }
  done
  php -r 'exit(extension_loaded("sqlite3") && extension_loaded("pdo_sqlite") && extension_loaded("openssl") ? 0 : 1);' || {
    echo 'ERROR: PHP sqlite3, pdo_sqlite, dan openssl wajib tersedia.' >&2
    exit 2
  }
}

install_os_dependencies() {
  if [[ "${SKIP_APACHE_PACKAGE_INSTALL:-0}" == "1" ]]; then
    echo 'SKIP_APACHE_PACKAGE_INSTALL=1: instalasi package OS dilewati.'
    return 0
  fi
  echo 'Installing Apache, PHP-FPM, Composer, ImageMagick, and PHP extensions...'
  apt-get update -qq
  DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
    apache2 apache2-utils php-fpm php-cli php-sqlite3 php-gd php-mbstring php-zip \
    composer imagemagick rsync openssl ca-certificates curl unzip
}

check_apache_commands() {
  local command_name
  for command_name in apache2ctl a2enmod a2ensite a2dissite htpasswd; do
    command -v "$command_name" >/dev/null 2>&1 || { echo "ERROR: Apache command tidak tersedia: $command_name" >&2; exit 2; }
  done
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
  local value="${UNDANGAN_MAIN_DOMAIN:-${MAIN_DOMAIN:-}}"
  while ! validate_domain "$(normalize_domain "$value")"; do
    if [[ ! -t 0 ]]; then
      echo 'ERROR: UNDANGAN_MAIN_DOMAIN harus berupa FQDN valid pada mode non-interaktif.' >&2
      exit 2
    fi
    read -r -p 'Main Domain untuk tenant pertama: ' value
    value="$(normalize_domain "$value")"
    if ! validate_domain "$value"; then echo 'Domain tidak valid.' >&2; fi
  done
  MAIN_DOMAIN="$(normalize_domain "$value")"
}

read_admin_username() {
  ADMIN_USERNAME="${ADMIN_USER:-admin}"
  if [[ -t 0 ]]; then
    read -r -p "Username Super Admin [$ADMIN_USERNAME]: " input_user
    [[ -n "$input_user" ]] && ADMIN_USERNAME="$input_user"
  fi
  [[ "$ADMIN_USERNAME" =~ ^[A-Za-z0-9_.-]{3,64}$ ]] || { echo 'Username Super Admin tidak valid.' >&2; exit 2; }
}

write_initial_env() {
  local env_file="$CANONICAL_TARGET/.env"
  if [[ -e "$env_file" ]]; then
    chmod 600 "$env_file" 2>/dev/null || true
    return 0
  fi
  read_main_domain
  read_admin_username
  ADMIN_PASSWORD="$(openssl rand -hex 16)"
  PASSWORD_KEY="$(openssl rand -hex 32)"
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

resolve_main_domain_from_env() {
  local env_file="$CANONICAL_TARGET/.env"
  if [[ -r "$env_file" ]]; then
    MAIN_DOMAIN="$(sed -n 's/^UNDANGAN_MAIN_DOMAIN=//p' "$env_file" | head -n 1 | tr -d '\r' | sed 's/^"//; s/"$//')"
  fi
  MAIN_DOMAIN="$(normalize_domain "${MAIN_DOMAIN:-${UNDANGAN_MAIN_DOMAIN:-}}")"
  validate_domain "$MAIN_DOMAIN" || read_main_domain
}

copy_application() {
  mkdir -p "$CANONICAL_TARGET"
  if command -v rsync >/dev/null 2>&1; then
    rsync -a \
      --exclude='.git/' --exclude='.env' --exclude='database.sqlite' --exclude='vendor/' \
      --exclude='config.json' --exclude='guest-links.json' --exclude='custom.css' --exclude='event.ics' \
      --exclude='uploads/' --exclude='backups/' --exclude='webdav/' \
      "$SOURCE_DIR/" "$CANONICAL_TARGET/"
  else
    tar -C "$SOURCE_DIR" \
      --exclude='.git' --exclude='.env' --exclude='database.sqlite' --exclude='vendor' \
      --exclude='config.json' --exclude='guest-links.json' --exclude='custom.css' --exclude='event.ics' \
      --exclude='uploads' --exclude='backups' --exclude='webdav' -cf - . | tar -C "$CANONICAL_TARGET" -xf -
  fi
}

install_composer_dependencies() {
  [[ -f "$CANONICAL_TARGET/composer.json" ]] || return 0
  command -v composer >/dev/null 2>&1 || { echo 'ERROR: composer tidak tersedia.' >&2; exit 2; }
  (cd "$CANONICAL_TARGET" && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader)
}

start_php_fpm_and_detect_socket() {
  local unit
  local started=0
  local units=(php-fpm.service php8.3-fpm.service php8.2-fpm.service php8.1-fpm.service)
  while IFS= read -r unit; do units+=("$unit"); done < <(systemctl list-unit-files 'php*-fpm.service' --no-legend --no-pager 2>/dev/null | awk '{print $1}' | sort -V)
  for unit in "${units[@]}"; do
    if systemctl enable --now "$unit" >/dev/null 2>&1; then started=1; break; fi
  done
  [[ "$started" -eq 1 ]] || { echo 'ERROR: tidak dapat mengaktifkan service PHP-FPM.' >&2; exit 2; }
  sleep 1
  PHP_FPM_SOCKET="$(find /run/php -maxdepth 1 -type s -name '*.sock' -print 2>/dev/null | sort -V | head -n 1 || true)"
  [[ -n "$PHP_FPM_SOCKET" ]] || { echo 'ERROR: socket PHP-FPM tidak ditemukan di /run/php.' >&2; exit 2; }
  echo "Using PHP-FPM socket: $PHP_FPM_SOCKET"
}

escape_sed_replacement() {
  printf '%s' "$1" | sed 's/[&|]/\\&/g'
}

backup_apache_file() {
  local source_file="$1"
  local backup_var="$2"
  local backup_file
  backup_file="$(mktemp /tmp/multytenant-apache-backup.XXXXXX)"
  if [[ -f "$source_file" ]]; then cp -p "$source_file" "$backup_file"; else : > "$backup_file"; fi
  if [[ -e "/etc/apache2/sites-enabled/$(basename "$source_file")" ]]; then
    [[ "$backup_var" == APACHE_HTTP_BACKUP ]] && APACHE_HTTP_WAS_ENABLED=1
    [[ "$backup_var" == APACHE_SSL_BACKUP ]] && APACHE_SSL_WAS_ENABLED=1
  fi
  printf -v "$backup_var" '%s' "$backup_file"
}

restore_apache_file() {
  local target_file="$1"
  local backup_file="$2"
  if [[ -s "$backup_file" ]]; then cp -p "$backup_file" "$target_file"; else rm -f "$target_file"; fi
  rm -f "$backup_file"
}

strip_webdav_config_if_disabled() {
  local config_file="$1"
  [[ "$APACHE_WEBDAV_ENABLE" == "1" ]] && return 0
  local staged
  staged="$(mktemp /etc/apache2/sites-available/.multytenant-no-webdav.XXXXXX)"
  sed '/^[[:space:]]*# WebDAV configuration/,/^[[:space:]]*# PHP-FPM handler via ProxyFCG/d' "$config_file" > "$staged"
  mv -f "$staged" "$config_file"
}

render_http_config() {
  local template="$CANONICAL_TARGET/deploy/templates/apache/apache-http.conf.template"
  local staged
  [[ -r "$template" ]] || { echo "ERROR: template Apache tidak ditemukan: $template" >&2; exit 2; }
  staged="$(mktemp /etc/apache2/sites-available/.multytenant-http.XXXXXX)"
  sed -e "s|{{DOMAIN}}|$(escape_sed_replacement "$MAIN_DOMAIN")|g" \
      -e "s|{{DOCUMENT_ROOT}}|$(escape_sed_replacement "$CANONICAL_TARGET")|g" \
      -e "s|{{PHP_SOCKET}}|$(escape_sed_replacement "$PHP_FPM_SOCKET")|g" \
      -e 's|{{LOG_PATH}}|${APACHE_LOG_DIR}|g' "$template" > "$staged"
  grep -q '{{' "$staged" && { rm -f "$staged"; echo 'ERROR: placeholder Apache tersisa.' >&2; exit 2; }
  backup_apache_file "$APACHE_HTTP_CONF" APACHE_HTTP_BACKUP
  mv -f "$staged" "$APACHE_HTTP_CONF"
  strip_webdav_config_if_disabled "$APACHE_HTTP_CONF"
}

render_ssl_config() {
  [[ "$APACHE_ENABLE_SSL" == "1" ]] || return 0
  local cert_dir="${APACHE_LETSENCRYPT_PATH:-/etc/letsencrypt/live/$MAIN_DOMAIN}"
  local template="$CANONICAL_TARGET/deploy/templates/apache/apache-ssl.conf.template"
  local staged
  [[ -r "$cert_dir/fullchain.pem" && -r "$cert_dir/privkey.pem" ]] || { echo "ERROR: certificate SSL tidak lengkap: $cert_dir" >&2; exit 2; }
  staged="$(mktemp /etc/apache2/sites-available/.multytenant-ssl.XXXXXX)"
  sed -e "s|{{DOMAIN}}|$(escape_sed_replacement "$MAIN_DOMAIN")|g" \
      -e "s|{{DOCUMENT_ROOT}}|$(escape_sed_replacement "$CANONICAL_TARGET")|g" \
      -e "s|{{PHP_SOCKET}}|$(escape_sed_replacement "$PHP_FPM_SOCKET")|g" \
      -e "s|{{LETSENCRYPT_PATH}}|$(escape_sed_replacement "$cert_dir")|g" \
      -e 's|{{LOG_PATH}}|${APACHE_LOG_DIR}|g' "$template" > "$staged"
  grep -q '{{' "$staged" && { rm -f "$staged"; echo 'ERROR: placeholder SSL tersisa.' >&2; exit 2; }
  backup_apache_file "$APACHE_SSL_CONF" APACHE_SSL_BACKUP
  mv -f "$staged" "$APACHE_SSL_CONF"
  strip_webdav_config_if_disabled "$APACHE_SSL_CONF"
}

rollback_apache() {
  a2dissite "$APACHE_SITE_NAME" >/dev/null 2>&1 || true
  [[ "$APACHE_ENABLE_SSL" == "1" ]] && a2dissite wedding-ssl.conf >/dev/null 2>&1 || true
  restore_apache_file "$APACHE_HTTP_CONF" "$APACHE_HTTP_BACKUP"
  [[ "$APACHE_ENABLE_SSL" == "1" ]] && restore_apache_file "$APACHE_SSL_CONF" "$APACHE_SSL_BACKUP"
  [[ "$APACHE_HTTP_WAS_ENABLED" -eq 1 ]] && a2ensite "$APACHE_SITE_NAME" >/dev/null 2>&1 || true
  [[ "$APACHE_ENABLE_SSL" == "1" && "$APACHE_SSL_WAS_ENABLED" -eq 1 ]] && a2ensite wedding-ssl.conf >/dev/null 2>&1 || true
  [[ "$APACHE_DEFAULT_WAS_ENABLED" -eq 1 ]] && a2ensite 000-default.conf >/dev/null 2>&1 || true
  apache2ctl configtest >/dev/null 2>&1 || true
}

configure_webdav_credentials() {
  if [[ "$APACHE_WEBDAV_ENABLE" != "1" ]]; then
    WEBDAV_PASSWORD_DISPLAY='disabled (set APACHE_WEBDAV_ENABLE=1 to provision)'
    return 0
  fi
  local username="${WEBDAV_USERNAME:-admin}"
  local password="${WEBDAV_PASSWORD:-}"
  if [[ -z "$password" ]]; then password="$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 24)"; fi
  htpasswd -bc /etc/apache2/.davpasswd "$username" "$password" >/dev/null
  chown root:www-data /etc/apache2/.davpasswd
  chmod 640 /etc/apache2/.davpasswd
  WEBDAV_PASSWORD_DISPLAY="$password"
}

validate_and_activate_apache() {
  APACHE_DEFAULT_WAS_ENABLED=0
  [[ -e /etc/apache2/sites-enabled/000-default.conf ]] && APACHE_DEFAULT_WAS_ENABLED=1
  a2ensite "$APACHE_SITE_NAME" >/dev/null
  if [[ "$APACHE_ENABLE_SSL" == "1" ]]; then a2ensite wedding-ssl.conf >/dev/null; fi
  if ! apache2ctl configtest; then
    echo 'ERROR: Apache configtest gagal; Apache tidak di-reload.' >&2
    rollback_apache
    exit 1
  fi
  a2dissite 000-default.conf >/dev/null 2>&1 || true
  if ! apache2ctl configtest; then
    echo 'ERROR: Apache configtest gagal setelah default site handling; Apache tidak di-reload.' >&2
    rollback_apache
    exit 1
  fi
  systemctl enable apache2 >/dev/null
  if systemctl is-active --quiet apache2; then systemctl reload apache2; else systemctl start apache2; fi
  [[ -n "$APACHE_HTTP_BACKUP" ]] && rm -f "$APACHE_HTTP_BACKUP"
  [[ -n "$APACHE_SSL_BACKUP" ]] && rm -f "$APACHE_SSL_BACKUP"
  APACHE_HTTP_BACKUP=""
  APACHE_SSL_BACKUP=""
}

check_base_commands
install_os_dependencies
verify_php_runtime
check_apache_commands
copy_application
install_composer_dependencies
write_initial_env
resolve_main_domain_from_env
ensure_runtime_directories "$CANONICAL_TARGET"
touch "$CANONICAL_TARGET/database.sqlite"
(cd "$CANONICAL_TARGET" && php deploy/migrate.php)

if id -u www-data >/dev/null 2>&1 && getent group www-data >/dev/null 2>&1; then chown -R www-data:www-data "$CANONICAL_TARGET"; fi
find "$CANONICAL_TARGET" -type d -exec chmod 755 {} \;
find "$CANONICAL_TARGET" -type f -name '*.php' -exec chmod 644 {} \;
chmod 600 "$CANONICAL_TARGET/.env" "$CANONICAL_TARGET/database.sqlite"

a2enmod rewrite headers expires proxy_fcgi setenvif >/dev/null
if [[ "$APACHE_ENABLE_SSL" == "1" ]]; then a2enmod ssl socache_shmcb >/dev/null; fi
if [[ "$APACHE_WEBDAV_ENABLE" == "1" ]]; then a2enmod dav dav_fs auth_basic alias >/dev/null; fi
start_php_fpm_and_detect_socket
configure_webdav_credentials
render_http_config
render_ssl_config
validate_and_activate_apache

cat <<EOF
Native multi-tenant Apache + PHP-FPM installation completed.
Document root  : $CANONICAL_TARGET
Apache site    : $APACHE_HTTP_CONF
Main domain    : $MAIN_DOMAIN
PHP-FPM socket : $PHP_FPM_SOCKET
SSL            : $([[ "$APACHE_ENABLE_SSL" == "1" ]] && echo enabled || echo disabled;)
WebDAV mode    : $APACHE_WEBDAV_ENABLE
WebDAV user    : ${WEBDAV_USERNAME:-disabled}
WebDAV password: $WEBDAV_PASSWORD_DISPLAY

Tenant routing remains application-owned through HTTP_HOST, tenant resolution,
and the existing .htaccess/media.php boundary. Re-running this installer does not
use rsync --delete and preserves .env, database, uploads, backups, and webdav data.
EOF

if [[ "$NEW_INSTALL_CREDENTIALS" -eq 1 ]]; then
  printf 'Super Admin username : %s\n' "$ADMIN_USERNAME"
  printf 'Super Admin password : %s\n' "$ADMIN_PASSWORD"
  printf 'UNDANGAN_PASSWORD_KEY: %s\n' "$PASSWORD_KEY"
  echo 'Simpan credential dan key tersebut sebelum terminal ditutup.'
else
  echo 'Credential existing dipertahankan; tidak ada password baru yang dicetak.'
fi
