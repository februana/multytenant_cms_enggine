# Audit Konsistensi Repository dan Perluasan Visual CMS

**Status audit lokal: READY TO MERGE setelah commit dan push.** Audit ini mencakup tujuh preset built-in aktif—`dewankl`, `rainier`, `archak`, `parang`, `pawiwahan`, `shubh-vivah`, dan `yami-buzzy`—serta mode `custom`. Fokus tambahan pada iterasi ini adalah memastikan font, warna font, background section, dan asset visual benar-benar mengalir dari panel admin hingga renderer frontend.

## Ringkasan Eksekutif

Katalog visual kini terpusat dan dipakai oleh seluruh preset. Panel admin menyediakan **18 pilihan font judul**, **21 pilihan font isi**, dan **41 warna bernama** yang dapat dipilih melalui swatch palette maupun color picker. Setiap preset aktif dan mode Custom memiliki capability `heading_font`, `body_font`, `accent_color`, `heading_color`, `text_color`, `muted_color`, dan `link_color` dengan default source-preserving, validasi, palette, live preview, persistence, reset, dan bridge CSS scoped.

Masalah utama yang ditemukan dan diperbaiki adalah enrichment schema yang berpotensi mengganti default font/color bawaan source, stylesheet font yang belum seragam, warna baru yang hanya tersimpan tetapi belum digunakan renderer, serta palette server-rendered yang belum memiliki handler klik pada load awal admin. Semua telah diperbaiki. Background section tidak lagi diperlakukan sebagai alias hero: tiap preset memiliki capability `section_background_*` yang dipetakan ke section renderer masing-masing, dengan fallback kosong yang kembali ke background source/CSS bawaan.

> **Kesimpulan:** tidak ditemukan ketidak-konsistenan aktif antara registry, helper, schema admin, persistence, preview, renderer, fallback, dan contract setelah validasi akhir. Seluruh 18 smoke test terisolasi lulus.

## Matriks Capability Final

| Area | Hasil | Bukti |
|---|---:|---|
| Registry preset | PASS | Tujuh preset aktif; Elix tidak terdaftar. |
| Kontrak section | PASS | `theme_contract_smoke.php` dan `repo_contract_audit.php`. |
| Font judul | PASS | 18 opsi terpusat, default source lama tetap dipertahankan. |
| Font isi | PASS | 21 opsi terpusat, termasuk font source seperti Josefin Sans, Outfit, Quicksand, Arvo, Gilroy, dan Segoe UI. |
| Warna font | PASS | `heading_color`, `text_color`, `muted_color`, `link_color`, dan `accent_color` tersedia pada semua preset serta Custom. |
| Palette warna | PASS | 41 swatch bernama, termasuk seluruh default source aktif. |
| Background section | PASS | Setiap preset memiliki image capability section-specific dan mapping renderer. |
| Asset custom | PASS | Image capability yang ditampilkan admin dipastikan digunakan layout/adapter terkait; asset kosong memakai fallback source. |
| Live preview | PASS | Mapping `visualMap` memiliki variable scoped untuk setiap preset dan warna baru. |
| Persistence/reset | PASS | Override tersimpan per preset, tidak bocor antar preset, dan reset mengembalikan default tanpa menghapus file. |
| Media deletion | PASS | Forced delete membersihkan nested reference visual, gallery, dan theme option. |
| Localization | PASS | UI admin dan frontend aktif tetap Bahasa Indonesia; label Inggris Pawiwahan yang ditemukan telah diperbaiki. |
| Dependency | PASS | Tidak ada dependency baru yang wajib untuk Shubh Vivah/Yami Buzzy. |
| Responsive visual | PASS | 14 screenshot override baru: tujuh preset pada 1440×900 dan 390×900. |

## Jumlah Background dan Asset Per Preset

| Preset | Image capability | Section background | Catatan fallback |
|---|---:|---:|---|
| DewanaKL | 8 | 6 | Background kosong kembali ke source/gradient; welcome, hero, dan tiap section memiliki jalur terpisah. |
| Rainier | 5 | 4 | Hero tetap memakai cover/source images saat tidak ada override. |
| Archak | 6 | 4 | Hero, timeline, gallery, stay, registry, dan badge tetap memiliki fallback source. |
| Parang | 6 | 3 | Hero, home, gallery, lokasi, dan ornamen memakai asset source ketika override kosong. |
| Pawiwahan | 6 | 4 | Hero, welcome, gallery, lokasi, gift, dan messages tetap memakai asset source. |
| Shubh Vivah | 7 | 4 | Hero/card, home surface, event, gallery, RSVP, dan ornaments tetap memiliki fallback source. |
| Yami Buzzy | 12 | 10 | Hero, welcome, seluruh section utama, dan dress/source image tetap memakai fallback lokal/source. |

