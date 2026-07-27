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

# Track configuration for rollback
declare -A BACKUP_STATE=(
    [apache_enabled_sites]=""
    [nginx_enabled_sites]=""
    [apache_status]="stopped"
    [nginx_status]="stopped"
)

if [ "$EUID" -ne 0 ]; then
  echo "This script must be run as root or via sudo." >&2
  exit 2
fi

echo "=== Wedding Invitation Deployment (v2.0) ==="
echo "Source (Repository): $SOURCE_DIR"
echo "Target (Runtime):    $CANONICAL_TARGET"
echo ""

# Rollback function
rollback() {
    local reason="${1:-Unknown error}"
    echo ""
    echo "=========================================="
    echo "ROLLBACK INITIATED"
    echo "Reason: $reason"
    echo "=========================================="
    # Remove newly created sites if they exist
    echo "Removing newly created site configurations..."
    a2dissite wedding.conf 2>/dev/null || true
    a2dissite wedding-ssl.conf 2>/dev/null || true
    rm -f /etc/nginx/sites-enabled/wedding 2>/dev/null || true
    
    # Restore Apache sites
    if [ -n "${BACKUP_STATE[apache_enabled_sites]}" ]; then
        echo "Restoring Apache enabled sites..."
        for site in ${BACKUP_STATE[apache_enabled_sites]}; do
            a2ensite "$site" 2>/dev/null || true
        done
    fi

    # Restore Nginx sites
    if [ -n "${BACKUP_STATE[nginx_enabled_sites]}" ]; then
        echo "Restoring Nginx enabled sites..."
        for site in ${BACKUP_STATE[nginx_enabled_sites]}; do
            ln -sf "/etc/nginx/sites-available/$site" "/etc/nginx/sites-enabled/$site" 2>/dev/null || true
        done
    fi

    # Restore services
    if [ "${BACKUP_STATE[apache_status]}" = "active" ]; then
        echo "Restarting Apache..."
        systemctl start apache2 2>/dev/null || true
    fi

    if [ "${BACKUP_STATE[nginx_status]}" = "active" ]; then
        echo "Restarting Nginx..."
        systemctl start nginx 2>/dev/null || true
    fi
    
    # Remove SSL config if it was created during failed installation
    if [ -f /etc/apache2/sites-available/wedding-ssl.conf ]; then
        echo "Removing partially created SSL configuration..."
        rm -f /etc/apache2/sites-available/wedding-ssl.conf
        rm -f /etc/apache2/sites-enabled/wedding-ssl.conf
    fi

    echo ""
    echo "Rollback complete. Please review the error above and try again."
}

# Validate domain format
validate_domain() {
    local domain="$1"
    if [[ "$domain" =~ ^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$ ]]; then
        return 0
    fi
    return 1
}

# Detect or prompt for domain name
detect_or_prompt_domain() {
    # Prompt user for domain
    echo "" >&2
    echo "==========================================" >&2
    echo "Domain Configuration" >&2
    echo "==========================================" >&2
    echo "Please enter your primary domain name (e.g., example.com or februandik.duckdns.org)" >&2
    echo "This will be used for:" >&2
    echo "  - Web server configuration (ServerName/server_name)" >&2
    echo "  - SSL certificate (Let's Encrypt)" >&2
    echo "  - Application URLs" >&2
    echo "" >&2
    
    while true; do
        read -p "Primary domain: " input_domain
        
        if [ -z "$input_domain" ]; then
            echo "Domain cannot be empty. Please enter a valid domain name." >&2
            continue
        fi
        
        # Remove http:// or https:// prefix if present
        input_domain=$(echo "$input_domain" | sed -E 's|^https?://||' | sed -E 's|/$||')
        
        if validate_domain "$input_domain"; then
            echo "$input_domain"
            return 0
        else
            echo "Invalid domain format. Please enter a valid domain (e.g., example.com)" >&2
        fi
    done
}

# Check if SSL certificate exists for the given domain
check_ssl_exists() {
    local domain="$1"
    local cert_path="/etc/letsencrypt/live/$domain/fullchain.pem"
    local key_path="/etc/letsencrypt/live/$domain/privkey.pem"
    
    if [ -f "$cert_path" ] && [ -f "$key_path" ]; then
        return 0
    fi
    return 1
}

