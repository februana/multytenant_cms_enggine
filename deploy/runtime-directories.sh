#!/bin/sh
# Shared runtime directory contract for native, Docker, and update deployments.
# This file is intentionally POSIX shell so docker/entrypoint.sh can source it.

WEDDING_BUILTIN_PRESETS="dewankl elix rainier archak parang pawiwahan custom"

runtime_upload_directories() {
    runtime_root="$1"
    printf '%s\n' \
        "$runtime_root/uploads" \
        "$runtime_root/uploads/cover" \
        "$runtime_root/uploads/music" \
        "$runtime_root/uploads/gallery" \
        "$runtime_root/uploads/background" \
        "$runtime_root/uploads/love-story" \
        "$runtime_root/uploads/theme-assets"
    for preset in $WEDDING_BUILTIN_PRESETS; do
        printf '%s\n' "$runtime_root/uploads/theme-assets/$preset"
    done
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
