# AGENTS — Developer & Agent Guidelines

## Architecture Overview (CMS-First)
- **Single Source of Truth**: `config.json` drives all application settings, sections, and theme options.
- **Section Visibility**: Controlled by `sections.*.enabled`. Disabled sections must not render HTML elements on the frontend.
- **Media Contract**: Logical keys (`media.cover`, `media.bride_photo`, `media.groom_photo`, `media.couple_photo`, `media.background_hero`, `media.music`) decouple theme templates from upload filenames.
- **Theme Presets**: Rendered via `app/theme-renderer.php` loading `themes/<preset>/layout.php`.
- **Live Preview**: Must use the exact same renderer as production (`app/theme-renderer.php`).

## Verification & Testing Commands
- Check PHP syntax: `php -l index.php admin.php config.php app/theme-helper.php app/theme-renderer.php`
- Validate CMS-first contracts: `php tools/validate.php`

## Key Entrypoints & Implementation
- Public Entrypoint: `index.php`
- CMS Admin: `admin.php` / `admin/index.php`
- Core Config & Utilities: `config.php`
- Theme System: `app/theme-helper.php`, `app/theme-renderer.php`, `themes/`
- Deployment: `Dockerfile`, `docker/`, `deploy/`
