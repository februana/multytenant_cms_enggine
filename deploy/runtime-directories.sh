#!/bin/sh
# Shared runtime directory contract for native, Docker, and update deployments.
# This file is intentionally POSIX shell so docker/entrypoint.sh can source it.

WEDDING_BUILTIN_PRESETS="dewankl rainier archak parang pawiwahan shubh-vivah yami-buzzy custom"

runtime_upload_directories() {
    runtime_root="$1"
    # Tenant-specific subdirectories are created by tenant_upload_dir() on demand.
    printf '%s\n' "$runtime_root/uploads"
}

runtime_required_directories() {
    runtime_root="$1"
    runtime_upload_directories "$runtime_root"
    printf '%s\n' "$runtime_root/backups" "$runtime_root/webdav"
}

ensure_runtime_directories() {
    runtime_root="$1"
    runtime_required_directories "$runtime_root" | while IFS= read -r directory; do
        [ -d "$directory" ] || mkdir -p "$directory"
    done
}
