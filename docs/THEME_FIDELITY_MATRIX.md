# Theme Fidelity Matrix

## Scope

This matrix compares the hardening branch against the original repositories rather than treating the previous CMS-authored layouts as authoritative.

| Preset | Source template boundary retained | Original selector/DOM hooks restored | Original CSS/JS/dependency posture | CMS integration retained | Hardening status |
|---|---|---|---|---|---|
| DewanaKL | Split desktop/mobile shell, `#root`, `#home`, `#bride`, Love Story boundary, `#wedding-date`, `#gallery`, Love Gift boundary, `#comment`, bottom nav, welcome/loading/modal | `#home`, `#bride`, `#wedding-date`, `#gallery`, `#comment`, `#navbar-menu`, `#welcome`, `#loading`, `#modal-image`, `#video-love-stroy`, `carousel-image-one`, `carousel-image-two` | Bootstrap 5.3.8, Font Awesome 7.1.0, original guest/common/animation CSS copied locally; AOS removed from the layout | Names, parents, schedule, countdown, venue/maps, gallery, gift, RSVP/messages, guest name, music, SEO, calendar | Restored and smoke-tested; remaining media files are deployment data rather than source layout defects |
| Elix | Original `#hero`, `#home`, `#info`, `#story`, `#gallery`, `#rsvp`, `#gifts`, offcanvas navigation, footer, `#audio-container` | Original IDs and RSVP field vocabulary `#my-form`, `#nama`, `#jumlah`, `#status`, `#ucapan`; original countdown/lightbox hooks | Bootstrap 5.3.5, Pacifico/Sacramento/Work Sans, Bootstrap Icons, SimplyCountdown, circle CSS, bs5-lightbox; AOS and Font Awesome removed | Dynamic couple, parents, schedule, maps, countdown, story, gallery, CMS RSVP, gift, music, calendar, SEO | Restored; responsive fidelity adapter wraps unusually long CMS names without replacing DOM |
| Rainier | Original `#app`, hero/event presentation, schedule and quotes boundaries, RSVP embed boundary, footer branding and simple/audio controls | `#event-title`, `#event-subtitle`, `#event-date`, `#event-time`, `#calendar-actions`, `#schedule-section`, `#quotes-section`, `#rsvp`, footer IDs, `#audio-control` | Original `invite.css` and `invite-1.js` lifecycle preserved in local adapter; CMS data is embedded JSON; AOS intentionally absent | Dynamic event/date/location/calendar/schedule/quotes, CMS RSVP bridge inside original `.form-embed`, music, SEO, guest data | Restored and console-smoke-tested; no AOS added |
| Archak | Original nav, `.home`, `.timeline`, `#story`, `.gallery`, quote, `#stay`, `#registry`, parting message, footer | `#check`, `#home-img-lg`, `#parallax1`, `#parallax2`, `#story`, `#stay`, `#registry`, original class vocabulary | Original `style.css` and `main.js` copied locally; Font Awesome kit restored | Dynamic names, schedule, story, gallery, maps, gift/registry, RSVP CTA, SEO, media | Restored; responsive fidelity adapter wraps unusually long CMS names |
| Custom | CMS-native section builder and global ordering | Existing global section schema retained | CMS renderer remains authoritative | Full CMS capability set | Preserved unchanged in principle |

## Change classification

The hardening changes are integration changes when they replace hardcoded content with CMS data, add safe URLs, embed event JSON, or bridge RSVP to the canonical backend. They are not presentation changes when they preserve the original selector, wrapper, order, dependency lifecycle, and template-specific UX. The new responsive adapters only protect original layouts from unusually long data values.

## References

[1]: https://github.com/dewanakl/undangan "DewanaKL original invitation template"
[2]: https://github.com/elix-stack/wedding-invitation-1 "Elix original wedding invitation template"
[3]: https://github.com/Rainier-PS/Invitation-Template "Rainier original invitation template"
[4]: https://github.com/archakNath/wedding-invitation-website "Archak original wedding invitation template"
