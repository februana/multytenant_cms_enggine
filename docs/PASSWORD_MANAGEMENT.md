# Password Management and First-DNS Auto-Provisioning

Implementasi ini menambahkan tiga kemampuan pada aplikasi multi-tenant.

## Super Admin installer

`deploy/install.sh` tidak lagi meminta password Super Admin. Setelah username dimasukkan, installer membuat:

- Password Super Admin acak dengan `openssl rand -hex 16`.
- Kunci enkripsi acak dengan `openssl rand -hex 32`.
- Hash password untuk login pada `users.password_hash`.
- Ciphertext password pada `users.visible_password`.

Password plaintext dicetak satu kali pada ringkasan akhir instalasi. Simpan password tersebut sebelum terminal ditutup.

## Auto-provisioning domain baru

Ketika `HTTP_HOST` belum ditemukan pada `tenants`, middleware memvalidasi FQDN dan, jika `UNDANGAN_AUTO_PROVISION` bukan `0`, membuat tenant aktif. Middleware kemudian membuat konfigurasi tenant dan satu akun `tenant_admin` berusername `admin` dengan password acak delapan karakter. Password tersebut tidak dikirimkan ke browser publik; Super Admin dapat melihatnya melalui `/admin/super-admin.php`.

Auto-provisioning hanya aman jika origin server tidak dapat diakses langsung dengan Host header arbitrer. Batasi origin melalui Cloudflare Tunnel/firewall dan pertahankan `UNDANGAN_AUTO_PROVISION=1` hanya pada deployment yang memang menggunakan jalur tersebut.

## Two-column password strategy

| Kolom | Isi | Penggunaan |
|---|---|---|
| `password_hash` | Hash one-way dari `password_hash()` | Login melalui `password_verify()` |
| `visible_password` | Ciphertext AES-256-CBC dengan IV acak | Didekripsi server-side untuk Super Admin |

Kunci berasal dari `UNDANGAN_PASSWORD_KEY`. Kunci tidak boleh disimpan di dalam database, repository, atau output HTML. Jangan mengganti key pada deployment aktif tanpa migrasi ciphertext karena password lama tidak dapat didekripsi setelah key berubah.

Password change pada CMS memperbarui kedua kolom secara atomik pada akun user yang sedang login. Dengan demikian, password terbaru tetap dapat dilihat Super Admin, sedangkan login tetap menggunakan hash one-way.

## Deployment lama

Tambahkan key pada `.env` dengan mode file `600`, lalu restart Apache:

```bash
sudo sh -c 'printf "UNDANGAN_PASSWORD_KEY=%s\n" "$(openssl rand -hex 32)" >> /var/www/wedding/.env'
sudo chmod 600 /var/www/wedding/.env
sudo systemctl restart apache2
```

Audit deployment memeriksa bahwa `users.visible_password` dan `UNDANGAN_PASSWORD_KEY` tersedia. Jika key hilang, jangan menghapus atau mengganti ciphertext secara manual; lakukan reset password melalui prosedur pemulihan yang menjaga key yang benar.

## Manual reset dan profil Super Admin

Pada `/admin/super-admin.php`, setiap Tenant Admin memiliki action **Reset/Set**. Super Admin dapat memasukkan password custom atau mengosongkannya untuk membuat password acak baru. Action tersebut memverifikasi pasangan `tenant_id` dan `user_id`, membatasi target pada role `tenant_admin`, lalu memperbarui `password_hash` dan `visible_password`.

Halaman `/admin/profile.php` hanya dapat dibuka oleh session dengan role `super_admin`. Halaman ini memungkinkan Super Admin mengganti username dan password sendiri. Password Super Admin tetap disimpan untuk login; nilainya tidak ditampilkan pada dashboard tenant.
