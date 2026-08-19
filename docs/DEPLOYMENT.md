# Installation and Deployment

This document describes the two supported installation paths in the current repository: Docker Compose and native/server deployment through `deploy/install.sh`. Both paths use the same PHP CMS, the same built-in preset contracts, the same global guest system, and the same optional-media semantics.

## Runtime model

The application code is deployed to `/var/www/wedding`. In native mode, runtime files remain in that root by default. In Docker, `UNDANGAN_DATA_DIR=/var/data` moves mutable CMS state to the `wedding_data` volume while `wedding_uploads` stores uploaded media under `/var/www/wedding/uploads`.

Mutable runtime state consists of:

- `config.json` — CMS configuration and active preset.
- `guest-links.json` — global Guest Link Generator records.
- `custom.css` — optional Custom CSS.
- `event.ics` — generated calendar file.
- SQLite database — RSVP and message storage.
- `uploads/cover`, `uploads/music`, `uploads/gallery`, `uploads/background`, and `uploads/love-story` — administrator-provided Wedding Media.
- `uploads/theme-assets/<preset>/` — preset-scoped administrator-provided Theme Assets, kept separate from Gallery and Wedding Media.
- `backups/` — generated backup artifacts.

The installer creates directories and empty state files. It does not invent or download arbitrary wedding media.

## A. Docker installation

### Prerequisites

Install Docker Engine and the Docker Compose plugin. Clone this repository and enter the checkout:

```bash
git clone https://github.com/februana/webserver_undangan.git
cd webserver_undangan
```

Create a local environment file from the tracked example and set a real administrator password. Do not commit `.env`:

```bash
cp .env.example .env
chmod 600 .env
# edit .env and set ADMIN_PASS to a strong value
```

`docker-compose.yml` deliberately refuses to start when `ADMIN_PASS` is absent. It does not contain a shared production password.

### Build and start

```bash
docker compose build
docker compose up -d
```

The container uses PHP 8.3 Apache and installs only the application runtime requirements: GD with JPEG/WebP support, mbstring, PDO SQLite, SQLite3, ZipArchive, and the Composer QR-code dependency. Apache rewrite, headers, and expires modules are enabled. Uploaded files are limited by the application configuration and Apache upload settings.

The entrypoint sources `deploy/runtime-directories.sh` and creates the complete upload contract, including `uploads/theme-assets/` and preset directories for DewanaKL, retired preset, Rainier, Archak, Parang, Pawiwahan, and Custom. It also creates `/var/data/database.sqlite`, `/var/data/config.json`, `/var/data/guest-links.json`, `/var/data/event.ics`, and `/var/data/custom.css` when they do not exist. It preserves existing runtime data on later starts and applies owner/group permissions for `www-data`.

### Access and health check

Open the public invitation at `http://127.0.0.1/` and the admin UI at `http://127.0.0.1/admin/`. Run the container health check:

```bash
docker compose exec wedding-cms /var/www/wedding/deploy/health-check.sh
```

A healthy clean checkout may report:

```text
PASS: application and storage checks
WARNING: optional cover/music/Open Graph media is not provisioned
```

Warnings for optional media do not make an otherwise functional installation fail. Missing required application files, missing runtime state, missing or unwritable upload/Theme Assets directories, unsupported active presets, missing ImageMagick/GD WebP processing, unreadable storage, bad permissions, failed web-server checks, or publicly readable sensitive files remain failures.

### Docker runtime persistence

Do not remove the named volumes when restarting or upgrading:

```bash
docker compose down
docker compose up -d
```

The following command intentionally deletes runtime data and should only be used when resetting a test installation:

```bash
docker compose down -v
```

To update code while retaining CMS data, rebuild and recreate without deleting volumes:

```bash
docker compose build
docker compose up -d --force-recreate
```

## B. Native/server installation

### Prerequisites

The native installer requires a root-capable Ubuntu host, a DNS name if a public domain is desired, and these commands before it starts:

- `rsync`
- `openssl`
- `composer`

The installer then installs the selected web server and the PHP runtime packages: PHP-FPM, PHP CLI, SQLite/PDO SQLite, GD, mbstring, ZipArchive, cURL, and supporting utilities. Composer installs the application dependency from `composer.lock`.

### Fresh installation

Run from the Git checkout. Do not create `/var/www/wedding` manually and do not run the installer from a directory containing untrusted files:

```bash
cd /path/to/webserver_undangan
sudo bash deploy/install.sh
```

The installer prompts for:

1. Nginx or Apache.
2. Primary domain.
3. Apache SSL/Let's Encrypt choices when Apache is selected.
4. Optional WebDAV configuration when Apache is selected.

