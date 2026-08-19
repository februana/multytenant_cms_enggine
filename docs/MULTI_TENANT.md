# Pure Multi-Tenant Deployment

Dokumen ini menjelaskan refaktor aplikasi menjadi **pure multi-tenant** dengan satu proses aplikasi, satu Apache, satu Cloudflare Tunnel, satu database SQLite, dan satu shared schema. Pemilihan tenant tidak berasal dari parameter URL atau input browser, melainkan dari `Host` header yang diteruskan oleh Cloudflare.

## Stack yang ditemukan

Repository ini adalah aplikasi **PHP tanpa framework aplikasi besar** dengan ekstensi SQLite3 dan penyimpanan konfigurasi lama berbasis JSON. Tabel database lama hanya `tamu`, sedangkan konfigurasi undangan, CSS, event ICS, dan guest links sebelumnya berada di berkas runtime. Refaktor ini mempertahankan kompatibilitas berkas tersebut sebagai mirror migrasi, tetapi sumber tenant-aware utamanya sekarang berada di SQLite.

| Area | Implementasi | Status setelah refaktor |
|---|---|---|
| Web runtime | Apache + PHP module, satu site catch-all | Semua hostname masuk ke satu DocumentRoot |
| Tenant resolution | Normalisasi `HTTP_HOST` lalu lookup `tenants.domain` | Domain tidak terdaftar atau `suspended` menerima 404 |
| Database | SQLite shared database/shared schema | Semua tabel bisnis tenant-aware memiliki `tenant_id` |
| Konfigurasi | `tenant_configs.config_json` | Setiap tenant memiliki konfigurasi terpisah |
| Guest links | `guest_links` | Diisolasi dengan `tenant_id` |
| RSVP | `tamu` | Foreign key dan filter tenant otomatis |
| Authentication | `users` + PHP session | Session memuat `role`, `tenant_id`, dan domain |
| Super Admin | `admin/super-admin.php` | Membuat tenant, membuat tenant admin, dan suspend/activate |

## Skema database

Migrasi eksplisit tersedia di [`database/migrations/001_multi_tenant.sql`](../database/migrations/001_multi_tenant.sql). Runtime bootstrap di `config.php` bersifat idempoten dan juga menangani kasus SQLite lama yang memerlukan table rebuild untuk menambahkan foreign key.

