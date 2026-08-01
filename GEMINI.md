# Project Overview

This project is a professional CMS-based premium digital wedding invitation built with PHP. The repository is designed to remain lightweight, self-hosted, production-ready, and fully compatible with both Virtual Private Servers (VPS) and shared hosting environments.

---

# Design Philosophy

The website's user interface and experience must resemble a luxury printed wedding invitation. It must focus on high aesthetic standards and visual elegance.

- **Strict Boundaries:** Under no circumstances should this project evolve into a:
  - Dashboard
  - Landing Page
  - Blog
  - Company Profile
- **Narrative Sections:** The core narrative sections (**Hero, Invitation, Story, Gallery**) must remain visually open and flowing. Avoid wrapping or boxing narrative sections inside large, stark white cards.
- **Visual Elements:** 
  - Decorative backgrounds (such as high-quality images, textures, patterns, and subtle overlays) serve as the primary visual element.
  - Typography, delicate borders, subtle ornaments, and elegant spacing should carry the visual hierarchy.
- **Card Constraints:** Cards are strictly reserved for information-heavy components:
  - **Schedule**
  - **Location**
  - **Gift**
  - **RSVP**
  - *Rule:* When used, cards must be lightweight, subtle, highly decorative, and transparent where possible to blend elegantly with the backgrounds.

---

# Repository Architecture

The project employs a single-root public wrapper pattern where root files act as thin entry points for the core application behavior.

- **Canonical Frontend Assets:** `style.css` and `script.js` located at the root of the repository are the single canonical frontend assets.
- **Legacy Compatibility:** While legacy structures or compatibility layers may exist in the codebase, future development must not reintroduce duplicate frontend assets.
- **No Duplication:** Do not encourage or implement duplicate frontend sources (such as `app/style.css` or `app/script.js`), preventing maintenance drift.

---

# Upload Architecture

- **Single Entry Point:** `upload_file()` is the absolute, single canonical upload entry point for the repository.
- **Pipeline Integration:** Any and all future upload features or validation rules must extend this function directly.
- **Strict Limitation:** Never create parallel upload pipelines or duplicate file-receiving scripts.

---

# Media Processor (Roadmap)

To enhance file handling, a future `MediaProcessor` helper will be integrated directly into the upload pipeline:

- **Integration Point:** Must integrate directly into `upload_file()`.
- **Processing Time:** Media processing must occur exclusively during uploads (on-upload only).
- **Libraries:**
  - **Imagick:** The preferred image engine.
  - **GD:** Automatic fallback if Imagick is not available.
- **Feature Set:**
  - EXIF auto orientation
  - Image resizing
  - Performance optimization
  - WebP conversion
  - Image presets

---

# Media Presets

When the media processor is active, uploads will be processed according to these planned presets:

| Preset Name | Target Use / Output Rule |
| :--- | :--- |
| **Hero** | Large widescreen splash images |
| **Cover** | Standard layout cover banners |
| **Gallery** | Gallery photos (optimized aspect ratios) |
| **Section Background** | Low-contrast background textures / images |
| **Thumbnail** | Low-resolution previews |
| **QRIS** | Must strictly remain PNG format (no WebP conversion) |
| **Music** | Audio uploads (must never be processed or optimized) |

---

# Upload Manager (Media Library)

The repository distinguishes between system configuration management and media management:

- **CMS Role:** The CMS acts strictly as a **Configuration Manager** (editing the JSON schema and text parameters). Do not redesign the CMS architecture.
- **Upload Manager Role:** A future Media Library/Upload Manager will handle all assets and is responsible for:
  - Uploading new files
  - Replacing existing files
  - Renaming files safely
  - Deleting files and purging orphaned assets
  - Assigning assets to sections
  - Copying assets directly to the gallery
  - Tracking asset usage throughout the website
- **Extension Constraint:** The Media Library must extend the current single-root and JSON configuration architecture without altering the underlying CMS schema logic.

---

# Deployment

The core deployment utilities under the `deploy/` directory must remain aligned with the repository architecture:
- `deploy/install.sh`
- `deploy/update.sh`
- `deploy/health-check.sh`

**Environment Detection Requirements:**
The deployment scripts must automatically detect the presence of:
- `Imagick` extension
- `GD` library
- `EXIF` extension / PHP support

*Rule:* During deployment and runtime check phases, `Imagick` is always preferred, with `GD` as the automatic fallback if Imagick is missing.

---

# Backward Compatibility

All future features, optimizations, and refactors must preserve the integrity and functionality of existing systems. Never break:
- **Theme Builder**
- **Live Preview**
- **QR Generator**
- **Guest Links**
- **Backup**
- **Restore**
- **Deployment**
- **Existing JSON structure**
- **Existing uploads / user media files**