Jumlah image capability mencakup field visual image yang benar-benar dideklarasikan oleh schema. Media umum seperti cover, foto pasangan, gallery, musik, dan video tetap berada pada File Manager/CMS Media sesuai kontrak aplikasi; panel preset hanya menampilkan role yang mempunyai jalur renderer.

## Perubahan Implementasi

### Registry dan katalog terpusat

`config.php` kini menyediakan `theme_color_palette()`, `theme_font_catalog()`, dan `theme_google_font_stylesheet_url()`. Font source lama tidak diganti secara paksa: enrichment capability membaca default visual yang sudah dideklarasikan preset, kemudian menambahkan pilihan baru. Dengan pendekatan ini, perluasan katalog tidak mengubah tampilan bawaan ketika pengguna belum memilih custom value.

Stylesheet global memuat keluarga font yang dipakai preset lama maupun katalog baru, termasuk Arvo, Beau Rivage, Bodoni Moda, Caveat, Cinzel, Cormorant Garamond, Dancing Script, DM Sans, DM Serif Display, Fraunces, Great Vibes, Inter, Josefin Sans, Lato, Libre Baskerville, Libre Caslon Text, Lora, Manrope, Merriweather Sans, Montserrat, Noto Naskh Arabic, Nunito Sans, Open Sans, Outfit, Plus Jakarta Sans, Poppins, Playfair Display, Quicksand, Raleway, Sacramento, Tangerine, dan Work Sans. Gilroy tetap memakai file font lokal Yami Buzzy.

### Admin palette dan field visual

`admin/index.php` kini merender swatch palette bernama untuk input warna awal. `admin/app.js` menambahkan palette yang sama pada field dinamis ketika preset diganti. Handler delegated untuk swatch server-rendered memastikan klik pada load awal juga mengubah input, memperbarui `unsavedVisualValues`, dan mengirim update ke live preview.

Field image tetap memakai canonical Media Library yang sama. Setiap capability image memiliki label Bahasa Indonesia, preview, fallback source, tombol **Kembalikan ke Bawaan**, dan catatan bahwa upload baru dilakukan melalui File Manager kemudian halaman dimuat ulang. Dengan demikian, background yang diunggah dapat dipilih pada `Latar Beranda`, `Latar Acara`, `Latar Galeri`, `Latar RSVP`, dan field section lain yang tersedia—bukan hanya `Latar Hero`.

### Renderer bridge

Seluruh layout menerima empat warna tambahan melalui variable scoped berikut: warna judul, warna teks, warna sekunder, dan warna tautan. Bridge telah diterapkan pada DewanaKL, Rainier, Archak, Parang, Pawiwahan, Shubh Vivah, dan Yami Buzzy. Selector source yang berpotensi terlalu luas dibatasi dengan scope preset atau root container agar warna tombol, video, footer, ikon, dan overlay tetap mempertahankan kontrak visualnya.

Background section juga diuji dengan path override yang sama. Smoke test memverifikasi bahwa path tersimpan muncul di HTML renderer pada section yang benar, bukan dipindahkan menjadi hero assignment. Ketika override dihapus, resolver kembali ke `none`, gradient, source image, atau asset fallback sesuai preset.

## Temuan Browser dan Screenshot

Fixture override disajikan melalui HTTP lokal agar path root-relative, stylesheet, font, dan asset dimuat seperti deployment nyata. Screenshot representative menunjukkan Shubh Vivah desktop tetap memiliki kartu, ornament, countdown, CTA, dan typography setelah Bodoni Moda serta warna custom diterapkan. Yami Buzzy mobile 390 px tetap memiliki welcome modal terpusat, nama pasangan membungkus, dan tidak menunjukkan overflow horizontal.

