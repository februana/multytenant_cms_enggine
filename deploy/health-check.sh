#!/usr/bin/env bash
set -euo pipefail

# deploy/health-check.sh
# Validates a production deployment at /var/www/wedding
# Critical checks are limited to the current single-root CMS-first architecture.

DEPLOY_DIR="${DEPLOY_DIR:-/var/www/wedding}"
DATA_DIR="${UNDANGAN_DATA_DIR:-$DEPLOY_DIR}"
CONFIG_FILE_PATH="${UNDANGAN_CONFIG_PATH:-$DATA_DIR/config.json}"
DB_FILE_PATH="${UNDANGAN_DB_PATH:-$DATA_DIR/database.sqlite}"
GUEST_LINKS_FILE_PATH="${UNDANGAN_GUEST_LINKS_PATH:-$DATA_DIR/guest-links.json}"
EVENT_ICS_FILE_PATH="${UNDANGAN_EVENT_ICS_PATH:-$DATA_DIR/event.ics}"
CUSTOM_CSS_FILE_PATH="${UNDANGAN_CUSTOM_CSS_PATH:-$DATA_DIR/custom.css}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RUNTIME_DIRECTORIES_SCRIPT="$SCRIPT_DIR/runtime-directories.sh"
WEDDING_BUILTIN_PRESETS="dewankl elix rainier archak parang pawiwahan custom"
PASS_COUNT=0
WARN_COUNT=0
FAIL_COUNT=0

pass() { echo "PASS: $1"; ((PASS_COUNT++)) || true; }
warning() { echo "WARNING: $1"; ((WARN_COUNT++)) || true; }
fail() { echo "FAIL: $1"; ((FAIL_COUNT++)) || true; }

echo "=== Deployment Health Check ==="
echo "Target: $DEPLOY_DIR"
echo ""

if [ -r "$RUNTIME_DIRECTORIES_SCRIPT" ]; then
  . "$RUNTIME_DIRECTORIES_SCRIPT"
else
  fail "Runtime directory contract missing: $RUNTIME_DIRECTORIES_SCRIPT"
fi

# Detect web server type
WEB_SERVER=""
if systemctl is-active --quiet apache2 2>/dev/null; then
    WEB_SERVER="apache"
elif systemctl is-active --quiet nginx 2>/dev/null; then
    WEB_SERVER="nginx"
fi

echo "Web Server: ${WEB_SERVER:-unknown}"
echo ""

# Check 1: Directory exists
if [ -d "$DEPLOY_DIR" ]; then
  pass "Deployment directory exists"
else
  fail "Deployment directory missing: $DEPLOY_DIR"
fi

# Check 2: Required application files exist in the document root.
for file in index.php admin.php save.php messages.php gallery.php config.php; do
  if [ -f "$DEPLOY_DIR/$file" ]; then
    pass "File exists: $file"
  else
    fail "Missing required file: $file"
  fi
done

# Runtime state may live in /var/data (Docker) or the document root (native).
for runtime_file in "$CONFIG_FILE_PATH" "$DB_FILE_PATH" "$GUEST_LINKS_FILE_PATH" "$EVENT_ICS_FILE_PATH"; do
  if [ -f "$runtime_file" ]; then
    pass "Runtime file exists: $runtime_file"
  else
    fail "Missing required runtime file: $runtime_file"
  fi
done
if [ -f "$CUSTOM_CSS_FILE_PATH" ]; then
  pass "Custom CSS runtime file exists: $CUSTOM_CSS_FILE_PATH"
else
  warning "Custom CSS runtime file is not provisioned: $CUSTOM_CSS_FILE_PATH"
fi

# Check 3: Themes directory and required themes exist
if [ -d "$DEPLOY_DIR/themes" ]; then
  pass "Themes directory exists"
else
  fail "Missing themes directory"
fi

for theme in dewankl elix rainier archak parang pawiwahan; do
  if [ -d "$DEPLOY_DIR/themes/$theme" ]; then
    pass "Theme directory exists: themes/$theme"
  else
    fail "Missing theme directory: themes/$theme"
  fi
  
  # Check for required theme files
  for tfile in layout.php style.css script.js; do
    if [ -f "$DEPLOY_DIR/themes/$theme/$tfile" ]; then
      pass "Theme file exists: themes/$theme/$tfile"
    else
      fail "Missing theme file: themes/$theme/$tfile"
    fi
  done
