# Audit Role Media Preset

## Scope

Audit ini memeriksa kesesuaian antara default media schema, contract admin capability, panel Admin, pipeline upload canonical, dan renderer built-in untuk tujuh preset aktif plus Custom.

## Baseline findings

| Preset | Renderer memakai bride | Renderer memakai groom | Renderer memakai couple | Cover/hero media | Panel Cover & Foto sebelum fix | Status |
|---|---:|---:|---:|---:|---:|---|
| DewanaKL | Ya | Ya | Ya sebagai fallback cover/home; tidak ada kartu couple terpisah | `media.cover` atau `media.couple_photo` fallback dan visual `hero_background` | Tampil setelah fix | Resolved: role contract dan fallback cover/home aktif |
| Shubh Vivah | Tidak | Tidak | Tidak | visual `hero_background` dengan fallback artwork source | Tidak tampil | Media panel/gallery valid; role couple tidak diminta oleh source boundary |
| Yami Buzzy | Tidak; avatar placeholder `B` | Tidak; avatar placeholder `G` | Tidak | visual `hero_background` dan `welcome_background` | Tidak tampil | P1: contract/media capability ada, renderer mengabaikan canonical couple photos |
| Rainier | Tidak | Tidak | Tidak | visual `hero_background` | Tidak tampil | Media role couple tidak dibuktikan oleh source boundary |
| Archak | Ya | Ya | Ya | `couple_photo` fallback untuk hero; cover fallback | Tidak tampil | P1: renderer siap, UI capability gate hilang |
| Parang | Ya | Ya | Tidak | visual `hero_background`/pattern; tidak memakai `media.cover` | Tidak tampil | P1: renderer siap untuk bride/groom, UI capability gate hilang |
| Pawiwahan | Ya | Ya | Tidak | `media.cover`/`background_hero` fallback | Tidak tampil | P1: renderer siap, UI capability gate hilang |
| Custom | CMS-native | CMS-native | CMS-native | full CMS media model | Tampil | Baseline reference |

## Root causes

The contract gives built-in presets the generic `media` admin capability but does not declare a media-role capability for `cover`, `bride_photo`, `groom_photo`, or `couple_photo`. The Admin sidebar and the detailed `Cover & Foto` panel are gated only by `cover`, so the upload/select forms are unreachable for every built-in preset even though the POST handlers and generic File Manager buttons exist.

The Yami Buzzy renderer declares a `couple` section with `media` embedded capability, but renders literal `B` and `G` avatar placeholders instead of canonical `media.bride_photo`, `media.groom_photo`, or `media.couple_photo`. This is a contract-to-renderer mismatch independent of the DewanaKL Admin gate.

## Applied correction

An explicit contract-level media role map is now authoritative. The Cover & Foto panel renders only when the active preset has at least one role consumed by its renderer: DewanaKL (`cover`, `bride_photo`, `groom_photo`, `couple_photo`), Archak (`cover`, `bride_photo`, `groom_photo`, `couple_photo`), Parang (`bride_photo`, `groom_photo`), Pawiwahan (`cover`, `bride_photo`, `groom_photo`), and Yami Buzzy (`bride_photo`, `groom_photo`, `couple_photo`). Shubh Vivah and Rainier remain on the generic Media Manager without misleading couple-photo controls because their current source boundaries do not consume those roles. Yami Buzzy avatars now use canonical bride/groom photos, fall back to `couple_photo`, and retain the original letter placeholders when no custom image is configured.

The generic File Manager role buttons use the same contract, so unsupported assignments are hidden rather than silently storing media that the active renderer ignores.

## Evidence files

- `app/theme-contract.php`: built-in admin capabilities currently contain `media` but not `cover`.
- `admin/index.php`: the role upload handlers exist, but the sidebar and panel are gated by `adminCapabilityEnabled('cover')`.
- `themes/dewankl/layout.php`, `themes/archak/layout.php`, `themes/parang/layout.php`, and `themes/pawiwahan/layout.php`: canonical bride/groom roles are consumed.
- `themes/dewankl/layout.php`: `couple_photo` is consumed as the home cover fallback when `media.cover` is empty.
- `themes/yami-buzzy/layout.php`: couple cards currently render literal `B` and `G` placeholders.
- `tools/media_pipeline_smoke.php` and `tools/media_requirement_smoke.php`: upload processing is covered, but role-to-renderer and Admin visibility were not covered.