DewanaKL diuji melalui browser dengan CTA **Buka Undangan**. Tampilan awal yang sangat pucat merupakan welcome card/overlay; setelah CTA ditekan, hero, kalender, navigasi, konten Arab/Indonesia, gallery, dan form tampil serta JavaScript berjalan. Ruang kosong luas di sisi kiri desktop adalah karakter layout source, bukan kegagalan render.

Seluruh artefak screenshot override tersimpan pada `docs/assets/visual-capability-audit/`, termasuk `contact-sheet.png`, tujuh screenshot desktop, dan tujuh screenshot mobile. Screenshot audit sebelumnya untuk Shubh Vivah dan Yami Buzzy tetap dipertahankan pada `docs/assets/responsive-audit/`.

## Validasi Akhir

Runner validasi terisolasi mereset `config.json` dan `database.sqlite` sebelum setiap test, lalu mengembalikan state runtime setelah eksekusi. Seluruh 18 test berikut lulus:

| Test | Status |
|---|---:|
| `validate.php` | PASS |
| `theme_render_smoke.php` | PASS |
| `theme_contract_smoke.php` | PASS |
| `theme_disabled_smoke.php` | PASS |
| `theme_regression_smoke.php` | PASS |
| `theme_localization_smoke.php` | PASS |
| `content_preservation_smoke.php` | PASS |
| `repo_contract_audit.php` | PASS |
| `visual_contract_smoke.php` | PASS |
| `visual_capability_consistency_audit.php` | PASS |
| `visual_color_font_smoke.php` | PASS |
| `visual_media_e2e_smoke.php` | PASS |
| `media_pipeline_smoke.php` | PASS |
| `media_requirement_smoke.php` | PASS |
| `media_delete_fallback_smoke.php` | PASS |
| `admin_guest_smoke.php` | PASS |
| `pawiwahan_smoke.php` | PASS |
| `deployment_smoke.php` | PASS |

`visual_capability_consistency_audit.php` mengunci tujuh capability umum, minimal 10 opsi font per kategori, minimal 10 palette entry per warna, default yang selectable, shared font stylesheet, setiap image capability yang mencapai layout, setiap section background yang mencapai renderer, serta setiap variable preview yang benar-benar dikonsumsi oleh layout/CSS.

## Berkas Utama

| Berkas | Perubahan |
|---|---|
| `config.php` | Katalog font, palette 41 warna, stylesheet global, common visual capability enrichment, dan capability home background Shubh Vivah. |
| `app/theme-helper.php` | Warna Custom, variable bridge seluruh preset, dan preservation default source. |
| `admin/index.php` | Swatch palette server-rendered dan datalist warna bernama. |
| `admin/app.js` | Palette dinamis, handler swatch static, preview, dan persistence state. |
| `themes/*/layout.php` | Shared font stylesheet serta bridge heading/text/muted/link dan section background. |
| `themes/shubh-vivah/fidelity-adapter.css` | Selector heading/link memakai variable custom. |
| `themes/yami-buzzy/fidelity-adapter.css` | Selector heading/link memakai variable custom. |
| `tools/visual_capability_consistency_audit.php` | Audit registry → schema → preview map → renderer. |
| `tools/visual_color_font_smoke.php` | Smoke test override nyata font, warna, dan section background seluruh preset. |
| `tools/visual_contract_smoke.php` | Kontrak capability baru dan palette admin. |
| `docs/assets/visual-capability-audit/` | 14 screenshot override dan contact sheet. |
| `docs/visual-capability-expansion-baseline.md` | Baseline, temuan browser, dan bukti visual. |

## Kesimpulan

Perluasan capability telah diterapkan secara repository-wide. Semua preset sekarang mempunyai alur custom font dan warna font yang sama secara arsitektural, tetapi tetap mempertahankan default source masing-masing. Background section dan asset custom hanya diekspos ketika benar-benar memiliki jalur renderer, sehingga tidak ada field admin palsu yang tersimpan tanpa efek frontend. Validasi menyeluruh tidak menemukan mismatch registry-helper-contract-renderer-admin, duplicate yang mematahkan render, broken anchor, default asset yang hilang, atau dependency wajib yang belum dipasang.

## Referensi

[1]: https://github.com/februana/webserver_undangan/pull/84 "PR #84 — Multi-preset CMS audit and fixes"
[2]: https://github.com/vinitshahdeo/wedding-website "Source repository — Shubh Vivah"
[3]: https://github.com/Tynab/Yami-Buzzy "Source repository — Yami Buzzy"
