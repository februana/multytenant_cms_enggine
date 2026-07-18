#!/usr/bin/env bash
set -euo pipefail

# deploy/install.sh
# Canonical installer for Ubuntu 24.04 + Nginx + PHP-FPM
# Single-Root Architecture v2.0 - Always deploys to /var/www/wedding

CANONICAL_TARGET="/var/www/wedding"
SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [ "$EUID" -ne 0 ]; then
  echo "This script must be run as root or via sudo." >&2
  exit 2
fi

echo "=== Wedding Invitation Deployment (v2.0) ==="
echo "Source (Repository): $SOURCE_DIR"
echo "Target (Runtime):    $CANONICAL_TARGET"
echo ""

# Deploy from any source location to canonical target
if [ -d "$CANONICAL_TARGET" ] && [ "$SOURCE_DIR" = "$CANONICAL_TARGET" ]; then
  # Running from target directory - update in place
  echo "Running from canonical target. Updating in place..."
  WORKING_DIR="$CANONICAL_TARGET"
  CLEANUP_SOURCE=false
elif [ -d "$CANONICAL_TARGET" ]; then
  # Target exists but running from different source
  echo "ERROR: $CANONICAL_TARGET already exists." >&2
  echo "To update an existing installation, run this script from within $CANONICAL_TARGET" >&2
  echo "Or remove $CANONICAL_TARGET first for a fresh install." >&2
  exit 1
else
  # Fresh install - migrate from any source location to canonical target
  echo "Deploying from $SOURCE_DIR to $CANONICAL_TARGET..."
  
  mkdir -p "$CANONICAL_TARGET"
  rsync -av --delete \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='.github' \
    --exclude='.vscode' \
    --exclude='*.md' \
    --exclude='composer.json' \
    --exclude='composer.lock' \
    "$SOURCE_DIR/" "$CANONICAL_TARGET/"
  
  WORKING_DIR="$CANONICAL_TARGET"
  CLEANUP_SOURCE=true
fi

echo ""
echo "Installing system packages..."
apt update -qq
apt install -y -qq nginx php-fpm php-sqlite3 php-gd php-mbstring php-curl jq ca-certificates curl unzip

# Find PHP-FPM socket automatically (supports Ubuntu PHP versions)
PHP_FPM_SOCK=$(find /run/php -name '*.sock' 2>/dev/null | head -n 1)
if [ -z "$PHP_FPM_SOCK" ]; then
  # Fallback: try to detect common socket paths
  if [ -S "/run/php/php-fpm.sock" ]; then
    PHP_FPM_SOCK="/run/php/php-fpm.sock"
  elif [ -S "/run/php/php8.3-fpm.sock" ]; then
    PHP_FPM_SOCK="/run/php/php8.3-fpm.sock"
  elif [ -S "/run/php/php8.2-fpm.sock" ]; then
    PHP_FPM_SOCK="/run/php/php8.2-fpm.sock"
  elif [ -S "/run/php/php8.1-fpm.sock" ]; then
    PHP_FPM_SOCK="/run/php/php8.1-fpm.sock"
  else
    PHP_FPM_SOCK="/run/php/php-fpm.sock"
  fi
fi
echo "Using PHP-FPM socket: $PHP_FPM_SOCK"

echo ""
echo "Creating runtime directories..."
mkdir -p "$WORKING_DIR/uploads/cover"
mkdir -p "$WORKING_DIR/uploads/music"
mkdir -p "$WORKING_DIR/uploads/gallery"
mkdir -p "$WORKING_DIR/uploads/background"
mkdir -p "$WORKING_DIR/backups"

echo "Setting permissions..."
chown -R www-data:www-data "$WORKING_DIR"
find "$WORKING_DIR" -type d -exec chmod 755 {} \;
find "$WORKING_DIR" -type f -name "*.php" -exec chmod 644 {} \;
find "$WORKING_DIR" -type f -name "*.json" -exec chmod 600 {} \;
find "$WORKING_DIR" -type f -name "*.sqlite" -exec chmod 600 {} \;

# Initialize database if missing
if [ ! -f "$WORKING_DIR/database.sqlite" ]; then
  touch "$WORKING_DIR/database.sqlite"
  chown www-data:www-data "$WORKING_DIR/database.sqlite"
  chmod 600 "$WORKING_DIR/database.sqlite"
fi

# Initialize config if missing
if [ ! -f "$WORKING_DIR/config.json" ]; then
  echo '{"site":{"title":"Wedding"},"media":{},"gallery":[]}' > "$WORKING_DIR/config.json"
  chown www-data:www-data "$WORKING_DIR/config.json"
  chmod 600 "$WORKING_DIR/config.json"
fi

# Initialize guest-links if missing
if [ ! -f "$WORKING_DIR/guest-links.json" ]; then
  echo '[]' > "$WORKING_DIR/guest-links.json"
  chown www-data:www-data "$WORKING_DIR/guest-links.json"
  chmod 600 "$WORKING_DIR/guest-links.json"
fi

# Initialize event.ics
touch "$WORKING_DIR/event.ics"
chown www-data:www-data "$WORKING_DIR/event.ics"

