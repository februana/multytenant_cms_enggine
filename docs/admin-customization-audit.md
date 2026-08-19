# Audit Kustomisasi Visual dan Panel Admin

Tanggal audit: 19 Agustus 2026. Repository yang diaudit adalah `februana/webserver_undangan` pada branch `fix/elix-floral-transition-navigation`. Preset aktif yang harus tetap terisolasi adalah DewanaKL, Rainier, Archak, Parang, Pawiwahan, Shubh Vivah, Yami Buzzy, dan Custom.

## Temuan baseline

| Preset | Kontrol visual yang sudah tersedia | Gap yang terlihat | Arah perbaikan |
|---|---|---|---|
| DewanaKL | Aksen, font heading/body, welcome background, background Beranda, Mempelai, Wedding Date, overlay | Belum semua section source yang memiliki permukaan visual memiliki slot; nama `Wedding Date` masih teknis bagi pengguna umum | Pertahankan slot yang ada dan tambahkan label ramah pengguna serta background section tambahan bila layout benar-benar mendukungnya |
| Rainier | Aksen, font, hero background, glass opacity | Layout masih memakai hero background yang sama untuk beberapa payload event dan memiliki fallback foto remote; belum ada slot background section yang terpisah | Tambahkan background section hanya pada selector yang benar-benar dipakai adapter; hilangkan ketergantungan remote untuk fallback baru dan sediakan pilihan per area bila aman |
| Archak | Aksen, font, hero background, skala judul | `header_badge_image` sudah ada sebagai Theme Option tetapi belum menjadi visual capability/media selector; ornamen dan area stay/registry belum memiliki slot custom | Expose badge/ornament sebagai aset preset melalui media selector dan tambahkan background section yang memiliki hook CSS nyata |
| Parang | Aksen, font, hero background untuk pola halaman | Gunungan/wayang dan ornamen dekoratif masih fixed source asset; hero background saat ini juga berperan sebagai pola global, bukan section background | Tambahkan opsi aset dekoratif yang aman serta bedakan pola global dari background section hanya jika CSS adapter dapat menjaga source fidelity |
| Pawiwahan | Aksen, font, hero background yang dipakai hero/welcome | Hero background dipakai bersama untuk welcome dan hero; belum ada section background terpisah; beberapa label frontend masih English | Tambahkan slot background section pada boundary source yang jelas, expose dekorasi yang tidak merusak source, dan sederhanakan label admin |
| Shubh Vivah | Aksen, font, hero background, overlay, ornament kiri/kanan | Event/gallery/RSVP belum memiliki background section capability walaupun layout memiliki card/section surfaces yang bisa diberi background secara terkontrol | Tambahkan background kartu/event/gallery hanya melalui variable scoped dengan fallback source |
| Yami Buzzy | Aksen, font, hero/welcome, background Beranda/Mempelai/Acara, overlay | Story/gallery/video/gift/invitation/closing belum seluruhnya mempunyai background capability; beberapa asset source dapat dipilih lebih ramah jika dipetakan ke role | Tambahkan capability section hanya pada area yang memiliki CSS hook, dan tampilkan label area dalam Bahasa Indonesia |
| Custom | Builder penuh dan visual controls generik | Istilah seperti `Hero Height`, `Shadow`, `Radius Border`, dan `CSS Khusus` masih memerlukan penjelasan awam; pilihan background section generik perlu dikaitkan dengan section yang dipilih | Tambahkan tooltip/help text Bahasa Indonesia, preset-friendly grouping, dan background per section melalui media library canonical |

## Audit jalur media

Panel admin sudah memiliki Media Library canonical, preview, reset assignment, rename propagation, delete protection, serta upload folder untuk cover, background, gallery, love-story, music, dan theme-assets. Visual image fields mengambil daftar gambar canonical melalui `data-media-assets`. Jalur ini harus dipakai untuk seluruh asset baru; tidak boleh dibuat uploader kedua atau field URL bebas yang melewati validasi.

Gap utama bukan penyimpanan, melainkan **coverage capability dan bridge renderer**. Sebuah field baru hanya dianggap selesai bila registry mendeklarasikan capability, panel admin menampilkan label/help/preview/reset, nilai disimpan di `theme_visuals[<preset>]`, renderer memakai CSS variable atau selector scoped, fallback source tetap bekerja, dan reset hanya menghapus referensi tanpa menghapus file fisik.

## Audit bahasa panel admin

