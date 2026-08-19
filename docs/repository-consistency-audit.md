# Audit Konsistensi Repository dan Fidelity Multi-Preset

**Status akhir: READY TO MERGE**

Audit ini memeriksa konsistensi antara registry preset, kontrak section, renderer, helper visual, panel admin, asset source, dependency frontend, localization, fallback media, alur penghapusan file, serta perilaku browser pada tujuh preset built-in: `dewankl`, `rainier`, `archak`, `parang`, `pawiwahan`, `shubh-vivah`, dan `yami-buzzy`.

## Ringkasan Eksekutif

Seluruh preset built-in terdaftar dan memiliki jalur render yang konsisten. Audit contract otomatis tidak menemukan duplicate DOM ID, broken anchor, section contract yang tidak dirender, atau visual capability schema yang tidak lengkap. Fallback asset, persistence visual override, reset ke bawaan, dan forced media deletion juga lulus smoke test.

Dua masalah nyata ditemukan selama audit lanjutan. Pertama, audio source bawaan Shubh Vivah sudah tersedia di repository tetapi tidak digunakan ketika media musik CMS kosong. Kedua, form RSVP Shubh Vivah dan Yami Buzzy hanya mengubah teks status secara lokal tanpa mengirim data ke backend `save.php`. Keduanya telah diperbaiki dan dikunci dengan regression assertion baru.

Audit responsif kemudian menemukan overflow pada Shubh Vivah ketika nama pasangan menggunakan token panjang seperti sentinel E2E. Wrapping eksplisit ditambahkan pada setiap nama pasangan. Setelah perbaikan, screenshot mobile menunjukkan nama tetap berada di dalam kartu. Label Inggris yang tersisa pada Pawiwahan juga telah dilokalkan ke Bahasa Indonesia dan ditambahkan ke smoke test localization.

## Matriks Konsistensi

| Area audit | Hasil | Bukti atau cakupan |
|---|---:|---|
| Registry preset | PASS | Tujuh preset built-in aktif; Elix tidak lagi terdaftar. |
| Contract section | PASS | Semua preset memiliki section contract dan admin capability yang sesuai. |
| Renderer | PASS | Setiap preset aktif memiliki `themes/<preset>/layout.php` dan berhasil dirender. |
| Duplicate DOM ID | PASS | `tools/repo_contract_audit.php` memeriksa semua preset dan custom renderer. |
| Broken anchor | PASS | Seluruh `href="#..."` memiliki target ID yang dirender. |
| Visual schema | PASS | Setiap capability memiliki `type`, `label`, dan `default`. |
| Admin visual editor | PASS | Schema dinamis, media asset canonical, preview image, reset per key, dan Bahasa Indonesia tersedia. |
| Fallback asset | PASS | Reset visual menghapus override CMS dan mengembalikan asset source lokal. |
| Media deletion | PASS | `clear_media_references()` membersihkan nested reference sebelum forced delete. |
| Localization | PASS | UI guest aktif lulus smoke test; label Inggris Pawiwahan yang ditemukan telah diterjemahkan. |
| Dependency coupling | PASS | Tidak ada selector atau import jQuery/UIkit/Swiper/Fancybox/AOS pada dua preset baru. |
| Browser critical flow | PASS | Welcome modal, CTA, countdown, navigasi, audio, asset lokal, dan seluruh section teruji. |
| Responsive screenshots | PASS | Dua preset diuji pada 1440, 1280, 1024, 768, 576, 390, dan 360 piksel. |

## Temuan dan Perbaikan

### Penyajian fixture browser

Fixture audit menggunakan URL root-relative seperti `/themes/shubh-vivah/fidelity-adapter.css`. Ketika dibuka melalui `file://`, browser tidak memuat stylesheet sehingga halaman terlihat seperti HTML tanpa render. Ini bukan kegagalan renderer aplikasi. Setelah fixture disajikan melalui HTTP lokal, CSS adapter, font, ornamen, background card, dan asset lokal termuat dengan benar. Karena itu, audit berikutnya menggunakan HTTP agar perilakunya menyerupai deployment nyata.

### Fallback audio Shubh Vivah

Asset `themes/shubh-vivah/assets/audio/source-background.mp3` sebelumnya tidak menjadi fallback ketika `config['media']['music']` kosong. Renderer sekarang memakai audio source tersebut secara default dan tetap mengutamakan media CMS apabila pengguna memilih musik sendiri. Tombol **Putar Musik** tampil pada render bersih dan berubah menjadi **Jeda Musik** ketika diklik.

### RSVP backend untuk dua preset baru

Form Shubh Vivah dan Yami Buzzy sekarang menggunakan pola CMS yang sama dengan Pawiwahan: `fetch('save.php', { method: 'POST', body: new FormData(form), credentials: 'same-origin' })`. CSRF token, field `nama`, `status`, `ucapan`, serta honeypot `website` tetap dipertahankan. Pesan sukses hanya diberikan berdasarkan respons backend dan form di-reset setelah penyimpanan berhasil.

### Admin visual preview dan smoke assertion

Implementasi admin saat ini memakai `mediaAssets`, `dataset.visualMediaSelect`, dan `visualMediaUrl()` untuk selector serta preview gambar. Assertion lama yang mencari `imageKeys` sudah tidak sesuai dengan implementasi aktual dan telah diperbarui agar mengunci kontrak perilaku yang benar, bukan nama variabel lama.

### Lokalisasi Pawiwahan

Label aktif yang masih berbahasa Inggris telah diperbaiki: `Home` menjadi **Beranda**, `About` menjadi **Tentang Kami**, `Our Stories` menjadi **Kisah Kami**, `Previous` menjadi **Sebelumnya**, `Next` menjadi **Berikutnya**, `View Google Maps` menjadi **Lihat Google Maps**, dan `Angpao cashless` menjadi **Amplop Digital**. Smoke test localization sekarang membaca Pawiwahan sebagai guest source aktif dan menolak label-label tersebut apabila muncul kembali.

