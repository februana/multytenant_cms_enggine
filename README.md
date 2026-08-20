# Wedding Invitation CMS

This repository contains a PHP 8.3 and SQLite wedding-invitation CMS with a preserved theme-adapter architecture. It runs as a **pure multi-tenant application**: one Apache instance, one application instance, one shared SQLite database, one shared schema, and multiple invitation domains resolved from the request `Host` header. The intended public ingress is a Cloudflare Tunnel.

## Current architecture

```text
Cloudflare Tunnel
        |
        v
Apache catch-all VirtualHost
        |
        v
Tenant resolver: normalized HTTP_HOST -> tenants.domain
        |
        +--> tenant_configs.config_json
        +--> tenant-scoped users, guest links, and RSVP rows
        +--> uploads/tenant_<id>/...
        |
        v
CMS engine -> theme adapter -> built-in preset or Custom renderer
```

The tenant context is established by the server. Public and tenant-admin requests do not accept a client-supplied `tenant_id`; they use the tenant resolved from `Host`, and tenant-admin sessions are checked against the current hostname. Super Admin operations are the deliberate cross-tenant exception and are restricted by role.

All mutable invitation configuration, custom CSS, calendar data, and guest links are stored in SQLite under the tenant's `tenant_configs` or related tenant-scoped tables. The runtime does **not** read or write a global `config.json`, `guest-links.json`, or global media namespace. Schema creation and legacy-data migration are deployment operations performed by `deploy/migrate.php`, not by normal web requests.

See [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) for ownership boundaries, [`docs/MULTI_TENANT.md`](docs/MULTI_TENANT.md) for tenant routing and isolation, and [`docs/ATTRIBUTIONS.md`](docs/ATTRIBUTIONS.md) for source provenance and license information.

## Presets

The seven built-in presets are:

- **DewanaKL** — welcome/loading, gallery, video, gift, comment, AOS, and confetti-oriented invitation flow.
- **Rainier** — event-oriented `#app` flow with timezone-aware event data, calendar, optional schedule/quotes, RSVP, and footer branding. Rainier does not use AOS.
- **Archak** — navigation, home, timeline, story, gallery, stay, registry, parting message, footer, parallax, and reveal flow.
- **Parang** — Javanese-inspired source-adapter flow with ornaments, side navigation, couple, event, story, gallery, gift, maps, RSVP, and music boundaries.
- **Pawiwahan** — preserved static source flow with Bootstrap carousel, welcome modal, guest resolver, couple, event/countdown, gallery, gift, maps, messages/RSVP, and audio boundaries.
- **Shubh Vivah** — centered invitation-card flow with floral ornaments, script typography, countdown, gallery, RSVP, and localized Indonesian UI.
- **Yami Buzzy** — welcome-modal/editorial flow with hero, couple, events, dress code, story, gallery, video, gift, invitation, RSVP, and localized Indonesian UI.

Custom mode remains the CMS-native builder for users who need a configurable section structure. A capability that is absent from a simple built-in preset is intentional when the source template has no equivalent presentation boundary.

## Public and administrative endpoints

| Endpoint | Responsibility |
|---|---|
| `/` or `index.php` | Resolve the current tenant, load its configuration, and render the invitation. |
| `/save.php` | Save public RSVP data with the server-resolved tenant ID. |
| `/messages.php` | Read tenant-scoped visible messages. |
| `/gallery.php` | Return tenant-scoped gallery data. |
| `/event.ics.php` | Serve the current tenant's calendar data. |
| `/media.php` | Authorize and deliver tenant media after path and MIME checks. |
| `/admin/` | Tenant Admin CMS, restricted to the matching tenant session. |
| `/admin/super-admin.php` | Super Admin tenant management and cross-tenant administration. |

Requests for `/uploads/...` are rewritten to `media.php`. A media path belonging to Tenant A is not readable through Tenant B's hostname, even when the filename is known.

## Repository layout

```text
/
├── index.php                 # public invitation controller
├── admin.php                 # legacy admin redirect wrapper
├── save.php                  # public RSVP wrapper
├── messages.php              # public message wrapper
├── gallery.php               # public gallery wrapper
├── event.ics.php             # tenant-scoped calendar endpoint
├── media.php                 # tenant-authorized media delivery
├── config.php                # tenant context, DB, security, and media helpers
├── database/migrations/      # deployment-time schema contract
├── admin/                    # tenant CMS and Super Admin UI
├── app/                      # shared renderer, helpers, and theme contracts
├── themes/                   # built-in adapters and retained source assets
├── uploads/                  # runtime tenant media root; do not commit production data
├── deploy/                   # install, migrate, update, backup, restore, and audit scripts
├── tools/                    # repository validators and dependency audits
├── docker/                   # optional Docker/Apache packaging
├── Dockerfile
├── docker-compose.yml
└── docs/                     # current architecture, deployment, security, and theme records
```