```sql
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS tenants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    domain TEXT NOT NULL UNIQUE COLLATE NOCASE,
    status TEXT NOT NULL DEFAULT 'active'
        CHECK (status IN ('active', 'suspended')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER NULL REFERENCES tenants(id) ON DELETE CASCADE,
    username TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('super_admin', 'tenant_admin')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (tenant_id, username)
);

CREATE TABLE IF NOT EXISTS tenant_configs (
    tenant_id INTEGER PRIMARY KEY REFERENCES tenants(id) ON DELETE CASCADE,
    config_json TEXT NOT NULL,
    custom_css TEXT NOT NULL DEFAULT '',
    event_ics TEXT NOT NULL DEFAULT '',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS guest_links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    guest_name TEXT NOT NULL,
    invitation_url TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tamu (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    nama TEXT NOT NULL,
    status TEXT NOT NULL,
    ucapan TEXT,
    visible INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

Untuk instalasi normal, jangan menjalankan potongan SQL secara manual terhadap database produksi apabila `database.sqlite` sudah berisi data lama. Gunakan `deploy/install.sh`, karena bootstrap PHP akan membuat tenant utama, mengisi `tenant_id` pada RSVP lama, membangun ulang tabel `tamu` bila diperlukan, dan menyalin konfigurasi lama ke `tenant_configs`.

## Alur routing dan isolasi

Setiap request publik melewati `current_tenant(true)`. Fungsi tersebut menormalisasi `HTTP_HOST`, menghapus port, menurunkan huruf hostname, kemudian mencari domain aktif pada tabel `tenants`. Nilai `tenant_id` yang dihasilkan server dipakai untuk membaca konfigurasi dan RSVP. Tidak ada endpoint publik yang menerima `tenant_id` dari client.

> **Invariant keamanan:** `tenant_id` untuk operasi tenant admin selalu berasal dari session yang telah diverifikasi dan dibandingkan dengan `Host` header pada request yang sedang berjalan.

| Request | Pemeriksaan | Operasi data |
|---|---|---|
| `/` | Host harus cocok dengan tenant aktif | Baca `tenant_configs` tenant tersebut |
| `/messages.php` | Host harus cocok | `SELECT ... FROM tamu WHERE tenant_id = :tenant_id` |
| `/save.php` RSVP | Host harus cocok | `INSERT INTO tamu (tenant_id, ...)` dengan ID server |
| Login CMS | Host harus cocok dengan tenant aktif | Cari user global atau user tenant terkait |
| CMS tenant admin | Role dan `session.tenant_id` harus cocok dengan Host | Semua konfigurasi dan RSVP memakai tenant context |
| Super Admin | Role `super_admin` dengan `tenant_id NULL` | Dapat melihat lintas tenant pada operasi administratif |

Password disimpan sebagai hash `password_hash()`. Session setelah login memuat `user_id`, `username`, `role`, `tenant_id`, `tenant_domain`, `last_activity`, dan CSRF token. Session tenant admin akan ditolak apabila dibawa ke hostname tenant lain.

## Instalasi Apache catch-all

Jalankan installer dari checkout repository:

```bash
sudo bash deploy/install.sh
```

Installer meminta **Main Domain** untuk tenant Super Admin, username, dan password. Installer tidak membuat VirtualHost per tenant, tidak membuat `ServerName`, dan tidak menjalankan banyak instance. File Apache yang dibuat adalah `/etc/apache2/sites-available/000-default.conf` dengan bentuk inti berikut:

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/wedding

    <Directory /var/www/wedding>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Cloudflare dapat mengarahkan beberapa custom domain ke Tunnel yang sama. Untuk menambah tenant, buka `/admin/super-admin.php` pada domain utama atau masukkan tenant menggunakan dashboard tersebut. Setiap domain baru harus mempunyai DNS/Cloudflare route ke Tunnel yang sama, kemudian dibuat aktif di tabel `tenants`.

```sql
INSERT INTO tenants (domain, status)
VALUES ('couple-a.com', 'active');
```

Cara yang dianjurkan adalah menggunakan Super Admin Dashboard karena dashboard juga membuat `tenant_configs` dan, bila diisi, akun `tenant_admin`.

## Audit deployment

Jalankan audit setelah instalasi:

```bash
sudo /var/www/wedding/deploy/audit.sh --host example.com
```

Audit memeriksa konfigurasi Apache, port 80, keberadaan `AllowOverride All`, ketiadaan `ServerName`/`ServerAlias` pada catch-all, tabel shared schema, foreign key `tenant_id`, tenant aktif, dan respons aplikasi dengan `Host: example.com`.

| Check | Hasil yang diharapkan |
|---|---|
| `apache2ctl configtest` | `Syntax OK` |
| Apache service | Aktif |
| Listener | Port 80 terbuka |
| Catch-all | `<VirtualHost *:80>` tanpa `ServerName` dan `ServerAlias` |
| Rewrite | `AllowOverride All` dan `mod_rewrite` aktif |
| Schema | `tenants`, `users`, `tenant_configs`, `guest_links`, `tamu` tersedia |
| Foreign keys | `tenant_id` pada tabel bisnis mengarah ke `tenants(id)` |
| Host routing | Host aktif menghasilkan HTTP 200/301/302, host tidak terdaftar menghasilkan 404 |

## Catatan operasional

Backup database berisi seluruh tenant dan karena itu dibatasi untuk Super Admin. Tenant admin tidak boleh diberi akses ke endpoint backup/restore. Database SQLite sebaiknya disimpan di lokasi yang tidak dapat diunduh publik dan `.env` harus memiliki mode `600`.

Arsitektur ini sengaja tidak memakai Docker per tenant, PM2 cluster, atau banyak proses aplikasi. Apache tetap menjadi satu entry point, SQLite tetap shared, dan pemisahan dilakukan melalui foreign key, session authorization, serta query scope di server.

## Password management dan auto-provisioning

Installer tidak lagi meminta password Super Admin. Setelah domain utama dan username ditentukan, installer membuat password acak menggunakan `openssl rand -hex 16`, membuat `UNDANGAN_PASSWORD_KEY` acak menggunakan `openssl rand -hex 32`, menyimpan hash password ke database, dan mencetak password plaintext tepat pada ringkasan akhir instalasi. Password tersebut harus disimpan oleh operator sebelum terminal ditutup.

Saat hostname FQDN baru mencapai aplikasi melalui Apache catch-all, `current_tenant()` memvalidasi hostname dan membuat tenant aktif beserta `tenant_configs` dan akun `tenant_admin` default `admin`. Password Tenant Admin dibuat acak sepanjang delapan karakter dan tidak pernah dikirimkan sebagai respons publik. Password tersedia untuk Super Admin melalui dashboard.

Kolom password menggunakan dua representasi dengan tujuan berbeda:

| Kolom | Fungsi |
|---|---|
| `password_hash` | Verifikasi login dengan `password_verify()`; bersifat one-way |
| `visible_password` | Ciphertext AES-256-CBC yang dapat didekripsi hanya dengan `UNDANGAN_PASSWORD_KEY` pada server |

Ketika akun yang sedang login mengganti password melalui **Pengaturan CMS**, controller memperbarui kedua kolom tersebut. Dashboard Super Admin mengambil ciphertext Tenant Admin dan mendekripsinya hanya pada server-side rendering. Plaintext tidak disimpan langsung pada database.

> **Catatan keamanan penting:** kemampuan Super Admin melihat password adalah pengecualian keamanan yang diminta oleh requirement bisnis. Karena itu `UNDANGAN_PASSWORD_KEY` harus dirahasiakan, `.env` harus bermode `600`, backup database harus dibatasi kepada Super Admin, dan akses origin Apache sebaiknya dibatasi sehingga hanya Cloudflare Tunnel yang dapat menjangkaunya. Jika `UNDANGAN_PASSWORD_KEY` hilang, password yang tersimpan tidak dapat dipulihkan dan harus di-reset melalui prosedur pemulihan.

Untuk deployment lama, tambahkan key secara aman sebelum memakai dashboard:

```bash
sudo sh -c 'printf "UNDANGAN_PASSWORD_KEY=%s\\n" "$(openssl rand -hex 32)" >> /var/www/wedding/.env'
```

Gunakan format assignment yang benar, misalnya `UNDANGAN_PASSWORD_KEY=<nilai-acak>`, kemudian restart Apache. Jangan mengganti key tanpa rencana migrasi karena ciphertext lama tidak lagi dapat didekripsi.
