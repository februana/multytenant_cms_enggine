# Deployment Quick Reference

The repository has two supported deployment paths: **Docker Compose** and **native Ubuntu/Linux** through `deploy/install.sh`. The complete operator guide, persistence model, health semantics, backup/restore flow, troubleshooting, and target limitations are documented in [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

## Native Ubuntu/Linux

The native installer deploys the checkout to `/var/www/wedding`, configures Nginx or Apache with PHP-FPM, initializes the shared runtime contract, and preserves the source checkout for later updates. It requires root-capable Ubuntu/Linux plus `rsync`, `openssl`, and Composer:

```bash
cd /path/to/webserver_undangan
sudo bash deploy/install.sh
sudo /var/www/wedding/deploy/health-check.sh
```

For normal operations, use the guarded update and archive scripts:

```bash
sudo /var/www/wedding/deploy/update.sh
sudo /var/www/wedding/deploy/backup.sh
sudo /var/www/wedding/deploy/restore.sh /path/to/backup.tar.gz
```

The updater preserves CMS state, `.env`, the full `uploads/` tree including `uploads/theme-assets/<preset>/`, `event.ics`, WebDAV data, backups, and legacy `storage/` data. The backup and restore scripts validate archives and reject unsafe paths or links.

## Docker Compose

Create `.env` from the tracked example and set a strong administrator password:

```bash
git clone https://github.com/februana/webserver_undangan.git
cd webserver_undangan
cp .env.example .env
chmod 600 .env
# Edit .env and set ADMIN_PASS.
docker compose build
docker compose up -d
docker compose ps
docker compose exec wedding-cms /var/www/wedding/deploy/health-check.sh
```

Docker persists CMS state in `wedding_data`, uploaded media in `wedding_uploads`, backup archives in `wedding_backups`, and optional WebDAV data in `wedding_webdav`. The image and Compose service both expose an HTTP healthcheck against `http://127.0.0.1/`. Do not run `docker compose down -v` unless intentionally resetting a disposable installation.

## Presets and runtime contract

The active built-in preset set is `dewankl`, `rainier`, `archak`, `parang`, `pawiwahan`, `shubh-vivah`, and `yami-buzzy`, with `custom` as the CMS-native builder. `deploy/runtime-directories.sh` is the shared source of truth for preset-scoped Theme Asset directories and required upload namespaces. The Admin panel provides localized visual customization, including supported section backgrounds, fonts, colors, Theme Assets, previews, and reset-to-default behavior.

## Health and security

The health check distinguishes required deployment failures from optional administrator media warnings. It verifies application files, all seven built-in theme adapters, runtime state, writable upload/Theme Asset directories, active preset support, WebP processing, ownership, HTTP reachability, and blocking of `.env`, `config.json`, SQLite, guest links, backups, and WebDAV data. Keep production credentials outside Git and use TLS for public native deployments.

For full details, read [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md), [`BACKUP_RESTORE.md`](BACKUP_RESTORE.md), [`SECURITY.md`](SECURITY.md), and [`docs/ATTRIBUTIONS.md`](docs/ATTRIBUTIONS.md).
