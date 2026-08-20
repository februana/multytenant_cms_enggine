# Contributor Instructions

## Pure multi-tenant architecture

This repository uses one Apache/PHP application instance, one shared SQLite database, and one shared schema. The current tenant is derived from the normalized request `Host` header. Do not add browser-controlled tenant IDs to public or Tenant Admin operations.

All mutable invitation settings, custom CSS, calendar data, and guest links belong in tenant-scoped SQLite rows, primarily `tenant_configs`, `guest_links`, and `tamu`. Do not reintroduce `config.json`, `guest-links.json`, or another global runtime store. Schema creation and upgrades belong in `database/migrations/` and `deploy/migrate.php`; normal requests must not run DDL or migration checks.

Tenant media must remain below `uploads/tenant_<id>/`. Preserve the upload pipeline of validation, WebP conversion where applicable, preset-specific resizing, original cleanup after successful conversion, and tenant-scoped persistence. All `/uploads/` delivery must pass through `media.php`; do not add a static bypass.

The existing theme contract and renderer architecture must remain intact. Built-in presets preserve their source DOM, CSS, JavaScript lifecycle, dependencies, section order, and capability boundaries. Custom mode remains the CMS-native builder. Do not redesign the architecture to force every preset into identical sections.

The installer is non-destructive and Apache-only for the target deployment. It must not install OS packages, run `a2dissite`, `a2ensite`, `a2enmod`, `systemctl`, or write `/etc/apache2` or `/etc/nginx`. Review and apply the sample Apache catch-all separately.

## Verification commands

Run the following before committing changes:

```bash
php -l index.php
php -l admin/index.php
php -l config.php
php -l media.php
php tools/validate.php
php tools/repo_contract_audit.php
php tools/dependency_graph_audit.php
```

For deployment-related changes, also run the isolated regression and media-isolation suites described in the current audit report.

## Key entrypoints

- Public invitation: `index.php`
- Public wrappers: `save.php`, `messages.php`, `gallery.php`, `event.ics.php`
- Tenant media delivery: `media.php`
- Tenant Admin: `admin/index.php`
- Super Admin: `admin/super-admin.php`
- Tenant context, database, encryption, and media helpers: `config.php`
- Theme renderer and contracts: `app/`, `themes/`
- Deployment migration: `deploy/migrate.php`
- Permanent dependency audit: `tools/dependency_graph_audit.php`
