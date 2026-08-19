# Release Notes — Current Unreleased Change Set

**Scope:** PR #84 on `fix/elix-floral-transition-navigation`
**Status:** Unreleased
**Deployment impact:** Docker and native Ubuntu/Linux paths remain supported; runtime data must be backed up before updates.

## Overview

This change set completes the multi-preset wedding invitation CMS transition. The repository now supports seven active built-in presets—DewanaKL, Rainier, Archak, Parang, Pawiwahan, Shubh Vivah, and Yami Buzzy—plus the CMS-native Custom builder. Built-in presets remain source-template adapters rather than generic skins: each preserves its own DOM boundaries, navigation, animation lifecycle, dependencies, and section order.

The CMS now has a preset-aware visual capability layer. Admin can use localized controls for supported section backgrounds, Theme Assets, named colors, heading/body font catalogs, previews, and reset-to-default actions. A reset removes only the saved reference and restores the source fallback; it does not delete the physical media file or create a second media pipeline.

## Content defaults

A clean configuration now starts with Indonesian wedding copy for **FEBRUANA** and **ANDI MUHAMAD BASUKI**, including the calls **Febru** and **Andi**, Arabic Bismillah, localized greeting and opening quotation, **QS. Ar-Rum 21**, and an Islamic closing. Admin values override these defaults field by field, and clearing a field resolves back to its default. Calendar metadata is generated from the current title, opening, schedule, and location.

## Deployment changes

Docker now declares an image-level HTTP healthcheck and a matching Compose service healthcheck against `http://127.0.0.1/`. Compose persists CMS state, uploads and preset Theme Assets, backup archives, and optional WebDAV data in separate named volumes. The entrypoint recreates the shared runtime directory contract, protects `.env` permissions after recursive normalization, and safely escapes environment substitutions when creating the initial file. `.dockerignore` excludes local runtime data while retaining `.env.example` for bootstrap.

Native deployment remains available through `deploy/install.sh` with Nginx or Apache, and `deploy/update.sh`, `deploy/backup.sh`, `deploy/restore.sh`, and `deploy/health-check.sh` remain the operational path. No Render Blueprint or other managed-cloud manifest is claimed because the repository does not contain or test one. The native installer is the supported non-Docker alternative.

## Upgrade procedure

Before changing an existing native installation, run:

```bash
sudo /var/www/wedding/deploy/backup.sh
sudo /var/www/wedding/deploy/update.sh
sudo /var/www/wedding/deploy/health-check.sh
```

For Docker, retain named volumes during rebuilds:

```bash
docker compose build
docker compose up -d --force-recreate
docker compose exec wedding-cms /var/www/wedding/deploy/health-check.sh
```

Do not run `docker compose down -v` unless intentionally resetting a disposable installation. Read [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) and [`BACKUP_RESTORE.md`](BACKUP_RESTORE.md) for the complete procedure.

## Provenance and licensing

Shubh Vivah is adapted from [vinitshahdeo/wedding-website](https://github.com/vinitshahdeo/wedding-website), audited at revision `f42fbe653b54ff38096c82fd63bb759885a3402b`, with the MIT notice retained in `docs/licenses/SHUBH-VIVAH-LICENSE.txt`. Yami Buzzy is adapted from [Tynab/Yami-Buzzy](https://github.com/Tynab/Yami-Buzzy), audited at revision `367f5a5fb33ce2f902d5fa2db5bb0508136eb2eb`; no SPDX license or license file was found at that revision, so `docs/licenses/YAMI-BUZZY-LICENSE.txt` records an unresolved status rather than granting or implying MIT rights. Elix is retired and its stale local license residue has been removed.

See [`docs/ATTRIBUTIONS.md`](docs/ATTRIBUTIONS.md) for every source revision, author, license status, representative source files, and integration boundary.

## Validation

The repository maintains smoke coverage for configuration defaults, theme rendering and contracts, localization, content preservation, visual capabilities, media lifecycle, admin guest access, preset behavior, deployment bootstrap, backup/restore, and update safety. The final audit requires all smoke tests to pass, `git diff --check` to be clean, and no untracked runtime data or generated secrets.

## Related documents

- [`README.md`](README.md) — project overview and quick start.
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — ownership and capability boundaries.
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) — complete deployment operations.
- [`BACKUP_RESTORE.md`](BACKUP_RESTORE.md) — archive and disaster recovery.
- [`SECURITY.md`](SECURITY.md) — security expectations and reporting.
- [`CHANGELOG.md`](CHANGELOG.md) — technical change summary.
