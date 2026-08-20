# Foundation Parity Action Plan

## Scope decision

The target will not be converted back into the single-tenant source application. The source repository is used as the foundation behavior reference; the target keeps its tenant resolver, domain mapping, provisioning, shared schema, tenant configuration, tenant media roots, and tenant-aware authorization.

## Approved changes

| Change | Reason | Must preserve |
|---|---|---|
| Integrate native Apache/PHP-FPM flow into target `deploy/install.sh` | Verified source foundation gap: target installer stops before web-server setup | Existing runtime data, migrations, tenant storage, `.env`, and Docker path. |
| Reuse/adapt source Apache templates and target updater helper contract | Source already provides the behavior; target already carries related templates/helper logic | Catch-all tenant routing through `HTTP_HOST` and `.htaccess/media.php`. |
| Install/verify source-required native dependencies | Target native installer does not install Composer, PHP-FPM, GD, mbstring, zip, or ImageMagick | Composer lock and Docker dependency strategy. |
| Make native Apache config generation idempotent and configtest-gated | Required by source deployment behavior and safe rollout | No destructive `rsync --delete`, no tenant/media deletion, no blind reload. |
| Correct target updater default repository identity | Current default points to `webserver_undangan` and can fetch wrong foundation | Existing backup/migration safety behavior. |
| Repair tenant-aware media smoke fixture | Current target smoke test runs without active tenant and fails before testing production pipeline | Production media code and canonical tenant path. |
| Update deployment documentation and parity report | Existing docs still describe manual Apache as the only native path | Docker remains documented separately; no Nginx reintroduction. |

## Explicitly rejected changes

The Dockerfile will not be replaced with a PHP-FPM container because source and target Dockerfiles are byte-identical and already provide the foundation Apache container path. The tenant database will not be replaced with source legacy `config.json`, `guest-links.json`, or `event.ics`; those files are foundation runtime inputs that the target intentionally migrated into tenant-scoped database persistence. The preset engine, media processing algorithm, and UI component layer will not be rewritten because parity evidence shows they are present and mostly identical.

WebDAV will not be silently deleted. Because the foundation installer and target runtime contract both contain optional WebDAV support, the adapted installer must preserve it as an explicit operator-controlled option with secure credential handling. If it is disabled, the Apache vhost must not expose the directory.

Nginx will not be added to the new target deployment path. Existing legacy Nginx migration code will be classified and documented separately; it must not become the default or be used as a fallback when Apache installation is requested.

## Validation gates

Implementation is accepted only when shell syntax, generated Apache placeholder checks, `apache2ctl configtest` on a fixture configuration, PHP-FPM socket detection, idempotent rerun behavior, Composer dry-run, ImageMagick/WebP media tests with an active tenant, tenant isolation tests, and the existing preset/regression suite all pass.
