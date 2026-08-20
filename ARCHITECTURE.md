# Architecture Documentation

The authoritative architecture description for this branch is maintained in [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

The application is a pure multi-tenant PHP/SQLite CMS served by one Apache instance. It uses a shared schema, resolves tenants from the normalized `Host` header, stores configuration in `tenant_configs`, scopes media below `uploads/tenant_<id>/`, and routes media delivery through `media.php`.

Read the following documents together:

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — CMS ownership, tenant context, rendering, and security boundaries.
- [`docs/MULTI_TENANT.md`](docs/MULTI_TENANT.md) — shared schema, ingress validation, tenant isolation, and media structure.
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) — Apache, Cloudflare Tunnel, installation, update, and health procedures.
- [`docs/ATTRIBUTIONS.md`](docs/ATTRIBUTIONS.md) — source provenance and license status for built-in presets.

This file intentionally contains no independent architecture contract, so it cannot drift from the detailed documentation under `docs/`.
