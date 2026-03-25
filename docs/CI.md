## CI / Deploy docs

What this repo's workflow does

- Runs `scripts/find_artifact_duplicates.py` and uploads a duplicate report.
- Runs `tools/run_wp_ftp_and_rest_safe.ps1` to sync FTP and perform REST deploys (safe dry-run defaults).
- Uploads logs and performs a simple smoke-check of the staging front page.

Required repo secrets (set these under Settings → Secrets → Actions):

- `WP_URL` — staging site base URL (https://loungenie.com/staging)
- `WP_USER` — WordPress username for Application Password
- `WP_APP_PASS` — WordPress Application Password
- `FTP_HOST` — FTP(S) host
- `FTP_USER` — FTP username
- `FTP_PASS` — FTP password
- `STAGING_URL` — staging site URL used for smoke checks

How to trigger the workflow from your workstation

1. Ensure you have `gh` CLI installed and authenticated (`gh auth login`).
2. From the repo root run the helper which uses your current local branch as the ref:

```powershell
pwsh .\tools\trigger_workflow.ps1
```

If your local branch is not pushed to the remote, the dispatch will fail with `No ref found` — either push the branch or specify a branch that exists on the remote.

Running the workflow locally (alternative)

- You can run the orchestrator and scripts locally for dry-run without secrets:

```powershell
pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\run_wp_ftp_and_rest_safe.ps1
python .\scripts\sync_media.py --dry-run
```

Next steps I can do for you

- Set repo secrets (you must provide them or set them in GitHub directly).
- Push the current branch to `main` or the remote branch you prefer.
- Run the workflow and collect artifacts/logs and a run summary.
