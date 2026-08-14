# Wedding Invitation CMS

This repository is a premium wedding invitation platform built with PHP, SQLite, and JSON configuration. The current implementation follows a CMS-first pattern:

CMS → config.json → config.php → renderer → frontend

## Current architecture

### Canonical public entrypoints

- `index.php` — public invitation renderer
- `admin.php` — redirect wrapper to the admin UI
- `save.php` — public wrapper for save actions
- `messages.php` — public wrapper for message APIs
- `gallery.php` — public wrapper for gallery APIs

### Canonical frontend assets

- `style.css`
- `script.js`

### Core implementation and config

- `config.php` — configuration loader, defaults, and helpers
- `config.json` — authoritative CMS configuration source
- `database.sqlite` — RSVP/message storage
- `uploads/` — canonical media storage
- `admin/` — CMS UI, upload flow, backup/restore endpoints
- `app/` — private implementation/legacy compatibility, not the canonical frontend

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

The frontend only consumes the values the CMS exposes and must not replace CMS rendering logic.

## Deployment

Use the existing deployment scripts as the repo’s operational path:

- `deploy/install.sh` — fresh install
- `deploy/update.sh` — update existing site
- `deploy/backup.sh` — backup user data and config
- `deploy/restore.sh` — restore from backup
- `deploy/health-check.sh` — runtime health verification

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
- Live Preview uses the same renderer logic as production.
- Do not create a second frontend or media pipeline in `app/`.
- Do not treat `app/` as the canonical public architecture.

See `ARCHITECTURE.md`, `DEPLOYMENT.md`, `BACKUP_RESTORE.md`, and `SECURITY.md` for repo-aligned operational details.
