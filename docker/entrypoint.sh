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

if [ ! -f "${APP_DIR}/database.sqlite" ]; then
    ln -sf "${DATA_DIR}/database.sqlite" "${APP_DIR}/database.sqlite"
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
chmod -R 755 "${APP_DIR}"
chmod 777 "${APP_DIR}/uploads" "${APP_DIR}/backups" "${DATA_DIR}"
chmod 666 "${DATA_DIR}/database.sqlite" 2>/dev/null || true

exec "$@"
