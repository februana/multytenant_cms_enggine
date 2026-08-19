# Audit Render, Fallback, dan Penghapusan Media

## Verifikasi dua sumber

Runtime `theme_presets()` mengembalikan dua preset terpisah: `shubh-vivah | Shubh Vivah` dari `vinitshahdeo/wedding-website` dan `yami-buzzy | Yami Buzzy` dari `Tynab/Yami-Buzzy`. Keduanya juga berada di `theme_builtin_preset_keys()` dan memiliki contract section aktif. Jadi sumber yang diminta tidak hilang; masalah yang dilaporkan perlu dibedakan antara registry/selector dan kualitas render/fallback.

## Temuan source dependency

Source Shubh Vivah memakai jQuery, plugin sakura falling-petals, countdown berbasis DOM `#time`, dan audio autoplay. Adapter CMS saat ini sengaja memakai JavaScript vanilla serta CSS scoped, sehingga dependency WordPress/plugin tidak diwajibkan untuk halaman utama. Source Yami Buzzy memakai UIKit, Swiper, Fancybox, AOS, Lazysizes, jQuery/jQuery-migrate, banyak selector WordPress, dan remote S3 media. Adapter CMS juga sengaja mengganti stack tersebut dengan CSS/JS self-contained dan markup CMS-aware, sehingga selector library asal tidak boleh dianggap otomatis tersedia.

## Temuan fallback

Asset Shubh Vivah sudah identik dengan asset source repository dan dibundel lokal. Yami Buzzy sebelumnya memakai fallback hero remote `8430.webp`, sementara markup source menunjukkan banner hero utama memakai `9115.webp`. Fallback Yami Buzzy diperbaiki untuk memakai `themes/yami-buzzy/assets/pic/source-hero.webp`, hasil bundel dari asset hero source `9115.webp`. Pilihan custom tetap mengalahkan fallback; ketika nilai custom kosong/reset, renderer kembali ke asset lokal tersebut.

## Temuan File Manager

Sebelumnya tombol Hapus dinonaktifkan ketika `detect_media_usage()` menemukan referensi di media, galeri, love story, theme visuals, atau theme options. Backend juga menolak delete untuk file yang masih dipakai. Ini aman untuk mencegah referensi rusak, tetapi membuat pengguna tidak memiliki alur publik untuk menghapus file yang memang ingin diganti. Perbaikan menambahkan alur **Lepaskan & Hapus**: semua referensi CMS dilepas, konfigurasi disimpan, lalu file fisik dihapus. Tombol memakai konfirmasi browser dan delete biasa tetap tersedia untuk asset yang tidak dipakai.
