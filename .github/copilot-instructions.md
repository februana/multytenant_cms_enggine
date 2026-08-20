# Copilot Instructions for webserver_undangan

This repository is a pure multi-tenant PHP/SQLite wedding-invitation CMS. Keep the existing theme-adapter architecture intact while preserving tenant isolation.

## Rules

1. **Tenant context is server-derived.** Resolve the active tenant from the normalized `HTTP_HOST` through `tenants.domain`. Never trust a client-provided `tenant_id` for public or Tenant Admin operations.
2. **SQLite is the runtime source of truth.** Store tenant settings, custom CSS, calendar data, and guest links in tenant-scoped database rows. Do not introduce global `config.json`, `guest-links.json`, or another global mutable store.
3. **Migrations are deployment-only.** Put schema creation and upgrades in `database/migrations/` or `deploy/migrate.php`. Do not run `CREATE TABLE`, `ALTER TABLE`, or legacy-file migration checks during normal requests.
4. **Media is tenant-isolated.** Save files only below `uploads/tenant_<id>/`. Preserve validation, WebP conversion, preset-specific resize, original cleanup after successful conversion, and tenant-aware containment checks.
5. **Media delivery is authorized.** `/uploads/...` requests must route through `media.php`, which resolves the current host tenant, validates containment, verifies MIME type, and serves only that tenant's file.
6. **Ingress is Cloudflare-validated.** Unknown-host auto-provisioning requires `UNDANGAN_AUTO_PROVISION=1`, localhost `REMOTE_ADDR`, `CF-RAY`, and a valid `CF-Connecting-IP`. This is defense-in-depth; do not weaken origin network restrictions.
7. **Preserve theme contracts.** Built-in presets retain their source DOM, CSS, JavaScript lifecycle, dependencies, section order, and capability boundaries. Custom mode remains the CMS-native builder.
8. **Keep installation non-destructive.** The installer must not install OS packages, change `/etc/apache2` or `/etc/nginx`, enable/disable web-server modules or sites, or restart services.

## Verification

```bash
php tools/validate.php
php tools/repo_contract_audit.php
php tools/dependency_graph_audit.php
```

For changes affecting deployment, media, authentication, or tenant routing, run the full regression and tenant-isolation suites before committing.