# Stop and disable conflicting web server
stop_conflicting_server() {
    local target_server="$1"
    
    if [ "$target_server" = "apache" ]; then
        # Stop and disable Nginx
        if systemctl is-active --quiet nginx 2>/dev/null; then
            echo "Stopping Nginx (conflicting with Apache)..."
            systemctl stop nginx 2>/dev/null || true
            BACKUP_STATE[nginx_status]="active"
        fi
        if systemctl is-enabled --quiet nginx 2>/dev/null; then
            systemctl disable nginx 2>/dev/null || true
        fi
        # Save enabled Nginx sites
        if [ -d /etc/nginx/sites-enabled ]; then
            BACKUP_STATE[nginx_enabled_sites]=$(ls /etc/nginx/sites-enabled 2>/dev/null | tr '\n' ' ')
        fi
    elif [ "$target_server" = "nginx" ]; then
        # Stop and disable Apache
        if systemctl is-active --quiet apache2 2>/dev/null; then
            echo "Stopping Apache (conflicting with Nginx)..."
            systemctl stop apache2 2>/dev/null || true
            BACKUP_STATE[apache_status]="active"
        fi
        if systemctl is-enabled --quiet apache2 2>/dev/null; then
            systemctl disable apache2 2>/dev/null || true
        fi
        # Save enabled Apache sites
        if [ -d /etc/apache2/sites-enabled ]; then
            BACKUP_STATE[apache_enabled_sites]=$(ls /etc/apache2/sites-enabled 2>/dev/null | tr '\n' ' ')
        fi
    fi
    
    # Verify ports are free
    sleep 1
    if command -v ss &>/dev/null; then
        local port_80=$(ss -tulpn 2>/dev/null | grep ':80 ' || true)
        local port_443=$(ss -tulpn 2>/dev/null | grep ':443 ' || true)
        if [ -n "$port_80" ] || [ -n "$port_443" ]; then
            echo "WARNING: Ports may still be in use:"
            echo "$port_80$port_443"
            echo "Attempting to force release..."
            sleep 2
        fi
    fi
}

# Redirect all stdout to stderr for banners/prompts
exec 3>&1
exec 1>&2

echo ""
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

# Stop conflicting web server BEFORE installing packages
echo "Checking for conflicting web servers..."
stop_conflicting_server "$WEB_SERVER"

# Get domain name
DOMAIN=$(detect_or_prompt_domain)
if [ -z "$DOMAIN" ]; then
    rollback "Domain cannot be empty"
    exit 1
fi
echo "Domain: $DOMAIN" >&3

# Check for existing SSL certificate
HAS_SSL=false
if check_ssl_exists "$DOMAIN"; then
    HAS_SSL=true
    echo "SSL certificate found for $DOMAIN" >&3
else
    echo "No SSL certificate found for $DOMAIN. Will generate HTTP-only config." >&3
    echo "Run certbot separately to enable HTTPS later." >&3
fi

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
    echo "Enabling Apache modules..." >&2
    a2enmod rewrite headers ssl proxy_fcgi setenvif dav dav_fs auth_basic alias >&2 2>&1 || true
    # Note: proxy_fcgi is a built-in module in modern Apache, no extra package needed
    
    # Enable SSL by default
    a2enmod socache_shmcb >&2 2>&1 || true
    
    # Fix ports.conf to use standard ports (80 and 443)
    echo "Configuring Apache ports.conf..." >&2
    if [ -f /etc/apache2/ports.conf ]; then
        cp /etc/apache2/ports.conf /etc/apache2/ports.conf.backup.$(date +%Y%m%d%H%M%S)
        cat > /etc/apache2/ports.conf << 'EOF'
# Apache ports configuration - managed by deployment manager
Listen 80

<IfModule ssl_module>
    Listen 443
</IfModule>

<IfModule gnutls_module>
    Listen 443
