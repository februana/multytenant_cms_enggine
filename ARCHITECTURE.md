# Architecture Documentation

## Overview

The application uses a single-root, CMS-first architecture. The repository root is the canonical application source and is deployed as the public document root.

## Canonical request flow

```text
CMS/admin
   ↓
config.json
   ↓
config.php
   ↓
index.php (controller/data preparation)
   ↓
theme resolver
   ↓
themes/<preset>/layout.php
   ↓
complete HTML document
```

`index.php` must not emit a second HTML document. Theme layouts own the complete frontend document.

## Theme presets

The canonical setting is `theme.theme_preset`.

Supported official presets:

- `dewankl`
- `rainier`
- `archak`
- `parang`
- `pawiwahan`
- `shubh-vivah`
- `yami-buzzy`

Unknown preset values fall back safely to `dewankl`. `custom` remains a separate renderer mode where supported by the existing configuration contract.

Each theme owns its document layout and loads its own theme CSS/JS. Custom CSS is loaded after theme CSS so CMS overrides remain effective.

## Canonical public entrypoints

- `index.php` — controller and theme delegation
- `admin.php` — redirect to `/admin/`
- `save.php` — public save wrapper
- `messages.php` — public message API wrapper
- `gallery.php` — public gallery API wrapper

## Shared implementation

- `app/theme-helper.php` — canonical frontend helper functions
- `app/theme-renderer.php` — theme resolver, shared section renderer, and layout loader
- `config.php` — configuration loader and runtime helpers
- `config.json` — single source of truth for CMS values
- `themes/` — official frontend preset layouts and assets

The helper is loaded once with `require_once`; theme layouts must not redefine shared helpers.

## Preserved frontend contracts

The single-renderer architecture preserves existing behavior including:

- site SEO metadata and JSON-LD schema
- theme live preview via `theme-preview:update`
- section visibility
- theme presentation settings
- theme CSS followed by Custom CSS
- gallery, music, RSVP, maps, gift, and other existing theme functionality

## Deployment architecture

```text
~/webserver_undangan
        │
        │ deploy/install.sh
        ▼
/var/www/wedding
```

The repository is the source tree. `/var/www/wedding` is deployment output and must not be used as the Git working tree.

`deploy/install.sh` creates/populates `/var/www/wedding`. `deploy/update.sh` synchronizes source while protecting runtime data. `deploy/health-check.sh` verifies the installed runtime.

## Runtime data

Updates must preserve:

- `config.json`
- `guest-links.json`
- `database.sqlite`
- `uploads/`
- `event.ics`
- `custom.css`
- `backups/`

## Security

- Sensitive runtime files must remain blocked from direct public access.
- `uploads/` is static content only; PHP execution should remain disabled there.
- `config.json`, `database.sqlite`, and `guest-links.json` require restrictive permissions.
- `app/` is private implementation, not a second public document root.

## Theme-driven section contracts

Built-in presets are rendered through their own layout files and consume the shared CMS data/services through `app/theme-contract.php`. The contract declares theme-specific section vocabulary, consumed capabilities, admin capabilities, source metadata, and asset hints.

The legacy `sections` array remains the source of truth for `CUSTOM`, including CMS-native ordering and visibility. Built-in preset controls are stored under `theme_sections[<preset>]`; their ordering and composition remain owned by the preset renderer. Existing `sections` data is preserved for backward compatibility and is not used to force built-in themes into a universal page structure.

Built-in layouts use `theme_section_enabled($config, $preset, $section)` rather than global normalized IDs. This prevents aliases such as `story`, `gallery`, or `opening` from silently becoming universal CMS sections during built-in rendering.
