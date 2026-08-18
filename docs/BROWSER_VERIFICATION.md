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
