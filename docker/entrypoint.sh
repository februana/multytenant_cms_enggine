#!/bin/sh
set -e

# Non-interactive docker entrypoint for Ubuntu and Armbian deployments

APP_DIR="/var/www/wedding"
DATA_DIR="/var/data"

mkdir -p "${APP_DIR}/uploads/cover" \
         "${APP_DIR}/uploads/music" \
         "${APP_DIR}/uploads/gallery" \
         "${APP_DIR}/uploads/background" \
         "${APP_DIR}/uploads/love-story" \
         "${APP_DIR}/backups" \
         "${DATA_DIR}"

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

if [ ! -f "${APP_DIR}/database.sqlite" ]; then
    ln -sf "${DATA_DIR}/database.sqlite" "${APP_DIR}/database.sqlite"
fi
if [ ! -e "${APP_DIR}/event.ics" ]; then
    ln -sf "${DATA_DIR}/event.ics" "${APP_DIR}/event.ics"
fi

if [ ! -f "${APP_DIR}/.env" ] && [ -f "${APP_DIR}/.env.example" ]; then
    cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
    if [ -n "${ADMIN_PASS}" ]; then
        sed -i "s/^ADMIN_PASS=.*/ADMIN_PASS=${ADMIN_PASS}/" "${APP_DIR}/.env"
    fi
    if [ -n "${ADMIN_USER}" ]; then
        sed -i "s/^ADMIN_USER=.*/ADMIN_USER=${ADMIN_USER}/" "${APP_DIR}/.env"
    fi
fi

chown -R www-data:www-data "${APP_DIR}" "${DATA_DIR}"
find "${APP_DIR}" -type d -exec chmod 755 {} +
find "${APP_DIR}" -type f -exec chmod 644 {} +
chmod 775 "${APP_DIR}/uploads" "${APP_DIR}/backups"
find "${APP_DIR}/uploads" -type d -exec chmod 775 {} +
chmod 770 "${DATA_DIR}"
chmod 660 "${DATA_DIR}/database.sqlite" "${DATA_DIR}/config.json" "${DATA_DIR}/guest-links.json" "${DATA_DIR}/event.ics" 2>/dev/null || true
chmod 640 "${DATA_DIR}/custom.css" 2>/dev/null || true
exec "$@"
