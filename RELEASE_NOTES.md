# Release Notes — Multi-Tenant CMS Engine

## Repository identity

This repository is [`februana/multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine), a complete Multi-Tenant CMS Engine derived from the Wedding Invitation CMS. It retains the complete wedding application, including RSVP, guest links, admin functionality, built-in wedding themes, theme assets, APIs, and deployment scripts. The engine and application are intentionally kept together in this repository.

The copied source baseline is commit `320eb837963b4df89c2757488b7371b29c31ce9d` from the source project's `multy-tenant_februana` branch. That provenance identifies the starting source state; it is not the target repository identity or an instruction to use the source repository for new checkouts.

## Architecture

The current architecture is one Apache/PHP application instance, one shared SQLite database and schema, Host-based tenant resolution, tenant-scoped configuration, and tenant-isolated media below `uploads/tenant_<id>/`. Cloudflare Tunnel is the intended public ingress. Unknown and suspended tenants fail closed, while validated unknown-host provisioning is guarded by localhost and Cloudflare request conditions that are explicitly defense-in-depth rather than cryptographic provenance proof.

Schema creation and legacy migration are deployment-time operations handled by `database/migrations/001_multi_tenant.sql` and `deploy/migrate.php`. Normal HTTP requests do not perform DDL or migration checks. `/uploads/...` requests pass through `media.php` for current-tenant path containment and MIME authorization.

## Theme system

The active registry contains `dewankl`, `shubh-vivah`, `yami-buzzy`, `rainier`, `parang`, `pawiwahan`, and `archak`, plus `custom`. Built-in presets retain distinct source-compatible DOM, CSS, JavaScript lifecycle, dependencies, section order, asset structure, and capability boundaries. Custom is the CMS-native full-capability renderer.

## Deployment and upgrade

The production path is Cloudflare Tunnel → Apache → PHP application → SQLite → tenant CMS. The native installer is non-destructive and does not modify `/etc/apache2` automatically, install packages, configure Nginx, or restart services. Operators review and apply the sample Apache catch-all separately.

For a normal deployment or upgrade:

```bash
git clone https://github.com/februana/multytenant_cms_enggine.git
cd multytenant_cms_enggine
sudo bash deploy/install.sh
sudo /var/www/wedding/deploy/health-check.sh
```

Read [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md), [`BACKUP_RESTORE.md`](BACKUP_RESTORE.md), and [`SECURITY.md`](SECURITY.md) before operating a production installation.

## Validation

The repository includes validators for PHP contracts, endpoint contracts, dependencies, tenant boundaries, theme rendering, media lifecycle, visual capabilities, deployment, and backup/restore. At minimum, run:

```bash
php tools/validate.php
php tools/repo_contract_audit.php
php tools/dependency_graph_audit.php
```

## Attribution

The CMS engine and integration code are distinguished from independently authored or user-provided theme sources. [`docs/ATTRIBUTIONS.md`](docs/ATTRIBUTIONS.md) is the source of truth for active preset provenance, revisions, licenses, original assets, and integration paths.
