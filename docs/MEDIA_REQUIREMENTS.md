# Media Requirement Matrix

This document describes the presentation requirements consumed by the existing shared canonical media pipeline. Upload handlers identify the media role and selected preset; `media_requirement($role, $preset)` resolves the final declarative policy; the processor converts, verifies, persists, and cleans up the media. Theme renderers consume only the resulting media URL.

## Fit semantics

| Policy | Meaning | Upscaling |
|---|---|---|
| `exact` | The output canvas must match `width × height`; crop is permitted only when the presentation requires that canvas. | Allowed for an explicit target canvas. |
| `cover` | The output fills the declared exact canvas or aspect ratio and crops the excess from the centered focal area. | Allowed only for an explicit target canvas; aspect-ratio cover does not upscale small images. |
| `maximum` | The output is bounded by `max_width` and `max_height` while preserving source ratio. | Never. |
| `preserve` | The source ratio is preserved and the image is resized down only when it exceeds the declared maximum. | Never. |

## Global requirements

These policies apply to Custom mode and to built-in presets unless a source-backed preset override is declared.

| Role | Width | Height | Fit | Crop | Upscale | Evidence |
|---|---:|---:|---|---|---|---|
| `generic` | max 2400 | max 1600 | preserve | No | No | Safe bounded default for otherwise unclassified raster media. |
| `cover` | max 1600 | max 1200 | preserve | No | No | Built-in renderers use CSS crop/frame behavior rather than one universal upload canvas. |
| `background` | max 2400 | max 1600 | preserve | No | No | Full-viewport/background surfaces differ by source and use CSS `cover`/fill behavior. |
| `bride_photo` | max 1600 | max 1600 | preserve | No | No | Couple frames crop at presentation time in DewanaKL, retired preset, Archak, Parang, and Pawiwahan. |
| `groom_photo` | max 1600 | max 1600 | preserve | No | No | Same source-backed circular/square frame behavior as bride photos. |
| `couple_photo` | max 1800 | max 1200 | preserve | No | No | Used by source adapters as a bounded couple/hero image without a universal canvas. |
| `gallery` | max 1600 | max 1200 | preserve | No | No | Natural gallery images in DewanaKL, retired preset, Rainier, and Pawiwahan; legacy ownership remains explicit. |
| `story` | max 1200 | max 900 | preserve | No | No | Story/timeline media remains bounded and ratio-preserving. |
| `qris_image` | max 1200 | max 1200 | preserve | No | No | QR/gift images must remain readable without distortion. |
| `og_image` | 1200 | 630 | cover | Yes | Yes | Open Graph requires the standard exact 1200×630 canvas. |
| `theme_asset` | max 2400 | max 1600 | preserve | No | No | Theme assets are separate from Wedding Media; alpha is preserved where supported. |

## Preset-specific overrides

Only source-backed differences are declared. There are no empty override arrays.

| Preset | Role | Width | Height | Fit | Crop | Upscale | Source evidence |
|---|---|---:|---:|---|---|---|---|
| DewanaKL | `cover` | max 1600 | max 1600 | preserve | No | No | `themes/dewankl/style.css` and retained source `css/guest.css`: `.img-center-crop` is a fixed 13rem square with `object-fit: cover`; preserving a square bound avoids discarding portrait detail before CSS framing. |
| Parang | `gallery` | max 1200 | max 1200 | cover, 1:1 | Yes | No | `themes/parang/style.css`: `.parang-gallery-item { aspect-ratio: 1; }` and its image uses `object-fit: cover`. |
| Parang | `theme_asset` | no forced canvas | no forced canvas | preserve | No | No | `themes/parang/assets/`: gunungan, wayang, pattern, and other decorative assets are not photographic Wedding Media; transparent raster alpha is retained. SVG/vector assets are not rasterized by this policy. |
| Pawiwahan | `background` | max 1600 | max 2400 | preserve | No | No | Localized source `themes/pawiwahan/assets/css/pawiwahan.css`: `.hero` uses full-viewport `background-size: cover`; source hero fallback is portrait 640×960. A universal landscape crop would destroy the source composition. |
| Pawiwahan | `cover` | max 1600 | max 2400 | preserve | No | No | `themes/pawiwahan/style.css`: carousel media is `width:100%; max-height:520px; object-fit:cover`, mobile max-height 360px; source fallback is portrait. |

