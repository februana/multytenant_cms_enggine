# Complete Architectural Audit


---

<!-- BEGIN AUDIT.md -->

# Phase 1 Architectural Audit

## Scope and method
Inspected every tracked repository file outside `vendor/` with `rg --files -g '!vendor' -g '!vendor/**'`, dependency/path searches with `rg`, and targeted source reads with `sed`/`nl`. This report is intentionally evidence-based and proposes no code changes.

## Executive findings
1. Two PHP application roots remain active: repository-root runtime (`index.php`, `config.php`, `admin/`) and legacy `/app` runtime (`app/index.php`, `app/config.php`, `app/save.php`, etc.). Root frontend requires root `config.php`; app frontend conditionally requires `app/config.proposed.php` before `app/config.php`, so the two frontends can use different loaders and paths.
2. Configuration has multiple sources of truth. Root `config.php` reads/writes only root `config.json`; `app/config.php` reads `config/site.json`, then modular `theme/sections/seo`, but writes only root `config.json`; `app/config.proposed.php` reads/writes `app/config.json` and app-local paths.
3. Calendar generation is path-split. Root `config.php` writes `ROOT_DIR/event.ics`; `app/config.php` writes `../event.ics` but checks for `app/event.ics` before generating; `app/config.proposed.php` writes app-local `event.ics`.
4. Upload storage is inconsistent. Root admin stores uploads in root `uploads/*`; legacy `app/upload.php` and `app/gallery.php` use `app/uploads` or `app/gallery`, and frontend JavaScript calls `/app/gallery.php`.
5. Guest links are path-split. Root and app config resolve root `guest-links.json`, but `app/config.proposed.php` resolves app-local `guest-links.json`.
6. Backup/restore depend on root `config.php` and therefore operate on root paths only; they do not include modular `config/*.json`, so a restore can omit runtime config used by `app/config.php`.

## No-file-left-unclassified inventory
| File | Class | Required now | Problems | Refactor disposition |
|---|---|---:|---|---|
| `index.php` | Canonical frontend candidate | Yes if root webroot | Uses root config only; duplicates `app/index.php` | Keep as sole frontend or route app to it |
| `config.php` | Canonical config candidate | Yes for root/admin | Does not merge `config/*.json`; differs from app config | Make only config implementation |
| `config.json` | Canonical write target currently | Yes | Competes with `config/*.json` | Choose one source; migrate atomically |
| `admin/index.php` | Canonical admin | Yes | Saves to `save_config()` root `config.json`; modular files can override | Keep; align loader/writer |
| `admin/backup.php` | Canonical admin utility | Yes | Omits `config/*.json`; uploads zip path bug risk from `substr` with root uploads | Fix after source-of-truth decision |
| `admin/restore.php` | Canonical admin utility | Yes | Restores only root files/uploads; not modular config | Fix allowlist after config decision |
| `admin/qr.php` | Canonical utility | Optional | Requires Composer autoload | Keep if QR generation used |
| `admin/app.js` | Canonical admin asset | Yes | Client-side guest-link post targets admin form | Keep |
| `admin/style.css` | Canonical admin asset | Yes | None architectural | Keep |
| `app/index.php` | Duplicate/legacy frontend | Maybe deployed by Nginx fallback | Duplicates root frontend; app asset URLs; conditional proposed config | Remove after routing migration |
| `app/config.php` | Duplicate config | Maybe deployed | Merges modular config but writes root config; event.ics check/write mismatch | Fold needed behavior into root config |
| `app/config.proposed.php` | Temporary/legacy fallback layer | No for production | If present, app endpoints bypass current `app/config.php` and use app-local config/uploads/event/guest-links | Remove after audit/refactor |
| `app/save.php` | Canonical RSVP endpoint for app URLs | Yes if frontend JS posts there | Uses proposed config if present; DB path may differ | Keep endpoint but require single config |
| `app/messages.php` | Canonical messages endpoint for app URLs | Yes | Uses proposed config if present | Keep endpoint but require single config |
| `app/gallery.php` | Legacy/Duplicate gallery API | Yes because JS calls it | Scans `app/uploads`/`app/gallery` instead of configured root gallery | Replace with config-backed API |
| `app/upload.php` | Legacy admin upload | No if `admin/` is canonical | Separate auth/constants/storage and no config update | Remove/disable after migration |
| `app/admin-rsvp.php` | Legacy admin RSVP | No if `admin/` is canonical | Separate auth route | Remove/merge |
| `app/export-rsvp.php` | Duplicate export | Maybe linked incorrectly | Root admin links `/export-rsvp.php` but file exists only under app | Move/link canonical export |
| `app/whatsapp-redirect.php` | Legacy endpoint | Maybe unused | Uses constants not defined by current app config defaults | Remove or reimplement via load_config |
| `app/index.html` | Unused static duplicate | No | Static stale copy bypasses config | Remove |
| `app/script.js` | Frontend asset | Yes for app frontend | Calls `/app/gallery.php`, `/app/messages.php`, `/app/save.php` | Keep but align endpoints |
| `app/style.css` | Frontend asset | Yes | None architectural | Keep/relocate |
| `config/site.json` | Duplicate config source | Yes for app/config loader | Can override `config.json` writes | Reconcile or remove |
| `config/theme.json` | Duplicate modular override | Yes for app/config loader | Overrides media written by admin to `config.json` | Reconcile or remove |
| `config/sections.json` | Duplicate modular override | Yes for app/config loader | Overrides sections independently | Reconcile or remove |
| `config/seo.json` | Duplicate modular override | Yes for app/config loader | Overrides SEO/admin saves | Reconcile or remove |
| `composer.json` | Canonical dependency manifest | Yes for QR | No autoload used except `admin/qr.php` | Keep if dependency installed |
| `README.md`, `DEPENDENCIES.md`, `FINAL_DEPLOYMENT_GUIDE.md`, `FINAL_PROJECT_STATUS.md`, `CHANGELOG.md`, `PROJECT_CLEANUP.md` | Documentation | Yes historically | Some statements contradict current code | Update after refactor |
| `deploy/*` | Deployment | Yes | Nginx fallback points to `/app/index.php`; docs describe app-root deployment | Update after canonical decision |

