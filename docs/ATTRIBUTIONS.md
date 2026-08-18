# Third-Party Templates and Source Attribution

This project incorporates and adapts multiple independent invitation templates and user-provided design references. The built-in presets are **not presented as original designs of this project**. The CMS contributes configuration, services, backend integration, capability filtering, and theme adapters; the original template repositories remain the provenance source for the corresponding presentation, selectors, CSS, JavaScript lifecycle, and dependency choices.

## Attribution policy

Each source revision was inspected locally and recorded below. The source repository, exact revision, original author identity, license file, README guidance, representative source files, and current integration path are listed separately. The project does not copy entire third-party repositories into this document and does not redistribute the original repositories' sample media as deployment defaults.

Where a source uses the MIT License, redistribution of substantial portions requires retaining the copyright and permission notice. Adapted project code is identified as an integration layer; it must not be represented as the original author's work. Template attribution links are retained in the relevant rendered footer where the original template provides one, and this document is the repository-level attribution record.

## DewanaKL

**Original project:** Template website undangan pernikahan sederhana.  
**Original author/copyright holder:** `dewana_kl`, as stated in the source LICENSE.  
**Repository:** [dewanakl/undangan][1]  
**Exact source revision used:** `99e7c2d141858455a96ed1af58a129cea002c3b0`  
**License:** MIT License, Copyright (c) 2023 `dewana_kl`.  
**License status:** Clearly established by the LICENSE file.  
**Attribution requirements:** Preserve the MIT copyright and permission notice in copies or substantial adaptations. The source README also credits visual assets to Pixabay; this project does not ship those source sample images as its clean-install media. The exact source notice is retained at [`docs/licenses/DEWANAKL-LICENSE.txt`](licenses/DEWANAKL-LICENSE.txt).

**Original characteristics.** The source is a vanilla HTML/CSS/JavaScript invitation with a welcome/loading flow, Bootstrap-based presentation, AOS animation hooks, Font Awesome icons, Canvas Confetti, a video love-story block, image/gallery behavior, guest query handling, and a modular runtime loaded through `js/libs/loader.js` and `js/guest.js`.

**Representative original source files used for provenance:**

- `index.html` — original DOM and section vocabulary.
- `css/common.css` — core layout and component styles.
- `css/animation.css` — animation and transition styles.
- `css/guest.css` — invitation/welcome and guest-facing styles.
- `js/guest.js` — guest-facing lifecycle and interactions.
- `js/libs/loader.js` — original dependency loading, including AOS and Canvas Confetti.
- `js/libs/confetti.js` — confetti integration.
- `js/common/*.js` — original shared utility/runtime modules.
- `assets/video/265501_tiny.mp4` and `assets/music/pure-love-304010.mp3` — source demo media references only; not copied as clean-install defaults.

**Current integration.** The current adapter lives in `themes/dewankl/layout.php` and `themes/dewankl/script.js`. Substantially source-based style/runtime files are retained under `themes/dewankl/original/`: `common.css`, `animation.css`, `guest.css`, `guest.js`, `audio.js`, `image.js`, `progress.js`, and `video.js`. `fidelity-adapter.css` and the PHP layout are project-specific integration layers. The adapter maps CMS data into the original IDs and DOM boundaries, adds the CMS RSVP bridge and global guest identity, and gates optional media without replacing the original template with generic CMS sections.

**CMS capabilities consumed:** wedding names/dates/location, guest identity, gallery, story, RSVP/messages, gift data, cover/background media, optional love-story video, explicit music enablement, calendar links, SEO metadata, and theme section visibility for template-relevant boundaries.

**Important dependencies:** Bootstrap 5.3.8, Font Awesome 7.1.0, AOS 2.3.4, Canvas Confetti 1.9.3, Google Fonts, and the original local CSS/JavaScript runtime. Rainier does not inherit these DewanaKL-only dependencies.

**Intentional limitations:** The source template owns the DOM order and UX. CMS capabilities that have no original DewanaKL presentation boundary are not forced into this preset. Cover, music, and video are optional user-provided media; absent files suppress or neutralize the corresponding behavior.

