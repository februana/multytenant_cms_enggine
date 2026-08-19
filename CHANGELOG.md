# Changelog

## [Unreleased]

This unreleased change set is prepared for PR #84 and covers the multi-preset CMS, deployment, provenance, and default-content finalization.

### Added

- Added the `shubh-vivah` preset from `vinitshahdeo/wedding-website` and the `yami-buzzy` preset from `Tynab/Yami-Buzzy`, with source-compatible adapters, Indonesian UI localization, guest identity, calendar metadata, media fallbacks, and the existing RSVP backend bridge.
- Added preset-aware visual customization for section backgrounds, Theme Assets, named color palettes, heading/body font catalogs, previews, reset-to-default behavior, and canonical Media Manager references.
- Added Indonesian default wedding copy for FEBRUANA and ANDI MUHAMAD BASUKI, including Febru/Andi calls, Arabic Bismillah, Islamic opening and closing, and QS. Ar-Rum 21. Clearing Admin fields restores the corresponding defaults.
- Added Docker image and Compose healthchecks, persistent named volumes for CMS state, uploads, backups, and optional WebDAV data, plus deployment smoke assertions for those contracts.
- Added local provenance records for the Shubh Vivah MIT notice and the unresolved Yami Buzzy source-license status.

### Changed

- Updated the shared runtime directory contract and deployment documentation for seven built-in presets plus Custom.
- Hardened the Docker entrypoint so initial environment substitution safely handles sed replacement characters and `.env` remains permission-restricted after recursive ownership normalization.
- Expanded the Docker build ignore rules to exclude local runtime data while retaining `.env.example` for entrypoint bootstrap.
- Reconciled README, architecture, deployment, attribution, backup/restore, and release records with the current CMS capability and persistence model.
- Clarified that native Ubuntu/Linux deployment is the supported non-Docker target; no unimplemented Render Blueprint or managed-cloud manifest is claimed by the repository.
- Removed redundant `pdo_sqlite` and `sqlite3` compilation from Docker. The official `php:8.3-apache` image already builds both extensions against system SQLite; recompiling them caused the Render build to fail with `Cannot find config.m4`.

### Fixed

- Restored source-template fallback behavior when an Admin-selected background or Theme Asset reference is cleared without deleting the physical upload.
- Removed stale references to a retired preset, outdated six-adapter counts, and incorrect attribution reference numbering.
- Preserved optional-media semantics: missing cover, music, gallery, Open Graph, or love-story media remains a warning or suppressed behavior rather than a fabricated required asset.

### Removed

- Removed the retired Elix preset license residue from `docs/licenses/`.
- Removed the stale `docs/RENDER.md` guide that claimed missing `render.yaml`, `docker/render-entrypoint.sh`, and `docker/render.ini` files.

### Media Role Audit Follow-up

- Added an explicit per-preset media-role contract so the Admin exposes only the cover, bride, groom, and couple-photo controls consumed by the active renderer.
- Restored DewanaKL's reachable bride/groom upload and assignment flow, added couple-photo upload with cover/home fallback behavior, and prevented unsupported couple-photo actions from appearing in presets that do not render them.
- Wired Yami Buzzy couple avatars to canonical bride/groom media, with `couple_photo` fallback and the original letter placeholders retained when no custom image is configured.
- Added `docs/media-role-audit.md` and `tools/media_role_contract_smoke.php`; the full repository regression suite now covers role mapping, Admin gates, renderer output, and fallback behavior.
- Added `docs/user-input-capability-audit.md` and `tools/user_input_capability_smoke.php` to cover user-configured dresscode, QRIS, video upload/assignment, renderer output, and media reference lifecycle.
- Added canonical `media.love_story_video` upload and Media Manager assignment with MP4 validation, preview, replacement, cleanup, and backward-compatible Yami Buzzy fallback fields.
- Replaced Yami Buzzy's hardcoded dresscode timeline with Admin-configured title, color, rule, and description fields; QRIS now renders conditionally in all gift-enabled preset renderers.
