# Foundation Parity Validation

**Target current tree:** `februana/multytenant_cms_enggine` at `77927f556822499f0dd985f38ab8ac6186c129a7`
**Foundation reference:** `februana/webserver_undangan` at pristine `main` commit `b2ecf80fb8c8efe7bd0f3669603aa29232e9b849`
**Historical pre-parity parent:** `165d28c`
**Validation date:** 2026-08-21

## Result

Foundation parity is supported by the available evidence for the extracted CMS capability model. The target preserves the source capability surface while retaining tenant resolution, tenant-scoped configuration, tenant-scoped media, provisioning, and role-aware authorization. This report records documentation/provenance corrections only; it does not claim that tenant adaptations should be byte-identical to the single-tenant foundation.

## Evidence summary

| Area | Result | Evidence |
|---|---|---|
| File inventory | **53 current adapted common files**, 51 historical pre-parity adapted files, 21 source-only paths, 22 current target-only paths | Reproducible source/target inventory at `b2ecf80f`, `165d28c`, and `77927f5` |
| Source-only decomposition | 4 legacy data artifacts and 17 audit/provenance documents; no source-only PHP, JS, CSS, shell, preset asset, or runtime endpoint | Source-only semantic inventory |
| Contract equality | 129 config leaf paths; preset registry; 7 presets; sections; media roles; admin capabilities; presentation capabilities; visual schemas; asset hints; and data capabilities equal | Extracted normalized source/target contracts |
| Composer | `composer.json` and `composer.lock` byte-identical; dry-run resolves `chillerlan/php-settings-container` 3.3.0 and `chillerlan/php-qrcode` 5.0.5 in both repositories | Composer validation and dry-run for both checkouts |
| Docker foundation | Source and target Dockerfiles are byte-identical | SHA-256 comparison |
| Dependency graph | Target graph: **0 confirmed failures, 0 warnings** | `tools/dependency_graph_audit.php` on target |
| Dependency graph limitation | Source does not contain the corresponding `tools/dependency_graph_audit.php`; therefore symmetric source-target graph proof is unavailable | Source inventory and tool existence check |
| Media/ImageMagick | Tenant-aware media pipeline, WebP/GD fallback, preset requirements, cleanup, canonical storage, delete safety, and backup/restore assertions pass | Media smoke suite |
| Presets/UI | Contract, render, localization, visual capability, color/font, Pawiwahan, and user-input capability tests pass | Theme/visual smoke suite |
| Authentication/tenant isolation | Session revalidation, role scope, action allowlist, current-password check, audit log, and tenant identity tests pass | `multitenant_auth_smoke.php` |
| Apache rendered config | Rendered PHP-FPM vhost passes `apache2ctl -t`; disabled WebDAV markers are absent when opt-out is selected | Temporary Apache fixture |
| Native installer | End-to-end copy, Composer, migration, PHP-FPM socket detection, Apache module setup, vhost rendering, configtest, site activation, and service reload pass | Isolated `/tmp` installer run |
| Idempotence | Second native installer run preserves a sentinel tenant media file | Isolated two-run installer test |
| Proof suite | **19/19** validator/smoke commands exit 0; no `FAIL`, fatal, parse, or unhandled-exception markers | Read-only target proof suite |
| Working tree | Unchanged before and after proof suite | `git status --porcelain` comparison |

## Count provenance

The historical **51 adapted** count is reproducible against target parent `165d28c`, before parity commit `77927f5`. The current PR #2 tree contains **53 adapted** files. The two additional adapted paths are `tools/pawiwahan_smoke.php` and `tools/visual_contract_smoke.php`. Both numbers are retained here for provenance, but only 53 describes the current tree.

## Source-only scope

The 21 source-only paths are not 21 missing CMS files. Four are legacy data artifacts: `config.json`, `database.sqlite`, `guest-links.json`, and `event.ics`. The remaining seventeen are audit/provenance documents. No source-only PHP, JavaScript, CSS, shell installer, preset asset, or runtime endpoint exists in the inventory. The target replaces the legacy global data model with tenant-aware configuration/database initialization, `guest_links` persistence, and the dynamic `event.ics.php` endpoint.

## Dependency model

The correct Composer model is:

> `composer.json` + `composer.lock` → `composer install` → generated `vendor/`

Neither checkout tracks `vendor/`, and neither contains a `package.json` or npm lockfile. The target dependency graph audit passed with 0 confirmed failures and 0 warnings. A symmetric source-target dependency-graph proof is not available because the source repository does not contain the corresponding graph tool. This is a limitation of evidence, not evidence that the source dependency graph is broken.

## Deployment evidence

The native installer follows the source Apache/PHP-FPM behavior while adapting document root, tenant catch-all routing, and tenant runtime paths. It installs or verifies the required runtime, runs Composer from the lock file, runs the standalone multi-tenant migration, detects the actual PHP-FPM socket, renders the Apache template, and gates activation/reload on `apache2ctl configtest`. It preserves `.env`, databases, tenant media, backups, and WebDAV data. Docker remains separate because its Dockerfile is byte-identical to the foundation.

## Final status

**Foundation parity is supported by the available evidence.** Remaining corrections are documentation/provenance accuracy issues: current adapted count 53 versus historical 51, explicit 21 source-only classification, exact replacement wording for `init_database()` and `verify_admin_password()`, and the non-symmetric dependency-graph limitation.

## References

[1]: https://github.com/februana/webserver_undangan — CMS foundation source repository.

[2]: https://github.com/februana/multytenant_cms_enggine/pull/2 — PR #2 under audit.

[3]: https://getcomposer.org/doc/01-basic-usage.md — Composer dependency installation and lock-file behavior.
