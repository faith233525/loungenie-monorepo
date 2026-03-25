**Deploy Checklist (Staging, Safe Runs)**

- **Goal:** Run the repo orchestrator in dry-run first, then enable live deploys only after secrets & environment approval.

- **Required secrets (repo Settings → Secrets → Actions):**
  - `WP_URL` — staging site base URL (https://loungenie.com/staging)
  - `WP_USER` — WP username for Application Password (recommended 'copilot' or admin)
  - `WP_APP_PASS` — Application Password (do NOT paste into chat)
  - `FTP_HOST` — FTPS host (ftp.poolsafeinc.com)
  - `FTP_USER` — FTP username
  - `FTP_PASS` — FTP password

- **How to run dry-run (recommended):**
  1. Create or review `.github/workflows/dry_run_sync.yml` in this repo.
  2. In GitHub Actions → select the workflow `Safe Dry-Run Staging Sync` → `Run workflow`.
  3. Leave `dry_run` = true (default) and `run_ftp_backup` as needed.
  4. Inspect run artifacts: `staging-artifacts` (duplicate_report, media_uploads.json, image_audit.json, logs).

- **How to enable live run (after review):**
  1. Add the secrets above to repo secrets.
  2. Protect the `staging` environment and require reviewers/approvals in repo Settings → Environments → staging.
  3. Re-run the workflow with `dry_run: false` and obtain environment approval when prompted.

- **Local run option (recommended if you prefer not to add secrets to repo):**
  - Run the orchestrator locally (it prompts securely for credentials and defaults to dry-run):

```powershell
pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\run_full_staging_sync.ps1
```

- **Post-run checklist:**
  - Review `artifacts/duplicate_report.txt` and accept the proposed canonical mapping before applying templates.
  - Review `artifacts/image_audit.json` and decide on ALT auto-fill heuristics.
  - QA the header/footer template-part artifacts in `artifacts/lg9_header_template_part.json` and `artifacts/lg9_footer_template_part.json`.
  - If live apply is approved, run with `-Live` locally or run the workflow with `dry_run=false` in CI with approved environment.

- **Do NOT:**
  - Paste Application Passwords or other secrets in chat.
  - Rotate App Passwords without coordination.