## Production-impact breakpoints
- Cover upload bug: root admin updates `config.json` via `save_config()`, but app frontend loader merges `config/theme.json` after `config.json`, so `theme.json` media cover can override the newly uploaded cover.
- `event.ics`: root config writes root `event.ics`; app config checks `app/event.ics` but writes root `event.ics`; proposed config writes app `event.ics`.
- `guest-links.json`: root/app current configs use root file; proposed config uses app-local file.
- Gallery: admin-managed gallery lives under root `uploads/gallery` and config gallery items; `/app/gallery.php` ignores config and scans `app/uploads`/`app/gallery`.


## Complete repository file classification
| File | Classification |
|---|---|
| `.env.example` | Canonical environment template |
| `.github/workflows/php.yaml` | Canonical CI configuration |
| `.gitignore` | Canonical repository hygiene config |
| `.htaccess` | Legacy/alternate Apache config |
| `.vscode/extensions.json` | Temporary developer tooling |
| `.vscode/settings.json` | Temporary developer tooling |
| `ARCHITECTURE.md` | Temporary audit deliverable |
| `AUDIT.md` | Temporary audit deliverable |
| `CALL_GRAPH.md` | Temporary audit deliverable |
| `CHANGELOG.md` | Canonical documentation, needs update |
| `CONFIGURATION_FLOW.md` | Temporary audit deliverable |
| `DATA_FLOW.md` | Temporary audit deliverable |
| `DEPENDENCIES.md` | Canonical documentation, needs update |
| `DEPENDENCY_GRAPH.md` | Temporary audit deliverable |
| `FILE_DEPENDENCY.md` | Temporary audit deliverable |
| `FINAL_DEPLOYMENT_GUIDE.md` | Canonical documentation, needs update |
| `FINAL_PROJECT_STATUS.md` | Canonical documentation, needs update |
| `LEGACY_CODE.md` | Temporary audit deliverable |
| `PROJECT_CLEANUP.md` | Canonical documentation, needs update |
| `README.md` | Canonical documentation, needs update |
| `REFACTOR_PLAN.md` | Temporary audit deliverable |
| `admin/app.js` | Canonical |
| `admin/backup.php` | Canonical |
| `admin/index.php` | Canonical |
| `admin/qr.php` | Canonical |
| `admin/restore.php` | Canonical |
| `admin/style.css` | Canonical |
| `app/admin-rsvp.php` | Legacy/Duplicate |
| `app/config.php` | Legacy/Duplicate |
| `app/config.proposed.php` | Temporary/Removable |
| `app/export-rsvp.php` | Legacy/Duplicate |
| `app/gallery.php` | Legacy/Duplicate |
| `app/index.html` | Temporary/Removable |
| `app/index.php` | Legacy/Duplicate |
| `app/messages.php` | Legacy/Duplicate |
| `app/save.php` | Legacy/Duplicate |
| `app/script.js` | Legacy/Duplicate |
| `app/style.css` | Legacy/Duplicate |
| `app/upload.php` | Legacy/Duplicate |
| `app/whatsapp-redirect.php` | Legacy/Duplicate |
| `composer.json` | Canonical |
| `config/sections.json` | Duplicate configuration source |
| `config/seo.json` | Duplicate configuration source |
| `config/site.json` | Duplicate configuration source |
| `config/theme.json` | Duplicate configuration source |
| `config.json` | Canonical |
| `config.php` | Canonical |
| `deploy/CONFIG_MIGRATION.md` | Canonical deployment, needs update |
| `deploy/DB_DEPLOY.md` | Canonical deployment, needs update |
| `deploy/PHASE_3_4_TESTS.md` | Canonical deployment, needs update |
| `deploy/backup-runtime.sh` | Canonical deployment, needs update |
| `deploy/health-check.sh` | Canonical deployment, needs update |
| `deploy/install.sh` | Canonical deployment, needs update |
| `deploy/nginx-site.conf` | Canonical deployment, needs update |
| `deploy/restore-runtime.sh` | Canonical deployment, needs update |
| `deploy/update.sh` | Canonical deployment, needs update |
| `index.php` | Canonical |

