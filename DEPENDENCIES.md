# Dependencies — definitive list for production

1) apt packages (install on Ubuntu LTS)

- nginx
- php-fpm
- php-sqlite3
- php-gd
- php-xml
- php-mbstring
- php-curl
- php-zip
- jq
- ca-certificates

Installation example

```bash
apt update
apt install -y nginx php-fpm php-sqlite3 php-gd php-xml php-mbstring php-curl php-zip jq ca-certificates
```

2) PHP extensions (extension package names in Ubuntu)

- sqlite3 (`php-sqlite3`)
- GD (`php-gd`)
- XML (`php-xml`)
- mbstring (`php-mbstring`)
- curl (`php-curl`)
- zip (`php-zip`)

3) Composer dependencies

- chillerlan/php-qrcode (^5.0) — used for QR code generation (refer `composer.json`)

4) Filesystem layout (expected after deploy/install.sh)

- /opt/februandik-web/      → repository copy (private to admin)
  - storage/                → runtime data (database, etc.)
    - storage/data/         → database location (legacy)
  - uploads/                → public media uploads
  - app/                    → application endpoints (served)
  - config/                 → modular config files
  - event.ics               → generated calendar file

Recommended production paths

- SQLite DB (recommended outside webroot): `/var/www/private/database.sqlite`

5) Permissions

- `www-data:www-data` should own:
  - `/opt/februandik-web/storage` (and subdirs)
  - `/opt/februandik-web/uploads` (and subdirs)
  - SQLite DB file (if inside webroot)

- Permissions: directories `750`, files `640` for DB file

6) Required services

- nginx
- php-fpm

7) Optional services

- systemd timers or cron for automated backups (not included)
- monitoring/log rotation (logrotate)
