#!/usr/bin/env bash
set -euo pipefail

# deploy/update.sh
# Deployment Manager for Wedding Invitation Web Application
# 
# Usage: sudo ./deploy/update.sh

CANONICAL_TARGET="${CANONICAL_TARGET:-/var/www/wedding}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATE_DIR="$SCRIPT_DIR/templates"
RUNTIME_DIRECTORIES_SCRIPT="$SCRIPT_DIR/runtime-directories.sh"
RUNTIME_DEPENDENCIES_SCRIPT="$SCRIPT_DIR/runtime-dependencies.sh"
REPOSITORY_URL="${REPOSITORY_URL:-https://github.com/februana/multytenant_cms_enggine.git}"
BACKUP_SCRIPT="${BACKUP_SCRIPT:-$CANONICAL_TARGET/deploy/backup.sh}"
HEALTH_CHECK_SCRIPT="${HEALTH_CHECK_SCRIPT:-$CANONICAL_TARGET/deploy/health-check.sh}"

if [ ! -r "$RUNTIME_DIRECTORIES_SCRIPT" ]; then
    echo "[ERROR] Missing runtime directory contract: $RUNTIME_DIRECTORIES_SCRIPT" >&2
    exit 2
fi
if [ ! -r "$RUNTIME_DEPENDENCIES_SCRIPT" ]; then
    echo "[ERROR] Missing runtime dependency contract: $RUNTIME_DEPENDENCIES_SCRIPT" >&2
    exit 2
fi
. "$RUNTIME_DIRECTORIES_SCRIPT"
. "$RUNTIME_DEPENDENCIES_SCRIPT"
REAL_USER="${SUDO_USER:-$USER}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
log_section() { echo -e "${BLUE}======================================${NC}\n${BLUE}$1${NC}\n${BLUE}======================================${NC}"; }

if [ "$EUID" -ne 0 ]; then log_error "Must run as root"; exit 2; fi

detect_web_server() {
    if systemctl is-active --quiet nginx 2>/dev/null; then echo "nginx"
    elif systemctl is-active --quiet apache2 2>/dev/null; then echo "apache"
    else echo "unknown"; fi
}

# Detect domain from existing configuration
detect_domain_from_config() {
    local ws=$(detect_web_server)
    local domain=""
    
    if [ "$ws" = "nginx" ] && [ -f "/etc/nginx/sites-enabled/wedding" ]; then
        domain=$(grep -oP 'server_name\s+\K[^\s;]+' /etc/nginx/sites-enabled/wedding 2>/dev/null | head -1 || echo "")
    elif [ "$ws" = "apache" ] && [ -f "/etc/apache2/sites-enabled/wedding.conf" ]; then
        domain=$(grep -oP 'ServerName\s+\K[^\s]+' /etc/apache2/sites-enabled/wedding.conf 2>/dev/null | head -1 || echo "")
    fi
    
    # If no domain found, return underscore as placeholder
    if [ -z "$domain" ]; then
        domain="_"
    fi
    
    echo "$domain"
}