## JSON analysis
| JSON file | Readers | Writers | Order/fallback risk |
|---|---|---|---|
| `config.json` | root `config.php`; app `config.php` fallback; backup/restore | root/app `save_config()` depending include | Root source for admin writes; can be overridden by modular files in app loader |
| `config/site.json` | app `config.php` preferred base | no in-app writer found | Preferred over `config.json`, so admin writes can be ignored |
| `config/theme.json` | app `config.php` modular merge | no in-app writer found | Merged after base; can override uploaded cover/music/background |
| `config/sections.json` | app `config.php` modular merge | no in-app writer found | Merged after theme |
| `config/seo.json` | app `config.php` modular merge | no in-app writer found | Merged last; can override SEO admin saves |
| `composer.json` | Composer tooling | developers/deploy | Dependency manifest only |
| `guest-links.json` (runtime, not tracked if absent) | `load_guest_links()` | `save_guest_links()` | Root/current app vs app-proposed path split |

## Path analysis summary
- `app/` references in deploy config and docs are canonical for the current deployment scripts but legacy for the desired root architecture.
- `../` in `app/config.php` intentionally reaches root files; `../` in deploy scripts runs sibling scripts from deployment location.
- `ROOT_DIR` means repository root in root `config.php`, but means `/app` in app configs; therefore identical helper names resolve different physical files.
- `uploads/` is canonical at root for admin, legacy under `app/uploads` for `app/gallery.php`/`app/upload.php`.
- `config/` is read only by `app/config.php`, not root `config.php`.


<!-- END AUDIT.md -->

---

<!-- BEGIN ARCHITECTURE.md -->

# Architecture Map

## Current architecture that exists

### Root application architecture
- Entry point: `index.php` requires root `config.php`, calls `load_config()`, and renders dynamic invitation HTML from the returned array.
- Admin: `admin/index.php` requires root `../config.php`, initializes session, calls `load_config()`, performs action switch, and normally persists through `save_config()`.
- Runtime files: root `config.json`, root `guest-links.json`, root `event.ics`, root `uploads/*`, and the SQLite database resolved by root `config.php`.

### App-directory architecture
- Entry point: `app/index.php` conditionally requires `app/config.proposed.php` if readable, otherwise `app/config.php`, then calls `load_config()`.
- RSVP/messages: `app/save.php` and `app/messages.php` use the same proposed-first fallback.
- Legacy gallery/upload: `app/gallery.php` and `app/upload.php` use `app/uploads` or `app/gallery` and are not connected to configured root uploads.
- Deployment: deploy Nginx configs use `/app/index.php` as fallback, so this architecture may be the actual production web path.

