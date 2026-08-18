# Theme Contract Matrix

| Preset | Actual presentation sections | Data capabilities | Presentation capabilities | Admin capabilities | Disabled behavior |
|---|---|---|---|---|---|
| DewanaKL | `home`, `bride`, `love_story`, `wedding_date`, `gallery`, `love_gift`, `comment` | Wedding, parents, schedule, countdown, gallery, music, gift, maps, RSVP, messages, story, guest name, media, SEO, WhatsApp, calendar | Welcome/loading, split shell, video story, carousel gallery, Love Gift cards, comment feed, image modal, bottom navigation | Only CMS data/service controls plus theme-specific visibility/copy for actual boundaries | Disabled gallery removes its `<section id="gallery">`; disabled comment removes RSVP/comment boundary; Custom global sections do not leak |
| Elix | `hero`, `home`, `info`, `story`, `gallery`, `rsvp`, `gifts` | Wedding, parents, schedule, countdown, gallery, music, gift, maps, RSVP, messages, story, guest name, media, SEO, WhatsApp, calendar | Hero cover, offcanvas nav, countdown circle, timeline, lightbox, RSVP form, gifts, audio container | Relevant CMS data and actual Elix presentation boundaries; no free DOM ordering | Disabled gallery/rsvp/gifts remove their presentation sections and nav links; order remains source order |
| Rainier | `hero`, `event_details`, `schedule`, `quotes`, `rsvp`, `footer` | Wedding, schedule, countdown, maps, RSVP, messages, guest name, calendar, SEO, music, media | Original hero/event details, schedule list, quotes list, CMS-backed form embed, footer branding, audio control | Data/service controls and actual Rainier section toggles; no gift or invented standalone countdown section | Disabled schedule/quotes/rsvp remove the original boundaries; RSVP adapter removes the section when disabled |
| Archak | `home`, `timeline`, `story`, `gallery`, `quote`, `stay`, `registry`, `parting`, `footer` | Wedding, parents, schedule, maps, RSVP, guest name, gallery, story, gift, media, SEO, WhatsApp | Responsive nav, parallax home, timeline, triptych gallery, quote, travel/stay, registry, parting message | Data/service controls and actual Archak boundaries; music is not offered because original template has no audio presentation | Disabled registry/story/gallery removes its original boundary; CTA behavior remains within original layout |
| Custom | Global `config.sections` with CMS ordering | Full CMS capability set | CMS-native sections and presentation controls | Full builder, ordering, custom titles/subtitles, theme controls | Existing CMS behavior preserved |

## Contract rules

Built-in renderers never call the Custom ordering resolver. `order` in `theme_sections[preset]` exists only for stable admin display and migration compatibility; it never changes built-in DOM order. Legacy IDs are mapped per preset in `theme-contract.php` and are not reused as universal aliases by built-in layouts.

References are maintained in the [Theme Fidelity Matrix](THEME_FIDELITY_MATRIX.md).
