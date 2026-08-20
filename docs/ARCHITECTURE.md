# Multi-Tenant CMS Engine Architecture

## Repository identity and scope

[`multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine) is a **Multi-Tenant CMS Engine** derived from the existing Wedding Invitation CMS. The repository intentionally retains the complete working application rather than extracting or deleting wedding-specific code. The source tree includes the CMS engine, wedding data model, RSVP and guest APIs, administrative interfaces, built-in wedding theme presets, theme assets, deployment scripts, and the completed multi-tenant implementation.

This document describes the implementation currently present in the repository. It is not an architectural proposal and does not claim that the wedding application has been physically separated from the engine.

## Runtime architecture

```text
Internet
    ↓
Cloudflare Tunnel
    ↓
Apache catch-all VirtualHost
    ↓
PHP application runtime (the host-configured PHP handler, such as Apache PHP or PHP-FPM integration)
    ↓
normalized HTTP_HOST
    ↓
tenant resolver → tenants.domain
    ↓
tenant-scoped SQLite configuration and data
    ↓
CMS/theme renderer
    ↓
tenant media namespace → media.php
```

One application instance serves multiple tenants through one shared SQLite database and one shared schema. The server derives tenant identity from the normalized `Host` header. Public and Tenant Admin controllers do not trust a browser-supplied `tenant_id`.

The `UNDANGAN_MAIN_DOMAIN` entered during installation is inserted as the initial normal tenant and is resolved through the same `tenants.domain` mechanism as every other tenant. It renders a normal public wedding invitation, owns tenant-scoped configuration/media/data, and supports its own Tenant Admin. Super Admin is not a hostname-based application: the initial Super Admin is stored as a role-based user with `role = super_admin` and `tenant_id IS NULL`, and its authorization permits cross-tenant management from an authenticated session. Access to the primary hostname alone never grants Super Admin privileges.

## Tenant and request ownership

| Concern | Current source of truth | Enforcement boundary |
|---|---|---|
| Tenant identity | Normalized `HTTP_HOST` resolved through `tenants.domain` | `current_tenant()` and fail-closed domain handling |
| Tenant configuration | `tenant_configs.config_json`, `custom_css`, and `event_ics` | Loaded with the resolved server-side tenant ID |
| RSVP and messages | `tamu.tenant_id` | Public reads/writes use the resolved tenant ID |
| Guest links | `guest_links.tenant_id` | Links are created and read within tenant context |
| Tenant Admin | `users.tenant_id` and verified PHP session | Session tenant and domain must match the current Host |
| Super Admin | `users.role = super_admin` and `tenant_id IS NULL` | Explicit cross-tenant administrative role |
| Media | `uploads/tenant_<id>/...` | Tenant containment plus `media.php` delivery |
| Schema | `database/migrations/001_multi_tenant.sql` and `deploy/migrate.php` | Deployment-time only; no request-time DDL |

Known active tenants continue to the CMS renderer. Unknown hosts fail closed unless validated auto-provisioning is enabled. Suspended and invalid domains return `404`. Direct-origin or incomplete Cloudflare validation for an unknown host returns `403` without creating a tenant.

## Configuration and migration

SQLite is the runtime source of truth. Tenant configuration, custom CSS, event calendar data, and guest links are stored in tenant-scoped database rows. Global `config.json`, `site.json`, `theme.json`, `sections.json`, and `guest-links.json` are not runtime sources of truth. Legacy files may be read by the migration process when upgrading an older installation, but normal HTTP requests do not read or write them as active configuration.

Schema creation and migration belong to [`database/migrations/001_multi_tenant.sql`](../database/migrations/001_multi_tenant.sql) and [`deploy/migrate.php`](../deploy/migrate.php). Normal requests do not execute `CREATE TABLE`, `ALTER TABLE`, or migration checks. The migration deterministically binds legacy RSVP data to the tenant identified by `UNDANGAN_MAIN_DOMAIN` and upgrades legacy password ciphertext when the configured key permits it.

The runtime flow is:

```text
SQLite tenant rows
       ↓
config.php and tenant-aware helpers
       ↓
theme registry and contracts
       ↓
theme helper and renderer
       ↓
current tenant's HTML, CSS, JavaScript, and media references
```

## Tenant resolution and Cloudflare defense-in-depth

The resolver normalizes the request hostname, removes a port, validates the hostname, and looks up `tenants.domain`. When `UNDANGAN_AUTO_PROVISION=1`, an unknown host may be provisioned only when all configured conditions pass:

- `REMOTE_ADDR` is `127.0.0.1` or `::1`.
- `CF-RAY` is present.
- `CF-Connecting-IP` contains a valid IP address.

These headers are **not cryptographic proof of Cloudflare provenance**. A local process can forge request metadata, so the origin must still be protected from direct Internet access through firewall, private-network, or equivalent network controls. A failed unknown-host validation returns `403` without creating a tenant. A suspended or invalid tenant returns `404`.

Validated provisioning creates the tenant, its initial `tenant_configs`, tenant-admin credentials, and `uploads/tenant_<id>/` directories transactionally. Super Admin can also create or activate tenants manually through `/admin/super-admin.php`.

## Media isolation and delivery

Tenant media is stored below the following namespace:

```text
uploads/
└── tenant_<id>/
    ├── cover/
    ├── gallery/
    ├── background/
    ├── music/
    ├── love-story/
    └── theme-assets/
```

The media lifecycle is upload validation → WebP conversion where applicable → preset-specific resizing → original cleanup after successful conversion → save inside the current tenant namespace. Upload, replacement, deletion, preview, and render-time references use tenant containment validation. MIME validation and path-traversal protection are part of the delivery boundary.

Apache rewrites `/uploads/...` to [`media.php`](../media.php). `media.php` resolves the current Host tenant again, rejects paths outside that tenant's approved roots, checks MIME type, and serves only the authorized file. There must be no static alias or alternate endpoint that bypasses tenant authorization.

## CMS and theme layers

The implementation is organized around these layers:

| Layer | Responsibility |
|---|---|
| Theme registry | Declares preset identity, source metadata, visual capabilities, and supported assets. |
| Theme contract | Declares data capabilities, presentation capabilities, sections, admin capabilities, media roles, and compatibility mappings. |
| Theme helper | Provides shared normalization, configuration, media, visual, and presentation bridges. |
| Theme renderer | Selects the active mode and loads the complete HTML document through the established renderer contract. |
| Preset layout | Preserves the source-compatible DOM, CSS, JavaScript lifecycle, dependencies, section order, and asset structure. |
| Visual capability layer | Provides supported backgrounds, colors, fonts, Theme Assets, previews, and reset-to-source-fallback behavior. |
| Custom mode | Provides the CMS-native full-capability builder with configurable sections, visibility, ordering, and Custom CSS. |

Built-in presets intentionally do not share identical sections. Their source DOM, CSS, JavaScript lifecycle, dependencies, section order, asset structure, and capability boundaries remain distinct. A capability that exists in the CMS is not automatically rendered as a section in every preset.

## Active preset registry

The current active registry is implemented in [`app/theme-contract.php`](../app/theme-contract.php). It contains these seven built-in presets and Custom mode:

| Registry key | Presentation boundary |
|---|---|
| `dewankl` | Welcome/loading, split desktop/mobile shell, story/video, gallery carousel, Love Gift, comments, audio, and source-aligned navigation. |
| `shubh-vivah` | Centered invitation card, ornamental corners, script typography, countdown, gallery, audio, and RSVP. |
| `yami-buzzy` | Welcome modal, hero countdown, couple cards, dress-code and love-story timelines, gallery, video, gift, mobile navigation, and RSVP. |
| `rainier` | Hero/event details, schedule and quote lists, RSVP embed, footer branding, calendar, and audio. |
| `parang` | User-provided Javanese-inspired reference integration with sidebar/app-bar navigation, ornaments, couple/event cards, gallery, gifts, maps, and RSVP. |
| `pawiwahan` | Source-aligned Bootstrap navigation, welcome modal, carousel, event/countdown, gallery, gifts, maps, messages/RSVP, and audio. |
| `archak` | Responsive navigation, parallax home, timeline, story, gallery, travel/stay, registry, parting message, and footer. |
| `custom` | CMS-native full-capability renderer with configurable sections, ordering, visibility, visual controls, and Custom CSS. |

The registry and current source code are authoritative. Historical documents must not be used to infer active preset availability.

## Public entrypoints

| Entrypoint | Responsibility |
|---|---|
| [`index.php`](../index.php) | Tenant-aware invitation controller and theme delegation |
| [`admin.php`](../admin.php) | Compatibility redirect to the administration area |
| [`save.php`](../save.php) | Tenant-scoped public RSVP wrapper |
| [`messages.php`](../messages.php) | Tenant-scoped public message wrapper |
| [`gallery.php`](../gallery.php) | Tenant-scoped public gallery wrapper |
| [`event.ics.php`](../event.ics.php) | Tenant-scoped calendar endpoint |
| [`media.php`](../media.php) | Tenant-authorized media delivery boundary |
| [`admin/`](../admin/) | Tenant Admin and Super Admin controllers |

## Deployment architecture

The intended production path is:

```text
Cloudflare Tunnel
        ↓
Apache
        ↓
PHP application runtime (the host-configured PHP handler)
        ↓
SQLite shared schema
        ↓
Tenant CMS and theme renderer
```

Apache is the web server. The public origin should not be exposed directly to the Internet. The native installer is non-destructive and does not install operating-system packages, modify `/etc/apache2` automatically, enable or disable sites or modules, restart services, or configure Nginx. Operators review and apply [`deploy/apache-catchall.conf.example`](../deploy/apache-catchall.conf.example) separately.

Docker Compose is an optional Apache/PHP packaging path for the same complete application. It is not a per-tenant architecture and requires persistent database and media volumes.

## Security invariants

Security boundaries include Host-based tenant resolution, server-assigned tenant IDs, session isolation, tenant-scoped database access, CSRF protection, path-traversal protection, MIME validation, upload validation, tenant media containment, and fail-closed handling for unknown or suspended tenants. Login uses one-way `password_hash()` verification. The intentional Super Admin recovery feature stores `visible_password` as AES-256-GCM ciphertext using `UNDANGAN_PASSWORD_KEY`.

The repository validators and dependency audit check the implementation contracts, but they do not replace firewall policy, Apache configuration review, filesystem permissions, secret management, or Cloudflare Tunnel network protection.
