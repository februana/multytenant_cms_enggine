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

## Admin preset filtering and global guest system

The admin execution path now resolves visible controls as the union of a small global capability set and the active preset's `admin_capabilities`. `guest_links` is explicitly global because its persistence and generator are CMS services, while theme-specific panels remain filtered. Panel bodies are server-side gated in addition to sidebar links, so unsupported controls are not merely hidden from navigation or reachable as empty sections. Custom continues to expose the full CMS-native control surface.

The new `tools/admin_guest_smoke.php` passed. It verifies the capability matrix for Custom, DewanaKL, Elix, Rainier, and Archak; asserts that unsupported controls remain absent; checks guest URL encoding and unsafe-base rejection; checks query normalization and length limits; verifies stored preset data is not mutated by filtering; and checks representative panel gates in `admin/index.php`.

The guest-link store remains the existing `guest-links.json` persistence path. Generated URLs continue to use `?to=` and are safely normalized and encoded. No second guest database or theme-specific guest-link store was introduced. Duplicate names remain possible by design because the existing store has no guest identifier; this is a documented data-model limitation rather than a filtering regression.

The personalized greeting path is now global at the capability/service level but theme-specific in presentation. Custom renders the resolved name in its CMS-native hero; DewanaKL retains its original `#guest-name` container and uses text-safe DOM rendering; Elix retains its original hero greeting and avoids double decoding; Rainier and Archak receive escaped greeting placements in their original hero/home flows. The resolver accepts the existing `to` parameter plus legacy aliases, removes control characters, collapses whitespace, and applies a length bound before HTML escaping.

## Final documentation and deployment finalization

The repository now documents the current architecture as CMS engine → theme adapter → built-in preset, with Custom as the CMS-native builder. `docs/ATTRIBUTIONS.md` records the exact source revisions, authors, licenses, source files, integration paths, dependencies, and limitations for DewanaKL, Elix, Rainier, and Archak. `docs/ARCHITECTURE.md` documents ownership boundaries and the global Guest Link/Guest Name system. `docs/DEPLOYMENT.md` and the top-level README now describe Docker, native installation, runtime persistence, health-check semantics, backup/update behavior, and media provisioning.

Clean-checkout media was classified as optional administrator-provided deployment data. The shipped `config.php` defaults and tracked `config.json` now leave cover, music, and Open Graph image references empty. The installer creates upload directories but does not create arbitrary sample media. `public_path('')` returns a request-free empty data URL so absent optional media does not become a request to `/`. `deploy/health-check.sh` checks required application/runtime files separately from optional media and reports missing optional cover/music/Open Graph assets as `WARNING`; it exits non-zero only for required failures.

Docker persistence was finalized with `UNDANGAN_DATA_DIR=/var/data`, a persistent runtime volume for config/guest links/custom CSS/event ICS/SQLite, a separate uploads volume, a required Compose `ADMIN_PASS`, and least-privilege entrypoint permissions. Native installation now checks prerequisites, installs PHP CLI/SQLite/GD/mbstring/ZipArchive and required utilities, initializes real config defaults when needed, preserves the source checkout, and keeps `custom.css` during updates. Backup/restore now follows configured runtime paths.

Targeted deployment checks passed: `tools/deployment_smoke.php`, PHP lint, Bash syntax checks, all existing theme/disabled/regression/timezone/admin-guest smoke tests, validator, and `git diff --check`. A root-run clean fixture passed `deploy/health-check.sh` with 35 PASS, 3 WARNING for absent optional media, and 0 FAIL. The native installer preflight correctly stopped with an explicit missing-prerequisite message in this sandbox because Composer and rsync are not installed. Docker build/container verification could not be executed because the sandbox has no Docker CLI/daemon; this is recorded as an environment limitation rather than reported as a false PASS.

## Final preset selector regression correction

### Root cause

The preset selector was located inside the `#theme` Admin panel. That entire panel is rendered only when `$adminCapabilityEnabled('theme')` is true. Built-in contracts intentionally do not declare `theme` because the manual theme editor is Custom-only. As a result, built-in modes hid the selector itself, even though switching the active preset is a global CMS operation. The previous smoke test encoded `theme` as forbidden for built-ins and did not assert selector HTML placement, so it did not catch this regression.

### Correction

`preset_selector` is now an explicit global admin capability returned by `theme_contract_global_admin_capabilities()`, alongside `guest_links`. The selector is rendered in a separate `#preset-selector` panel and sidebar link gated only by `$globalAdminCapabilityEnabled('preset_selector')`, before the theme-specific panel. No built-in `admin_capabilities` list was broadened with `preset` or `theme`.

The selector posts to a new `save_preset` action. `switch_active_theme_preset_config()` changes only `theme.mode` and `theme.theme_preset`; it does not apply a full theme-value reset and does not mutate wedding data, media, sections, guest links, RSVP data, or unrelated theme configuration. The manual theme editor remains inside the existing `theme` gate and remains Custom-only for built-in modes. A hidden current-preset field is retained in the Custom theme editor for live-preview compatibility.

### New coverage

`tools/admin_guest_smoke.php` now asserts the explicit global capability contract, forbids built-in theme-specific controls as before, verifies the exact sequence `Custom → DewanaKL → Elix → Rainier → Archak → Custom`, compares configuration snapshots before/after each switch, asserts selector markup occurs outside the theme panel gate, asserts the global gate and `save_preset` action, and rejects accidental use of `$adminCapabilityEnabled('preset_selector')`.

The previous theme contract, render, disabled, timezone, deployment, and guest-system checks remain unchanged and passing. No built-in frontend DOM/CSS/JS/dependency or template fidelity code was modified for this correction.
