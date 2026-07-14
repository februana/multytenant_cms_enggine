#!/usr/bin/env bash
set -euo pipefail

# deploy/install.sh
# Idempotent installer for Ubuntu + Nginx + PHP-FPM deployment

if [ "$EUID" -ne 0 ]; then
  echo "This script must be run as root or via sudo." >&2
  exit 2
fi

REPO_DIR="/opt/februandik-web"
WWW_DIR="/var/www/februandik-web"
NGINX_SITE="/etc/nginx/sites-available/februandik.duckdns.org.conf"

echo "Installing system packages (nginx, php-fpm, required extensions)..."
apt update
apt install -y nginx php-fpm php-sqlite3 php-gd php-xml php-mbstring php-curl php-zip jq ca-certificates

PHP_FPM_SOCK=$(find /run/php -name 'php*-fpm.sock' | head -n 1 || true)
if [ -z "$PHP_FPM_SOCK" ]; then
  echo "Could not find a PHP-FPM socket under /run/php. Please verify php-fpm is installed." >&2
  exit 1
fi

echo "Using PHP-FPM socket: $PHP_FPM_SOCK"

echo "Creating repository and web directories..."
mkdir -p "$REPO_DIR"
mkdir -p "$WWW_DIR"
chown -R root:root "$REPO_DIR"

echo "Copying application files to $REPO_DIR (assumes you ran git clone here)..."
if [ -d "$(pwd)/app" ]; then
  rsync -a --delete --exclude '.git' --exclude 'storage' ./app/ "$REPO_DIR/"
else
  echo "No app/ directory in current folder. Please run this script from repository root where app/ exists." >&2
  exit 1
fi

echo "Preparing runtime directories inside repo root (storage + public uploads)..."
mkdir -p "$REPO_DIR/storage/data"
mkdir -p "$REPO_DIR/uploads"
chown -R www-data:www-data "$REPO_DIR/storage"
chown -R www-data:www-data "$REPO_DIR/uploads"
chmod -R 750 "$REPO_DIR/storage"
chmod -R 750 "$REPO_DIR/uploads"

echo "Creating symlink from repo to webroot..."
if [ -L "$WWW_DIR" ]; then
  echo "Updating symlink $WWW_DIR -> $REPO_DIR"
  ln -sfn "$REPO_DIR" "$WWW_DIR"
else
  rm -rf "$WWW_DIR"
  ln -s "$REPO_DIR" "$WWW_DIR"
fi

echo "Installing Nginx site configuration (template)..."
cat > "$NGINX_SITE" <<NGINX_EOF
server {
    listen 80;
    server_name februandik.duckdns.org;

    root /var/www/februandik-web;
    index index.php index.html;

    access_log /var/log/nginx/februandik.access.log;
    error_log /var/log/nginx/februandik.error.log;

    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$PHP_FPM_SOCK;
    }

    location ^~ /storage/ {
        deny all;
        return 403;
    }

    location ^~ /deploy/ {
        deny all;
        return 403;
    }

    location ~ /\.(?:env|git|ht|gitignore)$ {
        deny all;
        return 403;
    }

    location ~* \.(?:jpg|jpeg|png|gif|webp|css|js|svg|ico)$ {
        try_files $uri =404;
        access_log off;
        expires 7d;
    }
}
NGINX_EOF

ln -sf "$NGINX_SITE" /etc/nginx/sites-enabled/februandik.duckdns.org.conf

echo "Testing Nginx configuration..."
nginx -t

echo "Reloading services..."
systemctl restart php*-fpm 2>/dev/null || systemctl restart php-fpm 2>/dev/null || true
systemctl reload nginx

echo "Checking .env presence..."
if [ ! -f "$REPO_DIR/../.env" ]; then
  echo "WARNING: .env not found at $REPO_DIR/../.env. Please create it (cp .env.example .env) and populate required variables before serving." >&2
else
  echo ".env found.";
fi

echo "Install complete. Next steps: edit .env at $REPO_DIR/../.env, ensure UNDANGAN_DB_PATH points to /var/www/private/database.sqlite or $REPO_DIR/storage/data/database.sqlite, then run health check: deploy/health-check.sh";

exit 0
