# Changelog

## [Unreleased]

### Added
- Docker containerization and Render cloud deployment support via `Dockerfile`, `docker/000-default.conf`, `docker/render-entrypoint.sh`, and `docker/render.ini`.
- Expanded media contract supporting `media.bride_photo`, `media.groom_photo`, and `media.couple_photo` alongside `media.cover` and `media.background_hero`.
- Enhanced CMS Admin Media Manager (`admin/index.php`) allowing uploads, File Manager assignment, and previewing of all logical media targets.

### Fixed
- Fixed preloader and loading overlay stalls in `dewankl` (`themes/dewankl/script.js`), `rainier` (`themes/rainier/script.js`), and `archak` (`themes/archak/script.js`) by checking `document.readyState === 'complete'`.
- Fixed missing `libonig-dev` build dependency for PHP 8.3 `mbstring` extension in `Dockerfile`.
- Fixed redundant `docker-php-ext-install` call on built-in `pdo_sqlite` driver.
- Ensured backward compatibility for theme layouts: missing bride/groom/couple photos safely fall back to `media.cover`.

### Cleaned
- Removed obsolete AI instruction prompt file `master_prompt_cms_first_architecture.txt`.
- Updated `AGENTS.md` developer guidelines.