done

# Check 4: Required upload directories and preset-scoped Theme Assets exist and are writable.
if command -v runtime_upload_directories >/dev/null 2>&1; then
  while IFS= read -r required_dir; do
    [ -z "$required_dir" ] && continue
    if [ -d "$required_dir" ] && [ -w "$required_dir" ]; then
      pass "Runtime directory writable: ${required_dir#$DEPLOY_DIR/}"
    else
      fail "Runtime directory issue: ${required_dir#$DEPLOY_DIR/}"
    fi
  done <<EOF
$(runtime_upload_directories "$DEPLOY_DIR")
EOF
else
  fail "Runtime directory contract unavailable; cannot validate upload paths"
fi

# Optional media is intentionally not provisioned by a clean checkout.
read_config_value() {
  local key="$1"
  if [ ! -f "$CONFIG_FILE_PATH" ] || ! command -v php >/dev/null 2>&1; then
    return 0
  fi
  CONFIG_KEY="$key" php -r '$value = json_decode(@file_get_contents($argv[1]), true); foreach (explode(".", getenv("CONFIG_KEY")) as $part) { if (!is_array($value) || !array_key_exists($part, $value)) { $value = null; break; } $value = $value[$part]; } if (is_string($value)) echo $value;' "$CONFIG_FILE_PATH" 2>/dev/null || true
}
check_optional_media() {
  local label="$1" reference="$2" resolved
  if [ -z "$reference" ]; then
    warning "$label is not provisioned (optional/user-provided)"
    return 0
  fi
  if [[ "$reference" =~ ^https?:// ]]; then
    pass "$label uses an external URL (local file not checked)"
    return 0
  fi
  if [[ "$reference" = /* ]]; then resolved="$reference"; else resolved="$DEPLOY_DIR/$reference"; fi
  if [ -f "$resolved" ] && [ -r "$resolved" ]; then
    pass "$label is readable: $reference"
  else
    warning "$label is configured but missing/unreadable: $reference"
  fi
}
check_optional_media "Optional cover media" "$(read_config_value 'media.cover')"
check_optional_media "Optional music media" "$(read_config_value 'media.music')"
check_optional_media "Optional Open Graph image" "$(read_config_value 'site.open_graph_image')"

ACTIVE_PRESET="$(read_config_value 'theme.theme_preset')"
case " $WEDDING_BUILTIN_PRESETS " in
  *" $ACTIVE_PRESET "*) pass "Active preset supported: ${ACTIVE_PRESET:-custom}" ;;
  *) fail "Active preset is not supported by the built-in contract: ${ACTIVE_PRESET:-empty}" ;;
esac

if command -v magick >/dev/null 2>&1 || command -v convert >/dev/null 2>&1; then
  pass "ImageMagick WebP processor is available"
elif command -v php >/dev/null 2>&1 && php -r 'exit(function_exists("imagewebp") ? 0 : 1);' >/dev/null 2>&1; then
  pass "PHP GD WebP fallback is available"
else
  fail "No ImageMagick or PHP GD WebP processor is available"
fi

# Check 4: Optional WebDAV should not be treated as a critical dependency
if [ -d "$DEPLOY_DIR/webdav" ]; then
  if [ -w "$DEPLOY_DIR/webdav" ]; then
    pass "WebDAV directory exists and is writable"
  else
    fail "WebDAV directory exists but is not writable: webdav/"
  fi
else
  pass "WebDAV not configured (optional; not a blocker)"
fi

# Check 5: Permissions checks
DATA_DIR_PERMS=$(stat -c %a "$DATA_DIR" 2>/dev/null || echo "000")
if [ -d "$DATA_DIR" ] && [ -w "$DATA_DIR" ]; then
  pass "Runtime data directory writable ($DATA_DIR_PERMS): $DATA_DIR"
else
  fail "Runtime data directory missing or not writable: $DATA_DIR"
fi

CONFIG_PERMS=$(stat -c %a "$CONFIG_FILE_PATH" 2>/dev/null || echo "000")
if [[ "$CONFIG_PERMS" == "600" ]] || [[ "$CONFIG_PERMS" == "640" ]] || [[ "$CONFIG_PERMS" == "660" ]]; then
  pass "Config file permissions secure ($CONFIG_PERMS)"
else
  fail "Config file permissions insecure ($CONFIG_PERMS), expected 600, 640, or 660"
fi

DB_PERMS=$(stat -c %a "$DB_FILE_PATH" 2>/dev/null || echo "000")
if [[ "$DB_PERMS" == "600" ]] || [[ "$DB_PERMS" == "640" ]] || [[ "$DB_PERMS" == "660" ]]; then
  pass "Database permissions secure ($DB_PERMS)"
else
  fail "Database permissions insecure ($DB_PERMS), expected 600, 640, or 660"
fi

OWNER=$(stat -c %U "$DEPLOY_DIR" 2>/dev/null || echo "unknown")
if [ "$OWNER" = "www-data" ]; then
  pass "Directory owned by www-data"
else
  fail "Directory not owned by www-data (owned by $OWNER)"
fi

# Check 6: Apache-specific checks (ports.conf, modules)
if [ "$WEB_SERVER" = "apache" ]; then
    echo ""
    echo "Apache-Specific Checks:"
    
    # Check ports.conf configuration
    if [ -f /etc/apache2/ports.conf ]; then
        if grep -q "^Listen 80" /etc/apache2/ports.conf && grep -q "Listen 443" /etc/apache2/ports.conf; then
            pass "ports.conf has standard configuration (Listen 80, Listen 443)"
        else
            fail "ports.conf may have non-standard port configuration"
        fi
    else
        fail "ports.conf not found"
    fi
    
    # Check required Apache modules
    REQUIRED_MODULES=("rewrite" "headers" "ssl" "proxy_fcgi" "setenvif")
    for mod in "${REQUIRED_MODULES[@]}"; do
        if [ -f "/etc/apache2/mods-enabled/${mod}.load" ]; then
            pass "Apache module enabled: $mod"
        else
            fail "Apache module missing: $mod"
        fi
    done
    
    # Check WebDAV modules and password file if WebDAV is configured
    if grep -q "DAV On" /etc/apache2/sites-enabled/wedding.conf 2>/dev/null || \
       grep -q "DAV On" /etc/apache2/sites-enabled/wedding-ssl.conf 2>/dev/null; then
        echo ""
        echo "WebDAV Configuration Checks:"
        
        # Check WebDAV modules
        for mod in dav dav_fs auth_basic; do
            if [ -f "/etc/apache2/mods-enabled/${mod}.load" ]; then
                pass "WebDAV module enabled: $mod"
            else
                fail "WebDAV module missing: $mod"
            fi
        done
        
        # Check WebDAV password file
        if [ -f /etc/apache2/.davpasswd ]; then
            pass "WebDAV password file exists (/etc/apache2/.davpasswd)"
            # Check permissions
            DAV_PERMS=$(stat -c %a /etc/apache2/.davpasswd 2>/dev/null || echo "000")
            if [[ "$DAV_PERMS" == "640" ]] || [[ "$DAV_PERMS" == "600" ]]; then
                pass "WebDAV password file permissions secure ($DAV_PERMS)"
            else
                fail "WebDAV password file permissions insecure ($DAV_PERMS)"
            fi
        else
            fail "WebDAV password file missing (/etc/apache2/.davpasswd)"
        fi
    fi
fi

# Check 7: HTTP checks (if Nginx or Apache is running)
WEB_SERVER_DETECTED=""
if command -v curl &> /dev/null; then
    # Use already detected WEB_SERVER if available, otherwise detect via pgrep
    if [ -n "$WEB_SERVER" ]; then
        WEB_SERVER_DETECTED="$WEB_SERVER"
    elif pgrep nginx > /dev/null 2>&1; then
        WEB_SERVER_DETECTED="nginx"
    elif pgrep apache2 > /dev/null 2>&1; then
        WEB_SERVER_DETECTED="apache"
    fi
    
    if [ -n "$WEB_SERVER_DETECTED" ]; then
        echo ""
        echo "HTTP Checks ($WEB_SERVER_DETECTED):"
        
        # Try to detect domain from web server configuration or use localhost
        SITE_HOST=""
        
        # Try to get domain from Nginx config
        if [ "$WEB_SERVER_DETECTED" = "nginx" ] && [ -f "/etc/nginx/sites-enabled/wedding" ]; then
            SITE_HOST=$(grep -oP 'server_name\s+\K[^\s;]+' /etc/nginx/sites-enabled/wedding 2>/dev/null | head -1 || echo "")
        fi
        
        # Try to get domain from Apache config
        if [ -z "$SITE_HOST" ] && [ "$WEB_SERVER_DETECTED" = "apache" ] && [ -f "/etc/apache2/sites-enabled/wedding.conf" ]; then
            SITE_HOST=$(grep -oP 'ServerName\s+\K[^\s]+' /etc/apache2/sites-enabled/wedding.conf 2>/dev/null | head -1 || echo "")
        fi
        
        # Fallback to localhost if no domain found
        if [ -z "$SITE_HOST" ] || [ "$SITE_HOST" = "_" ]; then
            SITE_HOST="localhost"
        fi
        
        HTTP_BASE="http://127.0.0.1"
        
        # Frontend
        HTTP_CODE=$(curl -H "Host: $SITE_HOST" -s -o /dev/null -w "%{http_code}" "$HTTP_BASE/" 2>/dev/null || echo "000")
        if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "301" ] || [ "$HTTP_CODE" = "302" ]; then
            pass "Frontend responds (HTTP $HTTP_CODE)"
        else
            fail "Frontend not responding (HTTP $HTTP_CODE)"
        fi

        # Admin panel: canonical admin entry is /admin/; admin.php is a redirect wrapper.
        HTTP_CODE=$(curl -H "Host: $SITE_HOST" -s -o /dev/null -w "%{http_code}" "$HTTP_BASE/admin/" 2>/dev/null || echo "000")
        if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "301" ] || [ "$HTTP_CODE" = "302" ]; then
            pass "Admin panel accessible (HTTP $HTTP_CODE)"
        else
            fail "Admin panel issue (HTTP $HTTP_CODE)"
        fi
        
        # Security: config.json should be blocked
        HTTP_CODE=$(curl -L -H "Host: $SITE_HOST" -s -o /dev/null -w "%{http_code}" "$HTTP_BASE/config.json" 2>/dev/null || echo "000")
        if [ "$HTTP_CODE" = "403" ]; then
            pass "config.json blocked from public access (HTTP $HTTP_CODE)"
        else
            fail "SECURITY: config.json publicly accessible (HTTP $HTTP_CODE)"
        fi
        
        # Security: .sqlite should be blocked
        HTTP_CODE=$(curl -L -H "Host: $SITE_HOST" -s -o /dev/null -w "%{http_code}" "$HTTP_BASE/database.sqlite" 2>/dev/null || echo "000")
        if [ "$HTTP_CODE" = "403" ]; then
            pass "database.sqlite blocked from public access (HTTP $HTTP_CODE)"
        else
            fail "SECURITY: database.sqlite publicly accessible (HTTP $HTTP_CODE)"
        fi
    else
        echo ""
        echo "Skipping HTTP checks (no web server running or curl unavailable)"
    fi
else
    echo ""
    echo "Skipping HTTP checks (curl not available)"
fi

echo ""
echo "=== Summary ==="
echo "Passed: $PASS_COUNT"
echo "Warnings: $WARN_COUNT"
echo "Failed: $FAIL_COUNT"

if [ $FAIL_COUNT -gt 0 ]; then
  echo ""
  echo "DEPLOYMENT FAILED: required checks did not pass."
  exit 1
else
  echo ""
  if [ "$WARN_COUNT" -gt 0 ]; then
    echo "DEPLOYMENT HEALTHY WITH WARNINGS: application is functional but optional media/data is not fully provisioned."
  else
    echo "DEPLOYMENT HEALTHY"
  fi
  exit 0
fi
