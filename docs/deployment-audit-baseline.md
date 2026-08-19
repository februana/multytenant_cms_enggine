# Deployment and provenance audit baseline

Date: 2026-08-20 (user timezone).
Branch: fix/elix-floral-transition-navigation
Commit: 2543cce0f90c7c10b77962bece0b8640f070c86e
PR: #84 — https://github.com/februana/webserver_undangan/pull/84

## Supported deployment assets found

| Target | Repository evidence | Status |
|---|---|---|
| Docker | Dockerfile, docker-compose.yml, docker/entrypoint.sh, docker/000-default.conf | Present; healthcheck instructions need review |
| Native Ubuntu/Linux | deploy/install.sh, update.sh, backup.sh, restore.sh, health-check.sh, runtime-directories.sh | Present; needs full contract review |
| Render | docs/RENDER.md only; no render.yaml found in repository inventory | Documentation drift; do not claim Blueprint until file is added or docs removed |
| Other cloud target | No Fly.io, Railway, Coolify, CapRover, or systemd-only target found | Not supported by current repository |

## Active built-in presets

dewankl, rainier, archak, parang, pawiwahan, shubh-vivah, yami-buzzy, plus custom.

## Confirmed provenance findings

| Preset | Source | Audited revision | License finding |
|---|---|---|---|
| Shubh Vivah | https://github.com/vinitshahdeo/wedding-website | f42fbe653b54ff38096c82fd63bb759885a3402b | GitHub metadata and LICENSE identify MIT, Copyright (c) 2022 Vinit Shahdeo |
| Yami Buzzy | https://github.com/Tynab/Yami-Buzzy | 367f5a5fb33ce2f902d5fa2db5bb0508136eb2eb | GitHub metadata has no SPDX license; HEAD tree has no LICENSE/COPYING/NOTICE file; permission status unresolved |
| Elix | retired and absent from active registry | n/a | docs/licenses/ELIX-LICENSE.txt is stale residue and must be removed |

## Confirmed documentation gaps

1. docs/DEPLOYMENT.md names a retired preset and says six built-in adapters while the active registry has seven.
2. docs/RENDER.md claims render.yaml exists, but no render.yaml is present; this target must be either implemented honestly or documented as manual Docker deployment only.
3. docs/ATTRIBUTIONS.md has shifted reference numbers, lacks a Parang section, and does not retain a Shubh Vivah license notice or clearly disclose the unresolved Yami Buzzy license status.
4. README, CHANGELOG, RELEASE_NOTES, and ARCHITECTURE need a consistent account of the PR #84 feature set and deployment targets.

## Review decisions

The deployment changes will remain scoped to the existing Docker and native paths unless a third-party target can be implemented and tested without introducing unsupported persistence or operational claims. Yami Buzzy will not be labeled MIT without a source license file or explicit source declaration.
