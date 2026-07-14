#!/usr/bin/env bash
set -euo pipefail

# Create a timestamped tarball containing uploads and database (not git tracked files)
STAMP=$(date -u +%Y%m%dT%H%M%SZ)
OUTDIR="/var/backups/februandik"
mkdir -p "$OUTDIR"

REPO_DIR="/opt/februandik-web"

echo "Backing up runtime data from $REPO_DIR/storage and $REPO_DIR/uploads to $OUTDIR/februandik-runtime-$STAMP.tar.gz"
tar --ignore-failed-read -czf "$OUTDIR/februandik-runtime-$STAMP.tar.gz" -C "$REPO_DIR" storage uploads

echo "Backup complete: $OUTDIR/februandik-runtime-$STAMP.tar.gz"
