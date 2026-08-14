# AGENTS — Petunjuk singkat untuk agen (CMS-first)

File ini merujuk ke `master_prompt_cms_first_architecture.txt` sebagai instruksi proyek utama tentang pendekatan "CMS-first".

Ringkasan singkat (untuk agen otomatis):
- Fokus utama: jadikan CMS sebagai sumber kebenaran untuk seluruh konfigurasi.
- Mulai selalu dengan PHASE 0 (audit & freeze) — jangan mengubah kode sebelum audit selesai.
- Section visibility harus dikendalikan oleh `sections.*` di konfigurasi; jika `enabled=false`, frontend tidak boleh merender HTML section tersebut.
- Semua pengaturan visual harus dipetakan ke `theme` dan diterjemahkan ke CSS variables.
- Live Preview harus menggunakan renderer yang sama dengan production.
- Jangan duplikasi konfigurasi; gunakan `config.json` sebagai single source of truth.

Lokasi instruksi lengkap: [master_prompt_cms_first_architecture.txt](master_prompt_cms_first_architecture.txt)

Tindakan agen yang disarankan:
- Sebelum perubahan kode: jalankan pemeriksaan sintaks PHP (`php -l`) dan audit repository sesuai PHASE 0.
- Buat mapping CMS→config→backend→frontend untuk setiap setting yang diubah.
- Jangan mengimplementasikan Phase 1 sebelum Phase 0 selesai.

Referensi cepat (periksa sebelum kerja):
- Entrypoints: [index.php](index.php), [admin.php](admin.php), [save.php](save.php), [messages.php](messages.php), [gallery.php](gallery.php)
- Private implementation: [app/](app/)
- Deployment scripts: [deploy/](deploy/)
