# Project Overview

This project is a professional CMS-based premium digital wedding invitation built with PHP and SQLite.
The repository root is the canonical application source, and the application runs from root-level public entrypoints.

## Architecture Summary

- Single-root application with the repository root as the canonical document root
- Public entrypoints:
  - `index.php`
  - `admin.php`
  - `save.php`
  - `messages.php`
  - `gallery.php`
- Canonical frontend assets:
  - `style.css`
  - `script.js`
- Core backend:
  - `config.php`
- Configuration file:
  - `config.json`
- Upload directory:
  - `uploads/`
- Database:
  - `database.sqlite`

## Runtime Layout

- `index.php` renders the public invitation and injects theme variables from `config.json`
- `admin.php` redirects to the admin UI at `/admin/`
- `save.php`, `messages.php`, and `gallery.php` are thin public wrappers that include private logic from `app/`
- `app/` is a private compatibility layer, not the primary frontend architecture

## CMS Ownership Rules

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

Frontend must always consume values from the CMS and must never become the source of truth.

## Deployment and Maintenance

- `deploy/install.sh` is the recommended fresh install path
- `deploy/update.sh` is the preferred update path for existing installations
- `deploy/backup.sh` and `deploy/restore.sh` manage user-data backups

## Future Development

- Do not add duplicate frontend assets under `app/`
- Do not create parallel root and `app/` implementations
- Keep `style.css` and `script.js` as canonical frontend sources
- Preserve the CMS-based theme builder and live preview behavior
- Implement new media features by extending the CMS upload pipeline, not bypassing it

## Notes

This file reflects the current repository architecture and is synchronized with `README.md`, `ARCHITECTURE.md`, `DEPLOYMENT.md`, `BACKUP_RESTORE.md`, and `.github/copilot-instructions.md`.