</IfModule>
EOF
        echo "ports.conf updated with standard configuration" >&2
    fi
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

    # Validate DOMAIN before proceeding
    if [ -z "$DOMAIN" ] || [ "$DOMAIN" = "{{DOMAIN}}" ]; then
        log_error "Invalid domain for Nginx configuration. Aborting."
        exit 1
    fi

    # Copy template and replace placeholders
    cp "$TEMPLATE_DIR/nginx/wedding.conf" "$NGINX_CONF"
    
    # Replace placeholders with actual values
    sed -i "s|{{DOMAIN}}|$DOMAIN|g" "$NGINX_CONF"
    sed -i "s|{{DOCUMENT_ROOT}}|$WORKING_DIR|g" "$NGINX_CONF"
    sed -i "s|{{PHP_SOCKET}}|$PHP_FPM_SOCK|g" "$NGINX_CONF"
    sed -i "s|{{LOG_PATH}}|/var/log/nginx|g" "$NGINX_CONF"
    sed -i "s|{{UPLOAD_LIMIT}}|20M|g" "$NGINX_CONF"
    
    # Handle SSL path based on certificate existence
    if [ "$HAS_SSL" = true ]; then
        sed -i "s|{{LETSENCRYPT_PATH}}|/etc/letsencrypt/live/$DOMAIN|g" "$NGINX_CONF"
    else
        # For HTTP-only config, use placeholder that won't break config
        # The HTTPS block is commented out by default in template
        sed -i "s|{{LETSENCRYPT_PATH}}|/etc/letsencrypt/live/$DOMAIN|g" "$NGINX_CONF"
    fi

    # Validate no unresolved placeholders remain
    if grep -q '{{' "$NGINX_CONF"; then
        log_error "Unresolved placeholders found in generated Nginx config"
        exit 1
    fi

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
    APACHE_HTTP_CONF="/etc/apache2/sites-available/wedding.conf"
    APACHE_SSL_CONF="/etc/apache2/sites-available/wedding-ssl.conf"

    # Validate DOMAIN before proceeding
    if [ -z "$DOMAIN" ] || [ "$DOMAIN" = "{{DOMAIN}}" ]; then
        rollback "Invalid domain for Apache configuration"
        exit 1
    fi

    # ===== STEP 1: Generate and enable HTTP VirtualHost first =====
    echo "Generating HTTP VirtualHost configuration..."
    cp "$TEMPLATE_DIR/apache/apache-http.conf.template" "$APACHE_HTTP_CONF"
    
    # Replace placeholders with actual values
    sed -i "s|{{DOMAIN}}|$DOMAIN|g" "$APACHE_HTTP_CONF"
    sed -i "s|{{DOCUMENT_ROOT}}|$WORKING_DIR|g" "$APACHE_HTTP_CONF"
    sed -i "s|{{PHP_SOCKET}}|$PHP_FPM_SOCK|g" "$APACHE_HTTP_CONF"
    sed -i "s|{{LOG_PATH}}|\${APACHE_LOG_DIR}|g" "$APACHE_HTTP_CONF"

    # Validate no unresolved placeholders remain
    if grep -q '{{' "$APACHE_HTTP_CONF"; then
        rollback "Unresolved placeholders found in generated Apache HTTP config"
        exit 1
    fi

    echo "Testing Apache HTTP configuration..."
    if ! apache2ctl configtest; then
        rollback "Apache HTTP configuration test failed"
        exit 1
    fi

    # Enable HTTP site
    a2ensite wedding.conf
    a2dissite 000-default.conf 2>/dev/null || true

    # Start Apache with HTTP only
    echo "Starting Apache (HTTP only)..."
    systemctl enable apache2 >/dev/null 2>&1 || true
    if ! systemctl is-active --quiet apache2; then
      systemctl start apache2
    else
      systemctl reload apache2
    fi

    # ===== STEP 2: Ask for Let's Encrypt email and run Certbot to obtain SSL certificate =====
    echo ""
    echo "=========================================="
    echo "SSL Certificate Setup"
    echo "=========================================="
    echo "Domain: $DOMAIN"
    echo ""
    
    # Ask for Let's Encrypt email
    echo "Enter your email address for Let's Encrypt certificate notifications:"
    echo "(This is required for certificate renewal reminders)"
    while true; do
        read -p "Email: " LETSENCRYPT_EMAIL
        if [ -z "$LETSENCRYPT_EMAIL" ]; then
            echo "Email cannot be empty. Please enter a valid email address."
            continue
        fi
        # Basic email validation
        if [[ ! "$LETSENCRYPT_EMAIL" =~ ^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$ ]]; then
            echo "Invalid email format. Please enter a valid email address."
            continue
        fi
        break
    done
    
    echo ""
    echo "Do you want to obtain an SSL certificate from Let's Encrypt?"
    echo "1. Yes, obtain SSL certificate now (recommended)"
    echo "2. No, configure SSL later manually"
    echo ""
    
    while true; do
        read -p "Enter your choice [1-2]: " SSL_CHOICE
        case $SSL_CHOICE in
            1)
                echo "Obtaining SSL certificate..."
                
                # Verify DNS resolution before running certbot
                echo "Verifying DNS resolution for $DOMAIN..."
                if ! command -v dig &>/dev/null && ! command -v nslookup &>/dev/null; then
                    apt install -y -qq dnsutils >/dev/null 2>&1 || true
                fi
                
                # Try to verify domain resolves to this server
                if command -v dig &>/dev/null; then
                    RESOLVED_IP=$(dig +short "$DOMAIN" 2>/dev/null | head -1 || echo "")
                elif command -v nslookup &>/dev/null; then
                    RESOLVED_IP=$(nslookup "$DOMAIN" 2>/dev/null | grep -A1 "Name:" | tail -1 | awk '{print $2}' || echo "")
                else
                    RESOLVED_IP=""
                fi
                
                if [ -n "$RESOLVED_IP" ]; then
                    echo "DNS resolved: $DOMAIN -> $RESOLVED_IP"
                    # Get local IP
                    LOCAL_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || echo "")
                    if [ -n "$LOCAL_IP" ] && [ "$RESOLVED_IP" != "$LOCAL_IP" ]; then
                        echo "WARNING: Resolved IP ($RESOLVED_IP) does not match local IP ($LOCAL_IP)"
                        echo "Certbot may fail if DNS is not correctly configured."
                        read -p "Continue anyway? [y/N]: " CONTINUE
                        if [[ ! "$CONTINUE" =~ ^[Yy]$ ]]; then
                            echo "Skipping SSL certificate setup."
                            SSL_CHOICE="2"
                            break
                        fi
                    fi
                fi
                
                # Install certbot if not present
                if ! command -v certbot &>/dev/null; then
                    echo "Installing certbot..."
                    apt install -y -qq certbot
                fi
                
                # Run certbot to obtain certificate only (not modifying Apache config)
                # Using --standalone to avoid certbot creating any Apache configs
                # Deployment manager owns all Apache configuration
                echo "Running certbot..."
                
                # Stop Apache temporarily for standalone mode
                systemctl stop apache2 2>/dev/null || true
                
                if certbot certonly --standalone -d "$DOMAIN" --non-interactive --agree-tos --email "$LETSENCRYPT_EMAIL"; then
                    echo "SSL certificate obtained successfully!"
                    HAS_SSL=true
                    
                    # Restart Apache after certificate obtained
                    systemctl start apache2
                    
                    # ===== STEP 3: Generate SSL VirtualHost after Certbot =====
                    echo "Generating SSL VirtualHost configuration..."
                    cp "$TEMPLATE_DIR/apache/apache-ssl.conf.template" "$APACHE_SSL_CONF"
                    
                    # Replace placeholders
                    sed -i "s|{{DOMAIN}}|$DOMAIN|g" "$APACHE_SSL_CONF"
                    sed -i "s|{{DOCUMENT_ROOT}}|$WORKING_DIR|g" "$APACHE_SSL_CONF"
                    sed -i "s|{{PHP_SOCKET}}|$PHP_FPM_SOCK|g" "$APACHE_SSL_CONF"
                    sed -i "s|{{LETSENCRYPT_PATH}}|/etc/letsencrypt/live/$DOMAIN|g" "$APACHE_SSL_CONF"
                    sed -i "s|{{LOG_PATH}}|\${APACHE_LOG_DIR}|g" "$APACHE_SSL_CONF"
                    
                    # Validate no unresolved placeholders remain
                    if grep -q '{{' "$APACHE_SSL_CONF"; then
                        rollback "Unresolved placeholders found in generated Apache SSL config"
                        exit 1
                    fi
                    
                    # Enable SSL site
                    a2ensite wedding-ssl.conf
                    
                    # Test and reload
                    echo "Testing Apache SSL configuration..."
                    if ! apache2ctl configtest; then
                        rollback "Apache SSL configuration test failed"
                        exit 1
                    fi
                    
                    echo "Reloading Apache with SSL enabled..."
                    systemctl reload apache2
                    
                    echo ""
                    echo "✓ HTTPS is now enabled for $DOMAIN"
                else
                    echo "WARNING: Certbot failed or was cancelled."
                    echo "Continuing with HTTP-only configuration."
                    HAS_SSL=false
                    
                    # Ensure Apache is running after certbot failure
                    systemctl start apache2 2>/dev/null || true
                fi
                break
                ;;
            2)
                echo "Skipping SSL certificate setup. You can run certbot later."
                HAS_SSL=false
                break
                ;;
            *)
                echo "Invalid choice. Please enter 1 or 2."
                ;;
        esac
    done

    # Final configuration test
    echo ""
    echo "Final Apache configuration test..."
    if ! apache2ctl configtest; then
        rollback "Final Apache configuration test failed"
        exit 1
    fi
    
    # ===== WebDAV Configuration =====
    echo ""
    echo "=========================================="
    echo "WebDAV Configuration"
    echo "=========================================="
    
    WEBDAV_ENABLED=false
    read -p "Enable WebDAV? [Y/n]: " WEBDAV_CHOICE
    if [[ "$WEBDAV_CHOICE" =~ ^[Yy]$ ]] || [ -z "$WEBDAV_CHOICE" ]; then
        WEBDAV_ENABLED=true
        echo "Configuring WebDAV..."
        
        # Install apache2-utils if not present
        if ! command -v htpasswd &>/dev/null; then
            echo "Installing apache2-utils for htpasswd..."
            apt install -y -qq apache2-utils
        fi
        
        # Prompt for WebDAV username
        read -p "WebDAV username [admin]: " WEBDAV_USERNAME
        if [ -z "$WEBDAV_USERNAME" ]; then
            WEBDAV_USERNAME="admin"
        fi
        
        # Prompt for WebDAV password with confirmation
        while true; do
            read -s -p "WebDAV password: " WEBDAV_PASS1
            echo ""
            read -s -p "Confirm password: " WEBDAV_PASS2
            echo ""
            
            if [ -z "$WEBDAV_PASS1" ]; then
                echo "Password cannot be empty. Please enter a valid password."
                continue
            fi
            
            if [ "$WEBDAV_PASS1" != "$WEBDAV_PASS2" ]; then
                echo "Passwords do not match. Please try again."
                continue
            fi
            
            break
        done
        
        # Create .davpasswd file
        echo "Creating WebDAV password file..."
        htpasswd -cb /etc/apache2/.davpasswd "$WEBDAV_USERNAME" "$WEBDAV_PASS1"
        chown root:www-data /etc/apache2/.davpasswd
        chmod 640 /etc/apache2/.davpasswd
        echo "WebDAV password file created at /etc/apache2/.davpasswd"
        
        # Store WebDAV credentials for display
        WEBDAV_USER_DISPLAY="$WEBDAV_USERNAME"
    else
        echo "WebDAV disabled."
        WEBDAV_USER_DISPLAY=""
    fi
