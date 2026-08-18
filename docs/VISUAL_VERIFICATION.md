# Visual Verification Log

## DewanaKL

Preview URL: `http://127.0.0.1:8000/preview.php?preset=dewankl`

The page rendered the DewanaKL-specific split desktop/mobile composition, guest welcome surface, home section, bride/groom section, wave separators, event/countdown, gallery, love gift, wishes/RSVP, music control, and bottom navigation. Dynamic sentinel names and schedule data were present in the rendered document. No browser console output was reported during the initial render.

The clean checkout does not contain the configured runtime cover/music files, so image/audio requests show the expected missing-runtime-asset behavior. This is a fixture warning, not a renderer exception.

## Elix

Preview URL: `http://127.0.0.1:8000/preview.php?preset=elix`

The initial welcome overlay rendered with a loading lifecycle. After clicking `Buka Undangan`, the page exposed Elix-specific navigation, hero, circular countdown, couple, event, gallery, location, gift, RSVP, and footer content. The browser viewport visibly differed from DewanaKL, with a centered editorial hero and circular countdown. The overlay did not remain stuck after interaction.

## Next checks

Rainier and Custom still require browser verification. The local preview endpoint is temporary and must be removed before committing the implementation.

## Rainier

Preview URL: `http://127.0.0.1:8000/preview.php?preset=rainier`

The welcome overlay rendered with Bismillah, wedding title, date, and `Buka Undangan`. After activation, the page exposed Rainier-specific sticky navigation, editorial hero, couple, event, countdown, gallery, location, gift, RSVP, footer, and music control. The browser did not show a blank screen after the opening interaction. The audited source uses CSS/IntersectionObserver-style animation rather than AOS; no AOS dependency was added by assumption.

## Custom

Preview URL: `http://127.0.0.1:8000/preview.php?preset=custom`

Custom rendered through the CMS-native shared renderer with global navigation, hero, countdown, story, gallery, event, location, RSVP, gift, music, and footer output. Its markup and ordering differ from the built-in template layouts, confirming that Custom remains a separate renderer mode rather than being forced through a built-in theme contract.

The screenshot is intentionally unstyled in the temporary preview because the local preview helper does not reproduce the full custom CSS asset pipeline. PHP smoke rendering and extracted content confirmed that the full CMS-native sections were present.
