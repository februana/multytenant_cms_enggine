# Multi-Tenant CMS Engine Architecture

[`multytenant_cms_enggine`](https://github.com/februana/multytenant_cms_enggine) is the complete Multi-Tenant CMS Engine and Wedding Invitation CMS application. The repository contains the engine, wedding-specific workflows, built-in theme presets, admin interfaces, APIs, deployment scripts, and multi-tenant implementation together.

The authoritative architecture record is [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md). Read it with [`docs/MULTI_TENANT.md`](docs/MULTI_TENANT.md), [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md), and [`docs/ATTRIBUTIONS.md`](docs/ATTRIBUTIONS.md).

At a high level, Internet traffic reaches the Cloudflare Tunnel, then Apache, then the PHP application. The application derives tenant identity from the normalized `HTTP_HOST`, loads tenant-scoped SQLite configuration, renders the selected built-in theme or Custom mode, and delivers tenant media through `media.php`.

This file is a navigation entry point rather than a second architecture contract, so future changes should be made in `docs/ARCHITECTURE.md` first.
