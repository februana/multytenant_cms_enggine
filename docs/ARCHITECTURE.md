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

## Preset contract

Built-in capabilities are intentionally different because the source templates are different. DewanaKL has original welcome/video/gallery/gift/comment boundaries; Elix has its original hero/story/gallery/RSVP/gifts/audio boundaries; Rainier has an event-oriented `#app` flow with optional schedule/quotes/RSVP; Archak has a compact navigation, story/gallery/stay/registry, parting-message, and footer composition. A missing generic CMS section in a built-in preset is not a defect when the source template has no equivalent.

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

The markup is not identical across presets. Custom renders the name through its CMS-native hero; DewanaKL retains its original `#guest-name`; Elix retains its original hero flow; Rainier and Archak place the greeting in their respective original hero/home flows.

## Persistence and deployment

The application stores code in the document root and runtime data separately when `UNDANGAN_DATA_DIR` is set. Native installations keep runtime files in the root by default. Docker stores configuration, guest links, custom CSS, event ICS, and SQLite data in `/var/data`, while uploaded media remains in its dedicated volume. This separation prevents container recreation or source updates from silently discarding CMS data.

The public entrypoint is `index.php`; `admin.php` redirects to the admin UI; `save.php`, `messages.php`, and `gallery.php` expose the backend wrappers used by the frontend. The canonical deployment output is `/var/www/wedding`; it is not a Git working tree.

## Provenance

The four built-in templates are third-party source adaptations. See [`ATTRIBUTIONS.md`](ATTRIBUTIONS.md) for exact revisions, authors, license status, original source files, current integration paths, and attribution requirements. The project intentionally keeps source attribution separate from CMS ownership: CMS integration code is project-specific, while the original template presentation remains attributed to its original creators.
