# Contributing to the Multi-Tenant CMS Engine

## Project scope

[`multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine) is the complete Multi-Tenant CMS Engine application derived from the Wedding Invitation CMS. Contributions must preserve the complete application, including wedding workflows, RSVP and guest APIs, admin functionality, built-in presets, theme assets, and deployment tooling.

This repository is not an extracted engine-only library. Do not remove wedding-specific code merely because it is application-specific, and do not redesign the renderer into a separate application layer without an explicit architectural decision.

## Tenant boundaries

Tenant identity is derived from the normalized request `Host` and resolved through `tenants.domain`. Public and Tenant Admin code must never trust a browser-supplied `tenant_id`. Tenant configuration, guest links, RSVP rows, and media references must remain tenant-scoped. Tenant Admin sessions must remain bound to the current host, while Super Admin is the intentional cross-tenant administrative exception.

Configuration belongs in SQLite tenant rows. Do not introduce or restore a global `config.json`, `site.json`, `theme.json`, `sections.json`, or `guest-links.json` runtime source. Schema changes belong in `database/migrations/` and `deploy/migrate.php`; normal HTTP requests must not run DDL or migration checks.

All media belongs below `uploads/tenant_<id>/`. Preserve validation, WebP conversion where applicable, preset-specific resize, cleanup after successful conversion, and tenant containment. Every `/uploads/...` request must remain authorized through `media.php`.

## Theme development

The active theme registry and contract are in `app/theme-contract.php` and the related registry/helper files. A built-in preset must retain its source-compatible DOM, CSS, JavaScript lifecycle, dependencies, section order, asset structure, and capability boundaries. Do not force all presets to expose identical sections.

Use the theme contract for data capabilities, presentation capabilities, section definitions, admin capabilities, media roles, assets, and compatibility mappings. Use the visual capability layer for supported colors, fonts, backgrounds, previews, reset behavior, and Theme Assets. Custom mode is the CMS-native full-capability renderer and should remain distinct from built-in source adapters.

When adding or modifying a preset, update the implementation and its attribution record in `docs/ATTRIBUTIONS.md`. Do not invent source repositories, authors, or licenses.

## Deployment safety

The native installer is non-destructive and application-only. It must not install operating-system packages, modify `/etc/apache2` automatically, enable or disable web-server sites or modules, restart services, or add an Nginx production path. Operators review and apply the sample Apache catch-all separately. Cloudflare Tunnel is the intended public ingress, and origin network restrictions remain mandatory.

## Validation

Run syntax checks and repository validators before opening a pull request:

```bash
php -l config.php
php -l media.php
php -l index.php
php -l admin/index.php
php tools/validate.php
php tools/repo_contract_audit.php
php tools/dependency_graph_audit.php
```

Use the individual smoke tests under `tools/` for affected areas. Deployment, media, tenant isolation, rendering, admin-session, configuration-isolation, and backup/restore changes require the corresponding existing smoke tests. Do not invent new commands in documentation; describe only scripts that exist in the repository or the separately maintained validation environment.

## Documentation

Update README, architecture, deployment, security, testing, and attribution records when runtime behavior or operational boundaries change. Classify mentions of legacy files or source repositories accurately as current behavior, migration compatibility, or historical/provenance context. Do not silently change application code to make documentation appear consistent; report an implementation/documentation mismatch instead.