## Why both exist
The project history matches the code: root files were introduced as application root, while older `/app` files remain. `app/config.php` contains comments stating it is consolidated for app endpoints, while root `config.php` remains separately implemented. Deployment docs and Nginx config still reference `/app/index.php`.

## Canonical architecture that should survive
Canonical should be repository-root application root:
- single config implementation: root `config.php`;
- single frontend entry: root `index.php` or an explicit wrapper that requires it;
- single public media tree: root `uploads/`;
- single JSON source-of-truth strategy;
- app endpoints either removed or kept as thin compatibility shims that require root config and use root paths.

## Current required compatibility
Until Nginx/deployment are changed, `/app/index.php`, `/app/save.php`, `/app/messages.php`, and frontend assets may still be required because deployed configs explicitly fall back to `/app/index.php` and frontend JS targets `/app/*` endpoints.


<!-- END ARCHITECTURE.md -->

---

<!-- BEGIN DEPENDENCY_GRAPH.md -->

# Dependency Graph

## PHP include graph
| File | Dependency |
|---|---|
| `index.php` | `require_once __DIR__ . '/config.php'` |
| `admin/index.php` | `require_once __DIR__ . '/../config.php'` |
| `admin/backup.php` | `require_once __DIR__ . '/../config.php'` |
| `admin/restore.php` | `require_once __DIR__ . '/../config.php'` |
| `admin/qr.php` | `require_once __DIR__ . '/../vendor/autoload.php'` |
| `app/index.php` | dynamic: `app/config.proposed.php` if readable, else `app/config.php` |
| `app/save.php` | dynamic: `app/config.proposed.php` if readable, else `app/config.php` |
| `app/messages.php` | dynamic: `app/config.proposed.php` if readable, else `app/config.php` |
| `app/upload.php` | `require_once __DIR__ . '/config.php'` |
| `app/admin-rsvp.php` | `require_once __DIR__ . '/config.php'` |
| `app/export-rsvp.php` | `require_once __DIR__ . '/config.php'` |
| `app/whatsapp-redirect.php` | `require_once __DIR__ . '/config.php'` |

## Autoload usage
- Composer autoload is referenced only by `admin/qr.php`.
- `composer.json` requires `endroid/qr-code`.

## Directory dependencies
- Root config: `uploads/cover`, `uploads/music`, `uploads/gallery`, `uploads/background`, `config.json`, `guest-links.json`, `event.ics`.
- App config: `../uploads/*`, `../config.json`, `../guest-links.json`, writes `../event.ics`, reads `../config/*.json`.
- Proposed app config: app-local `uploads/*`, `config.json`, `guest-links.json`, `event.ics`.
- Legacy gallery/upload: app-local `app/uploads` or `app/gallery`.

## Dynamic includes/fallbacks
The proposed-first pattern in `app/index.php`, `app/save.php`, and `app/messages.php` is the highest-risk dynamic include because `app/config.proposed.php` is present in the repository and therefore always wins over `app/config.php` when readable.


<!-- END DEPENDENCY_GRAPH.md -->

---

<!-- BEGIN CALL_GRAPH.md -->

# Call Graph

## Frontend root request
`GET /` -> `index.php` -> `config.php` -> `load_config()` -> `config.json` -> optional `write_event_ics()` -> render HTML -> `app/style.css` + `app/script.js`.

## Frontend app request
`GET /app/index.php` -> proposed-first config include -> `load_config()` -> either app-local config (proposed) or root/modular config (app config) -> render HTML -> `/app/style.css` + `/app/script.js`.

## RSVP submit
Browser form -> `/app/save.php` -> proposed-first config include -> CSRF/session checks -> SQLite `tamu` table create/migrate -> insert RSVP -> JSON response.

## Messages
Browser -> `/app/messages.php` -> proposed-first config include -> SQLite readonly query -> JSON response.

## Gallery API
Browser -> `/app/gallery.php` -> scan `app/uploads` if present else `app/gallery` -> create thumbnails in same tree -> JSON list.

## Admin dashboard
`GET /admin` -> `admin/index.php` -> root `config.php` -> `init_session()` -> `load_config()` -> render forms, gallery, guest links, recent RSVP.

