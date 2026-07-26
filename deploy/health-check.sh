#!/usr/bin/env bash
set -euo pipefail

# deploy/health-check.sh
# Validates a production deployment at /var/www/wedding

DEPLOY_DIR="/var/www/wedding"
PASS_COUNT=0
FAIL_COUNT=0

pass() { echo "✓ $1"; ((PASS_COUNT++)) || true; }
fail() { echo "✗ $1"; ((FAIL_COUNT++)) || true; }

echo "=== Deployment Health Check ==="
echo "Target: $DEPLOY_DIR"
echo ""

# Check 1: Directory exists
if [ -d "$DEPLOY_DIR" ]; then
  pass "Deployment directory exists"
else
  fail "Deployment directory missing: $DEPLOY_DIR"
fi

# Check 2: Required files exist
for file in index.php admin.php save.php messages.php gallery.php config.json database.sqlite; do
  if [ -f "$DEPLOY_DIR/$file" ]; then
    pass "File exists: $file"
  else
    fail "Missing file: $file"
  fi
done

# Check 3: Uploads directories exist and are writable
for dir in cover music gallery background; do
  if [ -d "$DEPLOY_DIR/uploads/$dir" ] && [ -w "$DEPLOY_DIR/uploads/$dir" ]; then
    pass "Upload directory writable: uploads/$dir"
  else
    fail "Upload directory issue: uploads/$dir"
  fi
done

# Check 4: Permissions check (config should be 600)
CONFIG_PERMS=$(stat -c %a "$DEPLOY_DIR/config.json" 2>/dev/null || echo "000")
if [[ "$CONFIG_PERMS" == "600" ]] || [[ "$CONFIG_PERMS" == "640" ]]; then
  pass "Config file permissions secure ($CONFIG_PERMS)"
else
  fail "Config file permissions insecure ($CONFIG_PERMS), expected 600 or 640"
fi

# Check 5: Ownership check
OWNER=$(stat -c %U "$DEPLOY_DIR" 2>/dev/null || echo "unknown")
if [ "$OWNER" = "www-data" ]; then
  pass "Directory owned by www-data"
else
  fail "Directory not owned by www-data (owned by $OWNER)"
fi

# Check 6: HTTP checks (if Nginx or Apache is running)
WEB_SERVER_DETECTED=""
if command -v curl &> /dev/null; then
    if pgrep nginx > /dev/null 2>&1; then
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
        
        # Admin panel
        HTTP_CODE=$(curl -H "Host: $SITE_HOST" -s -o /dev/null -w "%{http_code}" "$HTTP_BASE/admin.php" 2>/dev/null || echo "000")
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
echo "Failed: $FAIL_COUNT"

if [ $FAIL_COUNT -gt 0 ]; then
  echo ""
  echo "DEPLOYMENT HAS ISSUES. Review failures above."
  exit 1
else
  echo ""
  echo "DEPLOYMENT HEALTHY"
  exit 0
fi