## Preset audit summary

| Preset | Hero/background presentation | Couple presentation | Gallery presentation | Requirement result |
|---|---|---|---|---|
| DewanaKL | Full container image with CSS `object-fit: cover` and gradient mask. | Fixed 13rem circular `object-fit: cover` frame. | Bootstrap carousel with natural `img-fluid` images. | Only `cover` receives a square maximum override; background/gallery remain global bounded preserve. |
| retired preset | Full-height hero section with source hero/background assets and CSS presentation. | Source couple assets are 400×400 square images; visual framing remains in CSS. | Mixed landscape/portrait natural thumbnails in responsive Bootstrap grid. | All roles remain global preserve/maximum policies. |
| Rainier | `min-height:100vh` hero with absolute full-surface background and dynamic source image set. | No universal fixed couple-photo upload boundary. | No gallery capability in the source contract. | Global bounded preserve policy. |
| Archak | Background-image cover surfaces and responsive full/half viewport registry frame. | 280×280 circular `object-fit:cover` frame in the current adapter. | Fixed-height 300px masonry images with `object-fit:cover`. | Global bounded preserve; CSS owns final crop to retain source flexibility. |
| Parang | Repeating decorative background pattern; photographic overrides are still CSS background surfaces. | 12rem circular `object-fit:cover` portraits. | Fixed 1:1 gallery cards with `object-fit:cover`. | Gallery receives the 1:1 cover override; decorative Theme Assets stay preserve/alpha-safe. |
| Pawiwahan | Full-viewport `.hero` cover background with portrait source composition. | 200px circular Bootstrap images. | Full-width carousel images with max-height and CSS cover. | Background and cover receive portrait-preserve overrides; gallery remains natural preserve. |
| Custom | CMS-native renderer and user-controlled layout. | No built-in source frame is imposed by a preset adapter. | CMS-native gallery ownership and rendering. | Uses global policies only. |

## Ownership and cleanup

Processing and Gallery membership remain separate. A file processed into `uploads/gallery/` is not a Gallery item until explicitly referenced in Gallery configuration. File Manager exposes canonical WebP images, while unique, explicitly referenced, unverified, or not-yet-migrated legacy files remain available for review.

Replacement processing is atomic: the new image is processed and verified first, configuration references are updated and persisted, and the old asset is removed only when no longer referenced. Processing failures preserve the source and remove partial temporary outputs. Backup and restore continue to cover the complete `uploads/` tree, including preset-scoped Theme Assets; restore does not depend on deleted source JPG/PNG files.

## Implementation references

The requirement catalog is in `config.php` (`media_requirements()` and `media_requirement()`). Aspect-ratio verification is implemented by `verify_webp_output()`, and the existing `process_image_to_webp()` consumes exact, cover, maximum, and preserve policies through the ImageMagick/GD paths. Tests are in `tools/media_pipeline_smoke.php` and `tools/media_requirement_smoke.php`.

## Sources

1. [DewanaKL source repository](https://github.com/dewanakl/undangan)
2. [retired preset source repository](#retired-preset-source)
3. [Rainier source repository](https://github.com/Rainier-PS/Invitation-Template)
4. [Archak source repository](https://github.com/archakNath/wedding-invitation-website)
5. [Pawiwahan source repository](https://github.com/parta99/pawiwahan)
6. Parang: user-provided Stitch HTML design reference recorded in `app/theme-contract.php` and `docs/ATTRIBUTIONS.md`.
