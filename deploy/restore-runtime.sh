#!/usr/bin/env bash
set -euo pipefail

if [ "$EUID" -ne 0 ]; then
  echo "Run as root or via sudo." >&2
  exit 2
fi

if [ "$#" -lt 1 ]; then
  echo "Usage: $0 /path/to/februandik-runtime-YYYYMMDDT*.tar.gz" >&2
  exit 2
fi

ARCHIVE="$1"
REPO_DIR="/opt/februandik-web"

if [ ! -f "$ARCHIVE" ]; then
  echo "Archive not found: $ARCHIVE" >&2
  exit 2
fi

tar -xzf "$ARCHIVE" -C "$REPO_DIR"
chown -R www-data:www-data "$REPO_DIR/storage" "$REPO_DIR/uploads"
chmod -R 750 "$REPO_DIR/storage"
chmod -R 750 "$REPO_DIR/uploads"

echo "Restore complete."
