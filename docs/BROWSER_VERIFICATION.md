# Browser Verification

## DewanaKL

The preview endpoint rendered a non-blank page with title `TEST TITLE` and visible source-template controls: Google Calendar CTA, Google Maps CTA, two carousel groups with indicators and previous/next controls, two Love Gift info controls, bottom navigation with Home/Mempelai/Tanggal/Galeri, and the original welcome button `openInvitationBtn`. The response contained the expected `#root`, `#home`, `#bride`, `#wedding-date`, `#gallery`, and `#comment` boundaries in the server-rendered HTML.

A subsequent browser view did not retain the same navigation session and opened `about:blank`; this is recorded as a browser-session limitation rather than a passed visual assertion. The remaining visual checks will use fresh navigations and structural/console checks.

## Elix

A fresh browser navigation rendered the original selector vocabulary and CMS data: `#hero`, `#home`, `#info`, `#story`, `#gallery`, `#rsvp`, `#gifts`, offcanvas navigation, countdown circle, original RSVP field IDs `nama`/`status`/`ucapan`, `#my-form`, and `#audio-container`. The page displayed sentinel bride/groom values and event data, and the browser console had no output/errors after initialization.

## Rainier

A fresh browser navigation rendered the source template hooks `#app`, `#event-title`, `#google-calendar-link`, `#maps-link`, `#audio-control`, original footer branding/template/repository links, and the adapter-generated CMS RSVP fields. The console had no output/errors after inline event data and the original Rainier adapter initialized. No AOS dependency is loaded by the restored layout.

## Archak

A fresh browser navigation rendered the compact original structure: `OUR STORY`, `TRAVEL & STAY`, `PROMISES`, `.home`, timeline ceremony/reception, `#story`, `.gallery`, quote, `#stay`, `#registry`, parting RSVP, and original footer attribution. The server-rendered values used sentinel names, date, venue, maps, bank/e-wallet, and WhatsApp data. The console had no output/errors after the original `main.js` loaded.

## Mobile viewport

Automated Chromium renders completed at `390x844` for DewanaKL, Elix, Rainier, and Archak; all produced non-empty PNGs at the expected viewport size. The Elix screenshot showed the original countdown-circle presentation and no blank screen, but also exposed a horizontal overflow because the long sentinel names exceed the narrow hero width. This is a content-length stress case, but it must be mitigated with a fidelity-safe responsive rule rather than accepting overflow. Rainier rendered the original dark hero, countdown, calendar link, RSVP CTA, and audio control in the narrow viewport with no blank screen.

The Elix responsive adapter was added after the first mobile screenshot exposed long-name overflow. A new `390x844` render wraps the long sentinel names within the hero instead of extending the horizontal content area; the countdown-circle remains visible.

The DewanaKL mobile screenshot reached the original welcome overlay with `The Wedding Of`, circular cover placeholder, couple names, guest greeting, and `Buka Undangan` control. The long sentinel names still visually exceed the narrow viewport in this original typography shell; this is the same data-length stress case and should receive a source-compatible wrap rule before finalizing.

A second DewanaKL mobile render wraps the long sentinel bride/groom names into multiple lines within the welcome overlay while keeping the original open-invitation flow intact. Archak's mobile render exposed horizontal overflow for long sentinel names in the original `.home` typography; this is the same data-length stress case and requires a small responsive adapter that preserves the original classes/structure.

A second Archak mobile render wraps the long names inside the original `.home h1` while preserving original nav, date/venue block, and RSVP CTA. The Rainier mobile screenshot remains stable with the original dark hero/countdown/CTA/audio-control composition.
