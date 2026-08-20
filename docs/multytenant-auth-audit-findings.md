# Laporan Final Audit Otorisasi Multi-Tenant

**Repository:** [`februana/multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine)  
**Branch:** `fix/multitenant-super-admin-authorization`  
**Baseline audit:** `main` pada commit `613d378`  
**Status:** **READY TO MERGE**, setelah review PR dan deployment migration terbaru.

## Ringkasan eksekutif

Audit ini dilakukan ulang pada repository multi-tenant yang benar, yaitu `februana/multytenant_cms_enggine`. Repository single-tenant `webserver_undangan` dan PR hardening sebelumnya tidak dijadikan sumber implementasi. Fokus audit mencakup resolusi tenant berbasis host, session authentication, role model, controller mutation, menu Super Admin, backup/restore, dan audit trail.

Sebelum perbaikan, session admin hanya dipercaya dari nilai role dan tenant yang tersimpan di session. Mutation endpoint AJAX hanya memeriksa session admin yang valid, sementara menu backup/restore tampil untuk semua role karena hanya memakai resolver capability global. Perubahan credential tidak meminta password saat ini, logout menggunakan GET, halaman Super Admin menampilkan password tenant yang dapat didekripsi, dan belum tersedia audit log terstruktur.

Perbaikan sekarang mempertahankan model canonical `tenants`, `users`, `tenant_configs`, serta namespace media `uploads/tenant_<id>/`. Session setiap request direvalidasi terhadap row user di database, mutation melewati action allowlist, perubahan credential memerlukan current-password re-authentication, password baru mengikuti panjang 12–128 karakter, session dirotasi setelah perubahan, logout memakai POST+CSRF, backup/restore hanya terlihat oleh Super Admin, dan operasi istimewa dicatat di `audit_logs`.

## Model arsitektur dan otoritas

| Area | Model yang dipakai | Keputusan audit |
|---|---|---|
| Resolusi tenant | Domain request dinormalisasi lalu dicari pada `tenants.domain` | Tidak menerima `tenant_id` dari browser untuk menentukan tenant aktif. |
| Tenant Admin | `users.tenant_id = tenants.id`, `role = tenant_admin` | Session harus cocok dengan user database, tenant aktif, dan host request. |
| Super Admin | `users.tenant_id IS NULL`, `role = super_admin` | Dapat mengelola control plane lintas tenant, tetapi tetap memakai re-authentication untuk operasi berisiko. |
| Konfigurasi | `tenant_configs.tenant_id` | `load_config()` dan `save_config()` tetap tenant-scoped. |
| RSVP dan link tamu | `tamu.tenant_id` dan `guest_links.tenant_id` | Data tenant tidak boleh tercampur pada controller public maupun admin. |
| Media | `uploads/tenant_<id>/{cover,gallery,background,love-story,music,theme-assets}` | Path containment dan referensi canonical tetap menjadi batas penyimpanan. |
| Preset/RBAC UI | Theme contract menentukan capability preset; role policy menentukan akses control plane | Hidden menu bukan satu-satunya proteksi; controller juga memeriksa action. |

## Temuan sebelum perbaikan dan statusnya

| ID | Temuan | Dampak | Status |
|---|---|---|---|
| P1 | Session tidak mengambil ulang row user dari database | Role/username atau keberadaan user yang berubah tidak segera membatalkan session | **Ditutup** melalui `current_admin_user_record()` dan revalidation pada `session_admin_is_valid()`. |
| P1 | `app/save.php` menerima mutation admin hanya berdasarkan session | Action yang tidak semestinya dapat dicoba langsung melalui URL endpoint | **Ditutup** dengan `admin_action_is_authorized()` dan allowlist action. |
| P1 | Perubahan credential tanpa current-password | Session yang dicuri dapat mengganti username/password | **Ditutup** pada settings tenant dan profil Super Admin. |
| P1 | Password tenant minimal 6–8 karakter dan ditampilkan di daftar Super Admin | Risiko disclosure credential dan password lemah | **Ditutup** dengan policy 12–128 karakter dan penghapusan render `visible_password` dari daftar tenant. |
| P1 | Logout melalui GET | State session dapat diubah oleh request pihak ketiga | **Ditutup** dengan logout POST+CSRF. |
| P2 | Backup/restore tampil sebagai menu global untuk Tenant Admin | UI mengiklankan operasi sistem yang sebenarnya tidak boleh dilakukan role tersebut | **Ditutup** dengan gate `is_super_admin()` pada menu dan panel; endpoint tetap memeriksa role. |
| P2 | Tidak ada audit trail terstruktur | Operasi lintas tenant dan credential sulit ditelusuri | **Ditutup** dengan `audit_logs`, migration, deployment checker, halaman Log Keamanan, dan event pada operasi istimewa. |

## Perubahan implementasi

### Session dan autentikasi

`session_admin_is_valid()` sekarang memeriksa ulang `user_id`, username, role, dan hubungan tenant user dengan host aktif. `tenant_admin` hanya valid jika `users.tenant_id` cocok dengan `current_tenant()->id`; Super Admin hanya valid bila `tenant_id` pada database tetap `NULL`. Login juga menolak row Super Admin yang memiliki tenant ID, sehingga data role tidak dapat digunakan secara ambigu.

Helper `verify_current_admin_password()` memusatkan re-authentication. Helper `admin_password_policy_error()` menetapkan password baru minimal 12 dan maksimal 128 karakter. Setelah password berubah, `rotate_admin_session()` melakukan `session_regenerate_id(true)` dan membuat token CSRF baru.

### Policy mutation dan tenant scope

`admin_action_is_authorized()` menyediakan allowlist action untuk dashboard dan endpoint AJAX. `save_theme_options` juga memvalidasi preset melalui registry, sementara seluruh konfigurasi dan media tetap disimpan melalui resolver tenant yang sudah ada. `app/save.php` sekarang memanggil policy tersebut sebelum memproses upload foto mempelai atau opsi preset.

Perubahan ini tidak mengganti theme contract atau membuat sistem media kedua. Jalur existing `registry → admin → canonical persistence → renderer` dipertahankan, sehingga hardening otorisasi tidak mengubah perilaku preset dan media default.

### Control plane Super Admin

Halaman Super Admin sekarang berlabel **Panel Pengelola Semua Tenant** dan memiliki operasi create tenant, suspend/activate tenant, reset password Tenant Admin, profil Super Admin, serta menu **Log Keamanan**. Seluruh operasi lintas tenant memerlukan CSRF dan password Super Admin saat ini.

Password Tenant Admin tidak lagi didekripsi dan dirender pada daftar tenant. Password baru hanya ditampilkan pada pesan sukses operasi create/reset, sehingga administrator dapat menyalinnya satu kali tanpa menjadikannya bagian dari halaman daftar tenant.

### Audit log dan deployment

Schema canonical dan `deploy/migrate.php` kini membuat tabel berikut:

```sql
 audit_logs(
   actor_user_id,
   actor_role,
   actor_tenant_id,
   target_tenant_id,
   action,
   metadata_json,
   ip_address,
   created_at
 )
```

Event yang dicatat mencakup pembuatan tenant, perubahan status tenant, reset password Tenant Admin, perubahan credential admin, backup, restore, dan mutation foto/preset melalui endpoint AJAX. `deploy/audit.sh` juga memeriksa keberadaan tabel, kolom penting, serta foreign key actor dan target tenant.

## Menu dan otoritas

Menu **Cadangan Data** sekarang hanya ditampilkan ketika role aktif adalah Super Admin. Tenant Admin tetap dapat memakai menu konfigurasi undangannya sendiri, tetapi tidak melihat operasi backup/restore seluruh database dan seluruh media. Halaman Log Keamanan juga memiliki pembatasan server-side `require_admin()` plus `is_super_admin()`, bukan hanya pembatasan visual.

> Prinsip yang dipakai: **menu yang disembunyikan bukan authorization**. Setiap route dan mutation tetap memiliki pemeriksaan server-side.

## Validasi yang telah dijalankan

| Pemeriksaan | Hasil |
|---|---|
| `php -l` pada seluruh PHP repository | Lulus, tanpa syntax error. |
| `git diff --check` | Lulus. |
| `php tools/validate.php` | Lulus: `CMS-first contract validation succeeded.` |
| `php tools/multitenant_auth_smoke.php` | Lulus: tenant scope, role revalidation, password verification, denial unknown action, dan audit event. |
| Theme contract, disabled section, media role, visual contract, content preservation, wedding copy smoke | Lulus. |
| User-input capability smoke dengan tenant fixture aktif | Lulus. |
| `php tools/deployment_smoke.php` | Lulus. |
| `php deploy/migrate.php` pada SQLite sementara | Lulus; audit schema berhasil dibuat. |
| `bash -n deploy/audit.sh` | Lulus. |

## Batasan dan tindak lanjut deployment

Migration baru wajib dijalankan pada deployment existing sebelum route Log Keamanan digunakan secara penuh. Jalankan `deploy/migrate.php` melalui proses deployment resmi, kemudian jalankan `deploy/audit.sh` terhadap database dan host tenant aktif. Backup database sebelum migration tetap disarankan.

Audit ini tidak mengubah desain control-plane menjadi host khusus. Super Admin masih login melalui host tenant aktif yang telah terdaftar, sesuai arsitektur repository saat ini. Jika control plane tenant-neutral diperlukan di masa depan, perubahan tersebut harus menjadi desain terpisah dengan host allowlist, bukan bypass terhadap `current_tenant()`.

## Kesimpulan

Dengan perubahan pada branch ini, kelemahan utama otorisasi Super Admin dan tenant session pada repository multi-tenant telah ditutup tanpa menerapkan kode dari repository single-tenant. Tenant isolation tetap bersumber dari domain request, database tenant ID, dan namespace media. Otoritas Super Admin kini memiliki current-password re-authentication, audit trail, menu yang lebih jelas untuk pengguna publik, serta endpoint mutation yang memiliki policy eksplisit.

**Verdict:** **READY TO MERGE**, dengan syarat migration dijalankan pada deployment dan hasil `deploy/audit.sh` diperiksa setelah rollout.

## Referensi

[1]: https://github.com/februana/multytenant_cms_enggine — Repository multi-tenant target audit.
[2]: https://www.php.net/manual/en/function.session-regenerate-id.php — PHP session ID rotation reference.
[3]: https://www.php.net/manual/en/function.password-verify.php — PHP password verification reference.
