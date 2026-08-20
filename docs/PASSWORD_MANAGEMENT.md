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

Ketika `HTTP_HOST` belum ditemukan pada `tenants`, middleware hanya dapat melakukan auto-provisioning apabila `UNDANGAN_AUTO_PROVISION` aktif, `REMOTE_ADDR` adalah `127.0.0.1` atau `::1`, dan request membawa header `CF-RAY` serta alamat IP valid pada `CF-Connecting-IP`. Kombinasi ini memastikan request berasal dari daemon Cloudflare Tunnel lokal, bukan akses langsung ke origin dengan Host header arbitrer. Request yang gagal pada validasi ingress menerima `403` dan tidak membuat row tenant.

Setelah validasi berhasil, middleware membuat tenant aktif, menanam konfigurasi awal sepenuhnya ke `tenant_configs`, membuat akun `tenant_admin` dengan username berbasis domain dan password acak, menyimpan hash login serta ciphertext `visible_password`, lalu membuat namespace media `uploads/tenant_{id}/` beserta subdirektorinya. Password tidak dikirimkan ke browser publik; Super Admin dapat melihatnya melalui `/admin/super-admin.php`.

## Two-column password strategy

| Kolom | Isi | Penggunaan |
|---|---|---|
| `password_hash` | Hash one-way dari `password_hash()` | Login melalui `password_verify()` |
| `visible_password` | Ciphertext AES-256-GCM dengan IV dan authentication tag | Didekripsi server-side untuk Super Admin |

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
