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
