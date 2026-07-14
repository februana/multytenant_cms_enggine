#!/usr/bin/env bash
set -euo pipefail

if [ "$EUID" -ne 0 ]; then
  echo "Run as root or via sudo." >&2
  exit 2
fi

REPO_DIR="/opt/februandik-web"
cd "$REPO_DIR" || { echo "Repo dir $REPO_DIR not found."; exit 1; }

echo "Creating runtime backup before update..."
../deploy/backup-runtime.sh

echo "Pulling latest changes..."
if [ -d .git ]; then
  git fetch --all --prune
  git reset --hard origin/$(git rev-parse --abbrev-ref HEAD)
else
  echo "No .git in $REPO_DIR; skipping git pull.";
fi

echo "Syncing to webroot and restarting services..."
ln -sfn "$REPO_DIR" /var/www/februandik-web
systemctl restart php*-fpm 2>/dev/null || systemctl restart php-fpm 2>/dev/null || true
nginx -t && systemctl reload nginx

echo "Running health check..."
../deploy/health-check.sh || { echo "Health check failed after update!" >&2; exit 1; }

echo "Update complete."