fi
# Cleanup temporary source if needed
if [ "$CLEANUP_SOURCE" = true ]; then
  echo ""
  echo "Cleaning up temporary source directory..."
  rm -rf "$SOURCE_DIR"
fi

# ===== FINAL VERIFICATION AND HEALTH CHECKS =====
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

# Check 4: Web server configuration test
echo ""
echo "=== Configuration Validation ==="
if [ "$WEB_SERVER" = "apache" ]; then
    echo "Running apache2ctl configtest..."
    if ! apache2ctl configtest; then
        echo "ERROR: Apache configuration test failed" >&2
        VERIFICATION_FAILED=true
    else
        echo "✓ Apache configuration test passed"
    fi
    
    echo "Restarting Apache..."
    if ! systemctl restart apache2; then
        echo "ERROR: Failed to restart Apache" >&2
        VERIFICATION_FAILED=true
    else
        echo "✓ Apache restarted successfully"
    fi
    
    echo "Checking Apache status..."
    if systemctl is-active --quiet apache2; then
        echo "✓ Apache is running"
    else
        echo "ERROR: Apache is not running" >&2
        VERIFICATION_FAILED=true
    fi
elif [ "$WEB_SERVER" = "nginx" ]; then
    echo "Running nginx -t..."
    if ! nginx -t; then
        echo "ERROR: Nginx configuration test failed" >&2
        VERIFICATION_FAILED=true
    else
        echo "✓ Nginx configuration test passed"
    fi
    
    echo "Restarting Nginx..."
    if ! systemctl restart nginx; then
        echo "ERROR: Failed to restart Nginx" >&2
        VERIFICATION_FAILED=true
    else
        echo "✓ Nginx restarted successfully"
    fi
    
    echo "Checking Nginx status..."
    if systemctl is-active --quiet nginx; then
        echo "✓ Nginx is running"
    else
        echo "ERROR: Nginx is not running" >&2
        VERIFICATION_FAILED=true
    fi
