# Changelog

## [Unreleased]

### Added
- Initial deployment repository scaffold for Februandik wedding invitation app.
- `deploy/install.sh`, `deploy/update.sh`, `deploy/backup-runtime.sh`, `deploy/restore-runtime.sh`, `deploy/health-check.sh`.
- `.env.example` and `README.md` with installation, update, backup, and security guidance.
- `app/gallery.php` and `app/upload.php` fallback support for public `uploads/` and legacy `gallery/`.

### Fixed
- Enforced `ADMIN_PASS` environment variable in `app/config.php` to avoid default weak password.
- Corrected runtime storage directory handling in deployment scripts.
- Hardened Nginx access rules to deny `/storage/` and `/deploy/`.
- Added `jq` dependency to health-check system requirements.

### Notes
- Runtime `storage/` remains gitignored for security.
- Do not commit `.env` or any runtime data.
