#!/bin/sh
set -e

# Non-interactive docker entrypoint for Ubuntu and Armbian deployments

APP_DIR="/var/www/wedding"
DATA_DIR="${UNDANGAN_DATA_DIR:-/var/data}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASS="${ADMIN_PASS:-}"

if [ ! -r "${APP_DIR}/deploy/runtime-directories.sh" ]; then
    echo "Missing runtime directory contract: ${APP_DIR}/deploy/runtime-directories.sh" >&2
    exit 1
fi
. "${APP_DIR}/deploy/runtime-directories.sh"
ensure_runtime_directories "${APP_DIR}"
mkdir -p "${DATA_DIR}"

if [ ! -f "${DATA_DIR}/database.sqlite" ]; then
    touch "${DATA_DIR}/database.sqlite"
fi
if [ ! -f "${DATA_DIR}/config.json" ] && [ -f "${APP_DIR}/config.json" ]; then
    cp "${APP_DIR}/config.json" "${DATA_DIR}/config.json"
fi
if [ ! -f "${DATA_DIR}/guest-links.json" ]; then
    printf '[]\n' > "${DATA_DIR}/guest-links.json"
fi
if [ ! -f "${DATA_DIR}/event.ics" ]; then
    touch "${DATA_DIR}/event.ics"
fi
if [ ! -f "${DATA_DIR}/custom.css" ]; then
    : > "${DATA_DIR}/custom.css"
fi

if [ ! -f "${APP_DIR}/database.sqlite" ]; then
    ln -sf "${DATA_DIR}/database.sqlite" "${APP_DIR}/database.sqlite"
fi
if [ ! -e "${APP_DIR}/event.ics" ]; then
    ln -sf "${DATA_DIR}/event.ics" "${APP_DIR}/event.ics"
fi
if [ ! -e "${APP_DIR}/custom.css" ]; then
    ln -sf "${DATA_DIR}/custom.css" "${APP_DIR}/custom.css"
fi

escape_sed_replacement() {
    printf '%s' "$1" | sed 's/[\\&|]/\\&/g'
}

if [ ! -f "${APP_DIR}/.env" ] && [ -f "${APP_DIR}/.env.example" ]; then
    cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
    if [ -n "${ADMIN_PASS}" ]; then
        escaped_admin_pass=$(escape_sed_replacement "${ADMIN_PASS}")
        sed -i "s|^ADMIN_PASS=.*|ADMIN_PASS=${escaped_admin_pass}|" "${APP_DIR}/.env"
    fi
    if [ -n "${ADMIN_USER}" ]; then
        escaped_admin_user=$(escape_sed_replacement "${ADMIN_USER}")
        sed -i "s|^ADMIN_USER=.*|ADMIN_USER=${escaped_admin_user}|" "${APP_DIR}/.env"
    fi
fi

chown -R www-data:www-data "${APP_DIR}" "${DATA_DIR}"
find "${APP_DIR}" -type d -exec chmod 755 {} +
find "${APP_DIR}" -type f -exec chmod 644 {} +
chmod 600 "${APP_DIR}/.env" 2>/dev/null || true
chmod 775 "${APP_DIR}/uploads" "${APP_DIR}/backups" "${APP_DIR}/webdav"
find "${APP_DIR}/uploads" -type d -exec chmod 775 {} +
chmod 770 "${DATA_DIR}"
chmod 660 "${DATA_DIR}/database.sqlite" "${DATA_DIR}/config.json" "${DATA_DIR}/guest-links.json" "${DATA_DIR}/event.ics" 2>/dev/null || true
chmod 640 "${DATA_DIR}/custom.css" 2>/dev/null || true
exec "$@"
