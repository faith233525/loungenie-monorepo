Update Pages Automation — README

Overview:
- This repo contains automation that converts staging pages to Kadence/Gutenberg blocks and PATCHes them into the WordPress staging site via the REST API.
- Scripts live in `scripts/` and example page payloads live in `content/`.

Required repository secrets (GitHub Actions):
- `WP_USERNAME` — WordPress username with the application password (e.g., `copilot`).
- `WP_APP_PASSWORD` — the Application Password for the user.
- `WP_SITE_URL` — base URL of the site (example: https://loungenie.com/staging).

Important: Do NOT paste credentials into chat. Add these via the repository Settings → Secrets.

Local run (recommended for first-run verification):
1) Create and activate a venv, then install dependencies:

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
```

2) Export env vars (PowerShell example):

```powershell
$env:WP_USERNAME = "your-username"
$env:WP_APP_PASSWORD = "your-app-password"
$env:WP_SITE_URL = "https://loungenie.com/staging"
```

3) Validate `media_lookup.json` against the site before patching pages:

```powershell
python scripts\validate_media_lookup.py --media-file media_lookup.json
```

This writes `outputs/validate_media_report.json` with any missing IDs and a summary.

4) Run the updater (it will back up pages into `backups/` before PATCH):

```powershell
python scripts\update_pages.py --content-dir content --media-file media_lookup.json --stop-on-error
```

CI / GitHub Actions:
- The workflow `.github/workflows/update-pages.yml` reads same secrets and runs the updater.
- After adding repository secrets, trigger via workflow_dispatch or push.

Rollback:
- Backups are created in `backups/` with the original page JSON responses. To rollback, re-PATCH the saved backup content to the corresponding page ID.

Next steps checklist:
- Add `WP_USERNAME`, `WP_APP_PASSWORD`, `WP_SITE_URL` to GitHub Secrets.
- Run `scripts/validate_media_lookup.py` and fix any missing media IDs before running the updater.
- Replace `content/*.json` example payloads with the final Kadence block payloads for About, Contact, and Features.
- Run updater locally first; then run in CI once verified.
