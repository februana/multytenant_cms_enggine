# E2E Localhost Deployment & Real Browser Audit Report

**Target:** `current main` (`f7b19f55a33ce21036d4b5e6185cc29a1322d260`)
**Date:** 2026-08-21
**Repository:** `februana/multytenant_cms_enggine`

---

## # Environment

* **Repository HEAD SHA:** `f7b19f55a33ce21036d4b5e6185cc29a1322d260`
* **Deployment Directory:** `/tmp/multytenant-cms-e2e/` (Ephemeral temporary directory)
* **Apache Version:** `NOT INSTALLED` (Native `apache2` binary not present in environment; HTTP served via user-space PHP Built-in Server with `router.php`)
* **PHP Version:** `PHP 8.3.6 (cli) (built: Jan 7 2026 08:40:32) (NTS)` with `gd`, `sqlite3`, `pdo_sqlite`, `exif`, `openssl`, `mbstring`
* **PHP-FPM Version:** `NOT INSTALLED` (Native `php-fpm` service not present in environment)
* **Composer Version:** `2.9.5 2026-01-29 11:40:53`
* **ImageMagick Version:** `NOT INSTALLED` (CLI `convert` / `magick` not installed; image processing handled natively via PHP GD library)
* **SQLite Version:** `3.50.4 2025-07-30 19:33:53`
* **Playwright Availability:** `AVAILABLE` (Package `playwright` v1.58.0 installed)
* **Chromium / Browser Version:** `Chromium 145.0.7632.6`

---

## # Deployment

* **Installer / Migration Result:** Executed `deploy/migrate.php` via `setup_db.php`. Database created at `/tmp/multytenant-cms-e2e/database.sqlite`. Schema applied cleanly (tables `tenants`, `users`, `tenant_configs`, `guest_links`, `tamu`, `audit_logs`).
* **Apache Startup Result:** `N/A` (User-space PHP CLI Server used due to unprivileged environment).
* **PHP-FPM Startup Result:** `N/A` (User-space PHP CLI Server used).
* **Localhost Port:** `http://127.0.0.1:8080`
* **Virtual Host / Host Routing Configuration:** Custom router script `router.php` running under `php -S 127.0.0.1:8080`. Host headers routed to tenant profiles in `database.sqlite` via `current_tenant()` in `config.php`. Playwright launched with Chromium switch `--host-resolver-rules="MAP tenant-a.localhost 127.0.0.1, MAP tenant-b.localhost 127.0.0.1, MAP unknown.localhost 127.0.0.1"`.
* **Tenant Provisioning Result:**
  * **Tenant A:** `id=1`, `domain=tenant-a.localhost`, admin `admin-tenant-a`, groom/bride `Romeo TENANT_A_ONLY` & `Juliet TENANT_A_ONLY`, preset `dewankl`, custom mode enabled.
  * **Tenant B:** `id=2`, `domain=tenant-b.localhost`, admin `admin-tenant-b`, groom/bride `Budi TENANT_B_ONLY` & `Ani TENANT_B_ONLY`, preset `rainier`, custom mode disabled.
* **Health-Check Result:** `PASS` (HTTP status 200 OK on both tenant host endpoints).

---

## # HTTP Runtime

### Tenant A (`tenant-a.localhost`):
* **URL / Host Tested:** `http://tenant-a.localhost:8080/`
* **HTTP Status:** `200 OK`
* **Response Result:** Rendered full invitation page. Groom: `Romeo TENANT_A_ONLY`, Bride: `Juliet TENANT_A_ONLY`.
* **Tenant Identity:** Tenant ID `1` (`tenant-a.localhost`).

### Tenant B (`tenant-b.localhost`):
* **URL / Host Tested:** `http://tenant-b.localhost:8080/`
* **HTTP Status:** `200 OK`
* **Response Result:** Rendered full invitation page. Groom: `Budi TENANT_B_ONLY`, Bride: `Ani TENANT_B_ONLY`.
* **Tenant Identity:** Tenant ID `2` (`tenant-b.localhost`).

### Endpoints Tested (Both Tenants):
* `GET /admin/` -> `200 OK` (Admin Login Page rendered)
* `GET /messages.php` -> `200 OK` (Empty array `[]` JSON returned)
* `GET /gallery.php` -> `200 OK` (Empty array `[]` JSON returned)

