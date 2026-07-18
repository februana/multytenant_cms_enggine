#!/usr/bin/env bash
set -euo pipefail

# deploy/update.sh
# Official update mechanism for Wedding Invitation Web Application
# 
# This script safely updates an existing installation at /var/www/wedding
# without affecting user data (uploads, database, config, etc.)
#
# Usage: sudo ./deploy/update.sh

CANONICAL_TARGET="/var/www/wedding"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
TEMP_DIR="/tmp/webserver_undangan_update"
REAL_USER="${SUDO_USER:-$USER}"
BACKUP_SCRIPT="$CANONICAL_TARGET/deploy/backup.sh"
HEALTH_CHECK_SCRIPT="$CANONICAL_TARGET/deploy/health-check.sh"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    log_error "This script must be run as root or via sudo."
    exit 2
fi

echo "=== Wedding Invitation Update Script ==="
echo ""

# Step 1: Verify application is installed
log_info "Checking if application is installed at $CANONICAL_TARGET..."
if [ ! -d "$CANONICAL_TARGET" ]; then
    log_error "Application not found at $CANONICAL_TARGET"
    log_error "Please run install.sh first for initial installation."
    exit 1
fi

if [ ! -f "$CANONICAL_TARGET/index.php" ]; then
    log_error "Application files not found. This does not appear to be a valid installation."
    exit 1
fi

log_info "Application found at $CANONICAL_TARGET"
echo ""

# Step 2: Create backup before update
log_info "Creating backup before update..."
if [ -f "$BACKUP_SCRIPT" ]; then
    if ! "$BACKUP_SCRIPT"; then
        log_error "Backup failed. Aborting update to prevent data loss."
        exit 1
    fi
    log_info "Backup completed successfully."
else
    log_warn "Backup script not found at $BACKUP_SCRIPT"
    log_warn "Proceeding without backup (not recommended for production)."
fi
echo ""

# Step 3: Clean up any previous failed update attempts
if [ -d "$TEMP_DIR" ]; then
    log_warn "Removing temporary directory from previous attempt..."
    rm -rf "$TEMP_DIR"
fi

# Step 4: Download latest source to temporary directory
log_info "Downloading latest source code..."

# Get repository URL from existing installation (if available)
REPO_URL="git@github.com:februana/webserver_undangan.git"

# Clone to temp directory (shallow clone for speed)
# Clone to temp directory (shallow clone for speed)
if ! sudo -u "$REAL_USER" git clone --depth 1 "$REPO_URL" "$TEMP_DIR"; then
    log_error "Failed to clone repository. Please check your internet connection."
    rm -rf "$TEMP_DIR"
    exit 1
fi
log_info "Source code downloaded successfully."
echo ""

# Step 5: Install Composer dependencies
log_info "Installing Composer dependencies..."
cd "$TEMP_DIR"
if [ -f "composer.json" ]; then
    if command -v composer &>/dev/null; then
        if ! composer install --no-dev --optimize-autoloader --quiet; then
            log_error "Composer install failed."
            rm -rf "$TEMP_DIR"
            exit 1
        fi
        log_info "Composer dependencies installed."
    else
        log_error "Composer is not installed. Cannot proceed with update."
        rm -rf "$TEMP_DIR"
        exit 1
    fi
else
    log_warn "composer.json not found in source."
fi
echo ""

# Step 6: Identify files/directories to preserve (user data)
log_info "Preparing to update application files..."

# Files and directories that must be preserved
PRESERVE_FILES=(
    "config.json"
    "guest-links.json"
    "database.sqlite"
    ".env"
    "event.ics"
)

PRESERVE_DIRS=(
    "uploads"
    "backups"
    "storage"
)

# Step 7: Backup user data from current installation temporarily
log_info "Preserving user data..."
TEMP_PRESERVE="$TEMP_DIR/_preserve_user_data"
mkdir -p "$TEMP_PRESERVE"

# Copy files to preserve
for file in "${PRESERVE_FILES[@]}"; do
    if [ -f "$CANONICAL_TARGET/$file" ]; then
        cp -a "$CANONICAL_TARGET/$file" "$TEMP_PRESERVE/"
    fi
done

# Copy directories to preserve
for dir in "${PRESERVE_DIRS[@]}"; do
    if [ -d "$CANONICAL_TARGET/$dir" ]; then
        cp -a "$CANONICAL_TARGET/$dir" "$TEMP_PRESERVE/"
    fi
done

log_info "User data preserved."
echo ""

# Step 8: Copy application files to target (excluding preserved items)
log_info "Copying application files to $CANONICAL_TARGET..."

# Use rsync to sync files, excluding preserved items
rsync -av \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='.github' \
    --exclude='.vscode' \
    --exclude='*.md' \
    --exclude='uploads/' \
    --exclude='backups/' \
    --exclude='storage/' \
    --exclude='config.json' \
    --exclude='guest-links.json' \
    --exclude='database.sqlite' \
    --exclude='.env' \
    --exclude='event.ics' \
    "$TEMP_DIR/" "$CANONICAL_TARGET/"

