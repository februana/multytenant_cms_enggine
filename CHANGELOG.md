# Changelog

## [Unreleased] — Multi-Tenant CMS Engine

This repository is the complete Multi-Tenant CMS Engine application derived from the Wedding Invitation CMS. The current baseline is the source tree copied from `webserver_undangan` commit `320eb837963b4df89c2757488b7371b29c31ce9d`; the target repository identity is [`februana/multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine).

### Current implementation

The application provides one Apache/PHP instance, one shared SQLite database and schema, Host-based tenant resolution, tenant-scoped configuration, tenant-isolated media, tenant-aware public and administrative endpoints, and validated Cloudflare Tunnel auto-provisioning. The wedding invitation application remains complete, including RSVP, guest links, administration, built-in presets, theme assets, and deployment tooling.

The theme system includes the seven active built-in presets `dewankl`, `shubh-vivah`, `yami-buzzy`, `rainier`, `parang`, `pawiwahan`, and `archak`, plus the `custom` CMS-native renderer. The registry and contracts preserve source-compatible DOM, CSS, JavaScript lifecycle, dependencies, section order, asset structure, and capability boundaries for each built-in preset.

### Documentation baseline

The current documentation describes SQLite tenant rows as the runtime source of truth, deployment-time migrations, the `uploads/tenant_<id>/` media structure, `media.php` authorization, Apache and Cloudflare ingress, AES-256-GCM recovery storage, security boundaries, testing commands, and source attribution. Global configuration files are not documented as runtime sources of truth.

## Validation

Use the repository validators that are present in the target tree:

```bash
php tools/validate.php
php tools/repo_contract_audit.php
php tools/dependency_graph_audit.php
```

Use the individual `tools/` smoke tests or the existing external regression runner for the complete rendering, media, tenant-isolation, deployment, and backup/restore baseline.