get_php_fpm_socket() {
    local sock=$(find /run/php -name '*.sock' 2>/dev/null | head -n 1)
    if [ -z "$sock" ]; then
        for v in php-fpm php8.3-fpm php8.2-fpm php8.1-fpm; do
            [ -S "/run/php/$v.sock" ] && sock="/run/php/$v.sock" && break
        done
    fi
    echo "${sock:-/run/php/php-fpm.sock}"
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

create_backup() {
    log_info "Creating backup..."
    if [ ! -f "$BACKUP_SCRIPT" ]; then
        log_error "Backup script not found: $BACKUP_SCRIPT"
        return 1
    fi
    if ! bash "$BACKUP_SCRIPT"; then
        log_error "Backup script failed. No application files were changed."
        return 1
    fi
    return 0
}

generate_nginx_config() {
    local sock="$1"
    local domain="${2:-_}"
    local conf="/etc/nginx/sites-available/wedding"
    
    # Validate domain
    if [ -z "$domain" ] || [ "$domain" = "{{DOMAIN}}" ]; then
        log_error "Invalid domain for Nginx config generation"
        return 1
    fi
    
    cp "$TEMPLATE_DIR/nginx/wedding.conf" "$conf"
    sed -i "s|{{DOMAIN}}|$domain|g; s|{{DOCUMENT_ROOT}}|$CANONICAL_TARGET|g; s|{{PHP_SOCKET}}|$sock|g; s|{{LOG_PATH}}|/var/log/nginx|g; s|{{UPLOAD_LIMIT}}|20M|g; s|{{LETSENCRYPT_PATH}}|/etc/letsencrypt/live/$domain|g" "$conf"
    
    # Validate no unresolved placeholders
    if grep -q '{{' "$conf"; then
        log_error "Unresolved placeholders in generated Nginx config"
        return 1
    fi
    
    log_info "Nginx config generated from template"
}

generate_apache_config() {
    local sock="$1"
    local domain="${2:-_}"
    local http_conf="/etc/apache2/sites-available/wedding.conf"
    local ssl_conf="/etc/apache2/sites-available/wedding-ssl.conf"
    
    # Validate domain
    if [ -z "$domain" ] || [ "$domain" = "{{DOMAIN}}" ]; then
        log_error "Invalid domain for Apache config generation"
        return 1
    fi
    
    # Fix ports.conf to use standard ports (80 and 443)
    log_info "Configuring Apache ports.conf..."
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
        log_info "ports.conf updated with standard configuration"
    fi
    
    # Generate HTTP configuration
    cp "$TEMPLATE_DIR/apache/apache-http.conf.template" "$http_conf"
    sed -i "s|{{DOMAIN}}|$domain|g; s|{{DOCUMENT_ROOT}}|$CANONICAL_TARGET|g; s|{{PHP_SOCKET}}|$sock|g; s|{{LOG_PATH}}|\${APACHE_LOG_DIR}|g" "$http_conf"
    
    # Validate no unresolved placeholders in HTTP config
    if grep -q '{{' "$http_conf"; then
        log_error "Unresolved placeholders in generated Apache HTTP config"
        return 1
    fi
    
    # Check if SSL certificate exists and generate SSL config if so
    if check_ssl_exists "$domain"; then
        cp "$TEMPLATE_DIR/apache/apache-ssl.conf.template" "$ssl_conf"
        sed -i "s|{{DOMAIN}}|$domain|g; s|{{DOCUMENT_ROOT}}|$CANONICAL_TARGET|g; s|{{PHP_SOCKET}}|$sock|g; s|{{LETSENCRYPT_PATH}}|/etc/letsencrypt/live/$domain|g; s|{{LOG_PATH}}|\${APACHE_LOG_DIR}|g" "$ssl_conf"
        
        # Validate no unresolved placeholders in SSL config
        if grep -q '{{' "$ssl_conf"; then
            log_error "Unresolved placeholders in generated Apache SSL config"
            return 1
        fi
        
        log_info "Apache HTTP+SSL configs generated from templates"
    else
        log_info "Apache HTTP config generated from template (SSL not configured)"
    fi
    
    return 0
}

rollback_migration() {
    local src="$1" tgt="$2"
    log_error "Rolling back to $src..."
    if [ "$tgt" = "apache" ]; then
        a2dissite wedding.conf 2>/dev/null; a2dissite wedding-ssl.conf 2>/dev/null
        systemctl stop apache2 2>/dev/null; systemctl disable apache2 2>/dev/null
        ln -sf /etc/nginx/sites-available/wedding /etc/nginx/sites-enabled/wedding 2>/dev/null
        systemctl start nginx 2>/dev/null; systemctl enable nginx 2>/dev/null
    else
        rm -f /etc/nginx/sites-enabled/wedding 2>/dev/null; systemctl stop nginx 2>/dev/null; systemctl disable nginx 2>/dev/null
        a2ensite wedding.conf 2>/dev/null; a2ensite wedding-ssl.conf 2>/dev/null || true
        systemctl start apache2 2>/dev/null; systemctl enable apache2 2>/dev/null
    fi
    log_error "Rollback complete"
}

update_application() (
    local temp_dir="${TMPDIR:-/tmp}/webserver_undangan_update.XXXXXX"
    local preserve_dir=""
    local source_dir=""
    local ws=""
    local php_ver=""
    local -a preserve_files=("database.sqlite" ".env")
    local -a preserve_dirs=("uploads" "webdav" "backups" "storage")

    cleanup_update_staging() {
        [ -n "$preserve_dir" ] && [ -d "$preserve_dir" ] && rm -rf -- "$preserve_dir"
        [ -n "$source_dir" ] && [ -d "$source_dir" ] && rm -rf -- "$source_dir"
    }
    trap cleanup_update_staging EXIT

    log_section "Update Application"
    [ ! -d "$CANONICAL_TARGET" ] && { log_error "App not found"; return 1; }
    [ ! -f "$CANONICAL_TARGET/index.php" ] && { log_error "Invalid install"; return 1; }

    if ! create_backup; then
        log_error "Backup failed. Update aborted."
        return 1
    fi

    source_dir="$(mktemp -d "$temp_dir")" || { log_error "Could not create source staging directory"; return 1; }
    rm -rf -- "$source_dir"
    log_info "Downloading source from $REPOSITORY_URL..."
    if ! git clone --depth 1 "$REPOSITORY_URL" "$source_dir"; then
        log_error "Clone failed. The existing application was not modified."
        return 1
    fi
    if [ ! -f "$source_dir/index.php" ] || [ ! -f "$source_dir/config.php" ] || [ ! -r "$source_dir/deploy/runtime-dependencies.sh" ]; then
        log_error "Cloned source failed application/dependency contract verification. The existing application was not modified."
        return 1
    fi

    . "$source_dir/deploy/runtime-dependencies.sh"
    log_info "Reconciling required runtime dependencies from the new source..."
    if ! runtime_reconcile_dependencies; then
        log_error "Required runtime dependencies are unavailable or could not be reconciled. The existing application was not modified."
        return 1
    fi

    if [ -f "$source_dir/composer.json" ] && command -v composer >/dev/null 2>&1; then
        (cd "$source_dir" && composer install --no-dev --optimize-autoloader --quiet) || {
            log_error "Composer dependency installation failed. The existing application was not modified."
            return 1
        }
    fi

    preserve_dir="$(mktemp -d "${TMPDIR:-/tmp}/webserver_undangan_preserve.XXXXXX")" || {
        log_error "Could not create user-data preservation directory"
        return 1
    }
    for file in "${preserve_files[@]}"; do
        if [ -f "$CANONICAL_TARGET/$file" ]; then
            cp -a -- "$CANONICAL_TARGET/$file" "$preserve_dir/" || { log_error "Could not preserve $file"; return 1; }
        fi
    done
    for directory in "${preserve_dirs[@]}"; do
        if [ -d "$CANONICAL_TARGET/$directory" ]; then
            mkdir -p -- "$preserve_dir/$directory"
            if command -v rsync >/dev/null 2>&1; then
                rsync -a -- "$CANONICAL_TARGET/$directory/" "$preserve_dir/$directory/" || { log_error "Could not preserve $directory/"; return 1; }
            else
                cp -a -- "$CANONICAL_TARGET/$directory/." "$preserve_dir/$directory/" || { log_error "Could not preserve $directory/"; return 1; }
            fi
        fi
    done

    log_info "Synchronizing verified application source..."
    if command -v rsync >/dev/null 2>&1; then
        rsync -a --delete \
            --exclude='.git/' --exclude='*.md' \
            --exclude='uploads/' --exclude='webdav/' --exclude='backups/' --exclude='storage/' \
            --exclude='database.sqlite' --exclude='.env' \
            "$source_dir/" "$CANONICAL_TARGET/" || { log_error "Application source synchronization failed; verified backup retained."; return 1; }
    else
        log_error "rsync is required for a safe update; application was not modified."
        return 1
    fi

    for file in "${preserve_files[@]}"; do
        if [ -f "$preserve_dir/$file" ]; then
            cp -a -- "$preserve_dir/$file" "$CANONICAL_TARGET/" || { log_error "Could not restore preserved $file"; return 1; }
        fi
    done
    for directory in "${preserve_dirs[@]}"; do
        if [ -d "$preserve_dir/$directory" ]; then
            mkdir -p -- "$CANONICAL_TARGET/$directory"
            rsync -a -- "$preserve_dir/$directory/" "$CANONICAL_TARGET/$directory/" || { log_error "Could not restore preserved $directory/"; return 1; }
        fi
    done

    ensure_runtime_directories "$CANONICAL_TARGET" || { log_error "Runtime directory bootstrap failed"; return 1; }
    (cd "$CANONICAL_TARGET" && php deploy/migrate.php) || { log_error "Database migration failed after source replacement"; return 1; }

    chown -R www-data:www-data "$CANONICAL_TARGET"
    find "$CANONICAL_TARGET" -type d -exec chmod 755 {} \;
    find "$CANONICAL_TARGET" -type f -name '*.php' -exec chmod 644 {} \;
    find "$CANONICAL_TARGET" -type f \( -name 'database.sqlite' -o -name '.env' \) -exec chmod 600 {} \;

    php_ver="$(get_php_fpm_socket | grep -oP 'php\d\.\d' | head -1 || true)"
    if [ -n "$php_ver" ]; then
        systemctl restart "${php_ver}-fpm" 2>/dev/null || true
    else
        systemctl restart php-fpm 2>/dev/null || true
    fi

    ws="$(detect_web_server)"
    if [ "$ws" = "nginx" ]; then
        nginx -t || { log_error "Nginx configuration test failed after source replacement; verified backup retained."; return 1; }
        systemctl reload nginx || { log_error "Nginx reload failed; verified backup retained."; return 1; }
    elif [ "$ws" = "apache" ]; then
        apache2ctl configtest || { log_error "Apache configuration test failed after source replacement; verified backup retained."; return 1; }
        systemctl reload apache2 || { log_error "Apache reload failed; verified backup retained."; return 1; }
    fi

    if [ ! -f "$HEALTH_CHECK_SCRIPT" ]; then
        log_error "Health check script missing: $HEALTH_CHECK_SCRIPT"
        return 1
    fi
    if ! bash "$HEALTH_CHECK_SCRIPT"; then
        log_error "Health check failed after update. Verified backup retained; UPDATE COMPLETE was not emitted."
        return 1
    fi

    log_info "UPDATE COMPLETE"
)

migration_mode() {
    log_section "Migration Mode"
    local cur=$(detect_web_server)
    [ "$cur" = "unknown" ] && { log_error "No web server"; return 1; }
    
    log_info "Current: $(echo $cur | tr '[:lower:]' '[:upper:]')"
    
    if [ "$cur" = "nginx" ]; then
        echo "1. Apache  2. Nginx (current)"
        read -p "Choice [1-2]: " c; [ "$c" != "1" ] && { log_info "No migration needed"; return 0; }
        local tgt="apache"
    else
        echo "1. Nginx  2. Apache (current)"
        read -p "Choice [1-2]: " c; [ "$c" != "1" ] && { log_info "No migration needed"; return 0; }
        local tgt="nginx"
    fi
    
    echo "1. Safe Migration  2. Clean Migration  3. Cancel"
    read -p "Choice [1-3]: " mtype
    [ "$mtype" = "3" ] && { log_info "Cancelled"; return 0; }
    [ "$mtype" != "1" ] && [ "$mtype" != "2" ] && { log_error "Invalid"; return 1; }
    
    if ! create_backup; then
        log_error "Backup failed. Migration aborted."
        return 1
    fi
    local sock=$(get_php_fpm_socket)
    
    # Get domain from existing configuration
    local domain=$(detect_domain_from_config)
    log_info "Domain: $domain"
    
    if [ "$tgt" = "apache" ]; then
        apt update -qq && apt install -y -qq apache2 || { rollback_migration "$cur" "$tgt"; return 1; }
        a2enmod rewrite headers ssl proxy_fcgi setenvif dav dav_fs auth_basic alias socache_shmcb >/dev/null 2>&1
        # Start PHP-FPM to ensure socket is available before config generation
        systemctl enable php-fpm >/dev/null 2>&1 || systemctl enable php8.3-fpm >/dev/null 2>&1 || systemctl enable php8.2-fpm >/dev/null 2>&1 || true
        systemctl start php-fpm >/dev/null 2>&1 || systemctl start php8.3-fpm >/dev/null 2>&1 || systemctl start php8.2-fpm >/dev/null 2>&1 || true
        sleep 2
        sock=$(get_php_fpm_socket)
        generate_apache_config "$sock" "$domain" || { rollback_migration "$cur" "$tgt"; return 1; }
        apache2ctl configtest || { rollback_migration "$cur" "$tgt"; return 1; }
        a2ensite wedding.conf; a2dissite 000-default.conf 2>/dev/null
        # Enable SSL site if certificate exists
        if check_ssl_exists "$domain"; then
            a2ensite wedding-ssl.conf 2>/dev/null || true
        fi
        systemctl enable apache2; systemctl restart apache2 || { rollback_migration "$cur" "$tgt"; return 1; }
        systemctl stop nginx 2>/dev/null; systemctl disable nginx 2>/dev/null
    else
        apt update -qq && apt install -y -qq nginx || { rollback_migration "$cur" "$tgt"; return 1; }
        # Start PHP-FPM to ensure socket is available before config generation
        systemctl enable php-fpm >/dev/null 2>&1 || systemctl enable php8.3-fpm >/dev/null 2>&1 || systemctl enable php8.2-fpm >/dev/null 2>&1 || true
        systemctl start php-fpm >/dev/null 2>&1 || systemctl start php8.3-fpm >/dev/null 2>&1 || systemctl start php8.2-fpm >/dev/null 2>&1 || true
        sleep 2
        sock=$(get_php_fpm_socket)
        generate_nginx_config "$sock" "$domain" || { rollback_migration "$cur" "$tgt"; return 1; }
        nginx -t || { rollback_migration "$cur" "$tgt"; return 1; }
        ln -sf /etc/nginx/sites-available/wedding /etc/nginx/sites-enabled/wedding; rm -f /etc/nginx/sites-enabled/default
        systemctl enable nginx; systemctl restart nginx || { rollback_migration "$cur" "$tgt"; return 1; }
        systemctl stop apache2 2>/dev/null; systemctl disable apache2 2>/dev/null
    fi
    
    [ "$(detect_web_server)" != "$tgt" ] && { rollback_migration "$cur" "$tgt"; return 1; }
    [ -f "$HEALTH_CHECK_SCRIPT" ] && "$HEALTH_CHECK_SCRIPT" || log_warn "Health check skipped"
    
    if [ "$mtype" = "2" ]; then
        echo ""; log_section "Clean Migration"
        read -p "Remove $cur completely? [y/N]: " rm
        if [[ "$rm" =~ ^[Yy]$ ]]; then
            if [ "$cur" = "nginx" ]; then
                systemctl stop nginx 2>/dev/null; systemctl disable nginx 2>/dev/null
                apt purge -y -qq nginx nginx-common nginx-core 2>/dev/null; apt autoremove -y -qq 2>/dev/null
                rm -f /etc/nginx/sites-available/wedding /etc/nginx/sites-enabled/wedding 2>/dev/null
            else
                systemctl stop apache2 2>/dev/null; systemctl disable apache2 2>/dev/null
                apt purge -y -qq apache2 apache2-bin apache2-data apache2-utils 2>/dev/null; apt autoremove -y -qq 2>/dev/null
                a2dissite wedding.conf wedding-ssl.conf 000-default.conf 2>/dev/null || true
                rm -f /etc/apache2/sites-available/wedding.conf /etc/apache2/sites-enabled/wedding.conf 2>/dev/null
                rm -f /etc/apache2/sites-available/wedding-ssl.conf /etc/apache2/sites-enabled/wedding-ssl.conf 2>/dev/null
            fi
            log_info "Let's Encrypt certs preserved"
        else
            log_info "$cur kept but disabled"
        fi
    fi
    
    log_info "MIGRATION COMPLETE: $cur -> $tgt"
}

reconfigure_web_server() {
    log_section "Reconfigure Web Server"
    local cur=$(detect_web_server)
    [ "$cur" = "unknown" ] && { log_error "No web server"; return 1; }
    
    # Get domain from existing configuration
    local domain=$(detect_domain_from_config)
    log_info "Domain: $domain"
    
    log_warn "This will rebuild config from templates"
    read -p "Continue? [y/N]: " c; [[ ! "$c" =~ ^[Yy]$ ]] && { log_info "Cancelled"; return 0; }
    
    local sock=$(get_php_fpm_socket)
    if [ "$cur" = "nginx" ]; then
        generate_nginx_config "$sock" "$domain" || return 1
        nginx -t || return 1
        systemctl reload nginx
    else
        generate_apache_config "$sock" "$domain" || return 1
        apache2ctl configtest || return 1
        # Enable/disable SSL site based on certificate existence
        if check_ssl_exists "$domain"; then
            a2ensite wedding-ssl.conf 2>/dev/null || true
        else
            a2dissite wedding-ssl.conf 2>/dev/null || true
        fi
        systemctl reload apache2
    fi
    log_info "RECONFIGURATION COMPLETE"
}

while true; do
    clear
    log_section "Deployment Manager"
    echo "1. Update Application\n2. Migration Mode\n3. Reconfigure Web Server\n4. Exit"
    read -p "Choice [1-4]: " ch
    case $ch in
        1) update_application; read -p "Press Enter...";;
        2) migration_mode; read -p "Press Enter...";;
        3) reconfigure_web_server; read -p "Press Enter...";;
        4) log_info "Exiting"; exit 0;;
        *) log_error "Invalid"; sleep 2;;
    esac
done