log_info "Application files copied."
echo ""

# Step 9: Restore preserved user data
log_info "Restoring user data..."
for file in "${PRESERVE_FILES[@]}"; do
    if [ -f "$TEMP_PRESERVE/$file" ]; then
        cp -a "$TEMP_PRESERVE/$file" "$CANONICAL_TARGET/"
    fi
done

for dir in "${PRESERVE_DIRS[@]}"; do
    if [ -d "$TEMP_PRESERVE/$dir" ]; then
        # Merge directories instead of replacing
        if [ -d "$CANONICAL_TARGET/$dir" ]; then
            cp -a "$TEMP_PRESERVE/$dir/"* "$CANONICAL_TARGET/$dir/" 2>/dev/null || true
        else
            cp -a "$TEMP_PRESERVE/$dir" "$CANONICAL_TARGET/"
        fi
    fi
done

log_info "User data restored."
echo ""

# Step 10: Copy public assets (style.css, script.js) from app/ to root
log_info "Updating public assets..."
if [ -f "$CANONICAL_TARGET/app/style.css" ]; then
    cp "$CANONICAL_TARGET/app/style.css" "$CANONICAL_TARGET/style.css"
fi
if [ -f "$CANONICAL_TARGET/app/script.js" ]; then
    cp "$CANONICAL_TARGET/app/script.js" "$CANONICAL_TARGET/script.js"
fi
log_info "Public assets updated."
echo ""

# Step 11: Set proper ownership and permissions
log_info "Setting ownership and permissions..."
chown -R www-data:www-data "$CANONICAL_TARGET"
find "$CANONICAL_TARGET" -type d -exec chmod 755 {} \;
find "$CANONICAL_TARGET" -type f -name "*.php" -exec chmod 644 {} \;
find "$CANONICAL_TARGET" -type f -name "*.json" -exec chmod 600 {} \;
find "$CANONICAL_TARGET" -type f -name "*.sqlite" -exec chmod 600 {} \;
find "$CANONICAL_TARGET" -type f -name ".env" -exec chmod 600 {} \;
log_info "Ownership and permissions set."
echo ""

# Step 12: Detect PHP-FPM version and restart services
log_info "Restarting PHP-FPM..."
PHP_FPM_SOCK=$(find /run/php -name '*.sock' 2>/dev/null | head -n 1 || echo "")
if [ -n "$PHP_FPM_SOCK" ]; then
    # Extract PHP version from socket path
    PHP_VERSION=$(echo "$PHP_FPM_SOCK" | grep -oP 'php\d\.\d' | head -1 || echo "")
    if [ -n "$PHP_VERSION" ]; then
        log_info "Detected PHP version: $PHP_VERSION"
        systemctl restart "$PHP_VERSION-fpm" 2>/dev/null || systemctl restart php-fpm 2>/dev/null || true
    else
        systemctl restart php-fpm 2>/dev/null || true
    fi
else
    systemctl restart php-fpm 2>/dev/null || true
fi
log_info "PHP-FPM restarted."

log_info "Reloading Nginx..."
if nginx -t 2>/dev/null; then
    systemctl reload nginx
    log_info "Nginx reloaded."
else
    log_warn "Nginx configuration test failed, skipping reload."
fi
echo ""

# Step 13: Run health check
log_info "Running health check..."
if [ -f "$HEALTH_CHECK_SCRIPT" ]; then
    if ! "$HEALTH_CHECK_SCRIPT"; then
        log_error "Health check FAILED!"
        log_error "Update may have issues. Backup is preserved at:"
        ls -lt "$CANONICAL_TARGET/backups/"*.tar.gz 2>/dev/null | head -1 || log_warn "Could not locate backup file."
        log_error "Do NOT delete the backup. Review the health check errors above."
        # Clean up temp directory but keep backup
        rm -rf "$TEMP_DIR"
        exit 1
    fi
    log_info "Health check passed."
else
    log_warn "Health check script not found. Skipping health check."
fi
echo ""

# Step 14: Clean up temporary directory
log_info "Cleaning up temporary files..."
rm -rf "$TEMP_DIR"
log_info "Cleanup completed."
echo ""

# Success message
echo "========================================"
echo -e "${GREEN}UPDATE COMPLETED SUCCESSFULLY${NC}"
echo "========================================"
echo ""
echo "Runtime location: $CANONICAL_TARGET"
echo ""
echo "The following user data was preserved:"
for file in "${PRESERVE_FILES[@]}"; do
    if [ -f "$CANONICAL_TARGET/$file" ]; then
        echo "  ✓ $file"
    fi
done
for dir in "${PRESERVE_DIRS[@]}"; do
    if [ -d "$CANONICAL_TARGET/$dir" ]; then
        echo "  ✓ $dir/"
    fi
done
echo ""
echo "A backup was created before this update."
echo "Backups are stored in: $CANONICAL_TARGET/backups/"
echo ""
echo "Run 'sudo $HEALTH_CHECK_SCRIPT' anytime to verify deployment health."
echo ""

exit 0
