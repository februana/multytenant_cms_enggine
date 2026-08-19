# Audit Source Preset Baru

Tanggal audit: 19 Agustus 2026

## Scope

Task ini menambahkan dua preset sumber ke arsitektur CMS undangan berbasis PHP, menghapus preset legacy, dan melokalkan copy antarmuka preset baru ke Bahasa Indonesia. Source repository diaudit read-only sebelum adaptasi:

| Preset adaptasi | Source | Commit yang diaudit |
|---|---|---|
| Kandidat `shubh-vivah` | [vinitshahdeo/wedding-website](https://github.com/vinitshahdeo/wedding-website) | `f42fbe6` |
| Kandidat `yami-buzzy` | [Tynab/Yami-Buzzy](https://github.com/Tynab/Yami-Buzzy) | `367f5a5` |

## Source 1: wedding-website

Source merupakan single-page invitation ringkas dengan dekorasi sakura, dua ornamen sudut, typography `Arvo` dan `Dancing Script`, musik latar, countdown, dan copy undangan beraksara Hindi. Struktur utama terdiri dari `#media`, `.wrap` title block, `#time`, quote/greeting, CTA `.venue`, audio, dan closing inviter. Tidak ada navbar atau section CMS kompleks di source.

Fidelity yang dipertahankan dalam adapter adalah komposisi centered invitation card, decorative top-left/top-right artwork, script display type, sakura animation, countdown, music toggle, venue/CTA, dan closing blessing. Copy hard-coded source seperti nama pasangan, tanggal, venue, dan inviter harus diganti dengan data CMS. Copy UI yang diadaptasi ke Indonesia meliputi `Sang`, `Mengundang Anda`, `Buka Undangan`, `Menuju Hari Bahagia`, `Lokasi Acara`, dan closing Indonesian greeting.

Asset source yang relevan: `assets/img/left.png`, `assets/img/right.png`, `assets/img/rudra-shahdeo-wedding.png`, `assets/wedding.gif`, dan audio `assets/mp3/background.mp3`/`song.mp3`. Adapter tidak boleh mengambil remote asset yang tidak dikontrol CMS; visual capabilities diarahkan ke canonical media library yang sudah ada. Audio source diperlakukan opsional dan tidak boleh membuat opening flow blocking.

## Source 2: Yami-Buzzy

Source adalah single-page invitation dengan welcome modal `#welcome-modal` dan tombol `#startButton`, hero/banner countdown, about couple, countdown/event detail, dress code, timeline love story, album Swiper/Fancybox, video, gift QR, wedding gallery, invitation/map/video, thank-you section, dan audio. Source menggunakan UIkit, Swiper, Fancybox, AOS, lazy loading, Remix Icon/Font Awesome, serta font Gilroy local files. Source language metadata adalah `vi`, tetapi source copy mayoritas English dengan nama/venue Vietnam.

Section IDs/source markers yang dipetakan:

| Source marker | CMS section adaptasi |
|---|---|
| `#welcome-modal` | `welcome` / opening layer, tidak menjadi section navigasi |
| `section.banner` | `home` |
| `#about` | `couple` |
| `#countdown-section` | `event` |
| `#dresscode` | `dresscode` |
| `#time-line` | `story` |
| `#album` | `gallery` |
| `#video` | `video` |
| `#section-gift` | `gift` |
| `#wedding-gallery` | `gallery-secondary` atau digabung scoped ke gallery sesuai contract |
| `#wedding-invitation` | `invitation` |
| `#section-thankyou` | `closing` |

Fidelity yang dipertahankan adalah welcome modal, hero countdown, UIkit-like spacing and cards, about split layout, vertical timeline, album slider, video panel, dress-code timeline, QR/gift block, invitation details, thank-you overlay, lazy-safe image presentation, and scroll reveal. Implementasi CMS boleh memakai Bootstrap yang sudah tersedia, tetapi selector dan visual adapter harus scoped per preset dan mempertahankan hierarchy/spacing source; tidak membuat dependency runtime baru jika tidak diperlukan.

## Lokalisasi Indonesia

Preset baru harus memakai Bahasa Indonesia untuk teks antarmuka dan fallback: `Selamat Datang`, `Buka Undangan`, `Kami Mengundang Anda`, `Tentang Kami`, `Kisah Kami`, `Galeri`, `Video`, `Acara`, `Dress Code`, `Hadiah`, `RSVP`, `Lokasi`, `Terima Kasih`, dan teks countdown. Data personal CMS seperti nama pasangan, nama tamu, alamat, tanggal, link maps, cerita, dan rekening/QR tidak diterjemahkan atau diubah secara paksa.

## CMS decisions

Kedua preset wajib memakai section gating dari theme contract, canonical media references dari `uploads/`, visual capabilities hanya pada preset masing-masing, dan renderer bridge yang sama. Welcome/cover media, section background, gallery, dan gift media harus tetap memiliki ownership berbeda. Reset visual hanya menghapus override reference, bukan file fisik. Opening modal/layer tidak boleh menghalangi render ketika data tidak lengkap atau script tidak tersedia.

## Initial risks

1. Source 1 sangat minimal sehingga pemetaan ke contract section standar berisiko menambahkan konten yang tidak memiliki padanan visual. Solusi: tetap gunakan layout ringkas dengan section optional, dan tampilkan section standar hanya jika contract/data enabled.
2. Source 2 memiliki dua galeri dan banyak asset remote/WordPress paths. Solusi: gunakan satu contract gallery utama dengan data gallery CMS; section gallery kedua hanya dirender jika model data mendukungnya atau dipetakan sebagai media strip scoped.
3. Audio, countdown, lazy loading, and modal behavior must degrade gracefully; no external source dependency may break the invitation.
4. Responsive acceptance includes 1440, 1280, 1024, 768, 576, 390, and 360 px; long Indonesian labels and couple/guest names require wrapping checks.
