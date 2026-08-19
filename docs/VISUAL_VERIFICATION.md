# Visual Verification

## DewanaKL

The existing DewanaKL split desktop/mobile composition, guest welcome surface, home, bride/groom, event/countdown, gallery, Love Gift, wishes/RSVP, music control, and bottom navigation remain covered by the established regression suite. No changes were made to this preset in the new-preset task.

## Rainier, Archak, Parang, Pawiwahan, and Custom

The established render, contract, and preservation smoke tests continue to cover these retained presets. Their registry entries, layouts, source assets, and CMS behavior were not redesigned or replaced by the new adapters.

## Shubh Vivah

Fixture URL used for browser verification: `http://127.0.0.1:8090/.tmp-responsive/shubh-vivah.html`.

The page rendered a centered invitation card with source floral corner artwork, script typography, Indonesian opening greeting, long CMS names, guest label, countdown, and `Buka Undangan` CTA. Clicking the CTA navigated to `#shubh-event` and exposed the `Acara Pernikahan` section. Automated screenshots were generated at 1440, 1280, 1024, 768, 576, 390, and 360 px. The 360 px capture retained the card within the viewport; long names and the CTA wrapped without visible horizontal clipping.

## Yami Buzzy

Fixture URL used for browser verification: `http://127.0.0.1:8090/.tmp-responsive/yami-buzzy.html`.

The page rendered a source-style full-bleed photographic hero with a centered welcome modal, Indonesian opening copy, long CMS names, and `Buka Undangan`. After activation, the modal closed and the hero navigation exposed Indonesian labels including `Konfirmasi Kehadiran`. The navigation link reached `#yami-rsvp`, where the RSVP form was visible. DOM verification at a 1280 px browser viewport found all primary section anchors and reported `horizontalOverflow: false`. Automated screenshots were generated at 1440, 1280, 1024, 768, 576, 390, and 360 px.

## Evidence files

Detailed observations and screenshot paths are recorded in [`new-presets-responsive-evidence.md`](new-presets-responsive-evidence.md). Source audit and CMS mapping are recorded in [`new-presets-source-audit.md`](new-presets-source-audit.md) and [`new-presets-cms-mapping.md`](new-presets-cms-mapping.md).