Panel sudah berbahasa Indonesia secara umum, tetapi istilah berikut masih bercampur atau terlalu teknis bagi publik: `Preset / Tema`, `Theme Assets`, `Reset assignment`, `Hero Height`, `Center`, `Top`, `Bottom`, `Horizontal (side by side)`, `Live Preview`, `Shadow`, `Radius Border`, `Font`, `SEO`, `RSVP`, dan `CSS Khusus`. Perbaikan yang dibutuhkan adalah label utama yang sederhana, keterangan satu kalimat, contoh nilai, serta pengelompokan berdasarkan tujuan pengguna: **Warna**, **Tulisan**, **Latar dan Gambar**, **Bagian Undangan**, **Animasi**, dan **Pengaturan Lanjutan**.

Istilah teknis yang memang perlu dipertahankan sebaiknya diberi padanan, misalnya `RSVP — Konfirmasi Kehadiran`, `SEO — Tampilan di Google`, dan `CSS Khusus — Pengaturan Lanjutan untuk Pengembang`. Tombol harus memakai tindakan yang jelas: `Pilih gambar`, `Gunakan gambar bawaan`, `Kembalikan tampilan awal`, `Simpan perubahan`, dan `Lihat hasil`.

## Keputusan audit awal

Perluasan akan dilakukan secara preset-scoped. Background section hanya ditambahkan jika layout memiliki boundary visual dan CSS hook yang dapat diisolasi. Aset dekoratif akan diekspos sebagai pilihan media canonical bila penggantian tersebut tidak mengubah struktur source atau mencampur ownership dengan Gallery/Cover. Semua field baru akan memiliki fallback kosong/source-default, preview admin, reset per-field, reset per-preset, dan test persistence/isolation.

## Bukti hook renderer

Pemetaan renderer menunjukkan bahwa DewanaKL sudah memiliki hook welcome dan tiga section background; Yami Buzzy sudah memiliki hook hero, welcome, home, couple, dan event; Shubh Vivah sudah memiliki hero dan ornamen kiri/kanan. Rainier, Parang, Pawiwahan, dan Archak masih lebih terbatas: sebagian besar hanya menerima hero background atau memakai background global/source asset. Pada Yami Buzzy, `.yami-gallery`, `.yami-video`, `.yami-gift`, `.yami-rsvp`, dan `.yami-closing` memiliki surface CSS nyata sehingga dapat diberi capability section yang scoped. Pada Parang, pola global dan ornamen gunungan masih menjadi satu identitas visual; pada Pawiwahan, hero/welcome masih berbagi satu sumber visual. Perluasan harus menghormati perbedaan ini.

## Matriks keputusan capability

| Preset | Background hero | Background pembuka | Background section | Aset dekorasi | Status baseline |
|---|---|---|---|---|---|
| DewanaKL | Sudah ada melalui layout/source background | Sudah ada | Beranda, Mempelai, Wedding Date sudah ada | Belum perlu expose dekorasi source yang mengubah identitas split shell | Functional and visually bridged; coverage dapat diperluas secara scoped |
| Rainier | Sudah ada | Tidak terpisah | Belum ada selector section independen | Hero/event background source masih menjadi payload gabungan; perlu adapter bridge sebelum field baru | Functional but visually incomplete for per-section customization |
| Archak | Sudah ada | Tidak terpisah | Belum ada | Badge header tersedia sebagai Theme Option tetapi belum canonical visual selector; ornamen source masih tetap | Functional hero; asset customization incomplete |
| Parang | Sudah ada sebagai pola/presentation global | Tidak terpisah | Belum ada | Gunungan/wayang fixed source asset | Functional global pattern; section/decorative asset customization incomplete |
| Pawiwahan | Sudah ada | Berbagi dengan hero | Belum ada | Ornamen source fixed; carousel/media assets berasal dari CMS | Functional hero; welcome/section separation incomplete |
| Shubh Vivah | Sudah ada | Mengikuti kartu/hero | Belum ada untuk event/gallery/RSVP | Ornamen kiri/kanan sudah ada | Functional and scoped; add only safe card surfaces |
| Yami Buzzy | Sudah ada | Sudah ada | Beranda, Mempelai, Acara sudah ada; story/gallery/video/gift/RSVP/closing belum | Dress-code icons dan source picture assets masih fixed | Functional and scoped; add remaining section surfaces with CSS variables |
| Custom | Generic theme fields | Generic theme fields | Per-section builder exists but visual background assignment is not guided by section role | Media library exists | Functional but admin guidance needs simplification |

### Prioritas implementasi