## Elix

**Original project:** Wedding invitation website.  
**Original author/creator:** The repository is published under the `elix-stack` organization; the source commit author is `elix-stack` / Muhammad Ali Yusufgi.  
**Repository:** [elix-stack/wedding-invitation-1][2]  
**Exact source revision used:** `1ac23948c42febb1150d4ddf6d10a39211471448`  
**License:** MIT License. The exact LICENSE file contains the copyright line `Copyright (c) 2025 [Your Name]`.  
**License status:** MIT terms are clearly present; the copyright-holder text is a placeholder in the source LICENSE and therefore requires manual rights-holder review before any legal redistribution decision.  
**Attribution requirements:** Preserve the MIT notice and permission text. Do not replace the source LICENSE's placeholder with an invented person or organization. The exact source notice is retained at [`docs/licenses/ELIX-LICENSE.txt`](licenses/ELIX-LICENSE.txt).

**Original characteristics.** Elix is a responsive HTML/CSS/JavaScript invitation with the original `#hero`, `#home`, `#info`, `#story`, `#gallery`, `#rsvp`, `#gifts`, `#audio-container`, navbar, countdown, lightbox, and footer/Design by flow. The source README describes its story timeline, event details, RSVP, responsive behavior, and Google Sheets-inspired integration.

**Representative original source files used for provenance:**

- `index.html` — original DOM, IDs, section order, dependency declarations, and inline lifecycle.
- `style.css` — original visual design and selectors.
- `countdown/circle.css` — original countdown presentation.
- `countdown/simplyCountdown.umd.js` — bundled countdown dependency shipped by the source.
- `img/*` and `audio/cintaVina.mp3` — source demo media references only; not copied as clean-install defaults.

**Current integration.** The adapter is `themes/elix/layout.php` with `themes/elix/script.js`, `themes/elix/original-style.css`, `themes/elix/countdown/circle.css`, and `themes/elix/countdown/simplyCountdown.umd.js`. The PHP layout retains the original section IDs and structure while replacing hardcoded couple/event data with CMS values and replacing the source RSVP transport with the CMS backend. RSVP response parsing and audio autoplay fallback are project-specific safety additions; the original layout and dependency posture remain the source boundary.

**CMS capabilities consumed:** wedding names/dates/location, story, gallery, RSVP, gift data, optional music, guest identity, calendar metadata, SEO, and relevant preset section visibility.

**Important dependencies:** Bootstrap 5.3.5, Pacifico/Sacramento/Work Sans fonts, Bootstrap Icons 1.11.3, SimplyCountdown, countdown circle CSS, and bs5-lightbox 1.8.5. These are documented from the source `index.html`; no AOS dependency is added to Elix by this finalization task.

**Intentional limitations:** The original Elix presentation does not expose every CMS capability. Unsupported CMS controls remain unavailable when Elix is active; Custom is the full CMS-native alternative.

## Rainier

**Original project:** Invitation Website Template.  
**Original author/creator:** Rainier Pearson Saputra.  
**Repository:** [Rainier-PS/Invitation-Template][3]  
**Exact source revision used:** `443a04f07d12164a040d20cd4799ace74a6a3e81`  
**License:** MIT License, Copyright (c) 2026 Rainier-PS.  
**License status:** Clearly established by the LICENSE file.  
**Attribution requirements:** Preserve the MIT notice and permission text. The source README explicitly requires attribution to Rainier Pearson Saputra and links the author's personal site; the adapted footer retains a low-profile source-template link. The exact source notice is retained at [`docs/licenses/RAINIER-LICENSE.txt`](licenses/RAINIER-LICENSE.txt).

**Original characteristics.** Rainier is a data-driven invitation template whose original `#app` document is populated by an event JSON object. Its presentation includes the original hero/event structure, calendar actions, optional schedule and quotes, Tally/RSVP provider bridge, footer branding, smooth transitions, and responsive layout. The source does **not** use AOS.

**Representative original source files used for provenance:**

