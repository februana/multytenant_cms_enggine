# Repository baseline

origin	https://github.com/februana/multytenant_cms_enggine.git (fetch)
origin	https://github.com/februana/multytenant_cms_enggine.git (push)
main
613d3788c55619a1545d0893acd44ce297afcdbc
fix: load admin escape helper
2026-08-20T12:29:33+00:00

## Top-level
./.dockerignore
./.env.example
./.git/HEAD
./.git/config
./.git/description
./.git/index
./.git/packed-refs
./.github/copilot-instructions.md
./.gitignore
./.htaccess
./.vscode/extensions.json
./.vscode/settings.json
./AGENTS.md
./ARCHITECTURE.md
./BACKUP_RESTORE.md
./CHANGELOG.md
./CONTRIBUTING.md
./DEPLOYMENT.md
./Dockerfile
./README.md
./RELEASE_NOTES.md
./SECURITY.md
./admin.php
./admin/app.js
./admin/backup.php
./admin/index.php
./admin/profile.php
./admin/qr.php
./admin/restore.php
./admin/style.css
./admin/super-admin.php
./app/gallery.php
./app/love-story.php
./app/messages.php
./app/save.php
./app/theme-contract.php
./app/theme-helper.php
./app/theme-renderer.php
./composer.json
./composer.lock
./config.php
./deploy/apache-catchall.conf.example
./deploy/audit.sh
./deploy/backup.sh
./deploy/health-check.sh
./deploy/install.sh
./deploy/migrate.php
./deploy/restore.sh
./deploy/runtime-directories.sh
./deploy/update.sh
./docker-compose.yml
./docker/000-default.conf
./docker/entrypoint.sh
./docs/ARCHITECTURE.md
./docs/ATTRIBUTIONS.md
./docs/DEPLOYMENT.md
./docs/MULTI_TENANT.md
./docs/PASSWORD_MANAGEMENT.md
./docs/THEME_CONTRACT_MATRIX.md
./docs/THEME_FIDELITY_MATRIX.md
./docs/VISUAL_VERIFICATION.md
./docs/multytenant-auth-baseline.md
./docs/wedding-copy-research.md
./event.ics.php
./gallery.php
./index.php
./media.php
./messages.php
./save.php
./script.js
./style.css
./tools/admin_guest_smoke.php
./tools/content_preservation_smoke.php
./tools/dependency_graph_audit.php
./tools/deployment_backup_restore_smoke.sh
./tools/deployment_smoke.php
./tools/media_delete_fallback_smoke.php
./tools/media_inventory.php
./tools/media_pipeline_smoke.php
./tools/media_requirement_smoke.php
./tools/media_role_contract_smoke.php
./tools/pawiwahan_smoke.php
./tools/repo_contract_audit.php
./tools/test_rainier_timezone.js
./tools/theme_contract_smoke.php
./tools/theme_disabled_smoke.php
./tools/theme_localization_smoke.php
./tools/theme_regression_smoke.php
./tools/theme_render_smoke.php
./tools/user_input_capability_smoke.php
./tools/validate.php
./tools/visual_capability_consistency_audit.php
./tools/visual_color_font_smoke.php
./tools/visual_contract_smoke.php
./tools/visual_media_e2e_smoke.php
./tools/wedding_copy_default_smoke.php

## Candidate auth/RBAC files
./admin.php
./admin/super-admin.php
./database/migrations/001_multi_tenant.sql
./docs/MULTI_TENANT.md
./docs/multytenant-auth-baseline.md
./tools/admin_guest_smoke.php
./tools/media_role_contract_smoke.php

