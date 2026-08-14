# Architecture Documentation

# Architecture

## Overview

This application uses a single-root CMS-first architecture. The repository root is the canonical application source and the public document root.

## Canonical public entrypoints

- `index.php` — public frontend renderer
- `admin.php` — redirect to `/admin/`
- `save.php` — public wrapper for save actions
- `messages.php` — public wrapper for message APIs
- `gallery.php` — public wrapper for gallery APIs

## Canonical frontend and runtime files

- `style.css` — canonical stylesheet
- `script.js` — canonical frontend JavaScript
- `config.php` — config loader and runtime helper logic
- `config.json` — single source of truth for CMS values
- `database.sqlite` — database for RSVP and messages
- `guest-links.json` — guest link store
- `uploads/` — canonical media directory
- `admin/` — CMS UI and admin operations
- `deploy/` — deployment and health-check scripts

## Repository structure

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
├── guest-links.json
├── uploads/
├── admin/
├── app/
├── style.css
├── script.js
├── custom.css
├── backups/
├── deploy/
└── legacy docs / compatibility notes
```

## Request flow

- Public pages render via `index.php` using `config.php` and `config.json`.
- Admin UI is served from `admin/index.php`.
- Public wrappers call private app logic as needed, but root-level files remain the public interface.
- Frontend styles and behavior remain canonical in root-level `style.css` and `script.js`.

## CMS ownership rules

The CMS is the source of truth for:

- section visibility
- media references and upload metadata
- hero/background media
- theme preset resolution
- guest links and saved content
- live preview state

The frontend consumes these values and must not replace the CMS-owned rendering logic.

## Deployment

- `deploy/install.sh` — fresh install
- `deploy/update.sh` — update existing site
- `deploy/backup.sh` — backup runtime data
- `deploy/restore.sh` — restore runtime data
- `deploy/health-check.sh` — verify critical deployment dependencies

## Backups and runtime data

Backups preserve user and runtime data without replacing the source code. Current runtime data includes:

- `config.json`
- `guest-links.json`
- `database.sqlite`
- `uploads/`
- `event.ics`
- `custom.css` when present

## Security notes

- Sensitive runtime files must remain blocked from direct public access.
- `uploads/` is static content only; PHP execution should remain disabled there.
- `config.json`, `database.sqlite`, and `guest-links.json` require restrictive permissions.
- `app/` remains private implementation and legacy compatibility; it is not a public-facing frontend architecture.

