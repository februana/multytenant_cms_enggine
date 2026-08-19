# Current CMS Architecture

## Overview

This repository is a CMS-first PHP application. The CMS owns data, services, persistence, security boundaries, and capability metadata. A theme adapter translates those CMS values into the presentation vocabulary of the selected template. Built-in presets intentionally preserve their source template's DOM, CSS, JavaScript lifecycle, dependencies, and user experience; they are not generic CMS section skins.

```text
CMS ENGINE
    ↓
THEME ADAPTER
    ↓
BUILT-IN PRESET
```

Custom mode is a separate CMS-native path:

```text
CMS ENGINE
    ↓
CUSTOM CMS-NATIVE BUILDER
```

The active preset is selected through `config.json` at `theme.theme_preset`. `app/theme-contract.php` defines the vocabulary, capabilities, and admin controls that are valid for each preset. `app/theme-helper.php` provides shared normalization and service helpers. `themes/<preset>/layout.php` owns the complete built-in document output; the shared renderer owns the Custom document and global CMS-native sections.

## Ownership boundaries

| Layer | Owns | Does not own |
|---|---|---|
| CMS engine | `config.json`, runtime persistence, uploads, guest links, RSVP/messages, calendar/SEO data, capability metadata, admin services, security helpers | Source-template DOM order or a universal presentation design |
| Theme adapter | Mapping CMS data to a source template, safe backend bridges, source-compatible initialization, theme-specific optional media behavior | Inventing generic sections that the source template does not have |
| Built-in preset | Original presentation structure, selectors, order, CSS, JavaScript lifecycle, and dependency posture | Global CMS data storage or a competing renderer |
| Custom builder | Full CMS-native section ordering, visibility, Custom CSS, and generic builder experience | Pretending to be any built-in source template |

A CMS capability is not automatically a section. A capability can be a service or data source consumed only where a template has a suitable presentation boundary. It also does not have to appear in every preset. Users who need the broadest section flexibility should use Custom mode.

## Visual capability layer

Visual customization follows the same registry → Admin control → canonical persistence → adapter bridge → scoped CSS → source fallback path as other CMS capabilities. `visual_capabilities` declares the supported section backgrounds and Theme Asset roles for each preset; the Admin panel adds localized labels, previews, color palettes, font catalogs, and reset actions without creating a second media store.

A selected background or Theme Asset stores a canonical reference. Clearing that reference does not delete the physical upload; it only restores the source-template fallback. The renderer emits a scoped variable or asset value only for the supporting preset and section, which prevents a Gallery upload from silently becoming a Hero background or a disabled navigation item from pointing to an empty anchor.

```text
Media Manager / Theme Asset upload
              ↓
  preset-aware Admin selector + preview/reset
              ↓
       config.json canonical reference
              ↓
 theme adapter bridge + scoped visual variables
              ↓
 selected asset OR source-template fallback
```

The default invitation copy is also data, not hardcoded presentation. `config_defaults()` supplies Indonesian names, greetings, Arabic opening text, Qur'anic quotation, and Islamic closing; Admin values override individual fields, while an empty saved value resolves to the corresponding default. Calendar metadata is generated from the current title, schedule, and location.

## Preset contract

Built-in capabilities are intentionally different because the source templates are different. DewanaKL has original welcome/video/gallery/gift/comment boundaries; Rainier has an event-oriented `#app` flow with optional schedule/quotes/RSVP; Archak has a compact navigation, story/gallery/stay/registry, parting-message, and footer composition; Parang and Pawiwahan retain their source-aligned cultural and carousel boundaries; Shubh Vivah uses a centered invitation card with ornaments, countdown, gallery, and RSVP; Yami Buzzy uses a welcome modal, hero, couple, event, story, gallery, video, gift, invitation, and RSVP flow. A missing generic CMS section in a built-in preset is not a defect when the source template has no equivalent.

`theme_section_enabled()` is the built-in visibility boundary. The global `is_section_enabled()` and `config.sections` ordering belong to Custom mode. Admin UI filtering uses the active preset's `admin_capabilities` plus a small explicit global set. Unsupported preset controls are gated in both navigation and panel body; filtering never deletes stored configuration.

## Global guest system

Guest management is a global CMS capability. The Guest Link Generator persists records in the existing guest-link store and produces the repository's `?to=` URL contract. The personalized guest name is resolved centrally, normalized, length-limited, and escaped before presentation.

```text
CMS guest data / ?to=Andi
        ↓
shared guest resolver + theme adapter
        ↓
theme-specific greeting presentation
```

The markup is not identical across presets. Custom renders the name through its CMS-native hero; each built-in adapter places the greeting in its source-compatible hero or opening-card flow.

## Persistence and deployment

The application stores code in the document root and runtime data separately when `UNDANGAN_DATA_DIR` is set. Native installations keep runtime files in the root by default. Docker stores configuration, guest links, custom CSS, event ICS, and SQLite data in `/var/data`; uploaded media, backup archives, and optional WebDAV data use separate named volumes. This separation prevents container recreation or source updates from silently discarding CMS data. The Docker image and Compose service expose an HTTP healthcheck, while `deploy/health-check.sh` performs the deeper CMS, permission, preset, media, and security audit.

The public entrypoint is `index.php`; `admin.php` redirects to the admin UI; `save.php`, `messages.php`, and `gallery.php` expose the backend wrappers used by the frontend. The canonical deployment output is `/var/www/wedding`; it is not a Git working tree.

## Provenance

The seven built-in templates are source adaptations or user-provided design references: DewanaKL, Rainier, Archak, Parang, Pawiwahan, Shubh Vivah, and Yami Buzzy. See [`ATTRIBUTIONS.md`](ATTRIBUTIONS.md) for exact revisions, authors, license status, original source files, current integration paths, and attribution requirements. The project intentionally keeps source attribution separate from CMS ownership: CMS integration code is project-specific, while the original template presentation remains attributed to its original creators.
