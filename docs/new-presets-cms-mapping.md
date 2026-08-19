# Mapping Kontrak CMS Preset Baru

## Naming

| Source | Key internal | Label CMS | Rationale |
|---|---|---|---|
| vinitshahdeo/wedding-website | `shubh-vivah` | Shubh Vivah | Mempertahankan identitas source tanpa memakai nama pasangan source. |
| Tynab/Yami-Buzzy | `yami-buzzy` | Yami Buzzy | Mempertahankan identitas source repository dengan copy interface Indonesia. |

## Shared data contract

Kedua preset memakai data existing: `wedding`, `parents`, `schedule`, `countdown`, `gallery`, `music`, `gift`, `maps`, `rsvp`, `messages`, `guest_name`, `media`, `seo`, `whatsapp`, dan `calendar`. Tidak ada field backend baru yang diperlukan. Names, guest, dates, venue, links, story, gallery, gift, and RSVP remain CMS-driven.

## Shubh Vivah contract

Source 1 minimal and invitation-card oriented. Its contract preserves a compact opening/card experience while exposing only sections that have a safe visual mapping:

| ID | DOM ID | Indonesian label | Embedded capabilities |
|---|---|---|---|
| `home` | `shubh-home` | Beranda | wedding, guest_name, countdown |
| `event` | `shubh-event` | Acara | schedule, maps, calendar |
| `gallery` | `shubh-gallery` | Galeri | gallery |
| `rsvp` | `shubh-rsvp` | Konfirmasi Kehadiran | rsvp, messages |
| `footer` | none | Penutup | seo |

The invitation card itself remains the source identity. The optional event, gallery, and RSVP blocks use the existing CMS data and are not rendered when their contract section is disabled. `home` includes opening CTA and countdown; it must never duplicate couple identity in a second navbar/home block.

Visual capabilities are intentionally source-scoped: `accent_color`, `heading_font`, `body_font`, `hero_background`, `hero_overlay`, `ornament_left`, and `ornament_right`. Image capabilities use canonical media references and safe fallback to bundled local assets. `hero_background` is treated as a cover/hero visual only; ornaments remain source assets unless overridden by a supported image capability.

## Yami Buzzy contract

Source 2 has a broad editorial/storytelling flow. Its contract keeps the major source sections and localizes all interface copy:

| ID | DOM ID | Indonesian label | Embedded capabilities |
|---|---|---|---|
| `home` | `yami-home` | Beranda | wedding, guest_name, countdown, calendar |
| `couple` | `yami-couple` | Mempelai | parents, media |
| `event` | `yami-event` | Acara | schedule, countdown, maps, calendar |
| `dresscode` | `yami-dresscode` | Dress Code | none |
| `story` | `yami-story` | Kisah Kami | story |
| `gallery` | `yami-gallery` | Galeri | gallery |
| `video` | `yami-video` | Video | media |
| `gift` | `yami-gift` | Hadiah | gift |
| `invitation` | `yami-invitation` | Undangan | maps, schedule |
| `rsvp` | `yami-rsvp` | RSVP | rsvp, messages |
| `closing` | `yami-closing` | Terima Kasih | seo |

The source's album and wedding gallery are represented as one CMS-owned gallery to avoid duplicate media ownership. The source welcome modal is an opening layer, not a navigable section; it is safe to close and does not block the main document when JavaScript is unavailable. Video section supports a configured `media.video`/existing media URL if available and otherwise renders a non-breaking placeholder/fallback rather than a broken external player.

Visual capabilities are source-scoped: `accent_color`, `heading_font`, `body_font`, `hero_background`, `welcome_background`, `section_background_home`, `section_background_couple`, `section_background_event`, and `hero_overlay`. No gallery background capability is added. The admin media selector/reset path is the existing CMS path.

## Indonesian locale policy

All static labels and fallback copy are Bahasa Indonesia. Examples include `Buka Undangan`, `Kepada Yth.`, `Kami Mengundang Anda`, `Menuju Hari Bahagia`, `Tentang Kami`, `Kisah Kami`, `Galeri`, `Dress Code`, `Hadiah`, `Lokasi Acara`, `Konfirmasi Kehadiran`, `Kirim Ucapan`, `Hari`, `Jam`, `Menit`, `Detik`, and `Terima Kasih. Data supplied by users remains unchanged.

## legacy preset removal scope

The legacy preset key must be removed from `config.php` registry and built-in list, `app/theme-contract.php`, `app/theme-renderer.php` preset allow-list and any legacy preset-specific header branch, the retired theme directory, and all tests/fixtures/assertions. No global behavior of DewanaKL, Rainier, Archak, Parang, Pawiwahan, or Custom may be modified.

## Validation decisions

Targeted tests must assert registry/contract symmetry, no legacy preset directory or source reference, rendering for both new keys, section gating that removes both section and nav anchor, canonical media override/reset, Indonesian static labels, escaped guest/name values, and no accidental remote asset dependence. Responsive checks are performed at 1440, 1280, 1024, 768, 576, 390, and 360 px.