# Handle .env file setup
ENV_FILE="$WORKING_DIR/.env"
ENV_EXAMPLE="$WORKING_DIR/.env.example"

CREDENTIALS_GENERATED=false
ADMIN_USERNAME="admin"
ADMIN_PASSWORD=""

if [ ! -f "$ENV_FILE" ]; then
  echo ""
  echo "Setting up .env file..."
  
  # Copy .env.example to .env
  cp "$ENV_EXAMPLE" "$ENV_FILE"
  chown www-data:www-data "$ENV_FILE"
  chmod 600 "$ENV_FILE"
  
  # Generate cryptographically secure random password
  GENERATED_PASS=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 24)
  
  # Write generated password into ADMIN_PASS
  sed -i "s/^ADMIN_PASS=.*/ADMIN_PASS=${GENERATED_PASS}/" "$ENV_FILE"
  
  # Store credentials for display at the end
  ADMIN_PASSWORD="$GENERATED_PASS"
  CREDENTIALS_GENERATED=true

  echo "Generated secure administrator password."
fi

echo ""
echo "Deploying Nginx configuration..."
NGINX_CONF="/etc/nginx/sites-available/wedding"

# Generate Nginx config with detected socket
cat > "$NGINX_CONF" <<NGINX_EOF
server {
    listen 80;
    server_name _;

    root $WORKING_DIR;
    index index.php admin.php save.php messages.php gallery.php;

    access_log /var/log/nginx/wedding.access.log;
    error_log /var/log/nginx/wedding.error.log;

    client_max_body_size 20M;
    autoindex off;

    # Block sensitive paths
    location ~ ^/(app|deploy|backups|\\.git) {
        deny all;
        return 403;
    }

    # Block sensitive file types
    location ~ \\.(json|sqlite)$ {
        deny all;
        return 403;
    }

    # Block hidden files
    location ~ /\\. {
        deny all;
        return 403;
    }

    # Disable PHP in uploads
    location /uploads {
        location ~ \\.php$ {
            deny all;
            return 403;
        }
    }

    # Route static files, fallback to index.php
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP-FPM handler
    location ~ \\.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$PHP_FPM_SOCK;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Cache static assets
    location ~* \\.(jpg|jpeg|png|gif|webp|css|js|svg|ico|woff|woff2|ttf|eot)$ {
        expires 7d;
        access_log off;
    }
}
NGINX_EOF

# Enable site
ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/wedding
rm -f /etc/nginx/sites-enabled/default

echo "Testing Nginx configuration..."
if ! nginx -t; then
  echo "ERROR: Nginx configuration test failed" >&2
  exit 1
fi

# Enable and start Nginx (do not fail if already running)
echo "Enabling Nginx service..."
systemctl enable nginx >/dev/null 2>&1 || true

echo "Starting Nginx..."
if ! systemctl is-active --quiet nginx; then
  systemctl start nginx
else
  echo "Nginx is already running. Reloading configuration..."
  systemctl reload nginx
fi

# Cleanup temporary source if needed
if [ "$CLEANUP_SOURCE" = true ]; then
  echo ""
  echo "Cleaning up temporary source directory..."
  rm -rf "$SOURCE_DIR"
fi

# Verification step
echo ""
echo "=== Verifying Installation ==="

VERIFICATION_FAILED=false

# Check 1: .env exists
if [ ! -f "$ENV_FILE" ]; then
  echo "ERROR: .env file does not exist" >&2
  VERIFICATION_FAILED=true
else
  echo "✓ .env file exists"
fi

# Check 2: ADMIN_PASS is not empty
ADMIN_PASS_VALUE=$(grep "^ADMIN_PASS=" "$ENV_FILE" | cut -d'=' -f2)
if [ -z "$ADMIN_PASS_VALUE" ]; then
  echo "ERROR: ADMIN_PASS is empty in .env file" >&2
  VERIFICATION_FAILED=true
else
  echo "✓ ADMIN_PASS is set"
fi

# Check 3: No reference to "change-this-password" remains
if grep -r "change-this-password" "$WORKING_DIR" >/dev/null 2>&1; then
  echo "ERROR: Found insecure fallback password 'change-this-password' in files" >&2
  VERIFICATION_FAILED=true
else
  echo "✓ No insecure fallback password found"
fi

if [ "$VERIFICATION_FAILED" = true ]; then
  echo ""
  echo "INSTALLATION FAILED: Verification checks did not pass" >&2
  exit 1
fi

echo ""
echo "=== Installation Complete ==="
echo "Document Root: $WORKING_DIR"
echo "Site URL: http://$(hostname -I 2>/dev/null | awk '{print $1}' || echo 'localhost')"
echo ""

# Display generated credentials if this was a fresh install
if [ "$CREDENTIALS_GENERATED" = true ]; then
  echo "====================================="
  echo "Administrator account created"
  echo ""
  echo "Username: $ADMIN_USERNAME"
  echo "Password: $ADMIN_PASSWORD"
  echo ""
  echo "Save these credentials now."
  echo "They will not be displayed again."
  echo "====================================="
fi

echo ""
echo "Run 'sudo $WORKING_DIR/deploy/health-check.sh' to verify deployment."
exit 0
