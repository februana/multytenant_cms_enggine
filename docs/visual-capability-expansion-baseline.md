# Baseline Perluasan Visual Capability

## Scope

Audit ini mencakup preset built-in aktif `dewankl`, `rainier`, `archak`, `parang`, `pawiwahan`, `shubh-vivah`, dan `yami-buzzy`, serta mode `custom`. Fokusnya adalah jalur lengkap `registry → admin schema/preview/reset → canonical persistence → renderer bridge → scoped CSS → fallback` untuk font, warna teks, background section, dan asset visual.

## Baseline yang Ditemukan

| Preset | Font heading/body di panel | Warna font di panel | Background/asset visual di panel | Gap utama |
|---|---|---|---|---|
| DewanaKL | Ada, pilihan terbatas | Hanya accent | Hero, welcome, dan section backgrounds | Tidak ada text/heading/muted/link color atau palette; asset foto pasangan masih berada di Media umum. |
| Shubh Vivah | Ada, pilihan terbatas | Hanya accent | Hero, event, gallery, RSVP, ornament | Tidak ada warna font; belum ada background home/card surface yang eksplisit. |
| Yami Buzzy | Ada, pilihan terbatas | Hanya accent | Hero, welcome, seluruh section utama | Tidak ada warna font; tidak ada palette; asset role tidak diberi label per preset. |
| Rainier | Ada, pilihan terbatas | Accent dan schema hero option | Hero dan section backgrounds | Tidak ada text/heading/muted/link color; link dan paragraph masih mengikuti source hard-code. |
| Archak | Ada, pilihan terbatas | Hanya accent | Hero, timeline, gallery, stay, registry, header badge | Tidak ada warna font; selector source belum memiliki bridge warna teks. |
| Parang | Ada, pilihan terbatas | Hanya accent | Hero, section backgrounds, ornament | Tidak ada warna font; warna teks dan navigasi masih memakai variable source tetap. |
| Pawiwahan | Ada, pilihan menengah | Hanya accent | Hero, welcome, gallery, location, gift, messages | Tidak ada warna font; font source dan Google Fonts belum memakai katalog bersama. |
| Custom | Ada, pilihan terbatas | Warna global lebih lengkap | Hero dan section backgrounds | Font catalog dan palette belum terpusat serta preview preset-specific belum menjadi baseline bersama. |

## Keputusan Implementasi

1. Menambahkan katalog font terpusat dengan font source yang sudah dipakai repository serta font Google Fonts tambahan. Katalog dipakai oleh seluruh schema preset dan Custom, sedangkan font lokal seperti Gilroy tetap dipertahankan sebagai opsi source-specific.
2. Menambahkan capability warna `heading_color`, `text_color`, `muted_color`, dan `link_color` pada seluruh preset aktif. Semua capability wajib memiliki palette swatch, validasi hex, mapping preview, variable bridge, dan fallback registry.
3. Menambahkan palette swatch pada admin visual editor sehingga pengguna awam dapat memilih warna bernama, bukan hanya membuka color picker.
4. Memastikan tiap image capability ditampilkan sebagai field preset-specific dengan preview, reset reference-only, dan pilihan dari canonical Media Library. Background section tidak lagi diperlakukan sebagai alias hero.
5. Menambahkan audit otomatis yang memeriksa schema, mapping helper, renderer variable, fallback, dan keterhubungan setiap image capability ke DOM/CSS renderer.

## Bukti Screenshot Override

Screenshot fixture override memperlihatkan bahwa **Shubh Vivah desktop** menerima font Bodoni Moda, warna heading/text biru yang dipilih, tombol aksen, serta gambar background section tanpa merusak komposisi kartu. **Yami Buzzy mobile 390 px** menampilkan welcome modal yang tetap terpusat, nama pasangan membungkus ke dua baris, warna heading/text custom terbaca, dan tidak terlihat overflow horizontal. Artefak lengkap desktop/mobile untuk tujuh preset berada di `docs/assets/visual-capability-audit/`.

Contact sheet menunjukkan **Archak, Parang, Pawiwahan, Rainier, Shubh Vivah, dan Yami Buzzy** tetap memiliki struktur desktop/mobile yang terbaca dengan override aktif. Fixture **DewanaKL** tampak sangat pucat/tertutup pada contact sheet sehingga perlu pemeriksaan terpisah untuk membedakan welcome overlay, asset fallback, atau efek warna custom dari kegagalan render.

Audit browser DewanaKL mengonfirmasi bahwa screenshot pucat awal adalah **welcome card/overlay**, bukan halaman gagal render. Setelah CTA `Buka Undangan` ditekan, hero, kalender, navigasi, konten Arab/Indonesia, galeri, dan form tampil serta JavaScript berjalan. Komposisi desktop mempertahankan karakter source dengan konten utama pada panel kanan dan ruang kosong luas di kiri; tidak ada indikasi overflow atau asset path rusak.