fi

# Check 5: Port ownership verification
echo ""
echo "=== Port Verification ==="
if command -v ss &>/dev/null; then
    echo "Checking port 80..."
    PORT_80_OWNER=$(ss -tulpn 2>/dev/null | grep ':80 ' | awk '{print $7}' | head -1 || echo "")
    if [ -n "$PORT_80_OWNER" ]; then
        echo "✓ Port 80 is bound: $PORT_80_OWNER"
    else
        echo "WARNING: Port 80 does not appear to be bound"
    fi
    
    echo "Checking port 443..."
    PORT_443_OWNER=$(ss -tulpn 2>/dev/null | grep ':443 ' | awk '{print $7}' | head -1 || echo "")
    if [ -n "$PORT_443_OWNER" ]; then
        echo "✓ Port 443 is bound: $PORT_443_OWNER"
    else
        echo "INFO: Port 443 is not bound (SSL may not be configured yet)"
    fi
fi

# Check 6: HTTP/HTTPS connectivity
echo ""
echo "=== Connectivity Tests ==="
if command -v curl &>/dev/null; then
    # Test HTTP
    echo "Testing HTTP connectivity..."
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "Host: $DOMAIN" "http://127.0.0.1/" 2>/dev/null || echo "000")
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "301" ] || [ "$HTTP_CODE" = "302" ]; then
        echo "✓ HTTP responds (HTTP $HTTP_CODE)"
    else
        echo "WARNING: HTTP not responding as expected (HTTP $HTTP_CODE)"
    fi
    
    # Test HTTPS if SSL is configured
    if [ "$HAS_SSL" = true ]; then
        echo "Testing HTTPS connectivity..."
        HTTPS_CODE=$(curl -k -s -o /dev/null -w "%{http_code}" -H "Host: $DOMAIN" "https://127.0.0.1/" 2>/dev/null || echo "000")
        if [ "$HTTPS_CODE" = "200" ] || [ "$HTTPS_CODE" = "301" ] || [ "$HTTPS_CODE" = "302" ]; then
            echo "✓ HTTPS responds (HTTP $HTTPS_CODE)"
        else
            echo "WARNING: HTTPS not responding as expected (HTTP $HTTPS_CODE)"
        fi
    fi
