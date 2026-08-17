# AGENTS — Developer & Agent Guidelines

## Dynamic CMS Architecture
- **Dynamic Configuration**: `config.json` drives all application settings, sections, and theme options (`theme_options`).
- **Extensible Presets**: Dynamic form inputs in CMS adapt based on the selected active theme preset (DewanaKL, Elix, Rainier, Archak, etc.).
- **CMS-First Frontend**: All frontend templates strictly consume configuration from CMS without hardcoded content.
- **Section Visibility**: Controlled by `sections.*.enabled`. Disabled sections must not render HTML elements on the frontend.
- **Media Contract**: Logical keys (`media.cover`, `media.bride_photo`, `media.groom_photo`, `media.couple_photo`, `media.background_hero`, `media.music`) decouple theme templates from upload filenames. Groom and Bride assets must remain strictly isolated.

## Verification & Testing Commands
- Check PHP syntax: `php -l index.php admin/index.php config.php app/save.php app/theme-helper.php app/theme-renderer.php`
- Validate CMS contracts: `php tools/validate.php`

## Key Entrypoints & Implementation
- Public Entrypoint: `index.php`
- CMS Admin: `admin.php` / `admin/index.php`
- Core Config & Utilities: `config.php`
- Theme System: `app/theme-helper.php`, `app/theme-renderer.php`, `themes/`
- Deployment: `Dockerfile`, `docker/`, `deploy/`
