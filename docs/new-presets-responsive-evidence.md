# Responsive and Browser Evidence

## Shubh Vivah desktop browser check

Fixture URL: `http://127.0.0.1:8090/.tmp-responsive/shubh-vivah.html`.

Pada viewport browser 896×768, opening card menampilkan salam Indonesia, nama panjang `Nur Aisyah Putri Ramadhani` dan `Muhammad Rizky Pratama` dengan wrapping yang tetap berada di dalam kartu, label tamu, countdown, serta CTA `Buka Undangan`. Ornamen source kiri/kanan terlihat di tepi halaman dan kartu mempertahankan komposisi centered invitation-card. Setelah CTA diklik, browser berpindah ke area `Acara Pernikahan` melalui anchor `#shubh-event`; section acara dan tiga kartu detail tetap terlihat, sehingga CTA tidak mengarah ke target kosong.

Observed screenshot files: `/home/ubuntu/screenshots/127_0_0_1_2026-08-19_14-13-02_9046.webp` and `/home/ubuntu/screenshots/127_0_0_1_2026-08-19_14-13-15_5886.webp`.
## Yami Buzzy desktop browser check

Fixture URL: `http://127.0.0.1:8090/.tmp-responsive/yami-buzzy.html`.

Pada viewport browser 896×768, welcome modal menampilkan salam pembuka Indonesia, nama pasangan panjang, copy `Selamat datang`, dan tombol `Buka Undangan`. Setelah tombol diklik, modal menghilang dan hero utama tampil dengan navigasi `Beranda`, `Mempelai`, `Acara`, `Kisah`, `Galeri`, dan `Konfirmasi Kehadiran`. Hero countdown, `Simpan ke Kalender`, background source, dan wrapping nama panjang tetap terbaca. Tidak ada label navigasi English `RSVP` yang tersisa pada tampilan aktif.

Observed screenshot files: `/home/ubuntu/screenshots/127_0_0_1_2026-08-19_14-13-38_7485.webp` and `/home/ubuntu/screenshots/127_0_0_1_2026-08-19_14-13-49_9787.webp`.
## Automated responsive screenshot review — 360 px

`shubh-vivah-360.png` menunjukkan kartu tetap berada di dalam viewport 360 px. Nama pasangan panjang membungkus ke beberapa baris tanpa clipping horizontal; label tamu membungkus secara vertikal, tanggal/lokasi membungkus menjadi dua baris, countdown tetap terbaca, dan tombol `Buka Undangan` mengambil lebar kartu tanpa keluar viewport. Ornamen source tetap dekoratif di tepi.

`yami-buzzy-360.png` menunjukkan welcome modal tetap terpusat dan seluruh nama pasangan panjang terbaca dalam kartu. Copy welcome membungkus dengan baik, tombol `Buka Undangan` tetap berada di dalam batas modal, dan tidak ada horizontal overflow yang terlihat pada frame 360 px. Screenshot ini masih berada pada opening modal, sesuai flow source Yami Buzzy.
## Automated responsive screenshot review — 1440 px

`shubh-vivah-1440.png` menunjukkan invitation card centered dengan lebar proporsional, ornamen source menghuni dua sudut atas, dan typography script menjadi focal point. Tidak tampak elemen keluar viewport pada frame desktop.

`yami-buzzy-1440.png` menunjukkan source-style full-bleed hero photo dengan overlay gelap dan welcome card editorial terpusat. Nama panjang tetap berada di dalam card dan tombol opening tetap memiliki ruang aman di bawah copy.

All generated viewport captures exist for both presets at 1440, 1280, 1024, 768, 576, 390, and 360 px under `/home/ubuntu/responsive-evidence/`.
## Yami Buzzy anchor and overflow check

Pada browser, link `Konfirmasi Kehadiran` berpindah ke URL `#yami-rsvp` dan menampilkan section form RSVP, bukan target kosong. Pemeriksaan DOM pada viewport browser 1280 px menghasilkan `scrollWidth: 1265`, `viewport: 1280`, `horizontalOverflow: false`, dan semua anchor utama `#yami-home`, `#yami-couple`, `#yami-event`, `#yami-story`, `#yami-gallery`, serta `#yami-rsvp` ditemukan.