### Responsif Shubh Vivah

Screenshot 390 piksel dengan nama panjang menunjukkan overflow horizontal karena token nama pada heading belum memiliki wrapping eksplisit. Selector `.shubh-card h1 span` sekarang menggunakan `overflow-wrap:anywhere` dan `word-break:break-word`. Screenshot ulang 390 piksel menunjukkan kedua nama membungkus di dalam kartu tanpa terpotong.

## Audit Dependency

| Preset | Dependency runtime yang dipakai | Keputusan audit |
|---|---|---|
| Shubh Vivah | Google Fonts Arvo dan Dancing Script, CSS lokal, JavaScript vanilla, HTML audio | Tidak memerlukan jQuery, AOS, Swiper, Fancybox, UIkit, atau package PHP tambahan. |
| Yami Buzzy | Font Gilroy lokal, CSS lokal, JavaScript vanilla, HTML audio/video, Clipboard API, Fetch API | Tidak memerlukan UIkit, Swiper, Fancybox, AOS, jQuery, atau package PHP tambahan. |
| Pawiwahan | Bootstrap 5, Bootstrap Icons, jQuery countdown lokal, JavaScript adapter | Dependency source tetap dipertahankan dan memiliki native fallback untuk countdown, modal, copy, dan RSVP. |
| DewanaKL | Bootstrap, Font Awesome, AOS, Canvas Confetti | Semua dependency yang dipakai markup dimuat secara eksplisit pada layout preset. |
| Rainier, Archak, Parang | Asset CSS/JS lokal dan font source masing-masing | Jalur asset lokal tersedia dan smoke test renderer lulus. |
| Server | PHP 8.3 dan `chillerlan/php-qrcode` pada Composer manifest | Tidak ada dependency backend baru yang dibutuhkan untuk dua preset baru. |

## Validasi Akhir

Validasi final dijalankan dengan konfigurasi runtime di-reset sebelum setiap test agar perubahan test sebelumnya tidak memengaruhi test berikutnya. Seluruh 15 suite berikut lulus:

| Test | Status |
|---|---:|
| `content_preservation_smoke.php` | PASS |
| `deployment_smoke.php` | PASS |
| `media_delete_fallback_smoke.php` | PASS |
| `media_pipeline_smoke.php` | PASS |
| `media_requirement_smoke.php` | PASS |
| `pawiwahan_smoke.php` | PASS |
| `theme_contract_smoke.php` | PASS |
| `theme_disabled_smoke.php` | PASS |
| `theme_localization_smoke.php` | PASS |
| `theme_regression_smoke.php` | PASS |
| `theme_render_smoke.php` | PASS |
| `visual_contract_smoke.php` | PASS |
| `visual_media_e2e_smoke.php` | PASS |
| `admin_guest_smoke.php` | PASS |
| `repo_contract_audit.php` | PASS |

Smoke test renderer juga mengunci bahwa Shubh Vivah memakai `source-background.mp3` ketika music CMS kosong, serta bahwa Shubh Vivah dan Yami Buzzy merender form RSVP dengan endpoint `save.php`.

## Bukti Screenshot Responsif

Screenshot headless disimpan di `docs/assets/responsive-audit/` untuk kedua preset pada tujuh viewport: `1440x900`, `1280x900`, `1024x900`, `768x1000`, `576x1000`, `390x900`, dan `360x900`. Yami Buzzy mempertahankan welcome modal di dalam viewport mobile. Shubh Vivah mempertahankan kartu, ornamen, countdown, CTA, tombol musik, dan nama pasangan setelah perbaikan wrapping.

## Berkas Perubahan Utama

| Berkas | Perubahan |
|---|---|
| `themes/shubh-vivah/layout.php` | Fallback audio source lokal dan submit RSVP ke backend CMS. |
| `themes/shubh-vivah/fidelity-adapter.css` | Wrapping nama pasangan untuk breakpoint mobile. |
| `themes/yami-buzzy/layout.php` | Submit RSVP ke backend CMS. |
| `themes/pawiwahan/layout.php` | Lokalisasi label navigasi, carousel, Google Maps, galeri, dan amplop digital. |
| `tools/theme_render_smoke.php` | Regression assertion audio source dan endpoint RSVP. |
| `tools/theme_localization_smoke.php` | Coverage Pawiwahan dan guard label Inggris aktif. |
| `tools/visual_contract_smoke.php` | Assertion preview image disesuaikan dengan API admin aktual. |
| `tools/repo_contract_audit.php` | Audit otomatis registry, contract, renderer, ID, anchor, dan schema visual. |
| `docs/assets/responsive-audit/` | Screenshot tujuh breakpoint untuk dua preset baru. |

## Kesimpulan

Repository berada pada kondisi **READY TO MERGE** untuk PR #84. Tidak ditemukan dependency source yang wajib dipasang untuk Shubh Vivah atau Yami Buzzy. Keduanya sengaja memakai adapter CSS dan JavaScript vanilla agar tidak membawa coupling backend atau library source yang tidak diperlukan, sementara fungsi inti CMS—fallback, visual override, reset, media deletion, localization, dan RSVP—tetap terhubung ke arsitektur aplikasi.

## Referensi

[1]: https://github.com/februana/webserver_undangan/pull/84 "PR #84 — multi-preset CMS audit and fixes"
[2]: https://github.com/vinitshahdeo/wedding-website "Source repository — Shubh Vivah"
[3]: https://github.com/Tynab/Yami-Buzzy "Source repository — Yami Buzzy"