## Admin save actions
`POST /admin` -> CSRF -> switch action -> mutate `$config` or guest links -> most actions call `save_config()` -> `config.json` + `event.ics`; guest-link actions call `save_guest_links()` instead.

## Backup
`GET /admin/backup.php` -> root config -> `require_admin()` -> zip `CONFIG_FILE`, root `event.ics`, root `guest-links.json`, optional DB, `UPLOADS_DIR`.

## Restore
`POST /admin/restore.php` -> root config -> `require_admin()` -> validate ZIP entries -> copy allowed top files and `uploads/` into root.


<!-- END CALL_GRAPH.md -->

---

<!-- BEGIN FILE_DEPENDENCY.md -->

# File Dependency Map

## Frontend
- `index.php` depends on `config.php`, `config.json`, `event.ics`, `app/style.css`, `app/script.js`, media paths in config.
- `app/index.php` depends on `app/config.proposed.php` or `app/config.php`, `/app/style.css`, `/app/script.js`, media paths in whichever config loaded.
- `app/script.js` depends on `/app/save.php`, `/app/messages.php`, `/app/gallery.php` endpoints and DOM IDs rendered by the frontend templates.

## Admin
- `admin/index.php` depends on root `config.php`, root config JSON, root uploads, SQLite DB, `admin/app.js`, `admin/style.css`.
- `admin/app.js` depends on hidden inputs rendered by `admin/index.php` for guest links and QR preview.
- `admin/backup.php` and `admin/restore.php` depend on root config constants.

## Upload handlers
- Root admin upload actions use `upload_file()` from root `config.php`, storing under root `UPLOADS_*` and writing relative paths into `config.json` through `save_config()`.
- `app/upload.php` directly stores images in `app/uploads` or `app/gallery` and does not update config.
- `app/gallery.php` reads `app/uploads` or `app/gallery`, not root gallery config.

## Deployment
- `deploy/install.sh`, `deploy/nginx-site.conf`, and DB docs depend on `/app/index.php` fallback and app-directory database paths.


<!-- END FILE_DEPENDENCY.md -->

---

<!-- BEGIN DATA_FLOW.md -->

# Data Flow

## Cover upload
Start: `admin/index.php` action `upload_cover`. Store: `upload_file()` to root `uploads/cover`. Config write: `media.cover = relative_path()` then `save_config()` to root `config.json`. Render: root frontend reads root `config.json`; app frontend may read `config/theme.json` after `config.json`, causing stale cover if theme overrides media.

## Gallery upload
Start: `admin/index.php` action `upload_gallery`. Store: root `uploads/gallery`. Config write: gallery item added to `config.json`. Render: templates use `get_gallery_items()` from config, but `app/script.js` also calls `/app/gallery.php`, which scans app-local directories and can miss root uploads.

## Background upload
Start: admin background actions. Store: root `uploads/background`. Config write: media background fields in `config.json`. Render: root/app template uses loaded config; app modular theme can override.

## Music upload
Start: admin `upload_music`. Store: root `uploads/music`. Config write: `media.music` in `config.json`. Render: frontend audio source from loaded config; app modular config can override.

## QRIS upload
Start: admin `upload_qris`. Store: root uploads. Config write: gift/media QRIS path in `config.json`. Render: frontend gift section from loaded config.

## RSVP
Start: frontend form JavaScript. Endpoint: `/app/save.php`. Storage: SQLite `tamu` table at `DB_PATH` from proposed/app config. Read: `/app/messages.php`, admin RSVP table, export scripts. Break risk: root admin DB path and proposed/app DB path can differ.

## Guest links
Start: admin guest-link UI. Storage: `save_guest_links()` to `GUEST_LINKS_FILE`. Root/app current config use root file; proposed config uses app-local file. Break risk: endpoint choice changes which file is used.

## Backup
Start: `/admin/backup.php`. Output: ZIP containing root config, root event/guest-links, DB basename, uploads. Break: modular `config/*.json` are not included.

## Restore
Start: `/admin/restore.php`. Input: ZIP. Output: copies allowed files to root. Break: modular config not restored; DB basename copied to root, not necessarily `DB_PATH` directory when DB is external.

## Configuration save/load
Admin saves root `config.json`; app current loader prefers `config/site.json` and merges modular overrides; proposed loader uses app-local `config.json`. This is the central data-flow break.


