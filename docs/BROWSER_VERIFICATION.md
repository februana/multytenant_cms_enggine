# Browser Verification

The final browser pass used a local PHP preview on port 8001 with sentinel CMS data, followed by fresh desktop navigations and automated Chromium renders at **390×844**. The preview endpoint selects one of the five supported modes without changing the production config. Source repositories remain the reference for built-in DOM, CSS, JS, dependency, and UX behavior: [DewanaKL](https://github.com/dewanakl/undangan), [Elix](https://github.com/elix-stack/wedding-invitation-1), [Rainier](https://github.com/Rainier-PS/Invitation-Template), and [Archak](https://github.com/archakNath/wedding-invitation-website).

## Desktop verification

| Mode | Structural and UX observations | Console result |
|---|---|---|
| Custom | The CMS-native renderer now appears in a full document with root `style.css` and `script.js`. The hero, top navigation, countdown, story, gallery, event, location, RSVP, gift, footer, guest-name, data-saver, and music controls rendered with sentinel bride/groom values. | No browser console output observed in the fresh baseline check. |
| DewanaKL | The original welcome/loading/modal flow, `#root`, `#home`, `#bride`, `#wedding-date`, `#gallery`, `#comment`, bottom navigation, two carousel groups, Google Calendar, Google Maps, gift info controls, and RSVP form rendered. AOS and canvas-confetti are declared according to the source loader; the video boundary is absent when no valid video file is configured. | No browser console output observed. |
| Elix | The original `#hero`, `#home`, `#info`, `#story`, `#gallery`, `#rsvp`, `#gifts`, offcanvas navigation, countdown circles, gallery/lightbox hooks, `#my-form`, `#nama`, `#status`, `#ucapan`, and `#audio-container` rendered. | No browser console output observed. |
| Rainier | The original `#app`, `#event-title`, `#event-subtitle`, `#event-date`, `#event-time`, `#google-calendar-link`, `#maps-link`, `#schedule-section`, `#quotes-section`, `#rsvp`, footer branding links, and `#audio-control` rendered. The calendar event represented 12:00 in `Asia/Jakarta` as 05:00 UTC. The rendered page had no AOS dependency. | No JavaScript error observed. The earlier non-fatal invalid-end-time warning was removed by only passing an end time when the reception is later on the same date. |
| Archak | The original `nav`, `.home`, `.timeline`, `#story`, `.gallery`, `#stay`, `#registry`, parting message, RSVP CTA, and footer attribution rendered. Optional parallax hooks remained compatible with absent DOM elements. | No browser console output observed after original `main.js` initialization. |

Each built-in page was non-blank and retained its source-template vocabulary. The checks focused on DOM structure, visible controls, data substitution, responsive-safe names, and lifecycle initialization rather than replacing the template with generic CMS sections.

## Mobile verification at 390×844

Automated Chromium generated one PNG and one DOM dump for each mode in `/home/ubuntu/cms-browser-results/`. All five images are non-empty at exactly 390×844, and all five DOM dumps contain the expected source-template markers and sentinel CMS values.

| Mode | Mobile assertion | Result |
|---|---|---|
| Custom | Full document, styled root shell, sentinel content | Passed |
| DewanaKL | `#root`, `#wedding-date`, welcome controls, original composition | Passed |
| Elix | `#hero`, `#my-form`, countdown-circle layout | Passed |
| Rainier | `#app`, `#audio-control`, dark hero/countdown composition | Passed |
| Archak | `class="home hz-margin"`, `#registry`, original navigation and CTA | Passed |

Long sentinel names were stress-tested. The fidelity-safe responsive adapters wrap unusually long values in Elix, DewanaKL, and Archak without changing normal source typography or DOM order. Rainier remained stable without an adapter-specific structural change. The first Archak command used an overly exact `class="home"` assertion; the source has `class="home hz-margin"`, and the corrected assertion passed.

## Functional safety observations

DewanaKL suppresses the love-story video when the dedicated media field is empty or does not resolve to a valid local video file. Its music control requires both the explicit `enable_music` option and an existing media file. Rainier's audio control is hidden when `music.enabled` is false, and its polling stops when music is disabled. Elix catches autoplay rejection and displays a visible fallback notice. RSVP handlers in Elix and Rainier treat non-2xx responses, malformed JSON, and network failures as user-visible errors rather than uncaught exceptions. Archak's original reveal behavior runs on initial load and its parallax code now tolerates missing optional elements.

## Limitations

The clean checkout does not provision the sample cover, music, or Open Graph media files, so the browser run intentionally verifies safe absence behavior rather than playback or image delivery from deployment media. A human review with production media and real-content lengths remains advisable before release.

## Real admin UI verification

A temporary local session harness rendered the real `admin/index.php` without changing credentials or committing the harness. Fresh desktop browser inspections covered DewanaKL, Elix, Rainier, Archak, and Custom. DewanaKL and Elix displayed their relevant wedding, parents, schedule, media, story, gallery, music, gift, maps, SEO, WhatsApp, RSVP, and global Link Tamu panels, while unsupported theme/custom CSS/dresscode/cover/background/backup/settings panels were absent. Rainier displayed wedding, schedule, media, story, music, maps, SEO, WhatsApp, RSVP, and Link Tamu, with unrelated parents/gallery/gift/dresscode/theme/backup/settings absent. Archak displayed wedding, parents, schedule, media, story, gallery, gift, maps, SEO, WhatsApp, RSVP, and Link Tamu, with music and unrelated controls absent. Custom displayed the full CMS-native panel set, including sections, theme, custom CSS, backup, settings, and Link Tamu. These observations confirm that panel bodies, not only sidebar links, follow the active preset.

## Personalized guest frontend verification

Custom and DewanaKL were checked through the interactive browser preview with encoded guest input. Elix, Rainier, and Archak were checked with Chromium headless at **390×844**, producing non-empty screenshots and DOM dumps. All five modes rendered the guest marker. For the encoded test value `Sari & <script>`, DOM inspection showed escaped text such as `Sari &amp; &lt;script&gt;`; no raw guest `<script>alert(1)</script>` payload was present. Console inspection for Elix, Rainier, and Archak found no page errors; only the environment-level DBus/UPower warning appeared.

## Final clean-checkout deployment pass

After the default cover/music/Open Graph references were emptied, the current clean checkout was started with the PHP built-in server and opened in Chromium. The public invitation remained non-blank with the Archak preset, original navigation/home/timeline/stay/registry/RSVP/footer markers, and the encoded guest greeting `Andi & <script>` rendered as text. The browser console was empty for this pass. No sample cover or music file was requested by the application because those optional fields are now empty.

The existing desktop/mobile matrix for all five modes and the real admin matrix remain covered by the preceding verification sections. The post-finalization automated renderer/contract/disabled/regression tests passed with empty optional media. A root-run health-check fixture representing a clean deployment returned 35 PASS, 3 WARNING (optional cover, music, and Open Graph media not provisioned), and 0 FAIL.

Docker build and container browser verification could not be performed in this sandbox because Docker CLI/daemon is unavailable. Native installer execution was intentionally stopped at its prerequisite gate because Composer and rsync are not installed in the sandbox; the gate emitted the documented actionable error before changing the host. These are environment limitations, not application failures.

## Preset selector regression correction

The real Admin HTML/UI was rechecked after separating the selector from the theme-specific panel. Custom, DewanaKL, Elix, Rainier, and Archak each rendered a non-blank Admin page with a visible `Preset / Tema` sidebar link and `#preset-selector` panel. The corresponding current preset was selected in the global `<select id="globalThemePreset">`; Custom exposed the full CMS-native sidebar and builder, while built-in modes exposed only their contract-relevant controls. `Link Tamu`, personalized-link controls, and the global invitation preview remained visible in every mode.

The selector panel appears before the theme-specific `#theme` panel gate, and built-in pages do not expose the manual `Tema & Tampilan` panel. The real Admin page loaded without page-level console errors. Frontend theme renderer, disabled-section, and template-fidelity checks were not changed by this Admin-only correction.

## Indonesian UI and content preservation verification

A fresh browser navigation of the real Admin page at `http://127.0.0.1:8001/admin/` showed the document title **Admin CMS Undangan**, Indonesian dashboard/navigation labels, the globally visible `Preset / Tema` selector, and the `Simpan Preset Aktif` action. The media manager, story/gallery, gift, location, SEO, WhatsApp, guest-link, RSVP, and backup-related application controls were rendered with Indonesian labels. The selector options remained DewanaKL, Elix, Rainier, Archak, and Custom; the source preset names themselves were preserved.

The Admin textarea rendered the sentinel opening value on separate lines in the 390px browser screenshot. The frontend active Archak page retained original source-template identity wording (`OUR STORY`, `TRAVEL & STAY`, `PROMISES`, `We're getting married`, `Travel and Stay`, `Promises`, `Hope to See You!`, and `BLESS US`) while displaying user-entered invitation text and address content without automatic translation. This separation is intentional and required for source-template fidelity.

The save/config/render path is covered by `tools/content_preservation_smoke.php`, which passed for all five modes. The test verifies multilingual Unicode, newlines, meaningful spaces, English user text, CRLF-only normalization, JSON round-trip equality, safe HTML rendering, and no automatic translation call. Browser verification was passive and did not submit or mutate forms.


## PR #72 follow-up browser verification (working notes)

A fresh Admin navigation on the PR #72 branch showed `Admin CMS Undangan`, the visible global `Preset / Tema` panel, all five preset options, the selected Archak option, and Indonesian application labels. The opening textarea continued to display the two-line sentinel value.

The active Archak guest page showed Indonesian static labels including `KISAH KAMI`, `PERJALANAN & TEMPAT MENGINAP`, `JANJI`, `Kita Akan Menikah`, `Konfirmasi Kehadiran`, `PETA & DETAIL`, `REKOMENDASI KAMI`, `PILIHAN PENGINAPAN`, `DOAKAN KAMI`, `Sampai Jumpa!`, and `Hubungi Kami`. Sentinel bride/groom names, Indonesian invitation text, address, and gift data remained unchanged. A temporary fixture was also opened for Custom; its user/config section titles were left untouched as required, while its application navigation and action labels are now localized.


The DewanaKL fixture rendered `Simpan ke Google Kalender`, `Gulir ke Bawah`, Indonesian countdown units, Indonesian gallery controls (`Sebelumnya`, `Berikutnya`), `Beranda`, and `Hadiah Pernikahan`. The Elix fixture rendered Indonesian navigation (`BERANDA`, `INFORMASI`, `KISAH`, `GALERI`, `KONFIRMASI KEHADIRAN`, `HADIAH`), Indonesian countdown units, and preserved the multiline `Baris 1` / `Baris 3` user content. Both fixtures remained non-blank and retained their expected template layout.


The Rainier fixture rendered Indonesian countdown labels, `Tambahkan ke Google Kalender`, `Konfirmasi Kehadiran`, `Detail Acara`, `Jadwal`, `Kata-Kata Inspirasi`, `Mohon konfirmasi kehadiran Anda`, the Indonesian CMS RSVP form (`Nama Anda`, `Kehadiran`, `Pilih`, `Akan Hadir`, `Tidak Dapat Hadir`, `Pesan`, `Kirim Konfirmasi Kehadiran`), and Indonesian footer labels. The Rainier source repository link and `Rainier` attribution remained intact.

The Archak fixture rendered Indonesian navigation, hero, timeline, story, travel/stay, registry, parting, and footer labels. The `@NathArchak` attribution and source link remained intact. User-entered names, invitation text, address, and gift information remained unchanged.


The refreshed Custom fixture showed Indonesian application navigation/actions (`Konfirmasi Kehadiran`, `Mode Hemat Data`, `Buka Undangan`, `Tambah ke Kalender`, `Hubungi WA`, `Putar Musik`, `Buka di Google Maps`, and `Kirim Konfirmasi Kehadiran`). Custom section titles such as `Love Story`, `Gallery`, `Events`, `Location`, and `Gift` remained exactly as supplied by the current config; they are user/config content and were intentionally not translated. The Custom multiline opening content remained visible as separate lines. The browser console produced no output for this verification page.


## PR #72 follow-up: global Settings and Guest Link Generator

An unauthenticated browser request to the updated Admin confirmed the login page remains available and Indonesian. A previously authenticated Admin request on the updated branch showed the global `Preset / Tema`, `Link Tamu`, `Cadangan`, and `Pengaturan` navigation entries, an empty invitation preview with the placeholder `Konfigurasikan Site URL di Pengaturan`, and the Settings panel’s missing-origin warning. Interactive generation after login could not be completed because the browser session required Admin credentials; the configured-origin and missing-origin paths were verified by `tools/admin_guest_smoke.php` and the complete local suite.

## Follow-up visual CMS audit — 2026-08-18

The Admin browser fixture was inspected with a canonical `uploads/background/e2e-visual-probe.png` asset seeded through the existing Media Library folder. The Archak visual panel displayed `Background — e2e-visual-probe.png` as a selectable option and did not render a `visual_file_*` input. Selecting the asset after switching to Elix updated both the global and hidden preset to `elix`, stored the canonical relative reference in the visual field, and sent `--cms-elix-hero-bg: url("/uploads/background/e2e-visual-probe.png")` into the same-origin preview iframe.

The same browser session changed unsaved Elix values, switched to Rainier, changed Rainier values, and returned to both presets. Each preset restored its own accent and background reference without cross-contamination. The public E2E runtime then saved Elix visual values through a temporary production config, and computed `.hero::before` contained the actual `/uploads/background/e2e-visual-probe.png` URL. At 390×844, the post-fix screenshot showed readable greeting, Pacifico brush names, invitation message, compact four-part countdown, and the `Lihat Undangan` CTA. Computed hero text colors were white, the saved media background was present, and document scroll width matched client width.
