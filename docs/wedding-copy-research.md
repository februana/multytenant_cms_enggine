# Riset Copy Default Undangan Pernikahan

## Tujuan

Menyusun default teks undangan yang sopan, singkat, mudah dibaca publik, dan sesuai pola undangan pernikahan Islami di Indonesia. Teks harus dapat dioverride melalui panel admin; ketika input dihapus, resolver CMS harus kembali ke nilai default ini.

## Pola yang Dipilih

Referensi WeddingMarket menjelaskan bahwa undangan Islami umumnya memuat Bismillahirrahmanirrahim, salam, nama mempelai, orang tua, waktu/tempat acara, dan ayat Al-Qur’an sebagai pelengkap [1]. Bridestory memberi pola yang serupa: kutipan QS. Ar-Rum 21, salam, kalimat “dengan memohon rahmat dan ridho Allah SWT”, permohonan hadir dan doa restu, lalu salam penutup [2]. Wolipop menekankan bahwa kalimat undangan harus sopan dan jelas, serta mencontohkan gagasan bahwa kehadiran adalah silaturahmi dan doa restu merupakan hadiah terindah [3].

Untuk kutipan Arab, dipilih QS. Ar-Rum ayat 21 karena sumber Quran NU Online menampilkan teks Arab, transliterasi, dan terjemahan yang secara langsung membahas ketenteraman, cinta, dan kasih sayang dalam pasangan [4]. Implementasi memakai teks Arab dan terjemahan ringkas berbahasa Indonesia, dengan rujukan surah/ayat tetap dicantumkan.

## Copy Default Final

### Identitas

- Nama mempelai wanita: `FEBRUANA`
- Nama mempelai pria: `ANDI MUHAMAD BASUKI`
- Panggilan wanita: `Febru`
- Panggilan pria: `Andi`

### Opening text

`Dengan memohon rahmat dan ridha Allah SWT, kami bermaksud mengundang Bapak/Ibu/Saudara/i untuk hadir dan memberikan doa restu pada acara pernikahan kami.`

### Opening greeting

`Bismillahirrahmanirrahim\nAssalamu’alaikum Warahmatullahi Wabarakatuh`

### Quote

`وَمِنْ اٰيٰتِهٖٓ اَنْ خَلَقَ لَكُمْ مِّنْ اَنْفُسِكُمْ اَزْوَاجًا لِّتَسْكُنُوْٓا اِلَيْهَا وَجَعَلَ بَيْنَكُمْ مَّوَدَّةً وَّرَحْمَةًۗ اِنَّ فِيْ ذٰلِكَ لَاٰيٰتٍ لِّقَوْمٍ يَّتَفَكَّرُوْنَ ۝٢١\n\n“Di antara tanda-tanda kebesaran-Nya ialah Dia menciptakan pasangan-pasangan untukmu agar kamu merasa tenteram kepadanya. Dia menjadikan di antaramu rasa cinta dan kasih sayang. Sungguh, pada yang demikian itu benar-benar terdapat tanda-tanda kebesaran Allah bagi kaum yang berpikir.” (QS. Ar-Rum: 21)`

### Closing text

`Kehadiran dan doa restu Bapak/Ibu/Saudara/i merupakan kebahagiaan dan hadiah terindah bagi kami. Terima kasih atas perhatian, kasih sayang, dan doa yang diberikan. Semoga Allah SWT membalas kebaikan Anda dengan keberkahan.`

### Closing greeting

`Wassalamu’alaikum Warahmatullahi Wabarakatuh`

## Keputusan Fallback

Nilai default disimpan pada `config_defaults()` dan dipakai oleh `theme_visual_values_for_config()`/resolver konten ketika config kosong. Input panel admin tetap memiliki prioritas ketika diisi. Reset field atau pengosongan value menghapus override/persisted value, sehingga renderer kembali ke default di atas. Nama panggilan disimpan sebagai field wedding terpisah agar preset dapat menampilkan `Febru & Andi` tanpa mengubah nama resmi.

## Referensi

[1]: https://weddingmarket.com/artikel/isi-undangan-pernikahan-islami "WeddingMarket — Contoh Isi Undangan Pernikahan Islami"
[2]: https://www.bridestory.com/blog/8-contoh-kata-kata-undangan-pernikahan-islami-bisa-jadi-referensi "Bridestory — Contoh Kata-Kata Undangan Pernikahan Islami"
[3]: https://wolipop.detik.com/wedding-news/d-5326072/10-kumpulan-kata-kata-undangan-pernikahan-singkat-dan-sederhana "Wolipop Detik — Kumpulan Kata-Kata Undangan Pernikahan"
[4]: https://quran.nu.or.id/ar-rum/21 "NU Online Quran — Ar-Rum Ayat 21"

## Bukti Browser

Fixture Yami Buzzy dengan default copy baru diuji melalui HTTP. Welcome modal menampilkan Bismillah Arab, salam, serta `FEBRUANA & ANDI MUHAMAD BASUKI`; nama panjang membungkus dalam modal tanpa keluar viewport. Setelah tombol `Buka Undangan` ditekan, hero utama menampilkan greeting Arab, nama resmi, opening text, countdown, dan CTA kalender. Seluruh alur terbuka tanpa error render. Kutipan Arab Ar-Rum 21 tampil pada section Kisah Cinta dan closing text tampil pada section Terima Kasih.
