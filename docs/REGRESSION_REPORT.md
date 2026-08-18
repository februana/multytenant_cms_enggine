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

## Indonesian UI and user-content preservation finalization

The application-owned Admin interface is now localized in Indonesian, including the dashboard title, navigation, preset selector actions, media manager labels, story/gallery controls, gift/location/SEO/WhatsApp panels, guest-link generator, backup/settings labels, and CSV actions. Source-template identity wording in built-in frontend layouts remains intentionally unchanged.

The save path now uses `preserve_text_input()` for invitation content fields. It normalizes only CRLF/CR line endings to LF and preserves meaningful leading/trailing spaces, internal spacing, Unicode, and user wording. Rendering uses `render_preserved_text()` where HTML line breaks are required; it HTML-escapes without translating, correcting, paraphrasing, stripping tags, or rewriting content. Rainier now sends raw opening/description/closing text through event JSON and no longer applies `strip_tags()` to those fields.

`tools/content_preservation_smoke.php` covers Custom, DewanaKL, Elix, Rainier, and Archak. It verifies multilingual English/Indonesian/CJK/Korean/Arabic content, multiline opening and address values, doubled spaces, CRLF-only save normalization, JSON round-trip equality, HTML escaping/newline output, byte-for-byte Rainier event JSON fields, and the absence of automatic translation calls. The test passed together with the complete existing suite.


## PR #72 follow-up: guest-facing Indonesian UI

The four built-in guest-facing themes now translate static application/template UI into Indonesian while preserving DOM hierarchy, IDs, classes, data attributes, JavaScript behavior, dependencies, asset loading, responsive structure, and source attribution. DewanaKL now uses Indonesian calendar, loading, gallery, navigation, and gift labels. Elix now uses Indonesian navigation, countdown units, footer, audio accessibility, and RSVP labels. Rainier now uses Indonesian countdown, calendar, event-details, schedule, quote, RSVP, footer, audio, accessibility, and dynamic CMS RSVP form labels. Archak now uses Indonesian navigation, hero, timeline, story, travel/stay, registry, parting, and footer labels.

The CMS-native Custom renderer also uses Indonesian application navigation and actions. Its configured section titles such as `Love Story`, `Gallery`, `Events`, `Location`, and `Gift` remain unchanged because they are user/config content and the preservation rule prohibits automatic translation of custom titles and subtitles.

`tools/theme_localization_smoke.php` verifies Indonesian static labels across Custom and all four built-in active guest paths, forbids the confirmed visible English phrases, and asserts that `preset_selector` and `guest_links` remain global capabilities with the selector panel gated by the global capability. The existing `tools/admin_guest_smoke.php` continues to verify all five preset states and the complete switching sequence without data/media reset. Browser fixtures for Custom, DewanaKL, Elix, Rainier, and Archak were inspected; all were non-blank, showed Indonesian static UI, preserved sentinel user content and multiline text, and retained source attribution.


## PR #72 follow-up: global Settings and Guest Link origin

The root cause was confirmed in the capability layer: `theme_admin_capabilities_for_config()` merged `settings` and `backup` only for Custom mode, while built-in preset contracts did not declare them. Admin navigation and panels also used the theme-specific `$adminCapabilityEnabled()` gate. The global capability contract now owns `settings` and `backup`, and both Admin menu/panel gates use `$globalAdminCapabilityEnabled()`.

The Guest Link Generator no longer falls back silently to `window.location.origin`, `/`, or `example.com`. Clean-install configuration now starts with an empty site URL. When the origin is missing, the Admin displays an actionable Indonesian configuration warning, the preview remains empty, the browser generator refuses to create a URL and reports that Site URL must be configured, and the server-side URL helper returns an empty result. A configured origin such as `https://test.example.id` produces `https://test.example.id/?to=Budi`.

`tools/admin_guest_smoke.php` now verifies global Settings and Backup availability in Custom, DewanaKL, Elix, Rainier, and Archak; the Settings save action; configured-origin URL generation; missing-origin behavior; browser fallback removal; and origin persistence across `custom → dewankl → elix → rainier → archak → custom` switching. The complete existing suite and the localization/content-preservation tests remain passing.

## Follow-up Audit — Canonical Media and State Integrity

The follow-up audit fixed the visual image capability by replacing the duplicate visual file picker with a selector backed by the existing Media Library. Visual image values now persist as canonical `uploads/...` references or validated HTTPS URLs, and media usage detection/rename propagation includes `theme_visuals` so referenced assets cannot be deleted or silently disconnected.

The Admin editor now resolves the active preset from the current UI state only. Unsaved theme and visual values are snapshotted per preset during switching, while reset and cancel clear only the currently selected preset. Custom preview state now starts from the backend-provided `theme_custom_config()` payload rather than merging a second frontend representation.

