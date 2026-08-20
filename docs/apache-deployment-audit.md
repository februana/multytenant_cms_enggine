# Audit Awal Deployment Apache: Source vs Multi-Tenant

## Source of truth yang diperiksa

Repository sumber `februana/webserver_undangan` diperiksa dari checkout pristine branch `main` pada commit `b2ecf80`, bukan dari branch hardening single-tenant sebelumnya. Referensi utama adalah `deploy/install.sh` serta `deploy/templates/apache/apache-http.conf.template` dan `apache-ssl.conf.template`.

Source menggunakan alur Apache + PHP-FPM: install `apache2` dan `php-fpm`, mengaktifkan modul `rewrite headers ssl proxy_fcgi setenvif dav dav_fs auth_basic alias`, mendeteksi socket di `/run/php/*.sock`, mengisi placeholder template, menjalankan `apache2ctl configtest`, lalu `a2ensite`, `a2dissite 000-default.conf`, dan start/reload Apache. Template source memakai `SetHandler "proxy:unix:{{PHP_SOCKET}}|fcgi://localhost/"`, bukan mod_php `application/x-httpd-php`.

## Gap target yang terverifikasi

Target `multytenant_cms_enggine/deploy/install.sh` saat ini sengaja hanya menyiapkan aplikasi. Komentarnya menyatakan installer tidak menginstall package, mengaktifkan modul/site, menjalankan service, atau menulis `/etc/apache2`. Output installer masih meminta operator menerapkan `deploy/apache-catchall.conf.example` secara manual.

Sample target saat ini memakai `SetHandler application/x-httpd-php`, sehingga tidak mengikuti PHP-FPM source path. Sample juga tidak memiliki `ServerName`/`ServerAlias`, upload/webdav PHP execution guards, explicit FastCGI socket, security headers, cache policy, atau integrasi installer.

Target Docker menggunakan Apache image path terpisah dan perlu dipertahankan sebagai deployment container path yang tidak dirusak oleh installer native. Porting yang dirancang akan berlaku untuk native Ubuntu deployment dan tidak mengubah arsitektur CMS, tenant resolution, shared SQLite schema, preset, atau media namespace.

## Prinsip adaptasi

Adaptasi akan mengambil mekanisme source installer/template, tetapi mengganti single-domain `ServerName` dengan canonical catch-all VirtualHost untuk seluruh tenant domain. Installer native akan membuat config secara atomik, mendeteksi PHP-FPM socket secara dinamis, mengaktifkan modul yang diperlukan, menjalankan configtest sebelum perubahan site symlink dan reload, serta tidak menghapus database, tenant, media, atau konfigurasi runtime.

## SSL source dan keputusan adaptasi

Source juga memiliki alur optional Let's Encrypt: HTTP VirtualHost dibuat dan diuji lebih dahulu, Certbot standalone dijalankan bila operator memilih SSL, kemudian template SSL dibuat, diuji, di-enable, dan Apache di-reload. Untuk target multi-tenant di belakang Cloudflare Tunnel, porting tidak akan memaksa Certbot atau mengasumsikan TLS publik harus diterminasi Apache. Installer akan menyediakan HTTP catch-all yang sesuai untuk Cloudflare origin, sementara dukungan local HTTPS tetap dapat menjadi opsi eksplisit bila certificate path dan kebutuhan operator tersedia.

## Gap tambahan pada updater target

`deploy/update.sh` target masih mendefinisikan `REPOSITORY_URL` default ke `git@github.com:februana/webserver_undangan.git`, sehingga updater dapat menarik source repository yang salah. Updater juga masih memiliki migration mode Nginx. Keduanya harus dikoreksi pada pekerjaan ini: default source harus menunjuk repository multi-tenant target, dan deployment path yang didokumentasikan serta diaktifkan harus Apache + PHP-FPM; tidak boleh ada fallback diam-diam ke Nginx.

## Status setelah parity implementation

Gap native installer sudah ditutup secara source-adapted. `deploy/install.sh` sekarang memasang/verifikasi dependency native, menjalankan Composer dan migration, mendeteksi PHP-FPM socket, merender template Apache, mengaktifkan module, menjalankan `apache2ctl configtest`, lalu mengaktifkan site dan start/reload Apache. Data `.env`, database, tenant media, backups, dan WebDAV tidak dihapus; rerun installer divalidasi dengan sentinel runtime.

`deploy/apache-catchall.conf.example` sudah memakai PHP-FPM socket handler, wildcard tenant alias, sensitive-file blocking, dan upload PHP execution prevention. WebDAV tetap tersedia sebagai fitur foundation tetapi menjadi operator-controlled; ketika disabled, Alias/Location WebDAV dihapus dari generated vhost.

`deploy/update.sh` sekarang default ke `https://github.com/februana/multytenant_cms_enggine.git`. Docker tidak diubah karena Dockerfile source dan target byte-identical. Installer target tidak memperkenalkan Nginx. Existing legacy Nginx migration code pada updater belum dipakai sebagai jalur baru dan tetap harus diperlakukan sebagai legacy/out-of-scope pada operasi target Apache.
