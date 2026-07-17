#!/usr/bin/env bash
set -euo pipefail

# deploy/install.sh
# Idempotent installer for Ubuntu + Nginx + PHP-FPM deployment
# Updated for Single-Root Architecture (v2.0)

if [ "$EUID" -ne 0 ]; then
  echo "This script must be run as root or via sudo." >&2
  exit 2
fi

REPO_DIR="/opt/februandik-web"
WWW_DIR="/var/www/februandik-web"
SERVER_NAME_ARG="${1:-}"
SERVER_NAME="${SERVER_NAME:-$SERVER_NAME_ARG}"
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

echo "Copying repository files to $REPO_DIR..."
if [ -d "$(pwd)" ]; then
  rsync -a --delete \
    --exclude '.git' \
    --exclude 'backups' \
    --exclude 'vendor' \
    ./ "$REPO_DIR/"
else
  echo "Working directory not found. Run this script from repository root." >&2
  exit 1
fi

echo "Preparing runtime directories (uploads)..."
mkdir -p "$REPO_DIR/uploads/cover"
mkdir -p "$REPO_DIR/uploads/music"
mkdir -p "$REPO_DIR/uploads/gallery"
mkdir -p "$REPO_DIR/uploads/background"
chown -R www-data:www-data "$REPO_DIR/uploads"
chmod -R 755 "$REPO_DIR/uploads"

echo "Securing sensitive files..."
chmod 600 "$REPO_DIR/config.json" 2>/dev/null || true
chmod 600 "$REPO_DIR/guest-links.json" 2>/dev/null || true
chown www-data:www-data "$REPO_DIR/config.json" 2>/dev/null || true
chown www-data:www-data "$REPO_DIR/guest-links.json" 2>/dev/null || true

echo "Initializing backup directory..."
mkdir -p "$REPO_DIR/backups"
chown www-data:www-data "$REPO_DIR/backups"
chmod 750 "$REPO_DIR/backups"

echo "Creating placeholder event.ics..."
touch "$REPO_DIR/event.ics"
chown www-data:www-data "$REPO_DIR/event.ics"

echo "Setting up Nginx site configuration..."
if [ -z "$SERVER_NAME" ]; then
  echo "WARNING: SERVER_NAME not provided; defaulting to februandik.duckdns.org." >&2
  SERVER_NAME=februandik.duckdns.org
fi

cat > "$NGINX_SITE" <<NGINX_EOF
server {
    listen 80;
    server_name ${SERVER_NAME};

    root ${WWW_DIR};
    index index.php admin.php save.php messages.php gallery.php;

    access_log /var/log/nginx/${SERVER_NAME}.access.log;
    error_log /var/log/nginx/${SERVER_NAME}.error.log;

    client_max_body_size 20M;

    # Disable directory listing
    autoindex off;

    # Block access to sensitive directories and files
    location ~ ^/(app|storage|deploy|backups) {
        deny all;
        return 403;
    }

    location ~ \.(json|sqlite|md|sh)$ {
        deny all;
        return 403;
    }

    location ~ /\. {
        deny all;
        return 403;
    }

    # Disable PHP execution in uploads
    location /uploads {
        location ~ \.php$ {
            deny all;
            return 403;
        }
    }

    # Main routing: serve static files, fallback to index.php
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP-FPM handler
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$PHP_FPM_SOCK;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|webp|css|js|svg|ico|woff|woff2|ttf|eot)$ {
        expires 7d;
        access_log off;
    }
}
NGINX_EOF

ln -sf "$NGINX_SITE" /etc/nginx/sites-enabled/${SERVER_NAME}.conf

echo "Testing Nginx configuration..."
nginx -t

echo "Reloading services..."
systemctl restart php*-fpm 2>/dev/null || systemctl restart php-fpm 2>/dev/null || true
systemctl reload nginx

echo "Install complete. Run deploy/health-check.sh to verify."
exit 0
