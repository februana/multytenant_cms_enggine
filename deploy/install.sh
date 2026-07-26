#!/usr/bin/env bash
set -euo pipefail

# deploy/install.sh
# Canonical installer for Ubuntu 24.04 + Nginx/Apache + PHP-FPM
# Single-Root Architecture v2.0 - Always deploys to /var/www/wedding
# Supports both Nginx and Apache web servers

CANONICAL_TARGET="/var/www/wedding"
SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATE_DIR="$SCRIPT_DIR/templates"

if [ "$EUID" -ne 0 ]; then
  echo "This script must be run as root or via sudo." >&2
  exit 2
fi

echo "=== Wedding Invitation Deployment (v2.0) ==="
echo "Source (Repository): $SOURCE_DIR"
echo "Target (Runtime):    $CANONICAL_TARGET"
echo ""

# Web Server Selection Wizard
echo "================================="
echo "Select Web Server"
echo "================================="
echo ""
echo "1. Nginx (Recommended)"
echo "2. Apache"
echo ""

while true; do
    read -p "Enter your choice [1-2]: " WEB_SERVER_CHOICE
    case $WEB_SERVER_CHOICE in
        1)
            WEB_SERVER="nginx"
            echo "Selected: Nginx"
            break
            ;;
        2)
            WEB_SERVER="apache"
            echo "Selected: Apache"
            break
            ;;
        *)
            echo "Invalid choice. Please enter 1 or 2."
            ;;
    esac
done

echo ""
echo "Web Server: $WEB_SERVER"
echo ""

# Deploy from any source location to canonical target
if [ -d "$CANONICAL_TARGET" ] && [ "$SOURCE_DIR" = "$CANONICAL_TARGET" ]; then
  # Running from target directory - update in place
  echo "Running from canonical target. Updating in place..."
  WORKING_DIR="$CANONICAL_TARGET"
  CLEANUP_SOURCE=false

  # Copy public assets from app/ to runtime root
  # style.css and script.js must be in the document root for index.php to load them
  cp "$WORKING_DIR/app/style.css" "$WORKING_DIR/style.css"
  cp "$WORKING_DIR/app/script.js" "$WORKING_DIR/script.js"
  chown www-data:www-data "$WORKING_DIR/style.css" "$WORKING_DIR/script.js"
  chmod 644 "$WORKING_DIR/style.css" "$WORKING_DIR/script.js"
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
    "$SOURCE_DIR/" "$CANONICAL_TARGET/"
  
  WORKING_DIR="$CANONICAL_TARGET"
  CLEANUP_SOURCE=true

  # Copy public assets from app/ to runtime root
  # style.css and script.js must be in the document root for index.php to load them
  cp "$WORKING_DIR/app/style.css" "$WORKING_DIR/style.css"
  cp "$WORKING_DIR/app/script.js" "$WORKING_DIR/script.js"
  chown www-data:www-data "$WORKING_DIR/style.css" "$WORKING_DIR/script.js"
  chmod 644 "$WORKING_DIR/style.css" "$WORKING_DIR/script.js"
fi

# Install Composer dependencies
echo ""
echo "Installing Composer dependencies..."
if command -v composer &>/dev/null; then
  cd "$WORKING_DIR"
  if [ -f "composer.json" ]; then
    echo "Running composer install --no-dev --optimize-autoloader..."
    if ! composer install --no-dev --optimize-autoloader; then
      echo "ERROR: Composer install failed. Deployment aborted." >&2
      exit 1
    fi
    echo "Composer dependencies installed successfully."
  else
    echo "WARNING: composer.json not found in $WORKING_DIR. Skipping Composer install."
  fi
else
  echo "ERROR: Composer is not installed or not in PATH." >&2
  echo "Please install Composer first: https://getcomposer.org/download/" >&2
  echo "Deployment aborted to prevent broken application." >&2
  exit 1
fi

echo ""
echo "Installing system packages..."
apt update -qq

# Install packages based on selected web server
if [ "$WEB_SERVER" = "nginx" ]; then
    echo "Installing Nginx and PHP-FPM..."
    apt install -y -qq nginx php-fpm php-sqlite3 php-gd php-mbstring php-curl jq ca-certificates curl unzip
elif [ "$WEB_SERVER" = "apache" ]; then
    echo "Installing Apache and PHP-FPM..."
    apt install -y -qq apache2 php-fpm php-sqlite3 php-gd php-mbstring php-curl jq ca-certificates curl unzip
    
    # Enable required Apache modules
    echo "Enabling Apache modules..."
    a2enmod rewrite headers ssl proxy_fcgi setenvif dav dav_fs auth_basic alias
    # Note: proxy_fcgi is a built-in module in modern Apache, no extra package needed
    
    # Enable SSL by default
    a2enmod socache_shmcb
