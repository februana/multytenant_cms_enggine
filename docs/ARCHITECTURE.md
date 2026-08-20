# Current CMS Architecture

## Overview

The application is a **pure multi-tenant PHP/SQLite CMS**. One Apache instance serves one application checkout, one shared SQLite database, and one shared schema. The tenant is selected from the normalized request hostname and is never selected from a client-supplied `tenant_id` parameter.

The frontend theme architecture remains intentionally unchanged:

```text
Tenant resolver
      ↓
Tenant configuration and scoped data
      ↓
CMS engine
      ↓
Theme adapter
      ↓
Built-in preset or Custom renderer
      ↓
Complete HTML document
```

Built-in adapters preserve their source DOM, CSS, JavaScript lifecycle, dependency boundaries, section order, and UX. Custom mode remains the CMS-native builder for users who need a configurable section structure. Preset capability differences are intentional and are described in the theme contract records.

## Tenant and request ownership

| Concern | Source of truth | Boundary |
|---|---|---|
| Current tenant | Normalized `HTTP_HOST` resolved through `tenants.domain` | `current_tenant()`; invalid or suspended domains are rejected |
| Tenant configuration | `tenant_configs.config_json`, `custom_css`, and `event_ics` | Configuration is loaded with the resolved tenant ID |
| RSVP and messages | `tamu.tenant_id` | Public reads and writes use the server-resolved tenant ID |
| Guest links | `guest_links.tenant_id` | Links are created and read within the tenant context |
| Tenant Admin identity | `users.tenant_id` and verified PHP session | Session tenant must match the current hostname |
| Super Admin identity | `users.role = super_admin` and `tenant_id IS NULL` | Cross-tenant operations are explicit and role restricted |
| Media | `uploads/tenant_<id>/...` | Filesystem containment plus `media.php` delivery |
| Schema | `database/migrations/001_multi_tenant.sql` and `deploy/migrate.php` | Deployment-time only; no runtime DDL |

The server owns the tenant context. Controllers and SQL helpers do not trust a tenant ID supplied by the browser. Tenant Admin operations additionally verify the session role, session tenant ID, and current host before reading or changing data.

## Configuration and persistence

The runtime uses `UNDANGAN_DB_PATH` when provided and otherwise uses the deployment database path. Configuration is read from the tenant's row in `tenant_configs`; there is no global `config.json` or `guest-links.json` runtime store. Legacy files may be read by the standalone migration when upgrading an older installation, but normal requests never write those files.

The migration contract is deliberately outside the request path. `deploy/migrate.php` creates or upgrades the schema, deterministically binds legacy RSVP data to the tenant identified by `UNDANGAN_MAIN_DOMAIN`, and upgrades legacy visible-password ciphertext to AES-256-GCM when required. Runtime code opens and queries the resulting schema; it does not perform `CREATE TABLE` or `ALTER TABLE` checks during a web request.

## Tenant resolution and ingress validation

Every public request resolves a hostname by removing a port, normalizing case, and validating the resulting FQDN before looking it up in `tenants`. Known active domains continue to the application. Suspended or invalid domains receive `404`.

When `UNDANGAN_AUTO_PROVISION=1`, an unknown hostname can be provisioned only when the request satisfies the Cloudflare Tunnel ingress checks. `REMOTE_ADDR` must be `127.0.0.1` or `::1`, `CF-RAY` must be present, and `CF-Connecting-IP` must contain a valid IP address. Direct-origin requests and requests without the required Cloudflare headers receive `403` and do not create a tenant. Provisioning is transactional and creates the tenant row, initial `tenant_configs`, tenant-admin account, and tenant media directories together.

> **Operational invariant:** Localhost and Cloudflare headers are defense-in-depth checks, not a replacement for network policy. Apache must not be reachable directly from the public Internet; only the intended Cloudflare Tunnel path should reach the origin.

## Media isolation and delivery

All tenant media is stored below:

```text
uploads/
└── tenant_<id>/
    ├── cover/
    ├── gallery/
    ├── background/
    ├── love-story/
    ├── music/
    └── theme-assets/
```

The CMS upload pipeline remains upload → WebP conversion where applicable → preset-specific resize → original deletion after successful conversion → save under the current tenant namespace. Upload, replacement, deletion, preview, and render-time reference validation use tenant-aware containment checks.

Apache rewrites `/uploads/...` requests to [`media.php`](../media.php). That endpoint resolves the current host tenant again, rejects paths outside the active tenant's approved media roots, verifies the file type, and serves only authorized files. Static access to another tenant's namespace is therefore not an alternate delivery path.

## Canonical public entrypoints

| Entrypoint | Responsibility |
|---|---|
| [`index.php`](../index.php) | Tenant-aware controller and theme delegation |
| [`admin.php`](../admin.php) | Compatibility redirect to the admin application |
| [`save.php`](../save.php) | Tenant-scoped public RSVP wrapper |
| [`messages.php`](../messages.php) | Tenant-scoped public message wrapper |
| [`gallery.php`](../gallery.php) | Tenant-scoped public gallery wrapper |
| [`event.ics.php`](../event.ics.php) | Tenant-scoped calendar endpoint |
| [`media.php`](../media.php) | Tenant-authorized media delivery boundary |
| [`admin/`](../admin/) | Tenant Admin and Super Admin controllers |

Shared implementation remains in [`config.php`](../config.php), [`app/theme-helper.php`](../app/theme-helper.php), [`app/theme-renderer.php`](../app/theme-renderer.php), and the theme contract files. Shared helpers are loaded with `require_once`; theme layouts must not redefine them.

## Theme contracts

The canonical preset setting is `theme.theme_preset` inside the current tenant's configuration. Supported built-in presets are `dewankl`, `rainier`, `archak`, `parang`, `pawiwahan`, `shubh-vivah`, and `yami-buzzy`; `custom` is the CMS-native renderer mode. Unknown preset values fall back safely according to the existing renderer contract.

Each theme owns its document layout and loads its own theme CSS and JavaScript. Custom CSS is loaded after theme CSS so tenant-specific CMS overrides remain effective. The renderer preserves SEO metadata, JSON-LD, guest-name resolution, event/calendar data, section visibility, gallery, music, RSVP, maps, gifts, and other source-compatible functionality.

## Deployment architecture

```text
Repository checkout
        |
        | deploy/install.sh or deploy/update.sh
        v
/var/www/wedding  <-- Apache document root
        |
        +--> database.sqlite or UNDANGAN_DB_PATH
        +--> .env
        +--> uploads/tenant_<id>/
        +--> backups/
```

The native installer is application-only and non-destructive. It does not install packages, modify `/etc/apache2` or `/etc/nginx`, enable modules or sites, or restart services. Operators review and apply [`deploy/apache-catchall.conf.example`](../deploy/apache-catchall.conf.example) separately, then configure the Cloudflare Tunnel and network boundary according to [`docs/DEPLOYMENT.md`](DEPLOYMENT.md).

Docker Compose remains an optional packaging path for the same Apache/PHP application. It is not a per-tenant deployment model and must use persistent volumes for database and media data.

## Security invariants

Sensitive files such as `.env`, SQLite databases, backups, and deployment credentials must not be directly downloadable. Tenant Admin sessions cannot cross tenant hosts. Public SQL writes assign the tenant ID on the server. Media delivery is authorized by current host and filesystem containment. `UNDANGAN_PASSWORD_KEY` is required to decrypt the intentionally supported AES-256-GCM `visible_password` value for Super Admin password recovery; the key must remain outside Git and be protected with file mode `600`.

These invariants are enforced by the repository validators and the permanent dependency graph audit in [`tools/dependency_graph_audit.php`](../tools/dependency_graph_audit.php).