- `demo/invite-1.html` — original invite-1 DOM and presentation structure.
- `css/invite.css` — original invitation CSS.
- `data/event-1.json` — original event data shape.
- `js/demo/invite-1.js` — original invite-1 initialization and rendering lifecycle.
- `index.html` and `js/index.js` — original application shell and entry lifecycle.
- `README.md`, `docs/USAGE_GUIDE.md`, and `docs/RSVP_FORM_STRUCTURE.md` — source usage/attribution context.

**Current integration.** The current template boundary is retained under `themes/rainier/original/invite.css`, `themes/rainier/original/invite-1.js`, and `themes/rainier/original/invite-1-adapter.js`. `themes/rainier/layout.php` constructs the CMS-to-event JSON bridge, and `themes/rainier/script.js`/`style.css` provide project integration. The adapter adds CMS RSVP handling, guest identity, IANA timezone conversion, and explicit music safety while preserving the original `#app`/event lifecycle.

**CMS capabilities consumed:** wedding/event data, timezone-aware ceremony/reception times, venue/maps, optional schedule and quotes, RSVP backend, optional music, cover/background media, guest identity, calendar links, SEO, and relevant preset visibility.

**Important dependencies:** Cormorant Garamond and Outfit fonts, the original `invite.css` and invite-1 JavaScript lifecycle, optional Tally-compatible event shape, and the source media structure. **AOS is intentionally not used.**

**Intentional limitations:** Rainier remains a compact event-oriented preset. Gift, generic wishes, and other CMS capabilities are not presented as invented Rainier sections when the original template has no corresponding boundary.

## Archak

**Original project:** Wedding Invitation Website.  
**Original author/creator:** Archak Nath.  
**Repository:** [archakNath/wedding-invitation-website][4]  
**Exact source revision used:** `1b549022cf61c1ac4a9092fa65d5bc615b2de3bd` (`1b54902`).  
**License:** MIT License, Copyright (c) 2022 Archak Nath.  
**License status:** Clearly established by the LICENSE file.  
**Attribution requirements:** Preserve the MIT notice and permission text. The original README identifies Archak Nath and the source footer/social identity; the current adapted footer keeps the original `@NathArchak` attribution link. The exact source notice is retained at [`docs/licenses/ARCHAK-LICENSE.txt`](licenses/ARCHAK-LICENSE.txt).

**Original characteristics.** Archak is a compact static invitation composed of a navigation bar, `.home`, `.timeline`, `#story`, `.gallery`, quote, `#stay`, `#registry`, parting message, and footer. Its `main.js` implements parallax and reveal behavior. The source README describes responsive layout, navbar-linked sections, parallax, reveal animation, and the original JavaScript/CSS split.

**Representative original source files used for provenance:**

- `index.html` — original DOM and section order.
- `style.css` — original visual styling and selectors.
- `main.js` — original parallax and reveal lifecycle.
- `README.md` — original author, project characteristics, and usage context.

**Current integration.** The original files are retained at `themes/archak/original/style.css` and `themes/archak/original/main.js`. `themes/archak/layout.php`, `themes/archak/style.css`, `themes/archak/script.js`, and `fidelity-adapter.css` connect CMS data and backend services to the original selectors. The only source-runtime safety correction is null-guarding optional parallax nodes while preserving the original reveal invocation and animation behavior.

**CMS capabilities consumed:** wedding names/dates/location, story/gallery/stay/registry data, guest identity, RSVP/WhatsApp bridge, optional media, SEO, and relevant preset section visibility.

**Important dependencies:** the original `style.css`, `main.js`, and Font Awesome kit reference used by the source template. No AOS dependency is added.

**Intentional limitations:** Archak intentionally has fewer CMS presentation boundaries than Custom. Missing generic CMS sections are not defects when the original template has no equivalent. The preset preserves its compact composition and original navigation/animation identity.

## Pawiwahan

