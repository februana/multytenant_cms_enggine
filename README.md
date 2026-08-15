# Wedding Invitation CMS

This repository is a premium wedding invitation platform built with PHP, SQLite, and JSON configuration. The implementation follows a CMS-first, single-root architecture:

CMS → `config.json` → `config.php` → theme resolver → `themes/<preset>/layout.php` → frontend

## Current architecture

### Canonical public entrypoints

- `index.php` — public invitation controller; it prepares shared data and delegates the complete HTML document to the active theme layout
- `admin.php` — redirect wrapper to the admin UI
- `save.php` — public wrapper for save actions
- `messages.php` — public wrapper for message APIs
- `gallery.php` — public wrapper for gallery APIs

### Theme presets

Official presets are:

- `dewankl`
- `elix`
- `rainier`
- `archak`

The canonical setting is `theme.theme_preset`. Unknown preset values safely fall back to `dewankl`. Theme layouts are the single frontend document renderer; `index.php` must not emit a second HTML document.

### Canonical frontend assets

Each active theme loads its own `style.css` and `script.js`. `custom.css`, when present, is loaded after the theme CSS so CMS Custom CSS can override theme styles.

### Core implementation and config

- `config.php` — configuration loader, defaults, and helpers
- `config.json` — authoritative CMS configuration source
- `database.sqlite` — RSVP/message storage
- `uploads/` — canonical media storage
- `admin/` — CMS UI, upload flow, backup/restore endpoints
- `app/` — shared private implementation, including theme helpers and renderer

## Repository layout

```text
/
├── index.php
├── admin.php
├── save.php
├── messages.php
├── gallery.php
├── config.php
├── config.json
├── database.sqlite
├── uploads/
├── admin/
├── app/
├── themes/
│   ├── dewankl/
│   ├── elix/
│   ├── rainier/
│   └── archak/
├── style.css
├── script.js
├── custom.css
├── guest-links.json
├── backups/
├── deploy/
└── docs / legacy notes (kept only when still accurate)
```

## CMS ownership rules

The CMS owns the effective rendering contract for:

- cover / hero media
- section visibility
- theme preset and resolved theme values
- gallery images
- love story content
- background and media configuration
- guest links
- upload metadata and references
- Custom CSS

The frontend consumes the values the CMS exposes and must not create a competing renderer or media pipeline.

## Deployment

Source and runtime are intentionally separate:

```text
~/webserver_undangan
        │
        │ deploy/install.sh
        ▼
/var/www/wedding
```

Use the existing deployment scripts as the repo's operational path:

- `deploy/install.sh` — fresh install; creates `/var/www/wedding` when needed
- `deploy/update.sh` — update an existing site while preserving runtime data
- `deploy/backup.sh` — backup user data and config
- `deploy/restore.sh` — restore from backup
- `deploy/health-check.sh` — runtime health verification

Do not manually maintain a second application copy under `/var/www/wedding`; it is deployment output, not the Git working tree.

## Fresh-install verification

From the repository working tree:

```bash
sudo rm -rf /var/www/wedding
cd ~/webserver_undangan
sudo bash deploy/install.sh
sudo /var/www/wedding/deploy/health-check.sh
```

A fresh-install test must use the installer to create the runtime directory. Do not create `/var/www/wedding` manually.

## Backup and runtime data

The runtime data that must be preserved across updates keeps the current repo contract:

- `config.json`
- `guest-links.json`
- `database.sqlite`
- `uploads/`
- `event.ics`
- `custom.css` when present
- `backups/`

## Important constraints

- `config.json` is the single source of truth for sections and theme.
- Disabled sections must not be rendered in the frontend.
- Live Preview uses the same theme renderer and existing `theme-preview:update` bridge as production.
- `app/theme-helper.php` is the canonical source for shared frontend helpers.
- Custom CSS loads after the active theme CSS.
- Do not create a second frontend or media pipeline in `app/`.
- Do not treat `/var/www/wedding` as the Git source tree.

See `ARCHITECTURE.md`, `DEPLOYMENT.md`, `BACKUP_RESTORE.md`, and `SECURITY.md` for repo-aligned operational details.