## References
./.env.example:6:# Main domain is inserted into tenants during install; tenant domains are added in Super Admin.
./.env.example:8:# Required for decrypting visible Tenant Admin passwords in the Super Admin dashboard.
./.env.example:15:# Optional: canonical SQLite DB path. All tenant configuration, guest links,
./.github/copilot-instructions.md:3:This repository is a pure multi-tenant PHP/SQLite wedding-invitation CMS. Keep the existing theme-adapter architecture intact while preserving tenant isolation.
./.github/copilot-instructions.md:7:1. **Tenant context is server-derived.** Resolve the active tenant from the normalized `HTTP_HOST` through `tenants.domain`. Never trust a client-provided `tenant_id` for public or Tenant Admin operations.
./.github/copilot-instructions.md:8:2. **SQLite is the runtime source of truth.** Store tenant settings, custom CSS, calendar data, and guest links in tenant-scoped database rows. Do not introduce global `config.json`, `guest-links.json`, or another global mutable store.
./.github/copilot-instructions.md:10:4. **Media is tenant-isolated.** Save files only below `uploads/tenant_<id>/`. Preserve validation, WebP conversion, preset-specific resize, original cleanup after successful conversion, and tenant-aware containment checks.
./.github/copilot-instructions.md:11:5. **Media delivery is authorized.** `/uploads/...` requests must route through `media.php`, which resolves the current host tenant, validates containment, verifies MIME type, and serves only that tenant's file.
./.github/copilot-instructions.md:24:For changes affecting deployment, media, authentication, or tenant routing, run the full regression and tenant-isolation suites before committing.
./.htaccess:12:# Never serve tenant uploads as static files. Route them through media.php,
./.htaccess:13:# which resolves the current Host tenant and checks the requested path against
./.htaccess:14:# that tenant's media roots before reading the file.
./AGENTS.md:3:## Pure multi-tenant architecture
./AGENTS.md:5:This repository uses one Apache/PHP application instance, one shared SQLite database, and one shared schema. The current tenant is derived from the normalized request `Host` header. Do not add browser-controlled tenant IDs to public or Tenant Admin operations.
./AGENTS.md:7:All mutable invitation settings, custom CSS, calendar data, and guest links belong in tenant-scoped SQLite rows, primarily `tenant_configs`, `guest_links`, and `tamu`. Do not reintroduce `config.json`, `guest-links.json`, or another global runtime store. Schema creation and upgrades belong in `database/migrations/` and `deploy/migrate.php`; normal requests must not run DDL or migration checks.
./AGENTS.md:9:Tenant media must remain below `uploads/tenant_<id>/`. Preserve the upload pipeline of validation, WebP conversion where applicable, preset-specific resizing, original cleanup after successful conversion, and tenant-scoped persistence. All `/uploads/` delivery must pass through `media.php`; do not add a static bypass.
./AGENTS.md:35:- Tenant media delivery: `media.php`
./AGENTS.md:36:- Tenant Admin: `admin/index.php`
./AGENTS.md:37:- Super Admin: `admin/super-admin.php`
./AGENTS.md:38:- Tenant context, database, encryption, and media helpers: `config.php`
./ARCHITECTURE.md:1:# Multi-Tenant CMS Engine Architecture
./ARCHITECTURE.md:3:[`multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine) is the complete Multi-Tenant CMS Engine and Wedding Invitation CMS application. The repository contains the engine, wedding-specific workflows, built-in theme presets, admin interfaces, APIs, deployment scripts, and multi-tenant implementation together.
./ARCHITECTURE.md:5:The authoritative architecture record is [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md). Read it with [`docs/MULTI_TENANT.md`](docs/MULTI_TENANT.md), [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md), and [`docs/ATTRIBUTIONS.md`](docs/ATTRIBUTIONS.md).
./ARCHITECTURE.md:7:At a high level, Internet traffic reaches the Cloudflare Tunnel, then Apache, then the PHP application. The application derives tenant identity from the normalized `HTTP_HOST`, loads tenant-scoped SQLite configuration, renders the selected built-in theme or Custom mode, and delivers tenant media through `media.php`.
./BACKUP_RESTORE.md:3:This guide documents the production backup and restore contract for the pure multi-tenant Wedding Invitation CMS. A backup contains shared application state for **all tenants**, so access must be restricted to trusted operators.
./BACKUP_RESTORE.md:12:| Tenant configuration | Stored inside the shared SQLite database, including `tenant_configs`, custom CSS, calendar data, and guest links |
./BACKUP_RESTORE.md:13:| Tenant media | Complete `uploads/tenant_<id>/` tree, including cover, gallery, background, love-story, music, and theme assets |
./BACKUP_RESTORE.md:19:The backup process treats the complete tenant media tree as user data. It does not inspect gallery references, convert media, delete unreferenced files, or move a file between tenants. Missing optional runtime objects are omitted without failing the archive; staging, permission, or validation errors return non-zero.
./BACKUP_RESTORE.md:46:A normal archive contains the shared database, environment data, `uploads/tenant_<id>/`, optional `webdav/`, and the protected WebDAV credential namespace. Reject an archive that contains unexpected absolute paths, traversal components, links, or a second application root.
./BACKUP_RESTORE.md:62:5. Restores the shared database, `.env`, complete tenant media tree, and optional WebDAV data according to the deployment contract.
./BACKUP_RESTORE.md:63:6. Recreates missing runtime directories without deleting existing tenant media.
./BACKUP_RESTORE.md:64:7. Applies restrictive permissions to secrets and writable permissions only where required.
./BACKUP_RESTORE.md:71:Older archives may contain global media directories or legacy configuration files from before the pure multi-tenant migration. They are compatibility inputs for controlled restore and migration only. After restoration, run `deploy/migrate.php` with the correct `UNDANGAN_MAIN_DOMAIN`, verify tenant binding, and do not re-enable legacy files as runtime sources.
./BACKUP_RESTORE.md:73:The migration deterministically assigns legacy RSVP rows to the tenant identified by `UNDANGAN_MAIN_DOMAIN` and converts legacy password ciphertext when the configured key permits it. Review the migration output before reopening public traffic.
./BACKUP_RESTORE.md:75:## Permissions and secrets
./BACKUP_RESTORE.md:82:| SQLite database | Mode `600` or an equivalent private deployment permission |
./BACKUP_RESTORE.md:83:| Backups | Operator-only access; contain every tenant |
./BACKUP_RESTORE.md:84:| `uploads/tenant_<id>/` | Writable by the application where required, not a replacement for `media.php` authorization |
./BACKUP_RESTORE.md:85:| WebDAV credentials | Protected archive namespace and restricted filesystem permissions |
./BACKUP_RESTORE.md:91:`deploy/update.sh` creates and validates a backup before replacing application code. It preserves `.env`, the shared database, the complete tenant-prefixed media tree, backups, WebDAV data, and compatible legacy storage. It then recreates missing runtime directories, runs the standalone migration, and validates the installation.
./BACKUP_RESTORE.md:93:A failed backup or source synchronization must abort the update and must not print a success message. Preserve the verified backup until the new installation passes health and tenant-isolation checks.
./BACKUP_RESTORE.md:107:The validators check the tenant-aware schema contract, public endpoint wrappers, media delivery boundary, runtime file protection, and dependency/orphan contracts. The application-level health check should pass before the service is exposed through Cloudflare.
./BACKUP_RESTORE.md:111:Provision a host with the same PHP, SQLite3, OpenSSL, WebP, and Apache prerequisites. Apply the reviewed catch-all Apache configuration, restore the archive with `deploy/restore.sh`, confirm `UNDANGAN_MAIN_DOMAIN` and `UNDANGAN_PASSWORD_KEY`, run health and repository validation, then verify at least one active tenant and one tenant-media request through the Cloudflare route. Do not automatically roll back over newly uploaded media without preserving the current runtime state first.
./CHANGELOG.md:3:## [Unreleased] — Multi-Tenant CMS Engine
./CHANGELOG.md:5:This repository is the complete Multi-Tenant CMS Engine application derived from the Wedding Invitation CMS. The current baseline is the source tree copied from `webserver_undangan` commit `320eb837963b4df89c2757488b7371b29c31ce9d`; the target repository identity is [`februana/multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine).
./CHANGELOG.md:9:The application provides one Apache/PHP instance, one shared SQLite database and schema, Host-based tenant resolution, tenant-scoped configuration, tenant-isolated media, tenant-aware public and administrative endpoints, and validated Cloudflare Tunnel auto-provisioning. The wedding invitation application remains complete, including RSVP, guest links, administration, built-in presets, theme assets, and deployment tooling.
./CHANGELOG.md:15:The current documentation describes SQLite tenant rows as the runtime source of truth, deployment-time migrations, the `uploads/tenant_<id>/` media structure, `media.php` authorization, Apache and Cloudflare ingress, AES-256-GCM recovery storage, security boundaries, testing commands, and source attribution. Global configuration files are not documented as runtime sources of truth.
./CHANGELOG.md:27:Use the individual `tools/` smoke tests or the existing external regression runner for the complete rendering, media, tenant-isolation, deployment, and backup/restore baseline.
./CONTRIBUTING.md:1:# Contributing to the Multi-Tenant CMS Engine
./CONTRIBUTING.md:5:[`multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine) is the complete Multi-Tenant CMS Engine application derived from the Wedding Invitation CMS. Contributions must preserve the complete application, including wedding workflows, RSVP and guest APIs, admin functionality, built-in presets, theme assets, and deployment tooling.
./CONTRIBUTING.md:9:## Tenant boundaries
./CONTRIBUTING.md:11:Tenant identity is derived from the normalized request `Host` and resolved through `tenants.domain`. Public and Tenant Admin code must never trust a browser-supplied `tenant_id`. Tenant configuration, guest links, RSVP rows, and media references must remain tenant-scoped. Tenant Admin sessions must remain bound to the current host, while Super Admin is the intentional cross-tenant administrative exception.
./CONTRIBUTING.md:13:Configuration belongs in SQLite tenant rows. Do not introduce or restore a global `config.json`, `site.json`, `theme.json`, `sections.json`, or `guest-links.json` runtime source. Schema changes belong in `database/migrations/` and `deploy/migrate.php`; normal HTTP requests must not run DDL or migration checks.
./CONTRIBUTING.md:15:All media belongs below `uploads/tenant_<id>/`. Preserve validation, WebP conversion where applicable, preset-specific resize, cleanup after successful conversion, and tenant containment. Every `/uploads/...` request must remain authorized through `media.php`.
./CONTRIBUTING.md:43:Use the individual smoke tests under `tools/` for affected areas. Deployment, media, tenant isolation, rendering, admin-session, configuration-isolation, and backup/restore changes require the corresponding existing smoke tests. Do not invent new commands in documentation; describe only scripts that exist in the repository or the separately maintained validation environment.
./DEPLOYMENT.md:3:The production architecture is one PHP/SQLite application served by one Apache catch-all VirtualHost behind a Cloudflare Tunnel. Tenant domains are resolved from `HTTP_HOST`; configuration and data are stored in a shared tenant-aware SQLite schema; media is stored below `uploads/tenant_<id>/` and delivered through `media.php`.
./DEPLOYMENT.md:10:cd /path/to/multytenant_cms_enggine
./DEPLOYMENT.md:26:The updater and restore flow preserve the shared database, `.env`, all tenant media, backups, and optional WebDAV data. See [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) and [`BACKUP_RESTORE.md`](BACKUP_RESTORE.md) for the complete procedures.
./DEPLOYMENT.md:28:## Tenant onboarding
./DEPLOYMENT.md:30:Set `UNDANGAN_MAIN_DOMAIN`, `UNDANGAN_DB_PATH`, `UNDANGAN_PASSWORD_KEY`, and `UNDANGAN_AUTO_PROVISION` in the protected `.env`. Unknown hosts are auto-provisioned only when auto-provisioning is enabled and the request is local Cloudflare Tunnel traffic with `CF-RAY` and a valid `CF-Connecting-IP`. Super Admin can also create tenants manually at `/admin/super-admin.php`.
./DEPLOYMENT.md:40:Related references are [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md), [`docs/MULTI_TENANT.md`](docs/MULTI_TENANT.md), [`docs/PASSWORD_MANAGEMENT.md`](docs/PASSWORD_MANAGEMENT.md), and [`SECURITY.md`](SECURITY.md).
./README.md:1:# Multi-Tenant CMS Engine
./README.md:3:[`multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine) is a **Multi-Tenant CMS Engine** derived from the existing Wedding Invitation CMS. The repository intentionally contains the complete working application: the CMS engine, wedding invitation workflows, RSVP and guest APIs, administration, built-in wedding themes, theme assets, deployment scripts, and the completed multi-tenant implementation. This is a repository-level identity change, not a physical extraction or redesign of the application.
./README.md:23:tenant resolver -> tenants.domain
./README.md:25:   +--> tenant-scoped SQLite configuration and data
./README.md:27:   +--> uploads/tenant_<id>/ media namespace
./README.md:30:Tenant-authorized media.php delivery
./README.md:33:One application instance serves multiple tenants through one shared SQLite database and one shared schema. Tenant identity comes from the normalized request hostname, never from a client-supplied `tenant_id`. Tenant configuration is stored in tenant-scoped database rows, media is stored below `uploads/tenant_<id>/`, and public media delivery passes through `media.php`.
./README.md:35:The `UNDANGAN_MAIN_DOMAIN` entered during installation identifies the initial normal tenant. That tenant has its own public wedding invitation, tenant-scoped configuration, media, data, and Tenant Admin behavior exactly like any other tenant. The separately stored Super Admin account is identified by its database role (`role = super_admin`, `tenant_id IS NULL`) and may manage all tenants; the primary hostname is not proof of Super Admin authorization.
./README.md:37:Known active tenants continue to the renderer. Unknown hosts fail closed unless validated Cloudflare defense-in-depth conditions allow transactional auto-provisioning. Suspended or invalid tenants return `404`; invalid direct-origin or missing-header provisioning attempts return `403` without creating a tenant. Tenant Admin operates only within its resolved tenant context, while Super Admin is the explicit cross-tenant administrative role.
./README.md:41:The runtime source of truth is SQLite. Tenant configuration, custom CSS, event calendar data, and guest links are stored in `tenant_configs` and related tenant-scoped tables. Global `config.json`, `site.json`, `theme.json`, `sections.json`, and `guest-links.json` are not runtime configuration sources; legacy files are migration inputs only.
./README.md:43:Schema creation and migration are deployment-time operations. The schema contract is maintained in [`database/migrations/001_multi_tenant.sql`](database/migrations/001_multi_tenant.sql), and the standalone migration is [`deploy/migrate.php`](deploy/migrate.php). Normal HTTP requests do not perform DDL or migration checks.
./README.md:78:## Tenant media lifecycle
./README.md:80:Tenant media is organized as follows:
./README.md:84:└── tenant_<id>/
./README.md:93:The upload lifecycle validates the input, performs WebP conversion where applicable, resizes according to the selected preset, removes the original only after successful conversion, and saves the result inside the current tenant namespace. Upload, replacement, deletion, preview, and render-time references are checked with tenant containment rules.
./README.md:95:Apache rewrites every `/uploads/...` request to [`media.php`](media.php). That endpoint resolves the current host tenant, validates path containment and MIME type, and serves only the authorized tenant file. `/uploads/...` must never bypass tenant authorization through a static alias or alternate endpoint.
./README.md:101:| `/` or `index.php` | Resolve the tenant and render its invitation. |
./README.md:102:| `/save.php` | Save public RSVP data using the server-resolved tenant ID. |
./README.md:103:| `/messages.php` | Read tenant-scoped visible messages. |
./README.md:104:| `/gallery.php` | Return tenant-scoped gallery data. |
./README.md:105:| `/event.ics.php` | Serve tenant-scoped calendar data. |
./README.md:106:| `/media.php` | Authorize and deliver tenant media. |
./README.md:107:| `/admin/` | Tenant Admin CMS within the matching tenant session. |
./README.md:108:| `/admin/super-admin.php` | Super Admin tenant lifecycle and cross-tenant administration. |
./README.md:119:├── event.ics.php             # tenant calendar endpoint
./README.md:120:├── media.php                 # tenant-authorized media delivery
./README.md:121:├── config.php                # tenant context, SQLite, security, and media helpers
./README.md:123:├── admin/                    # Tenant Admin and Super Admin interfaces
./README.md:126:├── uploads/                  # runtime tenant media; do not commit production data
./README.md:148:Tenant CMS and theme renderer
./README.md:156:git clone https://github.com/februana/multytenant_cms_enggine.git
./README.md:157:cd multytenant_cms_enggine
./README.md:162:On a fresh installation, the migration provisions three distinct outcomes: the primary normal tenant, its tenant-scoped Primary Tenant Admin, and the global role-based Super Admin. The installer/migration output prints newly generated credentials once; repeat installations preserve existing credentials. See [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) and [`docs/PASSWORD_MANAGEMENT.md`](docs/PASSWORD_MANAGEMENT.md) for the credential flow.
./README.md:168:These headers are **not cryptographic proof of Cloudflare provenance**. The origin must still be protected from direct Internet access by firewall, private-network, or equivalent network policy. A failed validation returns `403` and does not create a tenant. Invalid or suspended tenant domains fail closed with `404`.
./README.md:172:The security model includes Host-based tenant resolution, session isolation, server-assigned tenant IDs, database tenant scoping, tenant media containment, path traversal protection, MIME validation, upload validation, CSRF protection, password hashing, AES-256-GCM `visible_password` recovery storage, and origin network protection. Details are in [`SECURITY.md`](SECURITY.md), [`docs/MULTI_TENANT.md`](docs/MULTI_TENANT.md), and [`docs/PASSWORD_MANAGEMENT.md`](docs/PASSWORD_MANAGEMENT.md).
./README.md:187:The regression runner used for the published baseline is `/home/ubuntu/run_full_regression.sh` in the validation environment. It covers deployment, rendering, tenant contracts, media lifecycle and isolation, visual capabilities, admin behavior, and backup/restore smoke tests. Do not treat the path above as a repository file; use the repository's individual `tools/` commands when operating from a normal checkout.
./RELEASE_NOTES.md:1:# Release Notes — Multi-Tenant CMS Engine
./RELEASE_NOTES.md:5:This repository is [`februana/multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine), a complete Multi-Tenant CMS Engine derived from the Wedding Invitation CMS. It retains the complete wedding application, including RSVP, guest links, admin functionality, built-in wedding themes, theme assets, APIs, and deployment scripts. The engine and application are intentionally kept together in this repository.
./RELEASE_NOTES.md:7:The copied source baseline is commit `320eb837963b4df89c2757488b7371b29c31ce9d` from the source project's `multy-tenant_februana` branch. That provenance identifies the starting source state; it is not the target repository identity or an instruction to use the source repository for new checkouts.
./RELEASE_NOTES.md:11:The current architecture is one Apache/PHP application instance, one shared SQLite database and schema, Host-based tenant resolution, tenant-scoped configuration, and tenant-isolated media below `uploads/tenant_<id>/`. Cloudflare Tunnel is the intended public ingress. Unknown and suspended tenants fail closed, while validated unknown-host provisioning is guarded by localhost and Cloudflare request conditions that are explicitly defense-in-depth rather than cryptographic provenance proof.
./RELEASE_NOTES.md:13:Schema creation and legacy migration are deployment-time operations handled by `database/migrations/001_multi_tenant.sql` and `deploy/migrate.php`. Normal HTTP requests do not perform DDL or migration checks. `/uploads/...` requests pass through `media.php` for current-tenant path containment and MIME authorization.
./RELEASE_NOTES.md:21:The production path is Cloudflare Tunnel → Apache → PHP application → SQLite → tenant CMS. The native installer is non-destructive and does not modify `/etc/apache2` automatically, install packages, configure Nginx, or restart services. Operators review and apply the sample Apache catch-all separately.
./RELEASE_NOTES.md:26:git clone https://github.com/februana/multytenant_cms_enggine.git
./RELEASE_NOTES.md:27:cd multytenant_cms_enggine
./RELEASE_NOTES.md:36:The repository includes validators for PHP contracts, endpoint contracts, dependencies, tenant boundaries, theme rendering, media lifecycle, visual capabilities, deployment, and backup/restore. At minimum, run:
./SECURITY.md:3:[`multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine) is a Multi-Tenant CMS Engine containing the complete PHP/SQLite wedding-invitation application, deployed as a **single shared-schema multi-tenant application**. Security depends on keeping tenant context server-derived, protecting the origin behind the intended Cloudflare Tunnel, and preserving the filesystem and database boundaries described below.
./SECURITY.md:7:Do not disclose a suspected vulnerability in a public issue. Provide a private report to the project maintainer with a description, affected branch or commit, reproduction steps, impact, and any proposed mitigation. Do not include production credentials, database dumps, or tenant media in a report.
./SECURITY.md:14:| Tenant resolution | Normalize and validate `HTTP_HOST`, then resolve through `tenants.domain` |
./SECURITY.md:15:| Tenant identity | Never accept `tenant_id` from browser input for public or tenant-admin operations |
./SECURITY.md:16:| Session | Tenant Admin session tenant and domain must match the current host |
./SECURITY.md:17:| Cross-tenant administration | Limited to authenticated Super Admin operations |
./SECURITY.md:18:| Database | Shared SQLite schema with tenant-scoped rows and foreign-key relationships |
./SECURITY.md:20:| Media | `uploads/tenant_<id>/` with path containment and `media.php` delivery authorization |
./SECURITY.md:25:Known active tenants are selected from the normalized hostname. Suspended or invalid domains return `404`. An unknown hostname is not provisioned unless `UNDANGAN_AUTO_PROVISION=1` and all of the following are true:
./SECURITY.md:35:The application uses a shared SQLite database with tenant-aware `tenants`, `users`, `tenant_configs`, `guest_links`, and `tamu` tables. Public controllers derive the tenant ID from the current request context before executing reads or writes. Tenant Admin controllers additionally verify role, session tenant, and hostname. Super Admin has `tenant_id IS NULL` and is intentionally allowed to administer multiple tenants.
./SECURITY.md:37:Schema creation and upgrades happen through `database/migrations/001_multi_tenant.sql` and `deploy/migrate.php` during installation, update, or restore. Normal requests must not perform `CREATE TABLE`, `ALTER TABLE`, or legacy-file migration checks.
./SECURITY.md:39:The runtime configuration source is `tenant_configs`. A global `config.json` or `guest-links.json` is not a supported runtime source. Legacy files are migration inputs only.
./SECURITY.md:43:All uploaded files belong to a tenant namespace:
./SECURITY.md:46:uploads/tenant_<id>/{cover,gallery,background,love-story,music,theme-assets}/
./SECURITY.md:49:The upload pipeline validates the file, performs WebP conversion and preset-specific resize where applicable, removes the original only after successful conversion, and saves the result below the current tenant root. Every upload, replacement, deletion, preview, and render-time reference must pass tenant-aware containment checks.
./SECURITY.md:51:Apache rewrites `/uploads/...` to `media.php`. The endpoint resolves the current host tenant again, rejects paths outside its approved roots, validates MIME type, and serves the file only when it belongs to that tenant. Do not add a static alias or alternate endpoint that bypasses this boundary.
./SECURITY.md:55:Login uses `users.password_hash` with `password_verify()`. The intentionally supported Super Admin password-recovery display uses `users.visible_password`, encrypted with AES-256-GCM in this format:
./SECURITY.md:61:`UNDANGAN_PASSWORD_KEY` must remain in protected server configuration, never in Git or HTML. Do not rotate it without a controlled migration or reset plan. Treat the ability of Super Admin to view Tenant Admin recovery passwords as a deliberate business exception that requires strict administrative access control.
./SECURITY.md:67:Protect `.env`, the SQLite database, backups, and WebDAV credentials with restrictive permissions. Do not use a blanket recursive `chmod -R 755`. Do not commit production uploads, database files, backup archives, or secrets.
./SECURITY.md:80:The validators are repository contracts, not substitutes for network controls, operating-system permissions, or Cloudflare configuration review.
./admin/app.js:79:    const csrfInput = document.querySelector('input[name=csrf_token]');
./admin/app.js:80:    if (!filename || !csrfInput || !confirm('Hapus foto ini dari galeri?')) return;
./admin/app.js:84:    const csrfField = document.createElement('input');
./admin/app.js:85:    csrfField.type = 'hidden';
./admin/app.js:86:    csrfField.name = 'csrf_token';
./admin/app.js:87:    csrfField.value = csrfInput.value;
./admin/app.js:96:    form.appendChild(csrfField);
./admin/app.js:223:  const csrfField = document.createElement('input');
./admin/app.js:224:  csrfField.type = 'hidden';
./admin/app.js:225:  csrfField.name = 'csrf_token';
./admin/app.js:226:  csrfField.value = document.querySelector('input[name=csrf_token]')?.value || '';
./admin/app.js:239:  form.append(csrfField, actionField, guestNameField, baseUrlField);
./admin/backup.php:3:require_admin();
./admin/backup.php:4:if (!is_super_admin()) {
./admin/backup.php:6:    exit('Backup database hanya dapat dilakukan oleh Super Admin.');
./admin/index.php:42:        session_regenerate_id(true);
./admin/index.php:47:        $_SESSION['tenant_id'] = $identity['tenant_id'];
./admin/index.php:48:        $_SESSION['tenant_domain'] = $identity['domain'];
./admin/index.php:50:        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
./admin/index.php:51:        header('Location: ' . ($identity['role'] === 'super_admin' ? '/admin/super-admin.php' : '/admin'));
./admin/index.php:58:    if (empty($_SESSION['csrf_token'])) {
./admin/index.php:59:        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
./admin/index.php:69:    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
./admin/index.php:70:        $error = 'Token CSRF tidak valid.';
./admin/index.php:86:                    'cover' => tenant_upload_dir('cover'),
./admin/index.php:87:                    'background' => tenant_upload_dir('background'),
./admin/index.php:88:                    'gallery' => tenant_upload_dir('gallery'),
./admin/index.php:89:                    'love_story' => tenant_upload_dir('love_story'),
./admin/index.php:90:                    'video' => tenant_upload_dir('love_story'),
./admin/index.php:91:                    'theme_assets' => tenant_upload_dir('theme_assets') . '/' . (preg_replace('/[^a-z0-9_-]/i', '', (string)($config['theme']['theme_preset'] ?? 'custom')) ?: 'custom'),
./admin/index.php:92:                    'music' => tenant_upload_dir('music'),
./admin/index.php:121:                if (!tenant_media_reference_is_safe($path)) {
./admin/index.php:122:                    $error = 'Asset media bukan milik tenant aktif.';
./admin/index.php:211:                if (!tenant_media_reference_is_safe($mediaValue)) {
./admin/index.php:212:                    $error = 'Asset media bukan milik tenant aktif.';
./admin/index.php:356:                                $themeAssetDir = tenant_upload_dir('theme_assets') . '/' . $themeAssetPreset;
./admin/index.php:650:                    $uploadResult = upload_file($_FILES['image'], tenant_upload_dir('love_story'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'story', $config['theme']['theme_preset'] ?? null);
./admin/index.php:673:                    $result = upload_file($_FILES['bride_photo'], tenant_upload_dir('cover'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'bride_photo', $config['theme']['theme_preset'] ?? null);
./admin/index.php:685:                    $result = upload_file($_FILES['groom_photo'], tenant_upload_dir('cover'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'groom_photo', $config['theme']['theme_preset'] ?? null);
./admin/index.php:697:                    $result = upload_file($_FILES['couple_photo'], tenant_upload_dir('cover'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'couple_photo', $config['theme']['theme_preset'] ?? null);
./admin/index.php:780:                    $result = upload_file($_FILES['cover_image'], tenant_upload_dir('cover'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'cover', $config['theme']['theme_preset'] ?? null);
./admin/index.php:792:                    $result = upload_file($_FILES['music_file'], tenant_upload_dir('music'), ALLOWED_AUDIO_TYPES, MAX_MUSIC_UPLOAD_SIZE, 'music', $config['theme']['theme_preset'] ?? null);
./admin/index.php:804:                    $result = upload_file($_FILES['background_hero'], tenant_upload_dir('background'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'background', $config['theme']['theme_preset'] ?? null);
./admin/index.php:816:                        $result = upload_file($_FILES[$field], tenant_upload_dir('background'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'background', $config['theme']['theme_preset'] ?? null);
./admin/index.php:829:                    $result = upload_file($_FILES['qris_image'], tenant_upload_dir('cover'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'qris_image', $config['theme']['theme_preset'] ?? null);
./admin/index.php:841:                    $result = upload_file($_FILES['og_image'], tenant_upload_dir('cover'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'og_image', $config['theme']['theme_preset'] ?? null);
./admin/index.php:864:                        $result = upload_file($file, tenant_upload_dir('gallery'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'gallery', $config['theme']['theme_preset'] ?? null);
./admin/index.php:876:                    if (!tenant_media_reference_is_safe($filename)) {
./admin/index.php:877:                        $error = 'File media bukan milik tenant aktif.';
./admin/index.php:898:                    if (!tenant_media_reference_is_safe($filename)) {
./admin/index.php:899:                        $error = 'File cover bukan milik tenant aktif.';
./admin/index.php:917:                    if (!tenant_media_reference_is_safe($selectedCover)) {
./admin/index.php:918:                        $error = 'File cover bukan milik tenant aktif.';
./admin/index.php:1064:                <?php if (is_super_admin()): ?><a href="/admin/super-admin.php">Super Admin</a><?php endif; ?>
./admin/index.php:1116:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1137:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1160:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1179:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1200:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1217:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1261:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1523:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1609:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1630:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1697:                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1706:                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1715:                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1724:                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1733:                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1742:                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1751:                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1760:                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1768:                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1775:                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1782:                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1804:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1879:                                    <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1932:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1938:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1954:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1985:                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:1991:                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2018:                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2024:                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2051:                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2057:                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2084:                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2090:                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2121:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2130:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2146:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2162:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2178:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2210:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2225:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2237:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2257:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2298:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2317:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2331:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2350:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2403:                                                        <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2437:                                        $db = tenant_database(true);
./admin/index.php:2438:                                        if (is_super_admin()) {
./admin/index.php:2441:                                            $stmt = $db->prepare('SELECT id, nama, status, ucapan, created_at, visible FROM tamu WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT 50');
./admin/index.php:2442:                                            $stmt->bindValue(':tenant_id', current_tenant_id(), SQLITE3_INTEGER);
./admin/index.php:2477:                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2493:                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
./admin/index.php:2575:                form.innerHTML = '<input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">' +
./admin/profile.php:5:require_admin();
./admin/profile.php:6:if (!is_super_admin()) {
./admin/profile.php:8:    exit('Akses hanya untuk Super Admin.');
./admin/profile.php:14:    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
./admin/profile.php:15:        $error = 'Token CSRF tidak valid.';
./admin/profile.php:26:            $message = 'Profil Super Admin berhasil diperbarui.';
./admin/profile.php:32:$csrf = get_csrf_token();
./admin/profile.php:35:<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Profil Super Admin</title><link rel="stylesheet" href="/admin/style.css"></head>
./admin/profile.php:38:  <h1>Profil Super Admin</h1>
./admin/profile.php:39:  <p><a href="/admin/super-admin.php">Kembali ke Super Admin Dashboard</a> · <a href="/admin/?logout=1">Keluar</a></p>
./admin/profile.php:44:      <input type="hidden" name="csrf_token" value="<?= escape_html($csrf) ?>">
./admin/profile.php:45:      <label>Username Super Admin<br><input name="username" required value="<?= escape_html((string)($_SESSION['username'] ?? '')) ?>"></label><br>
./admin/restore.php:3:require_admin();
./admin/restore.php:4:if (!is_super_admin()) {
./admin/restore.php:6:    exit('Restore database hanya dapat dilakukan oleh Super Admin.');
./admin/restore.php:22:if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
./admin/restore.php:24:    echo 'Token CSRF tidak valid.';
./admin/super-admin.php:5:require_admin();
./admin/super-admin.php:6:if (!is_super_admin()) {
./admin/super-admin.php:8:    exit('Akses hanya untuk Super Admin.');
./admin/super-admin.php:14:    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
./admin/super-admin.php:15:        $error = 'Token CSRF tidak valid.';
./admin/super-admin.php:18:            $db = tenant_database(false);
./admin/super-admin.php:20:            if ($action === 'create_tenant') {
./admin/super-admin.php:23:                    $domain = normalize_tenant_domain((string)($_POST['domain'] ?? ''));
./admin/super-admin.php:24:                    if (!is_valid_tenant_domain($domain)) throw new RuntimeException('Domain tidak valid.');
./admin/super-admin.php:25:                    $stmt = $db->prepare("INSERT INTO tenants (domain, status) VALUES (:domain, 'active')");
./admin/super-admin.php:28:                    $tenantId = (int)$db->lastInsertRowID();
./admin/super-admin.php:29:                    ensure_tenant_seed($db, $tenantId);
./admin/super-admin.php:30:                    $tenantUsername = trim((string)($_POST['tenant_username'] ?? '')) ?: 'admin';
./admin/super-admin.php:31:                    $tenantPassword = (string)($_POST['tenant_password'] ?? '');
./admin/super-admin.php:32:                    if ($tenantPassword === '') $tenantPassword = generate_random_password(8);
./admin/super-admin.php:33:                    $visiblePassword = encrypt_visible_password($tenantPassword);
./admin/super-admin.php:35:                    $user = $db->prepare("INSERT INTO users (tenant_id, username, password_hash, visible_password, role) VALUES (:tenant_id, :username, :password_hash, :visible_password, 'tenant_admin')");
./admin/super-admin.php:36:                    $user->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
./admin/super-admin.php:37:                    $user->bindValue(':username', $tenantUsername, SQLITE3_TEXT);
./admin/super-admin.php:38:                    $user->bindValue(':password_hash', password_hash($tenantPassword, PASSWORD_DEFAULT), SQLITE3_TEXT);
./admin/super-admin.php:40:                    if (!$user->execute()) throw new RuntimeException('Gagal membuat akun Tenant Admin.');
./admin/super-admin.php:42:                    $message = 'Tenant berhasil dibuat. Login Tenant Admin: ' . $tenantUsername . ' / ' . $tenantPassword;
./admin/super-admin.php:48:                $tenantId = (int)($_POST['tenant_id'] ?? 0);
./admin/super-admin.php:50:                if (!in_array($status, ['active', 'suspended'], true) || $tenantId < 1) throw new RuntimeException('Status tenant tidak valid.');
./admin/super-admin.php:51:                $stmt = $db->prepare('UPDATE tenants SET status = :status WHERE id = :tenant_id');
./admin/super-admin.php:53:                $stmt->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
./admin/super-admin.php:55:                $message = 'Status tenant diperbarui.';
./admin/super-admin.php:56:            } elseif ($action === 'reset_tenant_password') {
./admin/super-admin.php:57:                $tenantId = (int)($_POST['tenant_id'] ?? 0);
./admin/super-admin.php:60:                if ($tenantId < 1 || $userId < 1) throw new RuntimeException('Tenant atau user tidak valid.');
./admin/super-admin.php:65:                $stmt = $db->prepare("UPDATE users SET password_hash = :password_hash, visible_password = :visible_password WHERE id = :user_id AND tenant_id = :tenant_id AND role = 'tenant_admin'");
./admin/super-admin.php:66:                $stmt->bindValue(':password_hash', password_hash($newPassword, PASSWORD_DEFAULT), SQLITE3_TEXT);
./admin/super-admin.php:69:                $stmt->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
./admin/super-admin.php:70:                if (!$stmt->execute() || $db->changes() !== 1) throw new RuntimeException('Tenant Admin tidak ditemukan atau password gagal diperbarui.');
./admin/super-admin.php:71:                $message = 'Password Tenant Admin diperbarui. Password baru: ' . $newPassword;
./admin/super-admin.php:80:$db = tenant_database(true);
./admin/super-admin.php:81:$tenants = [];
./admin/super-admin.php:83:    (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id) AS user_count,
./admin/super-admin.php:84:    (SELECT COUNT(*) FROM tamu g WHERE g.tenant_id = t.id) AS guest_count
./admin/super-admin.php:85:    FROM tenants t ORDER BY t.id");
./admin/super-admin.php:87:    $row['tenant_admins'] = [];
./admin/super-admin.php:88:    $adminStmt = $db->prepare("SELECT id, username, visible_password FROM users WHERE tenant_id = :tenant_id AND role = 'tenant_admin' ORDER BY id");
./admin/super-admin.php:89:    $adminStmt->bindValue(':tenant_id', (int)$row['id'], SQLITE3_INTEGER);
./admin/super-admin.php:91:    while ($admin = $adminResult->fetchArray(SQLITE3_ASSOC)) $row['tenant_admins'][] = $admin;
./admin/super-admin.php:92:    $tenants[] = $row;
./admin/super-admin.php:95:$csrf = get_csrf_token();
./admin/super-admin.php:98:<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Super Admin — Tenant</title><link rel="stylesheet" href="/admin/style.css"></head>
./admin/super-admin.php:101:  <h1>Super Admin Dashboard</h1>
./admin/super-admin.php:102:  <p><a href="/admin">Kembali ke CMS tenant</a> · <a href="/admin/profile.php">My Profile</a> · <a href="/admin/?logout=1">Keluar</a></p>
./admin/super-admin.php:106:    <h2>Tambah Tenant</h2>
./admin/super-admin.php:108:      <input type="hidden" name="csrf_token" value="<?= escape_html($csrf) ?>">
./admin/super-admin.php:109:      <input type="hidden" name="action" value="create_tenant">
./admin/super-admin.php:111:      <label>Username Tenant Admin<br><input name="tenant_username" placeholder="admin (default)"></label><br>
./admin/super-admin.php:112:      <label>Password Tenant Admin<br><input type="password" name="tenant_password" placeholder="Kosongkan untuk membuat password acak"></label><br>
./admin/super-admin.php:113:      <button type="submit">Buat Tenant</button>
./admin/super-admin.php:117:    <h2>Tenant Terdaftar</h2>
./admin/super-admin.php:118:    <table><thead><tr><th>Domain</th><th>Status</th><th>Tenant Admin / Password</th><th>User</th><th>RSVP</th><th>Aksi</th></tr></thead><tbody>
./admin/super-admin.php:119:    <?php foreach ($tenants as $tenant): ?>
./admin/super-admin.php:121:        <td><?= escape_html($tenant['domain']) ?></td><td><?= escape_html($tenant['status']) ?></td><td><?php if (empty($tenant['tenant_admins'])): ?>—<?php else: ?><?php foreach ($tenant['tenant_admins'] as $tenantAdmin): ?><div style="margin-bottom:12px"><strong><?= escape_html((string)$tenantAdmin['username']) ?></strong><br><code><?= escape_html(decrypt_visible_password($tenantAdmin['visible_password'] ?? '')) ?></code><form method="post" style="margin-top:4px"><input type="hidden" name="csrf_token" value="<?= escape_html($csrf) ?>"><input type="hidden" name="action" value="reset_tenant_password"><input type="hidden" name="tenant_id" value="<?= (int)$tenant['id'] ?>"><input type="hidden" name="user_id" value="<?= (int)$tenantAdmin['id'] ?>"><input type="password" name="new_password" minlength="6" maxlength="128" placeholder="Kosongkan = acak"><button type="submit">Reset/Set</button></form></div><?php endforeach; ?><?php endif; ?></td><td><?= (int)$tenant['user_count'] ?></td><td><?= (int)$tenant['guest_count'] ?></td>
./admin/super-admin.php:122:        <td><form method="post"><input type="hidden" name="csrf_token" value="<?= escape_html($csrf) ?>"><input type="hidden" name="action" value="set_status"><input type="hidden" name="tenant_id" value="<?= (int)$tenant['id'] ?>"><input type="hidden" name="status" value="<?= $tenant['status'] === 'active' ? 'suspended' : 'active' ?>"><button type="submit"><?= $tenant['status'] === 'active' ? 'Suspend' : 'Activate' ?></button></form></td>
./app/messages.php:6:    $tenant = current_tenant(false);
./app/messages.php:7:    if (!is_array($tenant)) {
./app/messages.php:12:    $db = tenant_database(true);
./app/messages.php:13:    $stmt = $db->prepare("SELECT nama,status,ucapan,created_at FROM tamu WHERE tenant_id = :tenant_id AND visible = 1 ORDER BY id DESC LIMIT 50");
./app/messages.php:14:    $stmt->bindValue(':tenant_id', (int)$tenant['id'], SQLITE3_INTEGER);
./app/save.php:7:$tenant = current_tenant(false);
./app/save.php:8:if (!is_array($tenant)) respond(false, 'Domain tidak terdaftar atau sedang ditangguhkan.');
./app/save.php:13:if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_csrf'])) {
./app/save.php:14:    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
./app/save.php:15:    respond(true, '', ['csrf_token'=>$_SESSION['csrf_token']]);
./app/save.php:18:if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) respond(false, 'Token CSRF tidak valid.');
./app/save.php:33:        $result = upload_file($_FILES['groom_photo'], tenant_upload_dir('cover'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'groom_photo', $config['theme']['theme_preset'] ?? null);
./app/save.php:44:        $result = upload_file($_FILES['bride_photo'], tenant_upload_dir('cover'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'bride_photo', $config['theme']['theme_preset'] ?? null);
./app/save.php:55:        $result = upload_file($_FILES['couple_photo'], tenant_upload_dir('cover'), ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'couple_photo', $config['theme']['theme_preset'] ?? null);
./app/save.php:79:                        $themeAssetDir = tenant_upload_dir('theme_assets') . '/' . $themeAssetPreset;
./app/save.php:144:    $db = tenant_database(false);
./app/save.php:145:    $stmt = $db->prepare('INSERT INTO tamu (tenant_id,nama,status,ucapan,visible) VALUES (:tenant_id,:nama,:status,:ucapan,1)');
./app/save.php:146:    $stmt->bindValue(':tenant_id', (int)$tenant['id'], SQLITE3_INTEGER);
./app/theme-renderer.php:159:            return '<section id="rsvp" class="section panel" ' . $sectionStyle . '><div class="invitation-frame"><div class="ornament-corner top-left"></div><div class="ornament-corner top-right"></div><div class="ornament-corner bottom-left"></div><div class="ornament-corner bottom-right"></div><div class="section-head left"><p class="label">' . escape_html(get_section_title($config, 'rsvp', 'Konfirmasi Kehadiran')) . '</p><h2>' . escape_html(get_section_subtitle($config, 'rsvp', 'Konfirmasi Kehadiran')) . '</h2></div><form id="rsvpForm" class="rsvp-form"><input type="hidden" name="csrf_token" id="csrfToken" /><label>Nama<input type="text" name="nama" placeholder="Nama Anda" required /></label><label>Kehadiran<select name="status" required><option value="Hadir">Hadir</option><option value="Tidak Hadir">Tidak Hadir</option></select></label><label>Ucapan<textarea name="ucapan" rows="4" placeholder="Tulis ucapan dan doa"></textarea></label><input type="text" name="website" autocomplete="off" tabindex="-1" aria-hidden="true" style="display:none"><button type="submit">Kirim Konfirmasi Kehadiran</button><p id="formMessage" class="form-message" role="status" aria-live="polite"></p></form>' . (is_section_enabled($config, 'messages') ? '<div id="messages" class="messages"></div>' : '') . '</div></section>';
./config.php:57: * Multi-tenant runtime helpers. All tenant selection is server-side and is
./config.php:60:function normalize_tenant_domain(string $host): string {
./config.php:76:    return normalize_tenant_domain($host);
./config.php:134:function is_valid_tenant_domain(string $domain): bool {
./config.php:135:    $domain = normalize_tenant_domain($domain);
./config.php:139:function tenant_admin_username_for_domain(string $domain): string {
./config.php:140:    $label = explode('.', normalize_tenant_domain($domain))[0] ?? 'tenant';
./config.php:141:    $label = preg_replace('/[^a-z0-9_-]/i', '-', $label) ?: 'tenant';
./config.php:145:function tenant_database(bool $readOnly = false): SQLite3 {
./config.php:153:function tenant_from_domain(SQLite3 $db, string $domain, bool $activeOnly = true): ?array {
./config.php:154:    $sql = 'SELECT id, domain, status, created_at FROM tenants WHERE domain = :domain';
./config.php:159:    $stmt->bindValue(':domain', normalize_tenant_domain($domain), SQLITE3_TEXT);
./config.php:165:function tenant_auto_provision_enabled(): bool {
./config.php:178:function provision_tenant_from_validated_ingress(string $domain): ?array {
./config.php:179:    $domain = normalize_tenant_domain($domain);
./config.php:180:    if (!is_valid_tenant_domain($domain) || !cloudflare_tunnel_ingress_is_validated()) return null;
./config.php:184:        $db = tenant_database(false);
./config.php:186:        $existing = tenant_from_domain($db, $domain, false);
./config.php:191:        $insertTenant = $db->prepare("INSERT INTO tenants (domain, status) VALUES (:domain, 'active')");
./config.php:192:        if (!$insertTenant) throw new RuntimeException('Tenant provisioning prepare failed.');
./config.php:193:        $insertTenant->bindValue(':domain', $domain, SQLITE3_TEXT);
./config.php:194:        if (!$insertTenant->execute()) throw new RuntimeException('Tenant provisioning insert failed.');
./config.php:195:        $tenantId = (int)$db->lastInsertRowID();
./config.php:196:        if ($tenantId < 1) throw new RuntimeException('Tenant provisioning returned an invalid tenant ID.');
./config.php:197:        ensure_tenant_seed($db, $tenantId);
./config.php:198:        $tenantPassword = generate_random_password(12);
./config.php:199:        $visiblePassword = encrypt_visible_password($tenantPassword);
./config.php:200:        if ($visiblePassword === '') throw new RuntimeException('UNDANGAN_PASSWORD_KEY is required for tenant provisioning.');
./config.php:201:        $tenantAdmin = $db->prepare("INSERT INTO users (tenant_id, username, password_hash, visible_password, role) VALUES (:tenant_id, :username, :password_hash, :visible_password, 'tenant_admin')");
./config.php:202:        if (!$tenantAdmin) throw new RuntimeException('Tenant Admin provisioning prepare failed.');
./config.php:203:        $tenantAdmin->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
./config.php:204:        $tenantAdmin->bindValue(':username', tenant_admin_username_for_domain($domain), SQLITE3_TEXT);
./config.php:205:        $tenantAdmin->bindValue(':password_hash', password_hash($tenantPassword, PASSWORD_DEFAULT), SQLITE3_TEXT);
./config.php:206:        $tenantAdmin->bindValue(':visible_password', $visiblePassword, SQLITE3_TEXT);
./config.php:207:        if (!$tenantAdmin->execute()) throw new RuntimeException('Tenant Admin provisioning insert failed.');
./config.php:208:        $tenant = ['id' => $tenantId, 'domain' => $domain, 'status' => 'active', 'created_at' => gmdate('Y-m-d H:i:s')];
./config.php:209:        $tenantRoot = tenant_upload_root($tenant);
./config.php:210:        if ($tenantRoot === '') throw new RuntimeException('Tenant upload root initialization returned an empty path.');
./config.php:211:        if (!is_dir($tenantRoot)) $createdDirectories[] = $tenantRoot;
./config.php:213:            $directory = tenant_upload_dir($kind, $tenant);
./config.php:214:            if ($directory === '') throw new RuntimeException('Tenant media directory initialization returned an empty path.');
./config.php:217:                    throw new RuntimeException('Tenant media directory initialization failed: ' . $directory);
./config.php:222:        if (!is_dir($tenantRoot)) throw new RuntimeException('Tenant upload root initialization failed for tenant ' . $tenantId);
./config.php:223:        if (!$db->exec('COMMIT')) throw new RuntimeException('Tenant provisioning commit failed.');
./config.php:226:        error_log('Auto-provisioned tenant ' . $domain . ' with Tenant Admin ' . tenant_admin_username_for_domain($domain) . '.');
./config.php:227:        return $tenant;
./config.php:234:        error_log('Tenant auto-provisioning failed: ' . $e->getMessage());
./config.php:239:function current_tenant(bool $required = true): ?array {
./config.php:241:    static $tenant = null;
./config.php:243:        if ($required && $tenant === null) tenant_http_error(404, 'Domain tidak terdaftar.');
./config.php:244:        return $tenant;
./config.php:249:        $db = tenant_database(true);
./config.php:250:        $tenant = tenant_from_domain($db, $domain, true);
./config.php:251:        if ($tenant === null && tenant_auto_provision_enabled()) {
./config.php:252:            $knownTenant = tenant_from_domain($db, $domain, false);
./config.php:253:            if ($knownTenant === null) {
./config.php:257:                    tenant_http_error(403, 'Auto-provisioning hanya diizinkan melalui Cloudflare Tunnel lokal.');
./config.php:261:                $tenant = is_valid_tenant_domain($domain) ? provision_tenant_from_validated_ingress($domain) : null;
./config.php:266:        if (is_file(DB_PATH)) error_log('Tenant resolution failed: ' . $e->getMessage());
./config.php:267:        $tenant = null;
./config.php:269:    if ($required && $tenant === null) tenant_http_error(404, 'Domain tidak terdaftar atau sedang ditangguhkan.');
./config.php:270:    return $tenant;
./config.php:273:function tenant_http_error(int $status, string $message): void {
./config.php:282:function current_tenant_id(): int {
./config.php:283:    $tenant = current_tenant(true);
./config.php:284:    return (int)$tenant['id'];
./config.php:287:function tenant_upload_root(?array $tenant = null): string {
./config.php:288:    $tenant = $tenant ?? current_tenant(false);
./config.php:289:    if (!is_array($tenant) || (int)($tenant['id'] ?? 0) < 1) return '';