fi

# Start PHP-FPM service to ensure socket is available
echo "Starting PHP-FPM service..."
systemctl enable php-fpm >/dev/null 2>&1 || systemctl enable php8.3-fpm >/dev/null 2>&1 || systemctl enable php8.2-fpm >/dev/null 2>&1 || true
systemctl start php-fpm >/dev/null 2>&1 || systemctl start php8.3-fpm >/dev/null 2>&1 || systemctl start php8.2-fpm >/dev/null 2>&1 || true
sleep 2

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
if [ "$WEB_SERVER" = "nginx" ]; then
    echo "Deploying Nginx configuration from template..."
    NGINX_CONF="/etc/nginx/sites-available/wedding"

    # Copy template and replace placeholders
    cp "$TEMPLATE_DIR/nginx/wedding.conf" "$NGINX_CONF"
    
    # Replace placeholders with actual values
    sed -i "s|{{DOMAIN}}|_|g" "$NGINX_CONF"
    sed -i "s|{{DOCUMENT_ROOT}}|$WORKING_DIR|g" "$NGINX_CONF"
    sed -i "s|{{PHP_SOCKET}}|$PHP_FPM_SOCK|g" "$NGINX_CONF"
    sed -i "s|{{LOG_PATH}}|/var/log/nginx|g" "$NGINX_CONF"
    sed -i "s|{{UPLOAD_LIMIT}}|20M|g" "$NGINX_CONF"
    sed -i "s|{{LETSENCRYPT_PATH}}|/etc/letsencrypt/live/_|g" "$NGINX_CONF"

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

elif [ "$WEB_SERVER" = "apache" ]; then
    echo "Deploying Apache configuration from template..."
    APACHE_CONF="/etc/apache2/sites-available/wedding.conf"

    # Get domain name or use default
    DOMAIN="_"
    
    # Copy template and replace placeholders
    cp "$TEMPLATE_DIR/apache/wedding.conf" "$APACHE_CONF"
    
    # Replace placeholders with actual values
    sed -i "s|{{DOMAIN}}|$DOMAIN|g" "$APACHE_CONF"
    sed -i "s|{{DOCUMENT_ROOT}}|$WORKING_DIR|g" "$APACHE_CONF"
    sed -i "s|{{PHP_SOCKET}}|$PHP_FPM_SOCK|g" "$APACHE_CONF"
    sed -i "s|{{LOG_PATH}}|\${APACHE_LOG_DIR}|g" "$APACHE_CONF"
    sed -i "s|{{LETSENCRYPT_PATH}}|/etc/letsencrypt/live/$DOMAIN|g" "$APACHE_CONF"

    echo "Testing Apache configuration..."
    if ! apache2ctl configtest; then
      echo "ERROR: Apache configuration test failed" >&2
      exit 1
    fi

    # Enable site
    a2ensite wedding.conf
    
    # Disable default site if enabled
    a2dissite 000-default.conf 2>/dev/null || true

    # Enable and start Apache
    echo "Enabling Apache service..."
    systemctl enable apache2 >/dev/null 2>&1 || true

    echo "Starting Apache..."
    if ! systemctl is-active --quiet apache2; then
      systemctl start apache2
    else
      echo "Apache is already running. Reloading configuration..."
      systemctl reload apache2
    fi
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

# Check 3: No reference to "change-this-password" remains in sensitive config files
# Only check application config files where credentials would actually be stored
INSECURE_FOUND=false
for config_file in "$WORKING_DIR/.env" "$WORKING_DIR/config.php" "$WORKING_DIR/app/config.php"; do
  if [ -f "$config_file" ] && grep -q "change-this-password" "$config_file" 2>/dev/null; then
    INSECURE_FOUND=true
    break
  fi
done
if [ "$INSECURE_FOUND" = true ]; then
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
  echo ""
  echo "======================================"
  echo "Administrator account created"
  echo ""
  echo "Username:"
  echo "$ADMIN_USERNAME"
  echo ""
  echo "Password:"
  echo "$ADMIN_PASSWORD"
  echo ""
  echo "Credentials have been saved to:"
  echo ""
  echo "$ENV_FILE"
  echo ""
  echo "Save these credentials now."
  echo "======================================"
fi

echo ""
echo "Run 'sudo $WORKING_DIR/deploy/health-check.sh' to verify deployment."
exit 0
