# Architecture Documentation

## Overview

This wedding invitation application uses a **single-root architecture**.
The repository root is the canonical application source. Frontend rendering, configuration loading, and public entrypoints all originate from root-level files.

## Canonical Public Entry Points

- `index.php` — public frontend renderer
- `admin.php` — redirect to the admin UI (`/admin/`)
- `save.php` — AJAX save endpoint
- `messages.php` — messages API endpoint
- `gallery.php` — gallery API endpoint

## Canonical Frontend Assets

- `style.css`
- `script.js`

## Core Backend

- `config.php`
- `config.json`
- `database.sqlite`
- `uploads/`
- `admin/`
- `deploy/`

## Repository Structure

```
/ (Document Root)
├── index.php              # Frontend entry point
├── admin.php              # Redirect to /admin/
├── save.php               # AJAX save wrapper
├── messages.php           # Messages wrapper
├── gallery.php            # Gallery wrapper
├── config.php             # Configuration loader and helpers
├── config.json            # Stored CMS configuration
├── database.sqlite        # RSVP and message storage
├── uploads/               # Public media uploads
├── admin/                 # Admin UI and backup endpoints
├── app/                   # Private implementation / legacy compatibility
├── style.css              # Canonical stylesheet
├── script.js              # Canonical JavaScript
├── backups/               # Backup storage
└── deploy/                # Deployment scripts
```

### `app/` and compatibility

The `app/` directory is a private implementation layer and is not the canonical frontend architecture.
Root-level files remain the development target. Do not move active frontend development into `app/`.

## Request Flow

- Static assets are served directly from the root: `style.css`, `script.js`, `uploads/*`
- The public invitation is rendered by `index.php` using values from `config.json`
- `admin.php` redirects to `admin/index.php`
- `save.php`, `messages.php`, and `gallery.php` include private app logic for API operations

## Configuration and CMS

- `config.php` loads `config.json`, applies defaults, and saves configuration changes
- `config.json` stores site content, wedding details, theme values, and media paths
- `custom.css` can override frontend presentation after theme variables load

## Background Ownership Rules

The CMS is the source of truth for media rendering.

The CMS owns:

- uploaded media
- background images
- hero images
- section backgrounds
- desktop background settings
- mobile background settings
- image fit
- image position
- repeat mode
- future media configuration

The frontend owns:

- layout
- spacing
- typography
- overlays
- ornaments
- animation
- presentation

Frontend must only consume values produced by the CMS. It must never replace CMS rendering.

## Upload Flow

- Admin uploads media through the admin UI
- `upload_file()` is the canonical upload pipeline
- Uploaded files are stored under `uploads/`
- Media references are saved in `config.json`
- The public site reads those references from `config.json`

## Deployment

- `deploy/install.sh` — recommended for fresh installs to `/var/www/wedding`
- `deploy/update.sh` — recommended update path for existing installations
- `deploy/health-check.sh` — verifies runtime health
- `deploy/backup.sh` — creates user-data backups
- `deploy/restore.sh` — restores backups

## Backup & Restore

Backups are stored in `backups/` and include user data, not source code.
The canonical backup path is `deploy/backup.sh`, and restore is `deploy/restore.sh`.

## Rules for Future Development

- Do not duplicate frontend assets in `app/`
- Do not create parallel implementations between root and `app/`
- New frontend work must target root-level files unless explicitly instructed otherwise
- If deployment wrappers require updates, synchronize them from canonical files
- Preserve existing theme builder and live preview behavior

## Maintenance Notes

- Root files are the authoritative public APIs
- `app/` is private/legacy and may support internal implementations, but it is not the primary public architecture
- `admin.php` is a redirect, not a controller wrapper like `save.php`

## Security Notes

- Sensitive runtime files are protected by `.htaccess` and permissions:
  - `config.json`
  - `database.sqlite`
  - `.env`
  - `guest-links.json`
  - `backups/`

- `uploads/` should remain writable by the web server but not executable

