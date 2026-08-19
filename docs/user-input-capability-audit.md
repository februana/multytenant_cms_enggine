# Audit Jalur Input Pengguna per Preset

## Scope

Audit ini membandingkan source/template boundary, contract preset, default config, handler POST/upload, Media Manager, panel Admin, dan renderer untuk input yang secara praktis harus dapat ditentukan pemilik undangan.

## Findings before this follow-up

| Input pengguna | Contract/schema | Admin/persistence | Renderer coverage | Gap |
|---|---|---|---|---|
| Foto bride/groom/couple | Canonical `config.media` dan media role contract tersedia | Upload handler dan Media Manager tersedia; role panel sudah diperbaiki pada PR #88 | DewanaKL, Archak, Parang, Pawiwahan, Yami memakai role yang relevan | Tidak ada gap utama setelah PR #88; Shubh Vivah/Rainier tidak memakainya |
| Dresscode | Global `config.dresscode` dan `save_dresscode` sudah tersedia | Panel Admin tersedia, tetapi hanya preset dengan capability `dresscode` yang melihatnya | DewanaKL dan Parang memakai field; Yami Buzzy masih hardcode tiga agenda dresscode | P1: Yami contract/admin/renderer belum memakai input pengguna |
| QRIS | `config.gift.qris_image`, `upload_qris`, `gift.qris_image` selector tersedia | Panel Hadiah dan upload QRIS sudah tersedia untuk preset dengan `gift`; generic QR assignment sebelumnya tidak dibatasi | Gift renderer tidak memakai `qris_image`; DewanaKL hanya menampilkan ikon QR, bukan gambar | P1: upload tersimpan tetapi tidak terlihat; perlu bridge pada semua preset gift |
| Video love story/invitation | DewanaKL membaca `media.love_story_video`; Yami membaca `media.video`/`invitation_video` | Tidak ada folder video atau upload/selector canonical di Media Manager; `love_story` hanya image | DewanaKL/Yami memiliki slot video, tetapi pengguna tidak punya jalur upload umum | P1/P2: input media video belum end-to-end |
| Theme-specific ornament/background | Visual schema, canonical theme assets, upload/reset tersedia untuk registered image fields | Panel visual/Theme Assets tersedia untuk schema yang dideklarasikan | Shubh, Parang memakai ornament visual; preset lain memakai source fallback | Sebagian sudah complete; perlu regression coverage, bukan sistem baru |
| Gift bank/e-wallet | `config.gift` dan `save_gift` tersedia | Panel Hadiah tersedia untuk preset `gift` | Archak, DewanaKL, Yami, Parang, Pawiwahan memakai field | QRIS image adalah missing renderer bridge |

## Applied in this follow-up

- Dresscode capability contract ditambahkan untuk DewanaKL, Yami Buzzy, dan Parang; Yami Buzzy renderer diganti dari hardcoded agenda menjadi data `config.dresscode`.
- QRIS rendering ditambahkan pada DewanaKL, Yami Buzzy, Archak, Parang, dan Pawiwahan, dengan blok kondisional yang tidak tampil ketika field kosong.
- Generic Media Manager QR assignment dibatasi pada preset yang memiliki capability `gift`.

## Applied video correction

Video sekarang memakai role canonical `media.love_story_video`. Media Manager menyediakan folder `Video Cerita`, validasi MP4/MIME/ukuran sampai `MAX_VIDEO_UPLOAD_SIZE`, preview video, assignment button, dan cleanup reference lama. `detect_media_usage`, rename/replace, force-delete, dan renderer DewanaKL/Yami Buzzy memakai field yang sama. Konfigurasi lama `media.video` dan `media.invitation_video` tetap dibaca sebagai backward-compatible fallback oleh Yami Buzzy.

## Remaining scoped limitations

Shubh Vivah dan Rainier tidak diberi panel dresscode, gift, QRIS, atau video karena renderer dan contract aktifnya tidak menyediakan section yang mengonsumsi data tersebut. Asset source-specific seperti ornamen dan dresscode icon tetap memakai fallback source ketika pengguna tidak memilih Theme Asset; field yang sudah terdaftar tetap dapat diunggah, dipreview, di-reset, dan dipulihkan ke asset source.

## Validation

The final suite completed with `FINAL_SUITE_STATUS=0`: 21 PHP smoke tests plus the deployment backup/restore smoke passed. The captured suite contained 1,398 PASS lines and no `FAIL`, `Unhandled exception`, `Fatal error`, or `Parse error` markers. PHP lint across `app/`, `themes/`, and `tools/`, shell lint across deployment scripts, and `git diff --check` also passed. The existing media-delete smoke emits 24 non-fatal session-header warnings when multiple renderer probes are executed in one CLI process; all assertions remain successful.