**Original project:** Undangan nikah Pawiwahan, Thema 1.
**Original author/copyright holder:** DE Juna.
**Repository:** [parta99/pawiwahan][5]
**Exact source revision used:** `957b3f38a344a055318173c6adf3e36502e09615` (`957b3f3`).
**License:** MIT License, Copyright (c) 2021 DE Juna.
**License status:** Clearly established by `thema-1/LICENSE`. The exact source notice is retained at [`docs/licenses/PAWIWAHAN-LICENSE.txt`](licenses/PAWIWAHAN-LICENSE.txt).
**Attribution requirements:** Preserve the MIT copyright and permission notice. The source README asks users to retain the DE Juna footer credit; the adapted footer keeps a DE Juna/Pawiwahan credit link.

**Original characteristics.** Pawiwahan `thema-1` is a static HTML/CSS/JavaScript invitation using Bootstrap 5, Bootstrap Icons, Google Fonts, jQuery, and a jQuery countdown plugin. Its original DOM includes the fixed `home`, `about`, `mpl`, `mpw`, `calender`, `protokol`, `galeri`, `lokasi`, `gift`, `exampleModal`, `pesan`, footer, audio, scroll-to-top, and `welcomeModal` boundaries. The source JavaScript opens the welcome modal, reads the `?to=` guest parameter, controls audio, copies gift-account text, and manages scroll behavior.

**Representative original source files used for provenance:**

- `thema-1/index.html` — original DOM, section order, dependency declarations, and inline presentation lifecycle.
- `thema-1/assets/css/pawiwahan.css` and `pawiwahan.min.css` — original source selectors and visual rules.
- `thema-1/assets/js/pawiwahan.js` — original welcome, guest, audio, copy, and scroll lifecycle.
- `thema-1/assets/js/jquery.countdown.min.js` — source countdown dependency.
- `thema-1/assets/images/ornam/*`, `wave.svg`, and `site.webmanifest` — retained source decorative assets.
- `README.md` and `thema-1/LICENSE` — source feature, attribution, and license context.

**Current integration.** The adapter is `themes/pawiwahan/layout.php` with `themes/pawiwahan/style.css` and `themes/pawiwahan/script.js`. The original source HTML is retained at `themes/pawiwahan/original/index.html`; source CSS/JS/decorative assets are kept under `themes/pawiwahan/assets/`. The PHP adapter preserves source order and IDs while replacing hardcoded names, dates, photos, gallery, location, gift data, music, metadata, and RSVP transport with existing CMS data/services. The guest name is supplied by the global resolver and the RSVP form posts to the existing `save.php`/SQLite backend.

**CMS capabilities consumed:** wedding names and opening/closing text, parents, schedule/countdown, gallery, music, gift data, maps, guest identity, RSVP/messages, media, SEO, calendar links, and WhatsApp-related global configuration. The source Facebook Comments embed, Firebase, Express API, Google Spreadsheet, Vue runtime, and demo media are not imported.

**Important dependencies:** Bootstrap 5.0.1, Bootstrap Icons 1.9.1, jQuery 3.6.0, the local source jQuery countdown plugin, Google Fonts, and the retained source CSS/JavaScript lifecycle. Demo couple/gallery/QR images, Firebase-hosted sample photos, and the source demo MP3 are not copied as clean-install user media.

**Intentional limitations:** Pawiwahan retains the source’s fixed section order and only presents CMS data where the source has a compatible boundary. It does not invent a generic Story or Firebase-powered nested-comment panel. The static protocol note remains source-compatible presentation and is not turned into a new generic backend capability. Custom mode remains the full CMS-native alternative.

## Source-to-current relationship

For every built-in preset, the provenance chain is:

```text
original source repository and revision
        ↓
retained source DOM/CSS/JS files and theme-specific adapter
        ↓
CMS data, services, capability filtering, and safe backend bridges
        ↓
current built-in preset
```

The integration layer adds CMS data mapping and safety behavior; it does not claim ownership of the original template design or authorship.

## References

[1]: https://github.com/dewanakl/undangan "DewanaKL undangan source repository"
[2]: https://github.com/elix-stack/wedding-invitation-1 "Elix wedding invitation source repository"
[3]: https://github.com/Rainier-PS/Invitation-Template "Rainier Invitation Template source repository"
[4]: https://github.com/archakNath/wedding-invitation-website "Archak wedding invitation source repository"
[5]: https://github.com/parta99/pawiwahan "Pawiwahan source repository"
