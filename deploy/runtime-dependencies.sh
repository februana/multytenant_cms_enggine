#!/bin/sh
# Shared runtime dependency contract for native install/update/health checks.
# Keep this POSIX shell: deploy scripts source it from bash and health-check from sh.

RUNTIME_PHP_FPM_UPLOAD_MAX="${RUNTIME_PHP_FPM_UPLOAD_MAX:-512M}"
RUNTIME_PHP_FPM_POST_MAX="${RUNTIME_PHP_FPM_POST_MAX:-520M}"

runtime_required_os_packages() {
    printf '%s\n' \
        apache2 apache2-utils php-fpm php-cli php-sqlite3 php-gd php-mbstring php-zip \
        composer imagemagick ffmpeg rsync openssl ca-certificates curl unzip
}

runtime_required_php_extensions() {
    printf '%s\n' sqlite3 pdo_sqlite fileinfo mbstring openssl json
}

runtime_php_fpm_binary() {
    local candidate
    for candidate in php-fpm8.3 php-fpm8.2 php-fpm8.1 php-fpm; do
        if command -v "$candidate" >/dev/null 2>&1; then
            command -v "$candidate"
            return 0
        fi
    done
    return 1
}

runtime_php_fpm_service() {
    local candidate
    for candidate in php8.3-fpm php8.2-fpm php8.1-fpm php-fpm; do
        if command -v systemctl >/dev/null 2>&1 && systemctl is-active --quiet "$candidate" 2>/dev/null; then
            printf '%s' "$candidate"
            return 0
        fi
    done
    return 1
}

runtime_image_processor_available() {
    command -v magick >/dev/null 2>&1 || command -v convert >/dev/null 2>&1 || {
        command -v php >/dev/null 2>&1 && php -r 'exit(function_exists("imagewebp") ? 0 : 1);' >/dev/null 2>&1
    }
}

runtime_verify_commands() {
    local command_name
    for command_name in php composer ffmpeg ffprobe; do
        command -v "$command_name" >/dev/null 2>&1 || return 1
    done
    runtime_image_processor_available || return 1
    runtime_php_fpm_binary >/dev/null 2>&1 || return 1
    return 0
}

runtime_verify_php_extensions() {
    command -v php >/dev/null 2>&1 || return 1
    php -r '
        $required = ["sqlite3", "pdo_sqlite", "fileinfo", "mbstring", "openssl", "json"];
        foreach ($required as $extension) {
            if (!extension_loaded($extension)) exit(1);
        }
    '
}

runtime_install_os_dependencies() {
    if [ "${RUNTIME_DEPENDENCY_DRY_RUN:-0}" = "1" ]; then
        printf '%s\n' 'DRY-RUN: would install required OS packages:'
        runtime_required_os_packages
        return 0
    fi
    command -v apt-get >/dev/null 2>&1 || return 1
    apt-get update -qq || return 1
    # shellcheck disable=SC2046
    DEBIAN_FRONTEND=noninteractive apt-get install -y -qq $(runtime_required_os_packages)
}

runtime_configure_php_fpm_upload_limits() {
    if [ "${RUNTIME_DEPENDENCY_DRY_RUN:-0}" = "1" ]; then
        printf 'DRY-RUN: PHP-FPM upload_max_filesize=%s post_max_size=%s\n' "$RUNTIME_PHP_FPM_UPLOAD_MAX" "$RUNTIME_PHP_FPM_POST_MAX"
        return 0
    fi
    local found=0 fpm_ini
    for fpm_ini in /etc/php/*/fpm/php.ini; do
        [ -f "$fpm_ini" ] || continue
        found=1
        sed -Ei "s|^[;[:space:]]*upload_max_filesize[[:space:]]*=.*$|upload_max_filesize = $RUNTIME_PHP_FPM_UPLOAD_MAX|" "$fpm_ini"
        sed -Ei "s|^[;[:space:]]*post_max_size[[:space:]]*=.*$|post_max_size = $RUNTIME_PHP_FPM_POST_MAX|" "$fpm_ini"
        grep -Eq "^[[:space:]]*upload_max_filesize[[:space:]]*=[[:space:]]*$RUNTIME_PHP_FPM_UPLOAD_MAX[[:space:]]*$" "$fpm_ini" || printf '\nupload_max_filesize = %s\n' "$RUNTIME_PHP_FPM_UPLOAD_MAX" >> "$fpm_ini"
        grep -Eq "^[[:space:]]*post_max_size[[:space:]]*=[[:space:]]*$RUNTIME_PHP_FPM_POST_MAX[[:space:]]*$" "$fpm_ini" || printf 'post_max_size = %s\n' "$RUNTIME_PHP_FPM_POST_MAX" >> "$fpm_ini"
    done
    [ "$found" -eq 1 ]
}

runtime_php_limits_match() {
    local binary="${1:-php}" output upload post
    command -v "$binary" >/dev/null 2>&1 || return 1
    output="$($binary -i 2>/dev/null)" || return 1
    upload="$(printf '%s\n' "$output" | awk -F'=> ' '/^upload_max_filesize[[:space:]]*=>/{print $2; exit}' | awk '{print $1}')"
    post="$(printf '%s\n' "$output" | awk -F'=> ' '/^post_max_size[[:space:]]*=>/{print $2; exit}' | awk '{print $1}')"
    [ "$upload" = "$RUNTIME_PHP_FPM_UPLOAD_MAX" ] && [ "$post" = "$RUNTIME_PHP_FPM_POST_MAX" ]
}

runtime_php_fpm_limits_match() {
    local binary
    binary="$(runtime_php_fpm_binary 2>/dev/null)" || return 1
    runtime_php_limits_match "$binary"
}

runtime_reconcile_dependencies() {
    if runtime_verify_commands && runtime_verify_php_extensions && runtime_php_fpm_limits_match; then
        return 0
    fi
    runtime_install_os_dependencies || return 1
    runtime_configure_php_fpm_upload_limits || return 1
    runtime_verify_commands || return 1
    runtime_verify_php_extensions || return 1
    if [ "${RUNTIME_DEPENDENCY_DRY_RUN:-0}" != "1" ]; then
        runtime_php_fpm_limits_match || return 1
    fi
    return 0
}