Prioritas pertama adalah memperluas capability yang sudah memiliki hook nyata: Yami Buzzy untuk story, gallery, video, gift, RSVP, closing; Shubh Vivah untuk event, gallery, RSVP; DewanaKL untuk section source yang belum tertutup; serta Custom untuk assignment background per section dengan label mudah dimengerti. Prioritas kedua adalah membangun bridge scoped untuk Rainier, Archak, Parang, dan Pawiwahan sebelum membuka field baru, karena menambahkan field tanpa renderer hook akan menghasilkan setting yang tersimpan tetapi tidak terlihat.

## Keputusan implementasi

Capability baru akan memakai nama internal stabil `section_background_<id>` dan label publik sederhana seperti `Latar Acara`, `Latar Galeri`, atau `Latar Konfirmasi Kehadiran`. Semua tipe image akan tetap memakai selector Media Library canonical yang sama. Field aset dekorasi akan memakai label berbasis peran, misalnya `Emblem Header`, `Ornamen Kiri`, atau `Ikon Dress Code`, bukan label file.

Panel admin akan menampilkan tiga lapisan yang mudah dipahami: **Tampilan preset** untuk warna/font/efek; **Latar setiap bagian** untuk background section yang benar-benar didukung; dan **Media khusus preset** untuk asset dekoratif yang aman diganti. Keterangan setiap field akan menjelaskan dampak, fallback, dan cara reset. Istilah teknis akan ditempatkan sebagai keterangan tambahan, bukan judul utama.

Bridge renderer akan menggunakan CSS variables scoped dengan pola `--cms-<preset>-<visual-key>`. Untuk image reference, nilai kosong berarti kembali ke artwork atau warna bawaan source. Tidak ada field baru untuk section yang tidak memiliki selector atau boundary visual yang dapat diverifikasi.


## Implementasi final

Audit seluruh preset aktif menghasilkan capability berikut. **DewanaKL** sekarang menyediakan latar pembuka, Beranda, Mempelai, Tanggal Acara, Galeri, Hadiah, dan Ucapan. **Rainier** menyediakan latar pembuka, Detail Acara, Jadwal, Kata-Kata, dan Konfirmasi Kehadiran. **Archak** menyediakan latar pembuka, Rangkaian Acara, Galeri, Perjalanan/Penginapan, Janji/Hadiah, serta Emblem Header. **Parang** menyediakan pola pembuka, latar Beranda, Galeri, Lokasi, serta ornamen kiri dan kanan. **Pawiwahan** menyediakan latar pembuka, layar sampul, Galeri, Lokasi, Hadiah, dan Ucapan. **Shubh Vivah** menyediakan latar kartu, Acara, Galeri, Konfirmasi Kehadiran, serta ornamen kiri dan kanan. **Yami Buzzy** menyediakan latar hero, welcome modal, dan seluruh section utama: Beranda, Mempelai, Acara, Kisah, Galeri, Video, Hadiah, Lokasi, RSVP, dan Penutup. Mode **Custom** juga menyediakan latar Beranda, Acara, Cerita, Galeri, Lokasi, Hadiah, dan Konfirmasi Kehadiran.

Semua pilihan gambar menggunakan **Media Library canonical** melalui selector admin, validasi referensi gambar, preview, reset per item, dan reset seluruh visual preset. File fisik tidak dihapus ketika referensi visual direset. Live preview kini menangani background section, emblem Archak, ornamen Parang, dan mode Custom.

Bahasa panel publik disederhanakan: istilah seperti `Preset`, `Theme Options`, `Live Preview`, `Reset assignment`, `Theme Assets`, dan `SEO` diganti dengan label yang lebih mudah dimengerti seperti **Gaya Undangan**, **Pengaturan Tambahan Gaya Ini**, **Lihat Hasil Sementara**, **Kembalikan ke Bawaan**, **Aset khusus gaya aktif**, dan **Tampilan di Google**. Panel juga menyediakan petunjuk singkat bahwa perubahan hanya berlaku untuk gaya aktif dan tidak mencampur aset antar-preset.

Validasi tambahan mencakup schema capability semua preset, persistence background section melalui reload config, reset isolation, canonical media E2E, renderer bridge, dan pemeriksaan preview admin.


## Evidence responsive

Fixture semua delapan mode berhasil dirender pada lebar 1440, 768, dan 390 piksel. Screenshot mode Custom pada 390 px menunjukkan navigasi dan nama tamu panjang tetap membungkus di dalam viewport tanpa clipping horizontal pada area yang terlihat. Screenshot Yami Buzzy pada 390 px menunjukkan welcome modal, CTA **Buka Undangan**, dan foto hero tetap terbaca serta berada di dalam batas viewport. Screenshot lengkap disimpan sebagai evidence lokal selama validasi; smoke suite juga mengonfirmasi renderer tidak menghasilkan fatal error atau layout fallback yang hilang.