The new `tools/visual_media_e2e_smoke.php` test saves canonical background references through the production config path, reloads them, verifies all five preset adapters/public renderers, resets Rainier in isolation, and confirms Elix reset returns to the source background. The final run completed with 155 passing assertions and no reported failures.

The Elix adapter also restores readable contrast for greeting, names, and invitation message on the dark hero background while preserving Pacifico brush typography, compact countdown behavior, and the original DOM order.


## Latest deployment and runtime alignment

The deployment contract was updated after the Pawiwahan and canonical media additions. `deploy/runtime-directories.sh` is now the shared source of truth for runtime paths. It creates the upload roots, `uploads/theme-assets/`, and preset-scoped Theme Assets directories for DewanaKL, Elix, Rainier, Archak, Parang, Pawiwahan, and Custom.

`deploy/install.sh`, `deploy/update.sh`, and `docker/entrypoint.sh` all use this contract. Fresh native installs create the directory tree and `custom.css`; updates preserve the complete `uploads/` tree and recreate missing empty directories without replacing user media; Docker creates the same tree on every container start and persists it through the `wedding_uploads` volume. The Docker entrypoint also initializes `/var/data/custom.css` and links it into the application root.

`deploy/health-check.sh` now checks all six built-in theme adapters, the active preset, runtime config/database/guest-link/ICS files, required upload and preset-scoped Theme Assets directories, writable runtime storage, and ImageMagick or PHP GD WebP availability. Optional user media remains a warning; missing runtime directories, unsupported active presets, missing processing capability, missing state, or security failures are blockers.

The deployment smoke test now executes the shared directory contract in an isolated fixture and verifies every upload root plus all seven preset Theme Assets directories. Shell syntax, PHP lint, deployment smoke, and the complete repository regression suite passed after this update.

## Configurable Opening Greetings and Pawiwahan Angpau

The six built-in preset registries now expose a schema-driven `Salam Pembuka` textarea in the existing Theme Options admin panel. Defaults are `Assalamualaikum Warahmatullahi Wabarakatuh` for DewanaKL, `Bismillahirrahmanirrahim` for Elix, Rainier, Archak, and Parang, and `OM Swastiastu` for Pawiwahan. User-entered values are stored under `theme_options[<preset>].opening_greeting`, preserve Unicode and line breaks, and render through the shared escaped text resolver without hardcoding the final greeting in the preset layout.

Pawiwahan’s Angpau trigger and modal now use stable CMS-specific IDs. The adapter explicitly opens the Bootstrap modal when the Bootstrap bundle is available and provides a native modal/backdrop fallback when it is unavailable. The account-copy control and status feedback use the same stable namespaced IDs. The focused Pawiwahan smoke test and all-preset renderer smoke both pass.

An isolated Chromium check rendered the Pawiwahan page with `OM Swastiastu`. Because the sandbox could not load the external Bootstrap JavaScript (`window.bootstrap.Modal` was unavailable), the native fallback path was exercised; it opened the modal with `display: block`, `modal fade show`, `aria-modal="true"`, and `aria-expanded="true"` on the trigger.

## Deployment update and backup/restore hardening

The production deployment lifecycle was hardened around the canonical runtime directory contract. `deploy/backup.sh` now stages optional runtime files and the complete `uploads/` and `webdav/` trees in a secure temporary directory, includes Apache WebDAV credentials only under `.webdav/davpasswd`, validates archive existence, size, listing, and expected entries, restricts archives to mode `600`, and retains the latest ten valid archives without deleting older valid backups after a failed creation.

`deploy/restore.sh` now validates the archive before modification, rejects absolute paths, traversal paths, unexpected entries, and symbolic/hard links, extracts into a private temporary directory, restores runtime state without a second media store, supports legacy `_temp_backup/davpasswd` archives, restores WebDAV credentials only when present, and recreates missing canonical Theme Assets directories after legacy restores. Permissions are applied to secrets, runtime files, uploads, WebDAV, and backup directories without a blanket recursive `chmod 755`.

`deploy/update.sh` now aborts before source replacement when backup creation fails, keeps the SSH Git transport and exposes clone stderr, verifies the cloned source, preserves user data in a separate temporary directory, uses hidden-file-safe synchronization, never leaks `_preserve` into the web root, preserves legacy `storage/`, restores all runtime/media namespaces, runs health validation, and emits `UPDATE COMPLETE` only after critical post-update checks succeed. Migration mode uses the same backup gate.

`tools/deployment_backup_restore_smoke.sh` exercises populated and legacy backup/restore fixtures, WebDAV credentials, filenames with spaces, hidden Theme Assets, traversal rejection, successful SSH-shaped clone, failed clone diagnostics and non-modification, backup gating, canonical directory bootstrap, user-data preservation, and `_preserve` non-leakage. The isolated smoke test completed successfully with 7 grouped assertions.
