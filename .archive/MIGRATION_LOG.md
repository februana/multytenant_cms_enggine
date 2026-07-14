# Phase 2a: Cleanup Duplikat Root — Migration Log

**Date**: 2026-07-14 09:44:28  
**Project**: Wedding Invitation CMS  
**Strategy**: Opsi A — Consolidate to `app/` as canonical source  

## Backup Information

- **Full Backup**: `backup_20260714_094428.tar.gz` (4.3M)
- **Backup Time**: 2026-07-14 09:45
- **Archive Dir**: `.archive/`

## Files to Move to `unused/`

### Frontend Assets (Duplicates of `app/`)
1. **index.html** → `.archive/unused/index.html`
   - Reason: Exact duplicate of `app/index.html`
   - Status: Safe to remove (static fallback, not needed)

2. **style.css** → `.archive/unused/style.css`
   - Reason: Nearly identical to `app/style.css`
   - Status: Safe to remove (app/ is deployed)

3. **script.js** → `.archive/unused/script.js`
   - Reason: Nearly identical to `app/script.js`
   - Status: Safe to remove (app/ is deployed)

### PHP Endpoints (Duplicates of `app/`)
4. **save.php** → `.archive/unused/save.php`
   - Reason: Duplicate of `app/save.php` (RSVP submission)
   - Status: Safe to remove (app/ is deployed)

5. **messages.php** → `.archive/unused/messages.php`
   - Reason: Duplicate of `app/messages.php` (RSVP list)
   - Status: Safe to remove (app/ is deployed)

6. **gallery.php** → `.archive/unused/gallery.php`
   - Reason: Duplicate of `app/gallery.php` (gallery JSON)
   - Status: Safe to remove (app/ is deployed)

7. **upload.php** → `.archive/unused/upload.php`
   - Reason: Duplicate of `app/upload.php` (gallery upload/delete)
   - Status: Safe to remove (app/ is deployed)

8. **whatsapp-redirect.php** → `.archive/unused/whatsapp-redirect.php`
   - Reason: Duplicate of `app/whatsapp-redirect.php`
   - Status: Safe to remove (app/ is deployed)

9. **export-rsvp.php** → `.archive/unused/export-rsvp.php`
   - Reason: Duplicate of `app/export-rsvp.php` (CSV export)
   - Status: Safe to remove (app/ is deployed)

10. **admin-rsvp.php** → `.archive/unused/admin-rsvp.php`
    - Reason: Duplicate of `app/admin-rsvp.php` (RSVP admin)
    - Status: Safe to remove (app/ is deployed)

### Config Files (Requires Analysis)
11. **config.php** → `.archive/unused/config.php.root`
    - Reason: Similar to `app/config.php` but subtle differences
    - Status: KEEP ROOT TEMPORARILY for comparison during Phase 3
    - Note: Will be analyzed and merged in Phase 3

### Files to Keep in Root
- `.htaccess` — Nginx rewrite rules
- `config.json` — Shared by both root and app
- `event.ics` — Generated file, can be in root
- `composer.json` — Shared dependency
- `.env.example` — Environment template
- `CHANGELOG.md`, `README.md` — Documentation
- `admin/` — Admin panel (NOT duplicated, stays)
- `deploy/` — Deploy scripts (stay)
- `music/` — Media assets (stay)
- `.archive/` — Backup and migration

### Files Not Yet in Root (Already in `app/` only)
- `app/config.php` — canonical (will review in Phase 3)

## Migration Strategy

1. **Phase 2a-i**: Move frontend assets to unused
2. **Phase 2a-ii**: Move API endpoints to unused
3. **Phase 2a-iii**: Keep config.php for analysis
4. **Phase 2a-iv**: Health check
5. **Phase 2a-v**: Create symlinks or update imports if needed
6. **Phase 2a-vi**: Verify health check passes
7. **Phase 2a-vii**: Generate report

## Backward Compatibility

- If external systems reference `root/save.php` etc., will add symlinks or redirects
- `.htaccess` might need update to redirect root endpoints to `app/`
- Will verify via health check

## Next Steps

After health check passes:
- Move to Phase 3: Separate Config
- Consolidate `config.php` logic
- Create modular include structure
