Automated Staging Deploy Workflow
================================

What this workflow does
- Runs the repository duplicate detector (`scripts/find_artifact_duplicates.py`) and uploads the report.
- Runs the PowerShell orchestrator (`tools/run_local_orchestrator.ps1`) to perform FTP backup and REST deploy to the staging site.
- Uploads logs and a duplicate-report artifact for review.

Secrets required (set these in your repository Settings → Secrets):
- `WP_URL` — staging site base URL (e.g., https://loungenie.com/staging)
- `STAGING_URL` — staging site base URL (used for smoke checks)
- `WP_USER` — WordPress user (for Application Passwords)
- `WP_APP_PASS` — WordPress Application Password (do not rotate without instruction)
- `FTP_HOST` — FTP/FTPS host
- `FTP_USER` — FTP username
- `FTP_PASS` — FTP password
- `ALLOW_UNTRUSTED_FTP_CERT` — optional boolean when connecting to self-signed/legacy FTPS

How to trigger
- Push to `main` to run automatically, or choose "Run workflow" from the Actions tab to run manually.

Notes and safety
- The workflow uses the repo's PowerShell orchestrator which by default may run in `DRY_RUN` mode; review `tools/run_local_orchestrator.ps1` and the environment variables before enabling full deploys.
- Do NOT rotate the `WP_APP_PASS` secret until you're ready — the local deploy scripts rely on existing credentials.
