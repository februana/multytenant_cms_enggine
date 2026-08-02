# Project Overview

This project is a premium digital wedding invitation CMS built with PHP and SQLite.
It renders the public invitation from root-level files while storing content, theme settings, media references, and guest data in the CMS backend.

# Architecture Overview

This is a **single-root application**.
The repository root is the canonical application source and the public document root.

## Canonical public entrypoints

- `index.php`
- `admin.php`
- `save.php`
- `messages.php`
- `gallery.php`

## Canonical frontend

- `style.css`
- `script.js`

## Core backend

- `config.php`

## Configuration

- `config.json`

## Uploads

- `uploads/`

## Database

- `database.sqlite`

## Theme Builder

The CMS stores theme presets and resolved theme variables in `config.json`.
`index.php` injects theme values into the public page, while `style.css` and `script.js` remain the canonical frontend presentation layer.

## Live Preview

The admin UI supports live preview behavior. Changes are previewed before save and are only persisted to `config.json` when the administrator saves.

# Repository Structure

The repository root is the authoritative application.
Root-level files and directories are the primary development target.

```
/ (Document Root)
├── index.php              # Public frontend renderer
├── admin.php              # Redirect wrapper to /admin/
├── save.php               # AJAX save wrapper
├── messages.php           # Messages API wrapper
├── gallery.php            # Gallery API wrapper
├── config.php             # Configuration loader and helpers
├── config.json            # Stored CMS configuration
├── database.sqlite        # RSVP/message storage
├── uploads/               # Public media uploads
├── admin/                 # Admin UI and backup endpoints
├── app/                   # Private implementation / legacy compatibility
├── style.css              # Canonical stylesheet
├── script.js              # Canonical JavaScript
├── backups/               # Backup archive storage
└── deploy/                # Deployment scripts
```

## `app/` and Legacy Compatibility

The `app/` directory contains private implementation and legacy logic.
It is not the primary frontend architecture.
Developers must not treat `app/` as the main public application or duplicate root-level frontend assets there.

# CMS Ownership Rules

The CMS is the single source of truth for media and rendering configuration.

The CMS owns:

- uploaded media
- background rendering
- image fit
- image position
- repeat mode
- desktop background configuration
- mobile background configuration
- future media configuration

The frontend owns:

- layout
- spacing
- typography
- overlays
- ornaments
- animation
- presentation

The frontend must only consume CMS-provided values and must never replace CMS rendering.

# Future Development

Future media and upload features must extend the CMS upload pipeline and not bypass it.
Do not create parallel media rendering or duplicate frontend assets in `app/`.

Example future features:

- Upload Manager / Media Library
- ImageMagick support
- GD fallback
- Automatic WebP conversion
- AVIF support
- EXIF auto orientation
- Responsive images
- Media metadata
- Thumbnail generation

# Deployment

- Use `deploy/install.sh` for fresh installations.
- Use `deploy/update.sh` for existing installations.
- `deploy/backup.sh` creates user-data backups.
- `deploy/restore.sh` restores backups.
- `deploy/health-check.sh` verifies deployment health.

`deploy/install.sh` deploys the application to `/var/www/wedding` by default.
`deploy/update.sh` preserves user data and configuration while updating application files.

# Backup & Restore

Backups preserve user data and configuration while excluding source code.

Backed up items include:

- `config.json`
- `custom.css`
- `guest-links.json`
- `database.sqlite`
- `uploads/`
- `webdav/` (if present)
- `event.ics`
- `/etc/apache2/.davpasswd` (if present)

# Notes

- `admin.php` is a redirect wrapper, not a controller.
- `save.php`, `messages.php`, and `gallery.php` are public wrappers that include private `app/` logic.
- `style.css` and `script.js` are the canonical frontend assets.
- `config.php` is the core configuration loader used by the public frontend and admin UI.
- `config.json` is the CMS configuration store.

See `ARCHITECTURE.md`, `DEPLOYMENT.md`, `BACKUP_RESTORE.md`, and `.github/copilot-instructions.md` for synchronized architecture guidance.
