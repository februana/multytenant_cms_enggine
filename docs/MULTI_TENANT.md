# Pure Multi-Tenant Deployment

This document defines the current architecture of [`multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine): a Multi-Tenant CMS Engine with the complete Wedding Invitation CMS application, running as **one Apache instance, one PHP application instance, one shared SQLite database, one shared schema, and multiple invitation tenants**. Public traffic is expected to enter through a Cloudflare Tunnel. Tenants are resolved from the HTTP `Host` header rather than from URL parameters or browser input.

## Architecture contract

| Area | Current implementation |
|---|---|
| Web runtime | Apache catch-all VirtualHost with `.htaccess` and one application document root |
| Public ingress | Cloudflare Tunnel; the origin should not be directly Internet-accessible |
| Tenant resolution | Normalized `HTTP_HOST` lookup in `tenants.domain` |
| Database | Shared SQLite file and shared tenant-aware schema |
| Configuration | `tenant_configs` row keyed by `tenant_id` |
| Guest links | `guest_links` rows keyed by `tenant_id` |
| RSVP/messages | `tamu` rows keyed by `tenant_id` |
| Authentication | `users` plus PHP sessions containing role and tenant context |
| Media | `uploads/tenant_<id>/...` with `media.php` authorization |
| Super Admin | Explicit cross-tenant role for tenant lifecycle and administrative operations |

## Database schema and migrations

The schema contract is [`database/migrations/001_multi_tenant.sql`](../database/migrations/001_multi_tenant.sql). The standalone [`deploy/migrate.php`](../deploy/migrate.php) is the only application migration/bootstrap path. It creates or upgrades tables, deterministically binds legacy RSVP data to the tenant identified by `UNDANGAN_MAIN_DOMAIN`, and upgrades legacy visible-password ciphertext when necessary.

Normal web requests do not create or alter tables. `config.php` opens the already-migrated database and reads tenant-scoped rows. Do not execute the schema fragment manually against a production database containing legacy data; run the deployment migration with the correct environment values instead.

The principal tables are:

| Table | Tenant relation | Purpose |
|---|---|---|
| `tenants` | Root table | Domain, active/suspended state, and tenant identity |
| `users` | Nullable for Super Admin; otherwise tenant-scoped | Authentication and role assignment |
| `tenant_configs` | Primary key `tenant_id` | JSON configuration, custom CSS, and calendar data |
| `guest_links` | Required `tenant_id` | Personalized invitation links |
| `tamu` | Required `tenant_id` | RSVP and visible guest messages |

## Host routing

Every public request passes through tenant resolution. The resolver normalizes the hostname, removes a port, validates the FQDN, and looks up the active tenant. No public controller accepts a tenant ID from the client.

| Request state | Result |
|---|---|
| Known active domain | Continue with that tenant context |
| Known suspended domain | `404`, with no tenant data rendered |
| Invalid hostname | `404` |
| Unknown host with invalid ingress | `403`, with no tenant row created |
| Unknown host with validated Cloudflare ingress and auto-provision enabled | Transactional tenant provisioning |

Auto-provisioning is allowed only when `UNDANGAN_AUTO_PROVISION=1`, `REMOTE_ADDR` is `127.0.0.1` or `::1`, `CF-RAY` is present, and `CF-Connecting-IP` contains a valid IP address. These checks reduce accidental direct-origin provisioning but do not replace firewall or private-network controls. Local processes can forge headers, so Apache must not accept arbitrary public origin traffic.

A manually created tenant follows the same data model. Super Admin creates or activates the domain through `/admin/super-admin.php`, and the domain must be routed to the same Cloudflare Tunnel before it is made public.

## Tenant isolation

The resolved tenant ID is assigned by the server and is used for all public reads and writes. Tenant Admin sessions contain the tenant ID and domain and are rejected when used against another tenant's hostname. Super Admin is the only role intentionally allowed to inspect or modify multiple tenants.

Tenant isolation is enforced at four boundaries:

1. **Database:** tenant-aware tables carry `tenant_id`, and tenant operations use the current server-side tenant context.
2. **Session:** tenant-admin role and tenant identity are checked against the current request host.
3. **Filesystem:** all media references are contained below the current `uploads/tenant_<id>/` root.
4. **Delivery:** `/uploads/...` is rewritten to `media.php`, which resolves the current host tenant and authorizes the path again.

## Tenant media

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

The media pipeline remains upload → WebP conversion where applicable → resize using the selected preset → delete the original after successful conversion → save to the current tenant directory. Upload, replace, delete, preview, and render-time reference checks use tenant-aware path containment.

`media.php` verifies that a requested file belongs to the active tenant's approved roots and has an allowed MIME type before serving it. A Tenant A path cannot be read through Tenant B's hostname, even if the filename is known. There is no supported global upload directory.

## Apache and deployment

Run the application installer from a trusted checkout:

```bash
sudo bash deploy/install.sh
```

The installer is non-destructive and application-only. It does not run `apt-get`, `a2dissite`, `a2ensite`, `a2enmod`, `systemctl`, or equivalent commands, and it does not write `/etc/apache2` or `/etc/nginx`. Operators review and apply [`deploy/apache-catchall.conf.example`](../deploy/apache-catchall.conf.example) separately.

The Apache catch-all must resemble:

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/wedding

    <Directory /var/www/wedding>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Do not configure one VirtualHost or one application instance per tenant. A single catch-all is the intended routing boundary; Cloudflare supplies the public domain routing and the application supplies tenant resolution.

## Password handling

The installer generates the initial Super Admin password and `UNDANGAN_PASSWORD_KEY`. Login verification uses `users.password_hash`. The intentionally supported Super Admin recovery display uses `users.visible_password`, stored as AES-256-GCM in the format:

```text
gcm:base64(iv)::base64(tag)::base64(ciphertext)
```

The encryption key is read from `UNDANGAN_PASSWORD_KEY`, must not be committed or rendered to a client, and should be protected in `.env` with mode `600`. Do not rotate the key without a planned ciphertext migration or password reset procedure.

## Operational checks

After installation or update, run:

```bash
sudo /var/www/wedding/deploy/health-check.sh
php tools/validate.php
php tools/repo_contract_audit.php
php tools/dependency_graph_audit.php
```

Backups contain the shared database and the complete tenant-prefixed media tree. Restrict backup and restore access to operators; a database backup contains every tenant.
