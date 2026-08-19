# CMS Theme Restructure Audit

## Source-of-truth repositories

| Preset | Repository | Audited revision | Notes |
|---|---|---|---|
| DewanaKL | [dewanakl/undangan](https://github.com/dewanakl/undangan) | `99e7c2d` | Bootstrap 5, Font Awesome, custom guest runtime, AOS attributes, split desktop/mobile composition, wave separators, couple, quote, love story, gallery, RSVP, music |
| retired preset | [retired-preset-stack/wedding-invitation-1](#retired-preset-source) | `1ac2394` | Cover/introduction, couple, event timeline, circular countdown, gallery, location, gift, RSVP, music, Bootstrap/AOS-oriented presentation |
| Rainier | [Rainier-PS/Invitation-Template](https://github.com/Rainier-PS/Invitation-Template) | `443a04f` | Demo invitation renderer, event JSON contract, IntersectionObserver animations, RSVP embed, gallery, music control, responsive editorial layout |
| Custom | Current CMS | Current branch baseline | CMS-native section builder and full capability set remain the source of truth |

## Findings in the CMS repository

The existing frontend layouts were already separate files, but their feature gating still called `is_section_enabled()` with globally normalized IDs. The normalization function mapped theme-specific names such as `couple`, `event`, `story`, `gallery`, `gift`, and `opening` into shared CMS IDs. The admin `save_sections` action rebuilt one global ordered list, and the admin UI rendered that same list for every mode. This meant that the frontend file could be visually distinct while its composition contract was still controlled by the global CMS schema.

The current branch also retained `CUSTOM` as an inline CMS renderer fallback. The refactor therefore preserves the global `sections` array for Custom mode and introduces a separate `theme_sections` store for built-in preset controls. Built-in layouts now read their own contract IDs and do not depend on the normalized global section list for visibility.

## Classification of differences

| Difference | Classification | Decision |
|---|---|---|
| Built-in layouts use separate PHP documents | A / D | Preserve layout ownership and add contract-driven gating |
| Global `sections` list is used by all presets | B | Keep only for Custom; introduce `theme_sections` per preset |
| `normalize_section_id()` aliases theme names into CMS IDs | B for built-in rendering | Leave legacy normalizer available for Custom and old data; built-in adapters use raw contract IDs |
| Theme-specific visibility controls are CSS/UI-only | B | Route section editor data and save path through the active contract |
| Existing preset CSS and theme scripts | A | Preserve and continue loading per-theme assets |
| Rainier AOS dependency | D not required | Source audit found IntersectionObserver-based animation rather than AOS in the audited repository; do not add AOS merely by assumption |
| Existing Archak preset | C / D | Keep a compatibility contract so existing installs are not broken while the three requested presets become theme-driven |

## Implemented contract

`app/theme-contract.php` defines each built-in preset's consumed capabilities, original section vocabulary, admin capabilities, source metadata, and asset hints. It exposes helpers for default sections, stored per-theme sections, visibility, titles, subtitles, and admin editing. The contract is intentionally small and uses the existing PHP architecture rather than introducing a framework.

`config.php` initializes `theme_sections` at runtime for backward compatibility. Existing `sections` data is preserved and remains the source for Custom mode. No migration deletes or rewrites existing content. When a built-in preset is active, only that preset's contract is used to render its controls and feature gating.

## Verification baseline

The baseline had no PHP CLI installed in the sandbox. PHP 8.3 CLI with SQLite, mbstring, and XML extensions was installed only for local verification. Before and after the contract changes, the project validator reported `PASS: CMS-first contract validation succeeded`; the repository's default media warnings are unrelated missing runtime assets in the clean checkout.

## Dependency verification

The final built-in layouts keep their own asset loading. DewanaKL and retired preset load Bootstrap and AOS alongside their theme CSS/JavaScript, matching their current layout behavior and `data-aos` markup. Rainier loads its own CSS/JavaScript and does not load AOS because the audited source repository uses CSS and `IntersectionObserver`-style animation rather than AOS. Archak remains on its existing theme CSS/JavaScript path as a compatibility preset.
