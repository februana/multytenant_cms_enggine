# Config directory migration and testing

This document guides moving from `config.json` to the modular `config/` layout while preserving runtime behavior.

Principles

- Non-destructive: keep `config.json` as a fallback until you're confident with the modular files.
- Incremental: start with `config/site.json` (full config); later split into `theme.json`, `sections.json`, `seo.json`.
- Test thoroughly before removing the legacy `config.json`.

Steps

1. Backup current configuration

```bash
cp config.json config.json.bak
git add config.json.bak && git commit -m "Backup config.json before modular migration" || true
```

2. Create the `config/` directory (if not present)

```bash
mkdir -p config
```

3. Create `config/site.json` by copying your current `config.json` (already done in Phase 3.1)

```bash
cp config.json config/site.json
```

4. Validate JSON

```bash
python3 -m json.tool config/site.json
```

5. Start the dev server and confirm behavior

```bash
ADMIN_PASS=testingpass php -S 127.0.0.1:8000 -t .
# In another terminal:
curl -sS "http://127.0.0.1:8000/app/messages.php" | jq .
```

6. If you wish to split concerns, create and populate `config/theme.json`, `config/sections.json`, and `config/seo.json` as preparation-only files. Do not remove keys from `site.json` until `config.php` is updated to merge them.

7. Rollback

- To rollback to the legacy `config.json`, remove or rename `config/site.json` and restart PHP-FPM or the dev server.

8. Future switch

- A future Phase will update `app/config.php` to read and merge `config/*.json` and then `site.json` may be reduced to only site-level keys. That phase will include tests and a rollback plan.

Questions

If you want me to generate a one-line script to split `config/site.json` into the three modular files automatically, I can prepare it for review, but do not run it until you approve the merge phase.
