# Foundation Parity Validation

**Target:** `februana/multytenant_cms_enggine`
**Foundation reference:** `februana/webserver_undangan`
**Validation date:** 2026-08-21

## Result

Foundation parity validation passed for the implemented scope. The target preserves the source CMS capability surface while retaining tenant resolution, tenant-scoped configuration, tenant-scoped media, provisioning, and role-aware authorization. The verified implementation changes are limited to native deployment integration, source identity correction, tenant-aware smoke fixtures, deployment contract assertions, and documentation.

## Evidence summary

| Area | Result | Evidence |
|---|---|---|
| File inventory | 158 common files identical, 51 common files adapted, 21 source-only paths, 16 target-only paths | `foundation-parity-counts.txt`, `foundation-parity-path-diff.txt` |
| Composer | Source and target `composer.json`/`composer.lock` hashes identical; dry-run resolves `chillerlan/php-settings-container` 3.3.0 and `chillerlan/php-qrcode` 5.0.5 | Composer validation and dry-run for both checkouts |
| Frontend dependency graph | No `package.json` or npm lockfile in either repository | Foundation inventory |
| Docker foundation | Source and target Dockerfiles are byte-identical | Structural comparison |
| Media/ImageMagick | Target tenant-aware media smoke passes ImageMagick/WebP, GD fallback contract, preset resize, cleanup, canonical storage, delete safety, and backup/restore assertions | `media_pipeline_smoke.php`, `media_delete_fallback_smoke.php`, `media_requirement_smoke.php` |
| Presets/UI | Preset contract, render, localization, visual capability, color/font, Pawiwahan, and user-input capability tests pass | Existing theme/visual smoke suite |
| Authentication/tenant isolation | Session revalidation, role scope, action allowlist, current-password check, audit log, and tenant identity tests pass | `multitenant_auth_smoke.php` |
| Apache rendered config | Rendered PHP-FPM vhost passes `apache2ctl -t`; disabled WebDAV markers are absent when opt-out is selected | Temporary `/tmp` Apache fixture |
| Native installer | End-to-end install passes copy, Composer, migration, PHP-FPM socket detection, Apache module setup, vhost rendering, configtest, site activation, and service reload | Temporary `/tmp` target with isolated site |
| Idempotence | Second native installer run passes and preserves a sentinel tenant media file | Temporary `/tmp` target with two installer runs |
| Regression suite | 0 failures across validator, repo contract, dependency graph, auth, deployment, theme, visual, media, copy, Pawiwahan, and backup/restore tests | `foundation-parity-regression.out` |

## Source-adapted deployment behavior

The native installer now installs or verifies the source-required Apache/PHP-FPM/Composer/ImageMagick/PHP extension runtime, runs Composer from the lock file, runs the standalone multi-tenant migration, detects the actual PHP-FPM Unix socket under `/run/php`, renders the existing Apache template with the tenant catch-all domain, and gates site activation and service reload on `apache2ctl configtest`. It does not use `rsync --delete` and preserves `.env`, the database, tenant media, backups, and WebDAV data.

The target Dockerfile was not changed because it is already byte-identical to the foundation Dockerfile. The installer does not introduce Nginx. The target updater default repository was corrected from the single-tenant source URL to `https://github.com/februana/multytenant_cms_enggine.git`.

## Known operational notes

The native installer is designed for Debian/Ubuntu with `apt-get` and `systemd`, matching the source installer assumptions. `SKIP_APACHE_PACKAGE_INSTALL=1` is available only for operators who have already installed the required runtime. `APACHE_ENABLE_SSL=1` requires an existing certificate directory. WebDAV is preserved as an explicit opt-in and is removed from the generated vhost when disabled. The public deployment model remains one Apache catch-all behind the Cloudflare Tunnel; tenant isolation is application- and database-driven, not based on per-tenant VirtualHosts.

## References

[1]: https://github.com/februana/webserver_undangan — CMS foundation source repository.
[2]: https://github.com/februana/multytenant_cms_enggine — Multi-tenant target repository.
