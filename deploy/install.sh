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
# server name may be provided as first arg or via env SERVER_NAME
SERVER_NAME_ARG="${1:-}"
SERVER_NAME="${SERVER_NAME:-$SERVER_NAME_ARG}"
# optional email for certbot (for automated cert issuance)
CERTBOT_EMAIL="${CERTBOT_EMAIL:-}"
NGINX_SITE="/etc/nginx/sites-available/${SERVER_NAME:-februandik.duckdns.org}.conf"

echo "Installing system packages (nginx, php-fpm, required extensions)..."
apt update
apt install -y nginx php-fpm php-sqlite3 php-gd php-mbstring php-zip jq ca-certificates curl unzip

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

echo "Copying repository files to $REPO_DIR (assumes you ran git clone here)..."
if [ -d "$(pwd)" ]; then
  # Copy full repo into REPO_DIR but exclude runtime storage and version control metadata
  rsync -a --delete \
    --exclude '.git' \
    --exclude 'storage' \
    --exclude '.archive' \
    --exclude 'vendor' \
    --exclude 'deploy' \
    ./ "$REPO_DIR/"
else
  echo "Working directory not found. Run this script from repository root." >&2
  exit 1
fi

echo "Preparing runtime directories inside repo root (storage + public uploads)..."
mkdir -p "$REPO_DIR/storage/data"
mkdir -p "$REPO_DIR/uploads/cover"
mkdir -p "$REPO_DIR/uploads/music"
mkdir -p "$REPO_DIR/uploads/gallery"
mkdir -p "$REPO_DIR/uploads/background"
chown -R www-data:www-data "$REPO_DIR/storage"
chown -R www-data:www-data "$REPO_DIR/uploads"
chmod -R 750 "$REPO_DIR/storage"
chmod -R 750 "$REPO_DIR/uploads"

# Ensure private DB directory exists outside webroot
PRIV_DB_DIR="/var/www/private"
mkdir -p "$PRIV_DB_DIR"
chown www-data:www-data "$PRIV_DB_DIR"
chmod 750 "$PRIV_DB_DIR"

echo "Creating symlink from repo to webroot..."
if [ -L "$WWW_DIR" ]; then
  echo "Updating symlink $WWW_DIR -> $REPO_DIR"
  ln -sfn "$REPO_DIR" "$WWW_DIR"
else
  rm -rf "$WWW_DIR"
  ln -s "$REPO_DIR" "$WWW_DIR"
fi

echo "Installing Nginx site configuration (template)..."
if [ -z "$SERVER_NAME" ]; then
  echo "WARNING: SERVER_NAME not provided; defaulting to februandik.duckdns.org. Provide server name as first arg or SERVER_NAME env var for production." >&2
  SERVER_NAME=februandik.duckdns.org
fi
cat > "$NGINX_SITE" <<NGINX_EOF
server {
    listen 80;
    server_name ${SERVER_NAME};

    root ${WWW_DIR};
    index index.php index.html;

    access_log /var/log/nginx/${SERVER_NAME}.access.log;
    error_log /var/log/nginx/${SERVER_NAME}.error.log;

    client_max_body_size 20M;

    location / {
        try_files \$uri \$uri/ /app/index.php?\$query_string;
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
        try_files \$uri =404;
        access_log off;
        expires 7d;
    }
}
NGINX_EOF

ln -sf "$NGINX_SITE" /etc/nginx/sites-enabled/${SERVER_NAME}.conf

echo "Testing Nginx configuration..."
nginx -t

echo "Reloading services..."
systemctl restart php*-fpm 2>/dev/null || systemctl restart php-fpm 2>/dev/null || true
systemctl reload nginx

echo "Checking .env presence (repo root .env)..."
if [ ! -f "$REPO_DIR/.env" ]; then
  echo "Creating .env from .env.example (defaults) at $REPO_DIR/.env"
  if [ -f "$REPO_DIR/.env.example" ]; then
    cp "$REPO_DIR/.env.example" "$REPO_DIR/.env"
    chown root:root "$REPO_DIR/.env"
    chmod 640 "$REPO_DIR/.env"
  else
    echo "No .env.example found; create $REPO_DIR/.env manually if you need to override defaults." >&2
  fi
else
  echo ".env found at $REPO_DIR/.env";
fi

# Run composer install if composer.json exists
if [ -f "$REPO_DIR/composer.json" ]; then
  if ! command -v composer >/dev/null 2>&1; then
    echo "Composer not found; installing composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  fi
  echo "Running composer install (no-dev, optimized)..."
  (cd "$REPO_DIR" && composer install --no-dev --optimize-autoloader --no-interaction) || echo "composer install failed; check network and package availability." >&2
fi

# Optionally obtain TLS cert via certbot if email provided
if [ -n "$CERTBOT_EMAIL" ] && [ -n "$SERVER_NAME" ]; then
  echo "Installing certbot and attempting to obtain certificate for $SERVER_NAME (non-interactive)..."
  apt install -y certbot python3-certbot-nginx
  if certbot --nginx --agree-tos --non-interactive --redirect -m "$CERTBOT_EMAIL" -d "$SERVER_NAME"; then
    echo "Certificate obtained for $SERVER_NAME"
  else
    echo "Certbot run failed or requires manual DNS/email verification. Please run: certbot --nginx -d $SERVER_NAME" >&2
  fi
fi

echo "Install complete. Next steps: ensure UNDANGAN_DB_PATH points to /var/www/private/database.sqlite or $REPO_DIR/storage/data/database.sqlite. Run deploy/health-check.sh";

exit 0
