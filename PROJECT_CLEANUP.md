# Project Cleanup — Removed test artifacts and hygiene

What I removed during the final audit

- Test artifacts and temporary HTTP captures in repository root:
  - cookiejar, cookies.txt, tmp_csrf.json
  - .prop_*.txt, .legacy_*.txt, .messages_proposed*.txt, .save_*.txt

Why
- These files were generated during smoke tests and are not required for runtime or historical backups.

Files intentionally kept
- `.archive/` — repository backups and migration logs (kept as documented backups)
- `deploy/` scripts — installer and maintenance scripts
- `config/` files — runtime config and modular files

How to reproduce removed artifacts (if needed)
- Run the smoke tests (from repository root):

```bash
# start dev server (in separate terminal)
ADMIN_PASS=testingpass php -S 127.0.0.1:8000 -t .

# then in another terminal
curl -c cookiejar -b cookiejar "http://127.0.0.1:8000/app/save.php?get_csrf=1"
curl -c cookiejar -b cookiejar -X POST "http://127.0.0.1:8000/app/save.php" -d "csrf_token=...&nama=Test&status=Hadir&ucapan=Hello"
```

If you want these captures retained, consider placing them under a `tests/` directory and adding them to `.gitignore`.
