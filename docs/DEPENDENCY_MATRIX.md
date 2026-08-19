# Dependency Matrix

| Preset | Original dependency | Previous baseline | Hardening action | Reason/status |
|---|---|---|---|---|
| DewanaKL | Bootstrap 5.3.8, Font Awesome 7.1.0, `common.css`, `guest.css`, `animation.css`, original guest runtime and theme media | Bootstrap 5.3.2, Font Awesome 6.5.1, AOS, CMS `style.css`/`script.js` | Copied original CSS/runtime assets locally, updated versions, removed AOS stylesheet/script, kept CMS script only for data/backend bridges | Preserved/retained; AOS was not part of source dependency contract |
| retired preset | Bootstrap 5.3.5, Pacifico/Sacramento/Work Sans, Bootstrap Icons, SimplyCountdown UMD, `countdown/circle.css`, bs5-lightbox | Bootstrap 5.3.2, Cormorant/Inter, Font Awesome, AOS, custom script | Restored original fonts/icons/countdown/lightbox and local original assets; removed AOS and Font Awesome | Preserved/retained; responsive adapter is an integration-only CSS layer |
| Rainier | Cormorant Garamond/Outfit, `invite.css`, `invite-1.js`, optional provider widgets | Different fonts/CSS and custom script; no original event JSON bridge | Restored original CSS/JS source as local adapter; replaced only JSON fetch with embedded CMS data; added CMS RSVP provider branch | Preserved with A/B integration; AOS intentionally not added |
| Archak | `style.css`, Font Awesome kit, `main.js` | Different custom CSS/JS and expanded DOM | Restored original stylesheet/script and Font Awesome kit; added only responsive long-data adapter | Preserved/retained |
| Custom | CMS global styles/scripts and current backend | Existing CMS-native runtime | Kept unchanged in architecture; no built-in source assets are forced into Custom | Preserved |

## External dependency policy

The built-in layouts do not load dependencies borrowed from another preset. Rainier has no AOS dependency. Archak has no music capability because the original template has no audio presentation. The source repositories remain the authority for dependency decisions.

## References

[1]: https://github.com/dewanakl/undangan "DewanaKL source repository"
[2]: #retired-preset-source "retired preset source repository"
[3]: https://github.com/Rainier-PS/Invitation-Template "Rainier source repository"
[4]: https://github.com/archakNath/wedding-invitation-website "Archak source repository"
