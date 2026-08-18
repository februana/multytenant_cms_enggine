# Wedding Invitation CMS

This repository is a PHP/SQLite wedding-invitation CMS with a theme-adapter architecture. It incorporates and adapts six independently authored or user-provided invitation templates; the built-in presets are not presented as original designs of this project.

## Current architecture

```text
CMS ENGINE
    ↓
THEME ADAPTER
    ↓
BUILT-IN PRESET
```

Custom mode follows a separate CMS-native path:

```text
CMS ENGINE
    ↓
CUSTOM CMS-NATIVE BUILDER
```

The CMS provides data, persistence, backend services, security helpers, and capability metadata. Theme adapters connect those values to individual source templates. Built-in presets preserve their source DOM, CSS, JavaScript lifecycle, dependencies, section order, and UX. Presets intentionally have different capabilities: a CMS capability does not automatically become a section, and it does not have to appear in every preset. Custom is the full CMS-native builder for users who need maximum flexibility.

The **Guest Link Generator** and **personalized Guest Name** are global CMS capabilities. A generated invitation uses the current URL contract, for example `?to=Andi`; each theme presents the resolved guest name in its own original-compatible location rather than receiving identical markup.

See [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) for ownership boundaries and [`docs/ATTRIBUTIONS.md`](docs/ATTRIBUTIONS.md) for source provenance, licenses, authors, and attribution requirements.

## Presets

The six built-in presets are:

- **DewanaKL** — original welcome/loading, gallery, video, gift, comment, AOS, and confetti-oriented invitation flow.
- **Elix** — original hero/story/gallery/RSVP/gifts/audio flow with SimplyCountdown and lightbox support.
- **Rainier** — original event-oriented `#app` flow with timezone-aware event data, calendar, optional schedule/quotes, RSVP, and footer branding. Rainier does not use AOS.
- **Archak** — compact original navigation, home, timeline, story, gallery, stay, registry, parting message, footer, parallax, and reveal flow.
- **Parang** — Javanese-inspired source-adapter flow with preserved ornaments, side navigation, couple, event, story, gallery, gift, maps, RSVP, and music boundaries.
- **Pawiwahan** — preserved static source flow with Bootstrap carousel, welcome modal, guest resolver, couple, event/countdown, gallery, gift, maps, messages/RSVP, and audio boundaries.

Missing generic CMS functionality in a simple preset is intentional when the original template has no equivalent presentation boundary. Use Custom mode for the complete CMS-native section builder.

## Repository layout

```text
/
├── index.php                 # public invitation controller
├── admin.php                 # admin redirect wrapper
├── save.php                  # RSVP backend wrapper
├── messages.php              # message API wrapper
├── gallery.php               # gallery API wrapper
├── config.php                # defaults, persistence, helpers, security
├── config.json               # current CMS configuration
├── database.sqlite           # native runtime database placeholder
├── uploads/                  # user-provided media directories
├── admin/                    # CMS admin UI and services
├── app/                      # shared renderer/helpers/contracts
├── themes/                   # built-in adapters and retained source files
├── deploy/                   # native installer/update/backup/health scripts
├── docker/                   # Docker entrypoint and Apache config
├── Dockerfile
├── docker-compose.yml
└── docs/
```

## Deployment

### Docker

```bash
git clone https://github.com/februana/webserver_undangan.git
cd webserver_undangan
cp .env.example .env
chmod 600 .env
# Set ADMIN_PASS in .env before starting.
docker compose build
docker compose up -d
docker compose exec wedding-cms /var/www/wedding/deploy/health-check.sh
```

Docker uses PHP 8.3 Apache. The `wedding_data` volume persists config, guest links, Custom CSS, event ICS, and SQLite state; `wedding_uploads` persists uploaded media and preset-scoped `uploads/theme-assets/<preset>/` directories. The entrypoint sources `deploy/runtime-directories.sh` and recreates missing asset directories on every start without replacing user media. Compose refuses to use a shared hardcoded administrator password.

### Native/server deployment

Prerequisites are `rsync`, `openssl`, and Composer on a root-capable Ubuntu host. The installer installs the selected Nginx/Apache and required PHP packages:

```bash
cd /path/to/webserver_undangan
sudo bash deploy/install.sh
sudo /var/www/wedding/deploy/health-check.sh
```

The installer creates `/var/www/wedding`, initializes runtime directories/files through the shared `deploy/runtime-directories.sh` contract, creates preset-scoped Theme Assets directories, configures the selected web server, optionally configures TLS/WebDAV, and leaves the Git checkout intact. `deploy/update.sh` preserves the complete `uploads/` tree and recreates missing runtime asset directories. Use the existing scripts for operations:

```bash
sudo /var/www/wedding/deploy/update.sh
sudo /var/www/wedding/deploy/backup.sh
sudo /var/www/wedding/deploy/restore.sh /path/to/backup.zip
```

Full prerequisites, prompts, storage behavior, health statuses, troubleshooting, and media provisioning are documented in [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

## Optional media lifecycle

A clean checkout contains no sample cover, music, or Open Graph image. The shipped defaults leave those fields empty because these are optional administrator-provided deployment data, not required application assets. The installer creates the upload directories but does not invent copyrighted or arbitrary media. The health check reports missing optional media as `WARNING` while failing only required application/storage/security checks.

Upload or provision media through the Admin UI, then configure the corresponding `config.json` field. Music requires both a valid configured file and the relevant preset's explicit music option/section. DewanaKL video requires a valid supported file in `media.love_story_video`.

## Runtime data and security

Native mode stores mutable files in the document root by default. Docker sets `UNDANGAN_DATA_DIR=/var/data` and `UNDANGAN_DB_PATH=/var/data/database.sqlite`. Both deployment paths create `uploads/cover`, `music`, `gallery`, `background`, `love-story`, `theme-assets`, and preset-scoped Theme Assets directories. The application blocks direct public access to sensitive config, guest-link, environment, and SQLite files. Do not commit `.env` or production runtime data.

## License and attribution

The CMS integration code is project-specific. The built-in presentation templates are adaptations of the following source repositories:

- [DewanaKL — dewanakl/undangan](https://github.com/dewanakl/undangan)
- [Elix — elix-stack/wedding-invitation-1](https://github.com/elix-stack/wedding-invitation-1)
- [Rainier — Rainier-PS/Invitation-Template](https://github.com/Rainier-PS/Invitation-Template)
- [Archak — archakNath/wedding-invitation-website](https://github.com/archakNath/wedding-invitation-website)
- [Pawiwahan — parta99/pawiwahan](https://github.com/parta99/pawiwahan)
- Parang — user-provided HTML design reference recorded in `docs/ATTRIBUTIONS.md`

License status, exact revisions, original source files, current integration paths, and attribution requirements are maintained in [`docs/ATTRIBUTIONS.md`](docs/ATTRIBUTIONS.md).