## Native Apache deployment

The supported hardware deployment is a single Apache instance behind a Cloudflare Tunnel. The installer is intentionally **non-destructive**. It checks for PHP, OpenSSL, and SQLite3, copies application code without deleting existing runtime data, creates the shared runtime directories, and runs the standalone migration. It does not install OS packages, enable or disable Apache modules or sites, restart services, or write to `/etc/apache2` or `/etc/nginx`.

Prepare Apache manually with the reviewed example at [`deploy/apache-catchall.conf.example`](deploy/apache-catchall.conf.example). The catch-all VirtualHost must use the application directory as its document root, permit `.htaccess` overrides, and be reachable only through the intended ingress boundary. Do not expose the origin directly to the Internet.

```bash
cd /path/to/webserver_undangan
git checkout multy-tenant_februana
sudo bash deploy/install.sh
sudo /var/www/wedding/deploy/health-check.sh
```

The installer requires a valid `UNDANGAN_MAIN_DOMAIN` or prompts for it. It creates the initial Super Admin and runs `deploy/migrate.php`. For existing installations, use the guarded update and backup/restore procedures described in [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) and [`BACKUP_RESTORE.md`](BACKUP_RESTORE.md).

## Cloudflare and tenant onboarding

Set `UNDANGAN_AUTO_PROVISION=1` only when the origin is correctly restricted to the local Cloudflare Tunnel daemon. An unknown hostname is auto-provisioned only when all ingress checks pass: `REMOTE_ADDR` is `127.0.0.1` or `::1`, `CF-RAY` is present, and `CF-Connecting-IP` contains a valid address. Invalid direct-origin or missing-header requests do not create tenants and return `403`; invalid or suspended tenant domains return `404`.

Super Admin can also create or activate a tenant manually from `/admin/super-admin.php`. Every custom domain must first be routed to the same Cloudflare Tunnel. New tenant provisioning creates its database configuration, tenant-admin credentials, and `uploads/tenant_<id>/` media namespace transactionally.

## Tenant media lifecycle

Uploads are scoped below `uploads/tenant_<id>/` using the logical media categories `cover`, `gallery`, `background`, `love-story`, `music`, and `theme-assets`. The existing pipeline is preserved: upload, convert to WebP where applicable, resize according to the selected preset, delete the original when conversion succeeds, and save only inside the current tenant's namespace. Render-time path containment and the `media.php` delivery boundary prevent cross-tenant read, write, replace, delete, and preview operations.

A clean checkout contains no production cover, gallery, music, video, or Open Graph media. Administrators must upload or provision optional media through the tenant CMS. Do not copy production media into Git and do not use a global path as a substitute for a tenant namespace.

## Validation

Run the repository contract and dependency checks before deployment or a pull request:

```bash
php tools/validate.php
php tools/repo_contract_audit.php
php tools/dependency_graph_audit.php
```

The complete audit evidence for the current branch is maintained outside the runtime data directories. Operational procedures, backup semantics, and security boundaries are documented in [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md), [`docs/MULTI_TENANT.md`](docs/MULTI_TENANT.md), [`docs/PASSWORD_MANAGEMENT.md`](docs/PASSWORD_MANAGEMENT.md), and [`SECURITY.md`](SECURITY.md).

## Indonesian default copy

A new tenant configuration uses Indonesian wedding copy with the official names **FEBRUANA** and **ANDI MUHAMAD BASUKI**, the familiar calls **Febru** and **Andi**, an Arabic Bismillah opening, a localized greeting and opening quotation, **QS. Ar-Rum 21**, and an Islamic closing. These are defaults rather than locked content: Tenant Admin input replaces them, and clearing a field restores its corresponding default.

## License and attribution

The CMS integration code is project-specific. The built-in presentation templates are adaptations of the sources recorded in [`docs/ATTRIBUTIONS.md`](docs/ATTRIBUTIONS.md), including DewanaKL, Shubh Vivah, Yami Buzzy, Rainier, Archak, Pawiwahan, and the user-provided Parang reference. Review that document before redistributing a preset or its source assets.
