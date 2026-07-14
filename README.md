# Februandik Web (Undangan)

Panduan lengkap untuk men-deploy repository ini sebagai aplikasi web PHP sederhana di server Ubuntu.

## Ringkasan

Repositori ini berisi:
- `app/` : kode aplikasi PHP, aset statis, dan halaman undangan.
- `deploy/` : skrip install/update/backup/restore/health-check.
- `storage/` : direktori runtime yang diabaikan oleh Git, untuk database dan upload.
- `.env.example` : contoh konfigurasi lingkungan yang tidak boleh dikomit langsung.

## Persyaratan server

- Ubuntu 20.04+ atau distribusi Debian-based setara
- Akses root atau sudo
- Nginx
- PHP-FPM 8.x dan ekstensi:
  - `php-sqlite3`
  - `php-gd`
  - `php-xml`
  - `php-mbstring`
  - `php-curl`
  - `php-zip`
- `jq` (digunakan oleh `deploy/health-check.sh`)
- `rsync`, `tar`, `curl`

## Instalasi

1. Clone repository ke mesin deploy (bukan langsung ke webroot):

```bash
git clone https://github.com/februana/webserver_undangan.git \
  /opt/webserver_undangan-source

cd /opt/webserver_undangan-source
```

2. Buat file environment untuk server dan jangan pernah commit file ini:

```bash
cp .env.example ../.env
nano ../.env
```

Pastikan variabel berikut diisi:
- `APP_URL` menunjuk ke base URL publik
- `ADMIN_PASS` harus diisi dan tidak boleh menggunakan nilai default
- `UNDANGAN_DB_PATH` bisa diarahkan ke `storage/data/database.sqlite` atau ke path lain di luar webroot
- `uploads/` adalah direktori publik untuk media yang diunggah dan juga diabaikan oleh Git

3. Jalankan installer:

```bash
sudo bash deploy/install.sh
```

Skrip akan:
- memasang Nginx dan PHP-FPM
- membuat direktori aplikasi dan runtime
- menulis konfigurasi Nginx dengan socket PHP-FPM yang terdeteksi
- membuat symlink webroot ke `/var/www/februandik-web`
- mengarahkan fallback frontend ke `app/index.php` sebagai entrypoint kanonik

4. Setelah install selesai, edit `../.env` jika diperlukan dan jalankan health check:

```bash
sudo deploy/health-check.sh
```

## Update aplikasi

Untuk memperbarui kode dari Git dan me-reload layanan:

```bash
sudo /opt/februandik-web/deploy/update.sh
```

## Backup dan restore runtime data

Backup menyimpan isi `storage/` dan `uploads/` termasuk database dan file upload:

```bash
sudo /opt/februandik-web/deploy/backup-runtime.sh
```

Restore data runtime:

```bash
sudo /opt/februandik-web/deploy/restore-runtime.sh /path/to/februandik-runtime-YYYYMMDDT...tar.gz
```

## Health check

Jalankan pemeriksaan kesehatan lokal:

```bash
sudo /opt/februandik-web/deploy/health-check.sh
```

## Keamanan

- Jangan commit `.env`, `storage/`, `database.sqlite`, backup, atau sertifikat.
- `deploy/` dan `storage/` diblokir oleh konfigurasi Nginx.
- `ADMIN_PASS` harus diatur di `.env`.
- `storage/` diatur agar dimiliki `www-data:www-data` dengan permission `750`.
- Jika `UNDANGAN_DB_PATH` diarahkan ke lokasi lain, pastikan database tidak berada di tempat yang dapat diakses langsung melalui web.

## Struktur repositori

- `app/` : aset frontend dan endpoint PHP yang dijalankan from within the application directory when configured as the webroot
- `deploy/` : skrip instalasi dan pemeliharaan server
- `storage/` : runtime data yang diabaikan oleh Git
- `.env.example` : contoh konfigurasi environment
- `README.md` : dokumentasi ini

## Database configuration (UNDANGAN_DB_PATH)

This application uses a single SQLite database file. To support migration and deployments, the application resolves the database path in the following priority (highest → lowest):

1. `UNDANGAN_DB_PATH` environment variable (absolute or relative path)
2. Legacy path: `app/storage/data/database.sqlite` — used only if the file exists and is readable
3. Fallback: `app/database.sqlite`

## Configuration (modular)

The application now supports a modular configuration layout stored under a top-level `config/` directory. This change is backward-compatible and is rolled out incrementally.

- Current runtime source: `config/site.json` is used if present and valid. If not present, the application falls back to the legacy `config.json` file.
- Other files under `config/` (for example `theme.json`, `sections.json`, `seo.json`) are present as preparation-only and do not affect runtime until the loader is extended in a later phase.

Migration guidance (non-destructive):

1. Keep `config.json` in place as long as you need backward compatibility.
2. Create `config/` and add `site.json` (a copy of your current `config.json`) — this is sufficient for the app to pick up the modular config.
3. Optionally populate `theme.json`, `sections.json`, and `seo.json` with subsets for future use; these will be consumed in a later roadmap phase.
4. Validate the JSON files (e.g. `python3 -m json.tool config/site.json`) and confirm the app behavior before removing `config.json`.

Rollback: if `config/site.json` contains errors or you observe differences, restore the previous `config.json` file and remove or fix `config/site.json` to revert to legacy behavior.

See `deploy/CONFIG_MIGRATION.md` for step-by-step migration and testing instructions.
Recommendations

- Place the SQLite database outside the webroot (for example `/var/www/private/database.sqlite`) and set `UNDANGAN_DB_PATH` to that path.
- Ensure the webserver/PHP-FPM user has read/write access to the directory and file.

Example export (temporary for current shell):

```bash
export UNDANGAN_DB_PATH=/var/www/private/database.sqlite
```

Add this variable to your deployment environment (systemd unit, container env, or shared-hosting panel) rather than committing it into the repo.

Backward compatibility

- Existing installations that used `app/storage/data/database.sqlite` will continue to work: the application will prefer that file if present.
- If that legacy file is absent and `UNDANGAN_DB_PATH` is not set, the application will create and use `app/database.sqlite`.

Migration note

- To migrate an existing legacy DB into a new canonical location, copy the file and set `UNDANGAN_DB_PATH` to point to it; DO NOT modify schema or run migrations — the app uses the same tables and schema.

Troubleshooting

- Permission denied: ensure ownership and permissions are correct:

```bash
sudo chown www-data:www-data /var/www/private/database.sqlite
sudo chmod 640 /var/www/private/database.sqlite
```

- Database not found: verify `UNDANGAN_DB_PATH` and look for legacy path `app/storage/data/database.sqlite` or fallback `app/database.sqlite`.

For full deploy examples (Nginx, Apache, Docker, shared hosting) and migration steps see `deploy/DB_DEPLOY.md`.

## Catatan

Untuk SSL, gunakan Certbot atau penyedia lain secara manual setelah Nginx terpasang dan DNS `februandik.duckdns.org` menunjuk ke server.
