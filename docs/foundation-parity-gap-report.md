# Foundation Parity Gap Report

**Source foundation:** [`februana/webserver_undangan`](https://github.com/februana/webserver_undangan), pristine `main` at `b2ecf80fb8c8efe7bd0f3669603aa29232e9b849`.
**Target evolution:** [`februana/multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine), branch `fix/multitenant-super-admin-authorization` at `165d28cee955c3b6cbc6d928c3cf2f9505f0efcc` before this parity work.

## Executive conclusion

The target is not a new CMS that merely borrowed selected files. The comparison shows that the foundation application, Composer manifest/lock, preset assets, theme contract, most renderer components, media processing policy, Docker build, and baseline security helpers are carried forward. The target then adds a substantial multi-tenant layer around tenant resolution, tenant-scoped configuration, tenant-scoped media, provisioning, shared-schema migrations, and tenant-aware authorization.

The verified gaps are concentrated in **native deployment integration and test fixtures**, not in a missing CMS foundation. The target already contains the source Apache templates and an updater helper that can generate PHP-FPM configuration, but `deploy/install.sh` still deliberately stops at application installation and instructs operators to configure Apache manually. The target sample vhost also uses mod_php instead of the source Apache/PHP-FPM handler. Separately, the target updater still defaults to the source repository URL and retains a legacy Nginx migration path, which can lead to an incorrect source checkout or a deployment path outside the requested Apache-only target.

## Quantitative inventory

| Measure | Result | Interpretation |
|---|---:|---|
| Common tracked paths | 209 | Common foundation/application surface. |
| Common files byte-identical | 158 | Strong evidence that preset assets and much of the CMS foundation were carried forward unchanged. |
| Common files adapted/changed | 51 | Requires semantic classification; most changes are tenant storage, routing, docs, deployment, or security. |
| Source-only tracked paths | 21 | Mostly legacy runtime files and source audit documentation; each category is classified below. |
| Target-only tracked paths | 16 | Mostly tenant migrations, control-plane pages, tenant docs, and audit tooling. |
| Composer manifest/lock | Identical hashes | Foundation package graph is preserved. |
| Node package manifests | Absent in both | No npm build graph was found in either repository. |
| Preset directories | Same built-in preset set | Preset foundation is present in target. |

## Foundation component classification

| Foundation component | Source evidence | Target evidence | Status | Missing dependency / impact | Required action |
|---|---|---|---|---|---|
| Application core | Root PHP controllers, `config.php`, renderer, `.htaccess` | Same root controllers plus tenant-aware resolver and DB persistence | ADAPTED | No missing core identified; target adds tenant context | Preserve source behavior while reviewing tenant boundaries. |
| Composer | `composer.json` and `composer.lock` require `chillerlan/php-qrcode` and its settings container | Files are byte-identical; Composer dry-run resolves the same two packages | IDENTICAL | `vendor/` is not tracked in either checkout | Native installer must run Composer install or explicitly require it; Docker already installs it. |
| PHP extensions | Source Apache install requires `php-fpm`, `php-cli`, `php-sqlite3`, `php-gd`, `php-mbstring`, `php-zip` | Docker installs/builds the same functional extensions; native installer checks only PHP/SQLite/OpenSSL | PARTIAL | Native fresh install does not install the foundation extensions | Adapt native installer to install/verify the source-required runtime. |
| ImageMagick | Source `find_imagemagick_binary()` and `process_image_to_webp()` use `magick`/`convert`, with GD fallback | Target retains the same processing pipeline and adds tenant destination safety | ADAPTED | Target smoke fixture assumes an active tenant but does not create one | Keep pipeline; repair test fixture to create isolated tenant runtime. |
| Imagick PHP extension | No source code dependency on PHP `imagick` was found; source uses CLI ImageMagick plus GD fallback | Same behavior | IDENTICAL | `imagick` is not required by the observed foundation code | Do not add an unnecessary replacement dependency. |
| Media pipeline | Validate input, role/preset requirements, ImageMagick resize/WebP, GD fallback, verification, cleanup | Same algorithm plus `tenant_upload_dir()` and path containment | ADAPTED | No foundation algorithm loss found | Add parity test with tenant fixture; keep canonical tenant storage. |
| Helpers/internal library | 153 common symbols; source-only `init_database()` and `verify_admin_password()` are replaced by migration and DB auth | Target adds tenant/auth/audit helpers | ADAPTED / MULTI-TENANT ADDITION | No unexplained foundation helper loss identified yet | Trace source-only symbols and document replacement contracts. |
| Virtual UI/admin UI | Source admin UI, theme helper, preview/visual helpers | Target keeps common admin/theme surface and adds tenant/control-plane UI | ADAPTED | No missing frontend package graph; no `package.json` in either repo | Preserve foundation UI contracts; do not create a replacement UI engine. |
| Preset engine | Same preset directories, contracts, layouts, and assets; most assets are byte-identical | Target adds tenant-persisted config and tenant-safe media URLs | ADAPTED | No missing preset directory detected | Validate every preset through existing contract/smoke suite. |
| Database foundation | Source uses `config.json`, `guest-links.json`, `event.ics`, and legacy `init_database()` | Target moves runtime configuration to `tenant_configs`, `guest_links`, shared migrations, and `event.ics.php` | ADAPTED / INTENTIONALLY REMOVED | Legacy files must not be restored globally; restoring them would break isolation | Keep migration compatibility as input only and preserve tenant schema. |
| Security foundation | CSRF, sessions, password verification, upload MIME/path checks, `.htaccess` sensitive-file blocking | Target retains these and adds tenant containment, role revalidation, action allowlist, audit log | ADAPTED / MULTI-TENANT ADDITION | Native Apache sample still had a mod_php gap before parity work | Align deployment security with application security. |
| Runtime directories | Source has uploads/webdav/backups contracts | Target has tenant uploads plus backups/webdav runtime contract | ADAPTED | Tenant subdirectories are created on demand | Keep `runtime-directories.sh` as shared contract. |
| Docker deployment | Source Docker builds Apache image, Composer, GD/mbstring/zip, ImageMagick, and Apache modules | Target Dockerfile is byte-identical | IDENTICAL | Container path uses the foundation image's Apache PHP runtime; native PHP-FPM is a separate path | Do not alter Docker to PHP-FPM without source evidence. |
| Native Apache deployment | Source installer installs Apache/PHP-FPM, modules, detects socket, renders template, configtests, enables site, manages service | Target templates exist and updater has related helper, but `install.sh` is application-only and sample vhost uses mod_php | PARTIAL / FOUNDATION GAP | Fresh native clone cannot automatically provision the source Apache/PHP-FPM path | Adapt installer and sample using source behavior plus tenant catch-all. |
| Updater source identity | Source uses its own repository context | Target `deploy/update.sh` default was found pointing to `webserver_undangan` | FOUNDATION/DEPLOYMENT GAP | Update can pull the wrong CMS source | Change default to the target repository or require an explicit source URL. |
| Nginx deployment | Source supports Nginx as an optional branch | Target updater retains Nginx migration code | INTENTIONALLY OUT OF SCOPE for this target task | Nginx is not requested for the target deployment path | Do not port or advertise Nginx as the target path; preserve only if explicitly documented as legacy and fail closed. |

## Dependency and media conclusions

Composer parity is confirmed by identical `composer.json` and `composer.lock` hashes. Both repositories resolve `chillerlan/php-settings-container` 3.3.0 and `chillerlan/php-qrcode` 5.0.5 in Composer dry-run. Neither repository tracks `vendor/`, and neither contains a `package.json` or npm lockfile. The target Dockerfile is byte-identical to the source Dockerfile and therefore retains the foundation container dependency strategy.

The ImageMagick foundation is also present in target. Both codebases search for the `magick` or `convert` binary, use preset-specific media requirements, produce verified WebP, and fall back to GD when available. The target adds tenant-specific storage roots and rejects paths outside the active tenant. The first target media smoke run failed because its fixture invoked tenant storage without creating an active tenant and therefore attempted an invalid/empty path; this is a test-fixture parity issue, not evidence that the production pipeline is missing. It must be corrected before parity validation is declared complete.

## Deployment conclusion before implementation

The correct implementation is not to invent a new Apache system or copy the single-tenant application. The correct sequence is:

> **Source foundation behavior → understand dependency → adapt document root and catch-all tenant context → preserve application routing and storage → validate before service reload.**

Only the verified native deployment gap should be changed. The tenant resolver, shared schema, preset engine, media manager, and security model must remain the target architecture. Docker must remain separate because source and target already have identical container foundation behavior.

## References

[1]: https://github.com/februana/webserver_undangan — CMS foundation source repository.
[2]: https://github.com/februana/multytenant_cms_enggine — Multi-tenant evolution target repository.
[3]: https://getcomposer.org/doc/01-basic-usage.md — Composer dependency installation and lock-file behavior.
