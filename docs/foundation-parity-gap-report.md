# Foundation Parity Gap Report

**Source foundation:** [`februana/webserver_undangan`](https://github.com/februana/webserver_undangan), pristine `main` at `b2ecf80fb8c8efe7bd0f3669603aa29232e9b849`.
**Target current tree:** [`februana/multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine), PR #2 commit `77927f556822499f0dd985f38ab8ac6186c129a7`.
**Historical pre-parity parent:** `165d28c`.

## Executive conclusion

The available evidence supports the conclusion that **no CMS foundation capability is missing within the extracted source-target capability model**. The source and target have equal normalized configuration defaults, preset registry, section contracts, media roles, admin capabilities, presentation capabilities, visual schemas, asset hints, and data capabilities for all seven built-in presets. The target adds tenant resolution, tenant-scoped configuration and media, provisioning, migrations, role-aware authorization, and audit controls without replacing the foundation CMS capability surface.

This report is documentation-only evidence for the current PR tree. It does not claim that every implementation detail is byte-identical, and it does not claim a symmetric dependency-graph proof: the target contains `tools/dependency_graph_audit.php`, while the source repository does not contain the corresponding tool.

## Quantitative inventory

| Measure | Historical parent `165d28c` | Current PR #2 tree `77927f5` | Interpretation |
|---|---:|---:|---|
| Common tracked paths | 209 | 209 | Stable comparison universe. |
| Common files byte-identical | 158 | 156 | Two paths became adapted in the parity commit. |
| Common files adapted/changed | **51** | **53** | 51 is the historical pre-parity count; 53 is the current tree count. |
| Source-only tracked paths | 21 | 21 | Four legacy data artifacts plus seventeen audit/provenance documents. |
| Target-only tracked paths | 16 | 22 | Tenant, parity, deployment, and audit additions. |

The two paths that changed the adapted count from historical 51 to current 53 are `tools/pawiwahan_smoke.php` and `tools/visual_contract_smoke.php`.

## Source-only decomposition

The 21 source-only paths are **not 21 missing CMS files**. They contain no source-only PHP, JavaScript, CSS, shell installer, preset asset, or runtime endpoint.

| Category | Paths | Meaning |
|---|---|---|
| Legacy data artifacts | `config.json`, `database.sqlite`, `guest-links.json`, `event.ics` | Single-tenant/runtime data files replaced by tenant configuration/database, `guest_links` persistence, and the dynamic `event.ics.php` endpoint. They must not be restored globally because that would weaken tenant isolation. |
| Audit/provenance documents | `docs/BROWSER_VERIFICATION.md`, `docs/CMS_THEME_AUDIT.md`, `docs/DEPENDENCY_MATRIX.md`, `docs/HARDENING_AUDIT.md`, `docs/MEDIA_REQUIREMENTS.md`, `docs/REGRESSION_REPORT.md`, `docs/admin-customization-audit.md`, `docs/deployment-audit-baseline.md`, `docs/docker-render-build-audit.md`, `docs/media-role-audit.md`, `docs/new-presets-cms-mapping.md`, `docs/new-presets-responsive-evidence.md`, `docs/new-presets-source-audit.md`, `docs/preset-render-fallback-delete-audit.md`, `docs/repository-consistency-audit.md`, `docs/user-input-capability-audit.md`, `docs/visual-capability-expansion-baseline.md` | Documentation and historical evidence only; no executable capability provider. |

## Foundation component classification

| Foundation component | Source evidence | Target evidence | Status |
|---|---|---|---|
| Application core | Root controllers, `config.php`, renderer, `.htaccess` | Same core surface plus tenant resolver and persistence | Adapted; no missing core capability identified |
| Composer | Identical `composer.json` and `composer.lock` | Same locked package graph; Composer dry-run succeeds | Identical dependency definition |
| PHP runtime extensions | Source package/bootstrap requirements | Target Docker and native installer preserve the required runtime path | Parity supported; no unresolved extension gap in final validation |
| ImageMagick/GD media pipeline | ImageMagick CLI with GD fallback and WebP verification | Same processing policy plus tenant destination containment | Adapted; algorithm retained |
| Media pipeline | Role/preset requirements, conversion, verification, cleanup | Same behavior plus tenant storage and path safety | Adapted; no foundation algorithm loss |
| Preset engine | Source contracts, layouts, assets, and dependencies | Same seven preset contracts and assets with tenant-safe bridges | Adapted; contract equality verified |
| Database foundation | Legacy global data files and `init_database()` | Tenant migrations, `tenant_database()`, `tenant_configs`, `guest_links`, dynamic calendar endpoint | Intentionally tenant-adapted |
| Authentication | `verify_admin_password()` and single-tenant session path | Database-backed authentication, session revalidation, role/tenant checks | Intentionally tenant/security-adapted |
| Security foundation | CSRF, sessions, password checks, upload/path checks, sensitive-file blocking | Same controls plus tenant containment, role revalidation, action allowlist, audit log | Adapted; no capability loss identified |
| Native Apache deployment | Source Apache/PHP-FPM installer behavior | Target installer renders PHP-FPM tenant catch-all, configtests, activates site, and reloads service | Parity gap closed in PR #2 |
| Docker deployment | Source Docker image and Apache runtime | Dockerfile is byte-identical | Identical; Docker path remains separate |

## Helper comparison

The PHP symbol comparison found 155 source symbols, 190 target symbols, and 153 common symbols. Only two source symbols are absent from the target: `init_database()` and `verify_admin_password()`.

These are **single-tenant implementation details, not missing CMS capabilities**. `init_database()` is replaced by tenant-aware migration/database initialization. `verify_admin_password()` is replaced by database-backed authentication and session validation, including `authenticate_user()`, `current_admin_user_record()`, `verify_current_admin_password()`, and `session_admin_is_valid()`.

## Contract equality

The extracted normalized contract comparison found 129 configuration leaf paths on both sides with no source-only or target-only leaf. For `archak`, `dewankl`, `parang`, `pawiwahan`, `rainier`, `shubh-vivah`, and `yami-buzzy`, the following dimensions are equal between source and target:

| Contract dimension | Result |
|---|---|
| Preset registry and built-in preset keys | Equal |
| Sections and DOM IDs | Equal |
| Media roles | Equal |
| Admin capabilities | Equal |
| Presentation capabilities | Equal |
| Visual schema keys | Equal |
| Asset hints | Equal |
| Data capabilities | Equal |

This is evidence for **foundation contract parity**, not a claim that tenant routing or security additions should be byte-identical to the single-tenant source.

## Dependency evidence and limitation

`composer.json` and `composer.lock` are byte-identical. Composer dry-run succeeds in both repositories with `chillerlan/php-settings-container@3.3.0` and `chillerlan/php-qrcode@5.0.5`. The correct dependency model is:

> `composer.json` + `composer.lock` → `composer install` → generated `vendor/`

Neither repository tracks `vendor/`, and no `package.json` or npm lockfile exists in either repository.

The **target** dependency graph audit passed with **0 confirmed failures and 0 warnings**. A symmetric source-target dependency-graph proof is not available because the source repository does not contain the corresponding `tools/dependency_graph_audit.php`. This is a limitation of evidence, not evidence that the source dependency graph is broken.

## Validation status

The current target proof suite contains 19 validator/smoke tests. All exited with code 0. The output contained no `FAIL`, fatal error, parse error, or unhandled exception markers. The target working tree was unchanged before and after the read-only suite.

## Final status

**Foundation parity is supported by the available evidence.** The documentation corrections are limited to provenance accuracy: current adapted count **53** versus historical pre-parity count **51**, explicit classification of the 21 source-only paths, precise replacement wording for two single-tenant helpers, and the non-symmetric dependency-graph limitation.

## References

[1]: https://github.com/februana/webserver_undangan — CMS foundation source repository.

[2]: https://github.com/februana/multytenant_cms_enggine/pull/2 — PR #2 under audit.

[3]: https://getcomposer.org/doc/01-basic-usage.md — Composer dependency installation and lock-file behavior.
