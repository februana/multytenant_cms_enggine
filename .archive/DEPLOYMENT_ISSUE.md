# 🚨 DEPLOYMENT ISSUE - PHASE 2A HALT

**Date**: 2026-07-14 09:50  
**Status**: ⏸️ HALTED - Awaiting User Confirmation

## Issue Description

Structural mismatch detected between source control layout and deployment expectations:

### Problem

**Nginx Configuration** (`deploy/nginx-site.conf`):
- Line 6: `index index.php index.html;`
- Line 13: `try_files $uri $uri/ /index.php?$query_string;`
- **Expects**: `index.php` to be primary entry point

**Deploy Script** (`deploy/install.sh`):
- Line 37-41: Copies ONLY `./app/` to `/opt/februandik-web` via `rsync -a`
- **Result**: Only files in `app/` folder are deployed

**Actual File Structure**:
```
ROOT LEVEL:
  index.php         ✓ (285 lines - dynamic entry point)
  config.php        ✓ (558 lines - config + helpers)
  
APP FOLDER:
  index.html        ✓ (static HTML, hardcoded data)
  config.php        ✓ (3.8KB - different from root)
  [OTHER API FILES]
  
  ❌ NO index.php in app/ folder!
```

### Deployment Failure Risk

**When deployed**:
1. Script copies `./app/` contents to `/opt/februandik-web`
2. Nginx receives request for `/` (root)
3. Nginx tries to serve `index.php` (doesn't exist)
4. Falls back to `index.html` (static, hardcoded for demo couple)
5. **Result**: Frontend works but with hardcoded data, not from `config.json`

**Actual Runtime Issue**:
- `app/index.html` is static HTML with hardcoded "Andi & Februana" data
- `root/index.php` is dynamic PHP that loads `config.json` and renders per configuration
- Frontend appears to work locally, but deployed version would show demo data permanently

### Possible Root Cause

Likely explanations:
1. **Project not yet deployed to production** - developer uses local `root/index.php` during dev
2. **Incomplete source tree** - `app/index.php` should exist but doesn't
3. **Build step missing** - `app/index.php` should be generated from `root/index.php` or template
4. **Architecture mismatch** - `app/` folder design is incomplete

### Files Moved Before Detection

Files moved to `.archive/unused/` before this issue was discovered:
- index.html ✓ (was from root)
- style.css ✓ (was from root)
- script.js ✓ (was from root)
- save.php, messages.php, gallery.php, upload.php, whatsapp-redirect.php, export-rsvp.php, admin-rsvp.php (all from root)

**These can be restored if needed.**

## Required Decisions (User Confirmation Needed)

### Option A: Create `app/index.php` from `root/index.php`
- **Action**: Copy `root/index.php` to `app/index.php` to match deployment expectations
- **Reason**: Aligns deployment with dev structure; dynamic rendering per config
- **Risk**: None (additive change)
- **Benefit**: Frontend will render per `config.json` when deployed

### Option B: Keep `app/index.html` Static + Update Nginx
- **Action**: Rename `app/index.html` to `app/index.php` and add dynamic config loading
- **Reason**: Makes `app/` self-contained
- **Risk**: Duplicates config loading logic
- **Benefit**: Self-contained deployment package

### Option C: Create entry point dispatcher
- **Action**: Create new `app/index.php` that serves either static or dynamic based on config
- **Reason**: Hybrid approach
- **Risk**: More complex

## Recommendation

**Recommend Option A** (align deployment):
1. Copy `root/index.php` → `app/index.php`
2. Continue Phase 2a cleanup (move root duplicates to archive)
3. Remove confusion between dev and deployed structure
4. Maintain single entry point pattern

This aligns with "Opsi A - use `app/` as canonical source" strategy.

## Next Steps

**AWAITING USER CONFIRMATION** before:
1. Restoring moved files (if reverting cleanup)
2. Creating `app/index.php`
3. Proceeding with Phase 2a cleanup

---

## Temporary Resolution (If User Approves Option A)

Will execute:
```bash
cp root/index.php app/index.php
# Continue Phase 2a with verified structure
```

## Files Currently in Archive

- `.archive/unused/` — contains 10 moved files (can restore if needed)
- `.archive/backup_20260714_094428.tar.gz` — full backup (4.3M)
- `.archive/MIGRATION_LOG.md` — documented migration plan
