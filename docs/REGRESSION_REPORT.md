# Regression Report

## Automated checks

| Check | Result | Evidence |
|---|---|---|
| PHP syntax lint | Passed for `config.php`, `app/*.php`, `admin/*.php`, `themes/*/*.php`, `tools/*.php`, `index.php`, and `save.php` | Final validation command |
| Theme contract smoke | Passed | `tools/theme_contract_smoke.php` |
| Renderer smoke | Passed for Custom, DewanaKL, Elix, Rainier, and Archak | `tools/theme_render_smoke.php` |
| Disabled behavior | Passed for DewanaKL gallery, Elix gallery, Rainier schedule, and Archak registry | `tools/theme_disabled_smoke.php` |
| Theme switching | Passed in sequence Custom → DewanaKL → Elix → Rainier → Archak → Custom | `tools/theme_regression_smoke.php` |
| CMS-first validator | Passed | `tools/validate.php` |
| Whitespace / patch hygiene | Passed | `git diff --check` |
| Rainier AOS guard | Passed | Regression smoke rejects `aos` in Rainier output |

## Browser and responsive checks

The browser verification covered server-rendered markers and console state for DewanaKL, Elix, Rainier, and Archak. The Elix and Rainier browser consoles produced no errors after the original/adapted lifecycle initialized. Archak also produced no console errors after original `main.js` loaded. DewanaKL rendered its original welcome/loading/carousel/gift/comment controls and original selector boundaries.

Automated Chromium mobile screenshots were produced at `390x844` for all four built-in presets. Long sentinel names initially exposed overflow in Elix, DewanaKL, and Archak. Small template-specific fidelity adapter styles now wrap only unusually long CMS data while preserving the original DOM and normal typography. The corrected Elix, DewanaKL, and Archak screenshots wrap the names within the viewport; Rainier remained stable throughout.

## Known deployment-data warnings

The validator reports that the default sample references `uploads/cover/cover.jpg`, `music/lagu.mp3`, and the corresponding Open Graph image, which are absent in the clean checkout. These are configured media-data warnings, not code or contract failures. Production deployment must provision the referenced media or update the CMS configuration.

## Acceptance status

The architecture, contract, source fidelity, disabled behavior, dependency lifecycle, admin boundary, and regression requirements are implemented and covered by the checks above. Full production visual sign-off still depends on the deployment media being present and on a human review of the final design at representative real-content lengths.
