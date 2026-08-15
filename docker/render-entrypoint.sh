#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/html"
DATA_DIR="${UNDANGAN_DATA_DIR:-/var/data}"
PORT="${PORT:-10000}"

mkdir -p "$DATA_DIR"

# Seed mutable files into the data directory on first startup. The PHP
# Render bootstrap points the application directly at these paths, avoiding
# symlinks for files that are atomically replaced by the CMS.
seed_file() {
    local name="$1"
    local source="$APP_DIR/$name"
    local target="$DATA_DIR/$name"

    if [ ! -e "$target" ]; then
        if [ -e "$source" ] && [ ! -L "$source" ]; then
            cp -a "$source" "$target"
        else
            : > "$target"
        fi
    fi
}

persist_dir() {
    local name="$1"
    local source="$APP_DIR/$name"
    local target="$DATA_DIR/$name"

    mkdir -p "$target"
    rm -rf "$source"
    ln -s "$target" "$source"
}

seed_file "config.json"
seed_file "database.sqlite"
seed_file "guest-links.json"
seed_file "custom.css"
seed_file "event.ics"

persist_dir "uploads"
persist_dir "backups"
persist_dir "webdav"

# Required media subdirectories for a fresh Render instance.
for dir in cover music gallery background love-story; do
    mkdir -p "$DATA_DIR/uploads/$dir"
done

# Apache must listen on Render's externally routed port.
cat > /etc/apache2/ports.conf <<EOF
Listen ${PORT}
EOF

sed -i "s#\${APACHE_PORT}#${PORT}#g" /etc/apache2/sites-available/000-default.conf

chown -R www-data:www-data "$DATA_DIR" "$APP_DIR"
chmod 755 "$DATA_DIR" "$DATA_DIR/uploads" "$DATA_DIR/backups" "$DATA_DIR/webdav"
chmod 600 "$DATA_DIR/config.json" "$DATA_DIR/database.sqlite" "$DATA_DIR/guest-links.json"
chmod 644 "$DATA_DIR/custom.css" "$DATA_DIR/event.ics"

exec "$@"
