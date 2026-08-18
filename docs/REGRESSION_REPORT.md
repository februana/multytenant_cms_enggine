# Regression Report

## Scope and final status

This report records the final hardening pass for the Dynamic CMS theme architecture. The built-in presets remain source-template-owned renderers, while Custom remains the CMS-native renderer. The final suite passed after the last correction to the DewanaKL disabled `wedding_date` boundary, the Custom document shell, and Rainier's invalid cross-date `endTime` handling.

## Automated checks

| Check | Result | Evidence |
|---|---|---|
| PHP syntax lint | Passed for all PHP files in the checkout | `php -l` over `find . -name '*.php'` |
| Theme contract smoke | Passed | `tools/theme_contract_smoke.php` |
| Renderer smoke | Passed for Custom, DewanaKL, Elix, Rainier, and Archak | `tools/theme_render_smoke.php`; output sizes 8513, 47773, 13967, 7439, and 5512 bytes |
| Disabled behavior | Passed for all 14 cases | `tools/theme_disabled_smoke.php` |
| Theme switching | Passed in sequence Custom → DewanaKL → Elix → Rainier → Archak → Custom | `tools/theme_regression_smoke.php` |
| Rainier timezone | Passed for Asia/Jakarta and UTC conversion/display cases | `node tools/test_rainier_timezone.js` |
| CMS-first validator | Passed | `tools/validate.php` |
| Patch hygiene | Passed | `git diff --check` |
| Rainier AOS guard | Passed | Contract and regression checks reject AOS in Rainier output |

The disabled matrix covers DewanaKL `gallery`, `wedding_date`, and `comment`; Elix `gallery`, `story`, `rsvp`, and `gifts`; Rainier `schedule`, `quotes`, and `rsvp`; and Archak `story`, `gallery`, `stay`, and `registry`. Each case removes the corresponding presentation boundary rather than leaving an empty placeholder or a dangling template hook.

## Browser and responsive checks

A local PHP preview was verified through fresh browser navigations on desktop for all five modes. Custom rendered a styled CMS-native page after the Custom-only document shell linked the root `style.css` and `script.js`. DewanaKL exposed the original welcome/loading/modal, `#root`, `#home`, `#bride`, `#wedding-date`, `#gallery`, `#comment`, carousels, calendar/maps controls, gift controls, bottom navigation, and RSVP. Elix exposed the original `#hero`, `#home`, `#info`, `#story`, `#gallery`, `#rsvp`, `#gifts`, offcanvas navigation, countdown circles, lightbox hooks, `#my-form`, and original RSVP field vocabulary. Rainier exposed `#app`, event hooks, schedule/quotes, RSVP, footer branding, and `#audio-control`; its calendar event instant represented 12:00 Asia/Jakarta as 05:00 UTC. Archak exposed the original navigation, `.home`, `.timeline`, `#story`, `.gallery`, `#stay`, `#registry`, parting RSVP, and footer attribution.

Automated Chromium checks also rendered all five modes at **390×844**. Every mode produced a non-empty PNG and a DOM dump with its source-template markers and sentinel CMS values. Long-name responsive adapters were confirmed for Elix, DewanaKL, and Archak, while Rainier remained stable. The first Archak assertion used an over-specific class equality check; the actual source class is `home hz-margin`, and the corrected assertion passed without changing implementation behavior.

## Console results

DewanaKL, Elix, and Archak produced no browser console output during fresh initialization. Rainier produced no JavaScript error; before the final cross-date end-time correction it emitted the adapter's non-fatal `Invalid endTime` warning because the preview fixture used different ceremony and reception dates. The layout now sends an empty end time unless reception occurs later on the same date, eliminating that warning while preserving the minimal adapter contract. Headless Chromium logs contained only the environment-level DBus/UPower service warning, not a page error.

## Known deployment-data warnings

The validator reports that the clean checkout does not contain the configured sample files `uploads/cover/cover.jpg`, `music/lagu.mp3`, and the corresponding Open Graph image. These are deployment-data warnings, not code or contract failures. DewanaKL video and music rendering additionally require a valid configured media path and an existing file; the renderer intentionally suppresses absent media rather than emitting broken URLs or empty media elements.

## Acceptance status

The architecture, source fidelity, dependency posture, template-specific contracts, disabled behavior, music semantics, media gating, timezone conversion, RSVP handling, audio fallback, browser structure, and switching sequence are covered by passing automated and browser checks. Final production sign-off still depends on provisioning real deployment media and reviewing real-content lengths beyond the sentinel fixture.