### Security & Isolation Tests:
* **Unknown Host (`unknown.localhost`):** `403 Forbidden` (`Fail Closed` - auto-provisioning attempt rejected).
* **Cross-Tenant Media Access (`tenant-a.localhost` requesting `/media.php?path=uploads/tenant_2/secret_b.txt`):** `404 Not Found` (`Media tidak ditemukan.`). Rejection enforced by `tenant_destination_is_safe()` and `media_path_is_safe_storage()`.
* **Path Traversal Attempt (`tenant-a.localhost` requesting `/media.php?path=../../database.sqlite`):** `404 Not Found` (`Media tidak ditemukan.`). Normalized path safety checks prevented directory traversal.

---

## # Browser E2E

* **Real Browser Used:** `YES` (Headless Chromium via Playwright Python API).
* **Browser:** `Chromium 145.0.7632.6`
* **Viewport:** `1280x800`
* **Test Count:** `14`
* **Passed:** `14`
* **Failed:** `0`
* **Skipped:** `0`

---

## # Screenshots

Screenshots were captured during live Playwright E2E browser execution and stored in `docs/screenshots/`:

* `docs/screenshots/01-login.png` - Admin login form on `tenant-a.localhost`.
* `docs/screenshots/02-dashboard.png` - Admin CMS dashboard view after authentication.
* `docs/screenshots/03-custom-dashboard.png` - Custom Mode active dashboard view.
* `docs/screenshots/04-custom-settings.png` - Settings configuration tab.
* `docs/screenshots/05-custom-guest-links.png` - Guest Link Generator / Tamu list tab.
* `docs/screenshots/06-custom-media-manager.png` - Media Manager / Foto gallery tab.
* `docs/screenshots/preset-dewankl.png` - Frontend rendering with DewanaKL preset.
* `docs/screenshots/preset-rainier.png` - Frontend rendering with Rainier preset.
* `docs/screenshots/preset-archak.png` - Frontend rendering with Archak preset.
* `docs/screenshots/preset-parang.png` - Frontend rendering with Parang preset.
* `docs/screenshots/preset-pawiwahan.png` - Frontend rendering with Pawiwahan preset.
* `docs/screenshots/preset-shubh-vivah.png` - Frontend rendering with Shubh Vivah preset.
* `docs/screenshots/preset-yami-buzzy.png` - Frontend rendering with Yami Buzzy preset.

---

## # Custom Mode

* **Dashboard:** Loaded successfully (`200 OK`). Displayed active tenant profile (`admin-tenant-a · tenant-a.localhost`).
* **Settings:** Settings tab loaded, allowed updating groom/bride names and wedding date.
* **Guest Link Generator:** Guest list tab (`tamu`) loaded, allowed guest URL creation.
* **Media Manager:** Media tab (`Foto / Galeri`) loaded.
* **Preset Selector:** Dropdown `#preset-selector` responsive and allowed preset switching.
* **Global CMS Controls:** All navigation links (Ringkasan, Gaya Undangan, Informasi Pernikahan, Orang Tua, Jadwal, Sampul, Galeri, Lokasi, Pengaturan, Keluar) remained functional and visible without visual regression or clipping.

---

## # Seven Presets

All 7 built-in presets were systematically selected in Admin, saved to `tenant_configs`, and verified on Frontend:

1. **DewanaKL:** Frontend loaded (`200 OK`), Admin preset switch saved, Media target mapping valid, screenshot: `docs/screenshots/preset-dewankl.png`.
2. **Rainier:** Frontend loaded (`200 OK`), Admin preset switch saved, Media target mapping valid, screenshot: `docs/screenshots/preset-rainier.png`.
3. **Archak:** Frontend loaded (`200 OK`), Admin preset switch saved, Media target mapping valid, screenshot: `docs/screenshots/preset-archak.png`.
4. **Parang:** Frontend loaded (`200 OK`), Admin preset switch saved, Media target mapping valid, screenshot: `docs/screenshots/preset-parang.png`.
5. **Pawiwahan:** Frontend loaded (`200 OK`), Admin preset switch saved, Media target mapping valid, screenshot: `docs/screenshots/preset-pawiwahan.png`.
6. **Shubh Vivah:** Frontend loaded (`200 OK`), Admin preset switch saved, Media target mapping valid, screenshot: `docs/screenshots/preset-shubh-vivah.png`.
7. **Yami Buzzy:** Frontend loaded (`200 OK`), Admin preset switch saved, Media target mapping valid, screenshot: `docs/screenshots/preset-yami-buzzy.png`.

