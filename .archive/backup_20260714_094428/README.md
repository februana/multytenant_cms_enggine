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

- `app/` : aset frontend dan endpoint PHP yang dijalankan dari webroot
- `deploy/` : skrip instalasi dan pemeliharaan server
- `storage/` : runtime data yang diabaikan oleh Git
- `.env.example` : contoh konfigurasi environment
- `README.md` : dokumentasi ini

## Catatan

Untuk SSL, gunakan Certbot atau penyedia lain secara manual setelah Nginx terpasang dan DNS `februandik.duckdns.org` menunjuk ke server.
