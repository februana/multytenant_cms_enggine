# Copilot instructions for februana/webserver_undangan

Purpose: give future Copilot sessions the repo-specific commands, high-level architecture, and conventions needed to make safe, accurate edits.

1) Build / test / lint (what exists)

- Install runtime deps (required for QR/code):
  - composer install --no-dev --optimize-autoloader

- CI checks (GitHub Actions):
  - PHP syntax check used in CI:
    find . -type f -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l
  - Composer validation (if composer.json present):
    composer validate --strict

- Lint/tests: No automated unit tests or linters found. Use the above php -l and composer validate for quick checks. If adding tests, document how to run a single test in this file.


2) High-level architecture (single-root, short overview)

- Single-root public entrypoints live at repository root (index.php, save.php, messages.php, gallery.php, admin.php). These files are thin wrappers that include the application logic from app/.
- Private application logic and helpers are under app/ (blocked by webserver rules). Treat files in app/ as non-public implementation.
- Configuration and runtime data:
  - config.json (application settings, theme, admin password hash)
  - database.sqlite (SQLite DB)
  - guest-links.json (guest link store)
  - uploads/ (user media folders)
  These are protected by webserver rules and file-permission conventions.
- Admin UI lives under admin/ (UI, JS, backup endpoints). Admin operations update config.json and database.sqlite.
- Deployment scripts in deploy/: install.sh (ONE-TIME installer), update.sh (use for updates), backup.sh, restore.sh, health-check.sh. Installer auto-creates .env (from .env.example) and may generate an initial admin password.
- Web server support: Nginx (recommended) and Apache (full WebDAV on Apache). Templates live under deploy/templates/..

3) Key repository conventions and safety rules

- Authentication priority (important):
  1. admin.password_hash in config.json (takes precedence after admin changes password)
  2. .env (ADMIN_USER / ADMIN_PASS) — used only for initial install / recovery
  3. No hardcoded fallback passwords

- Public wrappers vs app/:
  - Do not move business logic into root public files. Root files should remain thin wrappers that include app/*.php.
  - New endpoints should follow the wrapper pattern: create a small root file that include './app/your_feature.php'.

- Protect secrets and data:
  - config.json, database.sqlite, guest-links.json, backups/ must remain blocked by server config and not be served.
  - File permissions: config and DB should be owner-only readable (600) and owned by webserver user (www-data). uploads/ must be writable by webserver.

- Upload handling:
  - Uploads are static assets only; PHP execution in uploads/ is disabled. Keep upload validation in app/ and ensure filenames are sanitized.

- Theme and UI config:
  - Admin theme presets write into config.json. Theme previewing uses an iframe and is debounce-posted before save; prefer editing theme presets through admin UI unless changing stored schema.

- Backups & updates:
  - Use deploy/update.sh for updates (it creates backups first). Do NOT run deploy/install.sh on existing installs (it is marked one-time only).
  - Backups are stored in backups/; restore via deploy/restore.sh. Tests expect backup scripts to preserve config.json, guest-links.json, database.sqlite, uploads/ and event.ics.

- Composer dependency note:
  - composer.json exists and currently requires a QR library (chillerlan/php-qrcode). Composer must be run on servers where QR features are used.

4) CI & automation

- See .github/workflows/php.yaml: CI runs PHP 8.2, installs sqlite extensions, runs php -l across PHP files and composer validate.
- When adding tests or static analysis, update CI to run those commands and document single-test invocation here.

5) Where to look first when asked to change behavior

- For UI/JS issues: admin/ (admin/app.js, style.css) and root templates (index.php).
- For backend logic: app/ (save.php, messages.php, gallery.php) and config.php loader.
- For deployment or webserver behavior: deploy/* and deploy/templates/ (nginx/apache templates). Update scripts and health-checks are authoritative.

6) Files that are intentionally public wrappers

- index.php, admin.php, save.php, messages.php, gallery.php — these should remain minimal and only include app/* implementations.

7) Existing docs to consult

- README.md, ARCHITECTURE.md, DEPLOYMENT.md, BACKUP_RESTORE.md, SECURITY.md, .github/workflows/php.yaml

---
If you (the developer) want additional coverage (example: documenting a specific app/* module, adding recommended linters or test harness), say which area and Copilot will extend this file accordingly.

========================================================
PROJECT IDENTITY
========================================================

This project is a professional Wedding Invitation CMS.

The goal is NOT to build a blog, landing page, SaaS dashboard, company profile, or generic website.

The goal is to create premium digital wedding invitations that preserve the emotional feeling of a printed invitation while maintaining the flexibility of a modern CMS.

Every implementation should support this vision.

========================================================
UI PHILOSOPHY
========================================================

The website should feel like opening a premium printed wedding invitation.

Not a website.

Every visual decision should reinforce:

- elegance
- warmth
- romance
- simplicity
- luxury
- timeless design

Avoid unnecessary visual effects.

Prefer elegant simplicity over complexity.

========================================================
SECTION ARCHITECTURE
========================================================

Do NOT remove or merge sections.

The existing section architecture is correct.

Each section must remain independent because it is required by:

- CMS
- Theme Builder
- Live Preview
- Future background customization
- Future decoration customization

========================================================
BACKGROUND
========================================================

Each section should continue supporting independent backgrounds.

Possible backgrounds include:

- image
- texture
- color
- overlay
- ornaments

Avoid automatically using background-size: cover when it crops decorative artwork.

Choose the most appropriate sizing strategy depending on the image.

========================================================
VISUAL HIERARCHY
========================================================

Highest priority:

- Bride & Groom
- Hero
- Wedding announcement

Medium priority:

- Story
- Ceremony
- Reception
- Gallery

Supporting priority:

- Countdown
- Gift
- Maps
- RSVP

The visitor should remember the couple.

Not the countdown.

========================================================
COUNTDOWN
========================================================

Countdown is a supporting component.

It should never visually dominate the page.

Avoid oversized countdown cards.

Avoid oversized numbers.

Keep it elegant and compact.

========================================================
HEADER
========================================================

Required behavior:

Top:
Large header.

Scrolling:
Compact header.

Scroll down:
Hide smoothly.

Scroll up:
Show smoothly.

Back to top:
Restore original size.

Never cover Hero content.

Verify behavior before considering the implementation complete.

========================================================
CONTENT LAYOUT
========================================================

Narrative sections should feel open.

Examples:

- Hero
- Invitation
- Story
- Gallery
- Closing

Avoid wrapping these sections inside oversized article-style cards.

Information-oriented sections may still use elegant cards.

Examples:

- Ceremony
- Reception
- Dresscode
- Gift
- RSVP

========================================================
VALIDATION
========================================================

Never claim visual verification unless the website has actually been rendered.

If browser rendering is unavailable,

explicitly state that visual verification could not be completed.

Do not infer visual quality from source code alone.