It deploys the repository to `/var/www/wedding`, sources the shared `deploy/runtime-directories.sh` contract, creates all upload/storage directories and preset-scoped Theme Assets directories, initializes SQLite/config/guest-link/ICS/Custom CSS files when absent, creates `.env` with a generated administrator password when needed, generates the selected web-server configuration, and runs final server/connectivity checks. The source checkout is not deleted.

If a fresh deployment requires an empty config because `config.json` is absent, the installer generates it through the repository's `config_defaults()` function rather than writing an unrelated minimal stub.

### Native health check

After installation, run:

```bash
sudo /var/www/wedding/deploy/health-check.sh
```

The health check validates required root files, all six built-in theme adapters, runtime state, upload and preset-scoped Theme Assets directories, the active preset, ImageMagick or PHP GD WebP capability, permissions, ownership, web-server configuration, HTTP/admin reachability, and blocking of `config.json`/SQLite from public access. It emits `PASS`, `WARNING`, and `FAIL` statuses and exits non-zero only when a required check fails.

For a non-standard runtime data location, use the same environment variables used by the application:

```bash
sudo env UNDANGAN_DATA_DIR=/var/data UNDANGAN_DB_PATH=/var/data/database.sqlite \
  /var/www/wedding/deploy/health-check.sh
```

### Updating an existing installation

`deploy/update.sh` preserves `config.json`, `custom.css`, `guest-links.json`, `database.sqlite`, `.env`, `event.ics`, the complete `uploads/` tree including preset-scoped Theme Assets, WebDAV data, backups, and storage before replacing application code. It then creates any missing runtime directories through the same shared contract without replacing user media:

```bash
sudo /var/www/wedding/deploy/update.sh
```

Use `deploy/backup.sh` before a planned update and `deploy/restore.sh` to restore a generated archive:

```bash
sudo /var/www/wedding/deploy/backup.sh
sudo /var/www/wedding/deploy/restore.sh /path/to/backup.zip
```

## Media lifecycle

A clean checkout contains only `.gitkeep` files in the upload roots. Preset-scoped Theme Assets directories are created by `deploy/install.sh`, `deploy/update.sh`, and `docker/entrypoint.sh`; they do not require demo media. The checkout does **not** contain `uploads/cover/cover.jpg`, `music/lagu.mp3`, or a default Open Graph image. These references were removed from the shipped defaults because they were sample/deployment data, not required application code.

| Media | Default state | Required? | Provisioning path |
|---|---|---:|---|
| Cover image | Empty | No | Upload through Admin media controls or set a valid `media.cover` path. |
| Music | Empty and disabled by missing path | No | Upload an allowed audio file, set `media.music`, and enable the relevant preset music option/section. |
| Open Graph image | Empty | No | Upload/choose a cover and set `site.open_graph_image` when social preview imagery is desired. |
| Gallery | Empty | No | Upload images through the Admin gallery flow. |
| Love-story video | Empty | No | Upload a valid supported video and set `media.love_story_video` for DewanaKL. |

When optional media is empty, the application remains usable and suppresses optional media behavior. The health check reports `WARNING: ... optional/user-provided` rather than pretending that a file exists. When an administrator configures a local optional path that does not exist or is unreadable, the health check reports a warning naming that path. It does not silently convert the condition into a required-asset failure.

To provision replacement media, use the Admin upload controls or place a file in the corresponding upload directory with ownership/read permissions appropriate for `www-data`, then update the matching `config.json` field through Admin. Never copy third-party copyrighted sample media solely to silence a warning.

## Configuration and presets

The active preset is `theme.theme_preset` in `config.json`. Built-in presets retain their source template presentation and have intentionally different capability sets. Guest Link Generator and personalized Guest Name are global CMS capabilities and are available regardless of the active preset. Custom mode is the full CMS-native builder.

See [`ARCHITECTURE.md`](ARCHITECTURE.md), [`ATTRIBUTIONS.md`](ATTRIBUTIONS.md), and [`../README.md`](../README.md) for the template ownership and provenance model.

## Troubleshooting

If Docker refuses to start, check that `.env` exists and contains a non-empty `ADMIN_PASS`; then inspect `docker compose logs wedding-cms`. If health-check reports a missing required runtime file, inspect `/var/data` in Docker or `/var/www/wedding` in native mode and rerun the entrypoint/installer initialization path. If a media warning appears, provide administrator media rather than creating a dummy file. If the admin is inaccessible, verify the web-server status, the generated virtual host, `admin.php`/`admin/`, and the health-check's HTTP section. If a config or database security check fails, verify that `.htaccess`/Apache rules or the generated Nginx configuration block direct access to sensitive files.