<!-- END DATA_FLOW.md -->

---

<!-- BEGIN CONFIGURATION_FLOW.md -->

# Configuration Flow

## Places that create defaults
- `config.php`, `app/config.php`, and `app/config.proposed.php` each define `config_defaults()`.

## Places that load config
- Root: `config.php::load_config()` reads only root `config.json`.
- App current: `app/config.php::load_config()` starts with defaults, prefers `../config/site.json`, falls back to `../config.json`, then merges `../config/theme.json`, `sections.json`, `seo.json`.
- App proposed: `app/config.proposed.php::load_config()` reads app-local `config.json`.

## Places that write config
- Root/admin: root `save_config()` writes root `config.json` and root `event.ics`.
- App current: writes `../config.json` and `../event.ics`.
- App proposed: writes app-local `config.json` and app-local `event.ics`.

## Source-of-truth determination
There is more than one source of truth. Runtime can read `config.json`, `config/site.json`, `config/theme.json`, `config/sections.json`, `config/seo.json`, or app-local `app/config.json` if proposed config is used. Admin writes only root `config.json`, so any read path that prefers/merges another file can diverge.

## Required refactor outcome
Choose exactly one persisted source or implement a single read/write abstraction that updates all canonical shards consistently. Remove proposed-first config fallback before relying on any audit fix.


<!-- END CONFIGURATION_FLOW.md -->

---

<!-- BEGIN LEGACY_CODE.md -->

# Legacy and Duplicate Code

## Legacy architecture
- `/app` contains a complete old application: frontend, config, upload admin, RSVP admin/export, gallery API, static index, script/style.
- Deployment still references `/app/index.php`, making legacy paths operational rather than dead.

## Duplicate functions/helpers
Duplicated in root `config.php`, `app/config.php`, and `app/config.proposed.php`: dotenv loader, upload directory constants, config defaults, load/save config, guest-link helpers, session/auth helpers, upload validation, gallery item helpers, event ICS generation, WhatsApp/calendar builders.

## Duplicate endpoints
- Frontend: `index.php` and `app/index.php`.
- Admin upload/gallery: `admin/index.php` upload actions versus `app/upload.php` and `app/gallery.php`.
- RSVP export/admin: `admin/index.php` table, `app/admin-rsvp.php`, `app/export-rsvp.php`.

## Compatibility/fallback layers
- proposed-first include in app endpoints.
- config fallback from modular `site.json` to legacy `config.json` in `app/config.php`.
- gallery fallback from `app/uploads` to `app/gallery`.
- DB fallback to legacy app storage in `app/config.php`.

## Unused/removable candidates after verification
`app/config.proposed.php`, `app/index.html`, `app/upload.php`, `app/admin-rsvp.php`, `app/whatsapp-redirect.php`, and one of the duplicate frontend/config implementations. They are not safe to delete until deployment routing and external links are checked.


<!-- END LEGACY_CODE.md -->

---

<!-- BEGIN REFACTOR_PLAN.md -->

# Refactor Plan

## Constraints
No functional refactor should begin until production routing is confirmed, because deploy configs still point to `/app/index.php` while root files appear intended as canonical.

## Phase 2 recommended steps
1. Freeze canonical target: repository root as app root, root `config.php` as only config implementation, root `uploads/` as only media tree.
2. Replace app config endpoints with thin compatibility shims requiring root config, or update Nginx and frontend URLs to root equivalents.
3. Remove `app/config.proposed.php` from runtime path first; it is an active override, not a proposal.
4. Resolve config source of truth: either keep monolithic `config.json` or migrate to modular config with one writer. Do not keep admin writing one file while runtime reads another later override.
5. Fix `event.ics` generation check/write paths so the check and write target are identical.
6. Fix gallery API to use `get_gallery_items()` and root uploads, or remove API if server-rendered config gallery is canonical.
7. Update backup/restore to include the selected config source and correct DB target semantics.
8. Remove legacy upload/admin/static files only after logs/routes confirm no traffic.
9. Add regression tests for cover upload, config save/load, event.ics location, guest-links location, gallery rendering, RSVP DB path, backup/restore coverage.

## Risk-reduction order
Config fallback removal -> path constants consolidation -> endpoint routing -> data migration -> file deletion. This order minimizes user-visible regressions.


<!-- END REFACTOR_PLAN.md -->