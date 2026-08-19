# Theme Fidelity Matrix

## Scope

This matrix compares the active CMS adapters against their source repositories or source design references. The source visual identity remains authoritative; CMS integration replaces content ownership without redesigning the presentation grammar.

| Preset | Source template boundary retained | Original selector/DOM hooks restored | Original CSS/JS/dependency posture | CMS integration retained | Hardening status |
|---|---|---|---|---|---|
| DewanaKL | Split desktop/mobile shell, `#root`, `#home`, `#bride`, Love Story, `#wedding-date`, `#gallery`, Love Gift, `#comment`, bottom nav, welcome/loading/modal | `#home`, `#bride`, `#wedding-date`, `#gallery`, `#comment`, `#navbar-menu`, `#welcome`, `#loading`, `#modal-image`, carousel hooks | Bootstrap, Font Awesome, guest/common/animation CSS copied locally; unnecessary AOS dependency removed | Names, parents, schedule, countdown, venue/maps, gallery, gift, RSVP/messages, guest name, music, SEO, calendar | Existing adapter preserved and smoke-tested |
| Rainier | Original hero/event presentation, schedule and quotes boundaries, RSVP embed, footer branding, simple/audio controls | `#event-title`, `#event-subtitle`, `#event-date`, `#event-time`, `#calendar-actions`, `#schedule-section`, `#quotes-section`, `#rsvp`, footer/audio hooks | Original invite CSS and lifecycle preserved in local adapter; no assumed AOS dependency | Dynamic event/date/location/calendar/schedule/quotes, CMS RSVP bridge, music, SEO, guest data | Existing adapter preserved and console-smoke-tested |
| Archak | Original nav, `.home`, `.timeline`, `#story`, `.gallery`, quote, `#stay`, `#registry`, parting message, footer | Source ID and class vocabulary retained | Original style and main script copied locally with source font/icon posture | Dynamic names, schedule, story, gallery, maps, gift/registry, RSVP CTA, SEO, media | Existing adapter preserved |
| Parang | Desktop sidebar, mobile app bar, gunungan ornaments, couple cards, event cards, gallery grid, digital gift cards | Source-aligned section IDs and mobile navigation hooks retained | Local source-aligned CSS, font, icon, and theme media assets | Dynamic wedding, parents, schedule, countdown, gallery, gift, maps, RSVP, music, SEO, guest data | Existing adapter preserved |
| Pawiwahan | Source-aligned home, event, story, gallery carousel, location, gift, RSVP, footer, and responsive navigation | Source gallery carousel and section hooks retained | Source HTML/CSS/JS posture remains local and source-aligned | Dynamic wedding, schedule, gallery, gift, maps, RSVP, music, SEO, guest data | Existing adapter preserved |
| Shubh Vivah | Centered invitation card, ornamental corners, script display typography, compact countdown, gallery, RSVP flow, source floral/card atmosphere | `#shubh-home`, `#shubh-event`, `#shubh-gallery`, `#shubh-rsvp`, localized CTA and source-card order | Source artwork and lightweight font/ornament assets are local; CMS behavior is isolated in `layout.php` and `fidelity-adapter.css` | Dynamic names, guest label, event, countdown, calendar, gallery, RSVP/messages, maps, music, SEO, canonical visual media | New adapter implemented, localized to Indonesian, smoke-tested at 1440/1280/1024/768/576/390/360 px |
| Yami Buzzy | Welcome modal, full-bleed hero, couple cards, event cards, dress-code timeline, story, gallery, video, gift, invitation, RSVP, closing, mobile navigation | `#yami-welcome-modal`, `#yami-home`, `#yami-couple`, `#yami-event`, `#yami-story`, `#yami-gallery`, `#yami-rsvp` | Source photography/artwork and lightweight local font/icon assets are preserved; CMS behavior is isolated in `layout.php` and `fidelity-adapter.css` | Dynamic names, guest label, parents, event/countdown, story, gallery, video, gift, maps, RSVP/messages, music, SEO, canonical visual media | New adapter implemented, localized to Indonesian, smoke-tested at 1440/1280/1024/768/576/390/360 px |
| Custom | CMS-native section builder and global ordering | Existing global section schema retained | CMS renderer remains authoritative | Full CMS capability set | Preserved unchanged in principle |

## Change classification

The adapter changes are integration changes when they replace hardcoded content with CMS data, add safe URLs, embed event data, bridge RSVP to the canonical backend, or expose canonical media through visual capabilities. They are not presentation changes when they preserve the source wrapper, order, typography posture, section grammar, and template-specific UX. Responsive guards only protect source layouts from unusually long CMS values.

## References

[1]: https://github.com/dewanakl/undangan "DewanaKL original invitation template"
[2]: https://github.com/Rainier-PS/Invitation-Template "Rainier original invitation template"
[3]: https://github.com/archakNath/wedding-invitation-website "Archak original invitation template"
[4]: https://github.com/vinitshahdeo/wedding-website "Shubh Vivah source repository"
[5]: https://github.com/Tynab/Yami-Buzzy "Yami Buzzy source repository"
