# Multi-Tenant CMS Engine

[`multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine) is a **Multi-Tenant CMS Engine** derived from the existing Wedding Invitation CMS. The repository intentionally contains the complete working application: the CMS engine, wedding invitation workflows, RSVP and guest APIs, administration, built-in wedding themes, theme assets, deployment scripts, and the completed multi-tenant implementation. This is a repository-level identity change, not a physical extraction or redesign of the application.

## Current architecture

```text
Internet
   |
   v
Cloudflare Tunnel
   |
   v
Apache catch-all VirtualHost
   |
   v
PHP application runtime (the host-configured PHP handler)
   |
   v
normalized HTTP_HOST
   |
   v
tenant resolver -> tenants.domain
   |
   +--> tenant-scoped SQLite configuration and data
   +--> theme/CMS renderer
   +--> uploads/tenant_<id>/ media namespace
   |
   v
Tenant-authorized media.php delivery
```

One application instance serves multiple tenants through one shared SQLite database and one shared schema. Tenant identity comes from the normalized request hostname, never from a client-supplied `tenant_id`. Tenant configuration is stored in tenant-scoped database rows, media is stored below `uploads/tenant_<id>/`, and public media delivery passes through `media.php`.

Known active tenants continue to the renderer. Unknown hosts fail closed unless validated Cloudflare defense-in-depth conditions allow transactional auto-provisioning. Suspended or invalid tenants return `404`; invalid direct-origin or missing-header provisioning attempts return `403` without creating a tenant. Tenant Admin operates only within its resolved tenant context, while Super Admin is the explicit cross-tenant administrative role.

## Configuration and persistence

The runtime source of truth is SQLite. Tenant configuration, custom CSS, event calendar data, and guest links are stored in `tenant_configs` and related tenant-scoped tables. Global `config.json`, `site.json`, `theme.json`, `sections.json`, and `guest-links.json` are not runtime configuration sources; legacy files are migration inputs only.

Schema creation and migration are deployment-time operations. The schema contract is maintained in [`database/migrations/001_multi_tenant.sql`](database/migrations/001_multi_tenant.sql), and the standalone migration is [`deploy/migrate.php`](deploy/migrate.php). Normal HTTP requests do not perform DDL or migration checks.

## CMS and theme architecture

The CMS is organized around a theme registry, theme contracts, shared helpers, theme renderers, preset layouts, visual capabilities, presentation capabilities, section capabilities, admin capabilities, media roles, and theme assets.

| Component | Responsibility |
|---|---|
| Theme registry | Declares preset identity, source metadata, capabilities, assets, and visual support. |
| Theme contract | Defines data, presentation, section, admin, media-role, and compatibility boundaries for each preset. |
| Theme helper | Provides shared normalization, configuration, visual, media, and presentation bridges. |
| Theme renderer | Selects the current preset and produces the complete document within the established rendering contract. |
| Preset layout | Preserves the source-compatible DOM, CSS, JavaScript lifecycle, dependencies, sections, and asset structure. |
| Visual capabilities | Expose supported backgrounds, colors, fonts, Theme Assets, previews, and reset behavior without creating a second media pipeline. |
| Custom mode | Provides the CMS-native and full-capability builder path with configurable sections, ordering, visibility, and Custom CSS. |

Built-in presets do not share identical sections. Each preserves its original presentation boundaries and capability limitations. A CMS data capability is not automatically a section in every preset. Custom mode is the full-capability CMS-native mode for users who need a configurable section structure.

## Active preset inventory

The active registry in [`app/theme-contract.php`](app/theme-contract.php) contains seven built-in presets plus Custom mode:

| Preset | Current contract focus |
|---|---|
| **DewanaKL** (`dewankl`) | Welcome/loading surfaces, split desktop/mobile presentation, story/video, gallery carousel, Love Gift, comments, audio, and source-aligned navigation. |
| **Shubh Vivah** (`shubh-vivah`) | Centered invitation card, ornamental corners, script typography, countdown, gallery grid, audio, and RSVP. |
| **Yami Buzzy** (`yami-buzzy`) | Welcome modal, hero countdown, couple cards, dress-code and love-story timelines, gallery, video, gift, mobile navigation, and RSVP. |
| **Rainier** (`rainier`) | Hero and event details, schedule and quotes lists, RSVP embed, footer branding, calendar, and audio. |
| **Parang** (`parang`) | User-provided Javanese-inspired reference integration with sidebar/app-bar navigation, ornaments, couple/event cards, gallery, gifts, maps, and RSVP. |
| **Pawiwahan** (`pawiwahan`) | Source-aligned Bootstrap navigation, welcome modal, carousel, event/countdown, gallery, gifts, maps, messages/RSVP, and audio. |
| **Archak** (`archak`) | Responsive navigation, parallax home, timeline, story, gallery, travel/stay, registry, parting message, and footer. |
| **Custom** | CMS-native full-capability renderer with configurable sections, visibility, ordering, visual controls, and Custom CSS. |

The registry and implementation are the source of truth for the active list. Historical audit prose must not be used to infer current preset availability.

## Tenant media lifecycle

Tenant media is organized as follows:

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

The upload lifecycle validates the input, performs WebP conversion where applicable, resizes according to the selected preset, removes the original only after successful conversion, and saves the result inside the current tenant namespace. Upload, replacement, deletion, preview, and render-time references are checked with tenant containment rules.

Apache rewrites every `/uploads/...` request to [`media.php`](media.php). That endpoint resolves the current host tenant, validates path containment and MIME type, and serves only the authorized tenant file. `/uploads/...` must never bypass tenant authorization through a static alias or alternate endpoint.

## Public and administrative endpoints

| Endpoint | Responsibility |
|---|---|
| `/` or `index.php` | Resolve the tenant and render its invitation. |
| `/save.php` | Save public RSVP data using the server-resolved tenant ID. |
| `/messages.php` | Read tenant-scoped visible messages. |
| `/gallery.php` | Return tenant-scoped gallery data. |
| `/event.ics.php` | Serve tenant-scoped calendar data. |
| `/media.php` | Authorize and deliver tenant media. |
| `/admin/` | Tenant Admin CMS within the matching tenant session. |
| `/admin/super-admin.php` | Super Admin tenant lifecycle and cross-tenant administration. |

## Repository layout

```text
/
├── index.php                 # public invitation controller
├── admin.php                 # compatibility admin redirect
├── save.php                  # public RSVP wrapper
├── messages.php              # public message wrapper
├── gallery.php               # public gallery wrapper
├── event.ics.php             # tenant calendar endpoint
├── media.php                 # tenant-authorized media delivery
├── config.php                # tenant context, SQLite, security, and media helpers
├── database/migrations/      # deployment-time schema contract
├── admin/                    # Tenant Admin and Super Admin interfaces
├── app/                      # registry, contracts, helpers, and renderers
├── themes/                   # built-in preset layouts and assets
├── uploads/                  # runtime tenant media; do not commit production data
├── deploy/                   # install, migrate, update, backup, restore, and audit scripts
├── tools/                    # validators, smoke tests, and dependency audit
├── docker/                   # optional Apache/PHP packaging
├── Dockerfile
├── docker-compose.yml
└── docs/                     # current architecture, operations, security, and attribution records
```

## Deployment

The production deployment architecture is:

```text
Cloudflare Tunnel
        ↓
Apache
        ↓
PHP application runtime (the host-configured PHP handler)
        ↓
SQLite
        ↓
Tenant CMS and theme renderer
```

Apache is the web server. The Cloudflare Tunnel is the intended public ingress, and the origin must not be exposed directly to the Internet. The native installer is non-destructive: it checks dependencies, copies application code without deleting runtime data, initializes runtime directories, and runs the standalone migration. It does not install packages, modify `/etc/apache2` automatically, enable or disable sites or modules, restart services, or configure Nginx.

Review and apply [`deploy/apache-catchall.conf.example`](deploy/apache-catchall.conf.example) separately. The catch-all must permit the repository's `.htaccess` rules, including the rewrite to `media.php`.

```bash
git clone https://github.com/februana/multytenant_cms_enggine.git
cd multytenant_cms_enggine
sudo bash deploy/install.sh
sudo /var/www/wedding/deploy/health-check.sh
```

See [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) for installation, update, backup, restore, Cloudflare onboarding, Docker packaging, and health-check procedures.

## Cloudflare auto-provisioning

Unknown-host auto-provisioning is enabled only when `UNDANGAN_AUTO_PROVISION=1` and all configured defense-in-depth conditions pass: `REMOTE_ADDR` is `127.0.0.1` or `::1`, `CF-RAY` is present, and `CF-Connecting-IP` contains a valid IP address.

These headers are **not cryptographic proof of Cloudflare provenance**. The origin must still be protected from direct Internet access by firewall, private-network, or equivalent network policy. A failed validation returns `403` and does not create a tenant. Invalid or suspended tenant domains fail closed with `404`.

## Security and testing

The security model includes Host-based tenant resolution, session isolation, server-assigned tenant IDs, database tenant scoping, tenant media containment, path traversal protection, MIME validation, upload validation, CSRF protection, password hashing, AES-256-GCM `visible_password` recovery storage, and origin network protection. Details are in [`SECURITY.md`](SECURITY.md), [`docs/MULTI_TENANT.md`](docs/MULTI_TENANT.md), and [`docs/PASSWORD_MANAGEMENT.md`](docs/PASSWORD_MANAGEMENT.md).

Run the checks that exist in this repository:

```bash
php -l config.php
php -l media.php
php -l index.php
php -l admin/index.php
php tools/validate.php
php tools/repo_contract_audit.php
php tools/dependency_graph_audit.php
bash /path/to/run_full_regression.sh
```

The regression runner used for the published baseline is `/home/ubuntu/run_full_regression.sh` in the validation environment. It covers deployment, rendering, tenant contracts, media lifecycle and isolation, visual capabilities, admin behavior, and backup/restore smoke tests. Do not treat the path above as a repository file; use the repository's individual `tools/` commands when operating from a normal checkout.

## Attribution

The CMS engine and integration code are maintained in this repository. Built-in presentation themes are adaptations or integrations of independently authored sources. [`docs/ATTRIBUTIONS.md`](docs/ATTRIBUTIONS.md) records the active presets, source repositories or user-provided references, exact revisions, license status, original assets, and current integration boundaries. Do not invent license claims or treat source-template code as original CMS engine code.
