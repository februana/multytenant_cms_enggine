# Dynamic CMS Theme Hardening Audit

## Baseline

The hardening branch starts from `main` at merge commit `714efdb`, which includes merged PR #67 (`1f6d8dc`). PR #64 is merged and contains the Rainier root-cause correction. PR #65 is closed and its template-fidelity attempt is preserved in history as commit `0d60e51`; it is not treated as the current source of truth. The untouched `theme-65-standalone` branch is not modified.

## Source repositories audited

| Preset | Source | Revision | Current baseline finding |
|---|---|---:|---|
| DewanaKL | [dewanakl/undangan](https://github.com/dewanakl/undangan) | `99e7c2d` | Current shell preserves `#root`, `#home`, `#bride`, `#gallery`, `#comment`, welcome/loading/modal, but omits original video story, carousel IDs, `#wedding-date` countdown IDs, QR/gift collapses, comment information/pagination, and original dependency/runtime contract. |
| Elix | [elix-stack/wedding-invitation-1](https://github.com/elix-stack/wedding-invitation-1) | `1ac2394` | Current layout is a different invitation design. It replaces original `#home`, `#info`, `#gifts`, `#my-form`, `#audio-container`, Bootstrap Icons, SimplyCountdown, and bs5-lightbox with invented couple/event/location wrappers, Font Awesome, AOS, and custom overlay DOM. |
| Rainier | [Rainier-PS/Invitation-Template](https://github.com/Rainier-PS/Invitation-Template) | `443a04f` | Current layout is a different CMS invitation. It replaces the original data-driven `#app` structure, `#event-title`, `#event-subtitle`, `#calendar-actions`, `#schedule-section`, `#quotes-section`, footer branding IDs, and original `invite-1.js` lifecycle. The original does not use AOS. |
| Archak | [archakNath/wedding-invitation-website](https://github.com/archakNath/wedding-invitation-website) | `1b54902` | Current layout is a different, expanded invitation with preloader, countdown, gallery, gift, RSVP, and modal infrastructure. The original is a compact `nav` + `.home` + `.timeline` + `#story` + `.gallery` + quote + `#stay` + `#registry` + parting message + footer structure powered by `main.js`. |
| Custom | Current CMS | `714efdb` | CMS-native renderer remains the source of truth for global `config.sections` ordering and full builder behavior. |

## Contract defects found

The current contract invents generic presentation sections. Rainier declares `opening`, `hero`, `couple`, `story`, `event`, `gallery`, `location`, `gift`, `rsvp`, `wishes`, and `music`, even though the original Rainier demo has only a hero/event presentation, optional schedule and quotes sections, RSVP embed, and footer. Countdown is a data/presentation element inside the hero, not a standalone template section. Gift and wishes are not original Rainier sections.

DewanaKL's contract aliases and sections also treat combined content as independent generic sections. The original has `#home`, `#bride`, an embedded love-story block, `#wedding-date`, `#gallery`, an embedded Love Gift block, `#comment`, welcome/loading/modal, and bottom navigation. The contract must represent these actual presentation boundaries while keeping gallery, music, RSVP, and media as separate data capabilities.

Elix's contract must use the original `#hero`, `#home`, `#info`, `#story`, `#gallery`, `#rsvp`, `#gifts`, `#audio-container`, and navbar/offcanvas structure. Archak must use the original `.home`, `.timeline`, `#story`, `.gallery`, `.quote`, `#stay`, `#registry`, parting message, and footer boundaries.

## Dependency findings

| Preset | Original dependency | Current baseline | Required action |
|---|---|---|---|
| DewanaKL | Bootstrap `5.3.8`, Font Awesome `7.1.0`, `css/guest.css`, `js/guest.js` and its modular runtime, theme media | Bootstrap `5.3.2`, Font Awesome `6.5.1`, AOS, custom theme JS/CSS | Restore original dependency versions and guest runtime behavior; do not invent AOS as the primary runtime. |
| Elix | Bootstrap `5.3.5`, Pacifico/Sacramento/Work Sans, Bootstrap Icons, `countdown/circle.css`, SimplyCountdown UMD, bs5-lightbox | Bootstrap `5.3.2`, Cormorant/Inter, Font Awesome, AOS, custom theme JS | Restore original fonts, icons, countdown, lightbox, and DOM hooks. |
| Rainier | Cormorant Garamond/Outfit, `css/invite.css`, `js/demo/invite-1.js`, optional Tally widget | Different fonts/CSS and custom script; no AOS | Restore original CSS/JS and preserve vanilla lifecycle; never add AOS. |
| Archak | `style.css`, Font Awesome kit, `main.js` | Different custom CSS/JS and expanded DOM | Restore original DOM and asset contract, then inject CMS data and backend-safe RSVP only where the CMS extends the original. |

## Difference classification

The following are classified as **A/B** only when they inject CMS data or connect backend services while preserving the original selector and structure: dynamic names, dates, venue, media, guest query, CMS RSVP endpoint, CMS gallery data, CMS music source, SEO, and safe media URLs. Replacing original IDs, wrappers, section composition, order, fonts, animation hooks, dependency versions, or JavaScript selectors without a runtime requirement is classified as **D** and must be corrected.
