# Security Policy

This project is a PHP/SQLite wedding-invitation CMS deployed as a **single shared-schema multi-tenant application**. Security depends on keeping tenant context server-derived, protecting the origin behind the intended Cloudflare Tunnel, and preserving the filesystem and database boundaries described below.

## Reporting a vulnerability

Do not disclose a suspected vulnerability in a public issue. Provide a private report to the project maintainer with a description, affected branch or commit, reproduction steps, impact, and any proposed mitigation. Do not include production credentials, database dumps, or tenant media in a report.

## Security boundaries

| Boundary | Required behavior |
|---|---|
| Public ingress | Cloudflare Tunnel is the only intended public ingress; Apache origin access must be restricted by network policy |
| Tenant resolution | Normalize and validate `HTTP_HOST`, then resolve through `tenants.domain` |
| Tenant identity | Never accept `tenant_id` from browser input for public or tenant-admin operations |
| Session | Tenant Admin session tenant and domain must match the current host |
| Cross-tenant administration | Limited to authenticated Super Admin operations |
| Database | Shared SQLite schema with tenant-scoped rows and foreign-key relationships |
| Runtime migration | `deploy/migrate.php` only; no runtime DDL |
| Media | `uploads/tenant_<id>/` with path containment and `media.php` delivery authorization |
| Secrets | `.env`, database, backups, and `UNDANGAN_PASSWORD_KEY` remain outside Git and are protected on disk |

## Host and Cloudflare validation

Known active tenants are selected from the normalized hostname. Suspended or invalid domains return `404`. An unknown hostname is not provisioned unless `UNDANGAN_AUTO_PROVISION=1` and all of the following are true:

- `REMOTE_ADDR` is `127.0.0.1` or `::1`.
- `HTTP_CF_RAY` is present.
- `HTTP_CF_CONNECTING_IP` contains a valid IP address.

Invalid direct-origin or missing-header requests return `403` and do not create database rows. These checks are defense-in-depth. They do not prove that a local process is trustworthy, so Apache must not be exposed directly to the Internet.

## Database and authorization

The application uses a shared SQLite database with tenant-aware `tenants`, `users`, `tenant_configs`, `guest_links`, and `tamu` tables. Public controllers derive the tenant ID from the current request context before executing reads or writes. Tenant Admin controllers additionally verify role, session tenant, and hostname. Super Admin has `tenant_id IS NULL` and is intentionally allowed to administer multiple tenants.

Schema creation and upgrades happen through `database/migrations/001_multi_tenant.sql` and `deploy/migrate.php` during installation, update, or restore. Normal requests must not perform `CREATE TABLE`, `ALTER TABLE`, or legacy-file migration checks.

The runtime configuration source is `tenant_configs`. A global `config.json` or `guest-links.json` is not a supported runtime source. Legacy files are migration inputs only.

## Media isolation

All uploaded files belong to a tenant namespace:

```text
uploads/tenant_<id>/{cover,gallery,background,love-story,music,theme-assets}/
```

The upload pipeline validates the file, performs WebP conversion and preset-specific resize where applicable, removes the original only after successful conversion, and saves the result below the current tenant root. Every upload, replacement, deletion, preview, and render-time reference must pass tenant-aware containment checks.

Apache rewrites `/uploads/...` to `media.php`. The endpoint resolves the current host tenant again, rejects paths outside its approved roots, validates MIME type, and serves the file only when it belongs to that tenant. Do not add a static alias or alternate endpoint that bypasses this boundary.

## Passwords and encryption

Login uses `users.password_hash` with `password_verify()`. The intentionally supported Super Admin password-recovery display uses `users.visible_password`, encrypted with AES-256-GCM in this format:

```text
gcm:base64(iv)::base64(tag)::base64(ciphertext)
```

`UNDANGAN_PASSWORD_KEY` must remain in protected server configuration, never in Git or HTML. Do not rotate it without a controlled migration or reset plan. Treat the ability of Super Admin to view Tenant Admin recovery passwords as a deliberate business exception that requires strict administrative access control.

## Filesystem and deployment safety

The native installer is application-only and non-destructive. It does not run package-manager commands, enable or disable Apache sites or modules, restart services, or write `/etc/apache2` or `/etc/nginx`. Operators review and apply the catch-all Apache configuration separately.

Protect `.env`, the SQLite database, backups, and WebDAV credentials with restrictive permissions. Do not use a blanket recursive `chmod -R 755`. Do not commit production uploads, database files, backup archives, or secrets.

## Validation

Before release or deployment, run:

```bash
php tools/validate.php
php tools/repo_contract_audit.php
php tools/dependency_graph_audit.php
sudo /var/www/wedding/deploy/health-check.sh
```

The validators are repository contracts, not substitutes for network controls, operating-system permissions, or Cloudflare configuration review.
