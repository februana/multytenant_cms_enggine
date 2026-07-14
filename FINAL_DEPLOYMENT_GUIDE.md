# Final Deployment Guide — Production-ready

Target: Fresh Ubuntu LTS VM (20.04/22.04/24.04)

Prerequisites (run as root or sudo)

1. System update

```bash
apt update && apt upgrade -y
```

2. Install required packages (see DEPENDENCIES.md for full list)

```bash
apt install -y nginx php-fpm php-sqlite3 php-gd php-xml php-mbstring php-curl php-zip jq ca-certificates
```

3. Clone repository and prepare directories

```bash
git clone <repo-url> /opt/februandik-web
cd /opt/februandik-web
```

4. Create `.env` outside webroot (one level up) and populate required vars

```bash
cp .env.example ../.env
nano ../.env
# Set ADMIN_PASS and optionally UNDANGAN_DB_PATH
```

Notes:
- `UNDANGAN_DB_PATH` can be an absolute path outside webroot (recommended), e.g. `/var/www/private/database.sqlite`.
- Ensure `.env` is owned by root and NOT accessible via webserver.

5. Run installer script

```bash
sudo ./deploy/install.sh
```

What the installer does
- Installs system packages (nginx, php-fpm, required extensions)
- Copies `app/` into `/opt/februandik-web` and sets up runtime directories
- Creates `storage/` and `uploads/` under repo and sets ownership to `www-data`
- Installs an Nginx site config and reloads services

6. Verify services and permissions

```bash
# Verify nginx
systemctl status nginx

# Verify php-fpm
systemctl status php-fpm

# Ensure runtime dirs are writable by www-data
ls -ld /opt/februandik-web/storage /opt/februandik-web/uploads
```

7. Health check

```bash
sudo /opt/februandik-web/deploy/health-check.sh
```

8. Common troubleshooting
- If the site returns 500: check `/var/log/nginx/februandik.error.log` and PHP-FPM logs.
- If SQLite permissions issue: ensure the DB file and its directory are owned by `www-data:www-data` and have `640` permissions.

9. Rollback
- To rollback config migration, remove `config/site.json` and restart PHP-FPM; app will fallback to `config.json`.

10. Final notes
- Do NOT commit `.env` or runtime data.
- Place the SQLite DB outside webroot and update `UNDANGAN_DB_PATH` in `.env` for production.