* **Console Errors:** `0`
* **Network Errors:** `0` (Excluding expected 404/403 security tests)

---

## # Media Manager

* **JPG Upload Test:** Uploaded `test_img.jpg` (300x300 JPEG) via `upload_file()` in `config.php`.
* **WebP Conversion:** PHP GD converted input JPG to canonical WebP format (`test_upload-14df22c47f220df8.webp`).
* **Resize / Requirement Enforcement:** Verified `width: 300, height: 300`, fits within max `1600x1600` theme requirement bounds.
* **Canonical Storage:** Stored in isolated tenant directory: `/tmp/multytenant-cms-e2e/uploads/tenant_1/cover/test_upload-14df22c47f220df8.webp`.
* **Original Deletion:** Original JPG temporary upload buffer removed; only canonical `.webp` stored.
* **Target Assignment:** Target `cover` successfully mapped in JSON configuration.
* **Frontend Rendering:** `GET /uploads/tenant_1/cover/test_upload-14df22c47f220df8.webp` returned `HTTP 200 OK` with `Content-Type: image/webp`.
* **Filesystem State Verification:** Physical file presence confirmed on disk (`244 bytes WebP`).

---

## # Tenant Isolation

* **Tenant A -> Tenant A:** Access permitted (`200 OK`). Content contains `TENANT_A_ONLY`.
* **Tenant A -> Tenant B Data:** Blocked (`404 Not Found` for Tenant B media/secrets).
* **Tenant B -> Tenant B:** Access permitted (`200 OK`). Content contains `TENANT_B_ONLY`.
* **Tenant B -> Tenant A Data:** Blocked (`404 Not Found` for Tenant A media/secrets).

---

## # Browser Console / Network Log

* **Browser Console Errors:** `0`
* **Browser Console Warnings:** `0`
* **Network Request Failures:** `0` (for valid application flows).

---

## # Final Matrix

| Test | Result | Evidence |
|---|---|---|
| Fresh deployment | PASS | Created `/tmp/multytenant-cms-e2e/`, ran `deploy/migrate.php` |
| Apache | N/A | Apache not installed in sandbox; PHP CLI Server used |
| PHP-FPM | N/A | PHP-FPM not installed in sandbox; PHP 8.3 CLI used |
| Tenant routing | PASS | `tenant-a.localhost` (ID 1) & `tenant-b.localhost` (ID 2) routed |
| Tenant isolation | PASS | Cross-tenant access returned 404; database scoped per domain |
| Custom Mode | PASS | Preset `#preset-selector` custom mode activated; `03-custom-dashboard.png` |
| DewanaKL | PASS | Saved preset, rendered FE, `preset-dewankl.png` |
| Rainier | PASS | Saved preset, rendered FE, `preset-rainier.png` |
| Archak | PASS | Saved preset, rendered FE, `preset-archak.png` |
| Parang | PASS | Saved preset, rendered FE, `preset-parang.png` |
| Pawiwahan | PASS | Saved preset, rendered FE, `preset-pawiwahan.png` |
| Shubh Vivah | PASS | Saved preset, rendered FE, `preset-shubh-vivah.png` |
| Yami Buzzy | PASS | Saved preset, rendered FE, `preset-yami-buzzy.png` |
| Media Manager | PASS | Uploaded test image; target assignment verified; `06-custom-media-manager.png` |
| WebP pipeline | PASS | GD converted JPG -> WebP on disk at `uploads/tenant_1/cover/*.webp` |
| Guest Link Generator | PASS | Tab loaded; `05-custom-guest-links.png` |
| Settings | PASS | Config updated; `04-custom-settings.png` |
| Browser E2E | PASS | 14/14 tests passed in Playwright Chromium 145 |

---

## # Limitations

1. **System Apache2 & PHP-FPM Services:** `apache2` and `php-fpm` system daemons are not installed in the execution container. Tested using PHP 8.3 CLI built-in web server with custom multi-tenant `router.php`.
2. **ImageMagick CLI (`convert` / `magick`):** ImageMagick CLI binary is not present in the environment. WebP image conversion and resizing were executed using PHP GD extension natively.