fi

if [ "$VERIFICATION_FAILED" = true ]; then
  echo ""
  echo "INSTALLATION FAILED: Verification checks did not pass" >&2
  rollback "Verification checks failed"
  exit 1
fi

echo ""
echo "=== Installation Complete ===" >&3
echo "" >&3
echo "Domain: $DOMAIN" >&3
echo "Document Root: $WORKING_DIR" >&3
echo "Web Server: $WEB_SERVER" >&3
if [ "$HAS_SSL" = true ]; then
    echo "HTTPS Status: Enabled" >&3
else
    echo "HTTPS Status: Not configured (HTTP only)" >&3
fi
echo "" >&3

# Display WebDAV information if enabled
if [ "$WEBDAV_ENABLED" = true ] && [ -n "$WEBDAV_USER_DISPLAY" ]; then
    echo "======================================" >&3
    echo "WebDAV Configuration" >&3
    echo "======================================" >&3
    echo "WebDAV URL:" >&3
    if [ "$HAS_SSL" = true ]; then
        echo "https://$DOMAIN/webdav" >&3
    else
        echo "http://$DOMAIN/webdav" >&3
    fi
    echo "" >&3
    echo "Username:" >&3
    echo "$WEBDAV_USER_DISPLAY" >&3
    echo "" >&3
    echo "(Password not displayed for security)" >&3
    echo "======================================" >&3
    echo "" >&3
fi

# Display generated credentials if this was a fresh install
if [ "$CREDENTIALS_GENERATED" = true ]; then
  echo "" >&3
  echo "======================================" >&3
  echo "Administrator account created" >&3
  echo "" >&3
  echo "Username:" >&3
  echo "$ADMIN_USERNAME" >&3
  echo "" >&3
  echo "Password:" >&3
  echo "$ADMIN_PASSWORD" >&3
  echo "" >&3
  echo "Credentials have been saved to:" >&3
  echo "" >&3
  echo "$ENV_FILE" >&3
  echo "" >&3
  echo "Save these credentials now." >&3
  echo "======================================" >&3
fi

echo "" >&3
echo "Run 'sudo $WORKING_DIR/deploy/health-check.sh' to verify deployment." >&3
exit 0
