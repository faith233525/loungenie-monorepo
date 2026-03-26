Automated deploy to staging
===========================

This repository includes an automated deploy workflow at `.github/workflows/auto-deploy-staging.yml`.

What it does
- Uploads `dist/lg-block-patterns.zip` to your staging site's plugins directory using the repository PowerShell helpers.
- Calls the existing LiteSpeed notify / smoke-check script.
- Runs a quick `tools/page_audit.py` check against the staging URL.

How to safely enable automatic deploys
1. Add the required repository secrets (do NOT put secrets in code or chat). From a local clone of this repo, run (requires `gh`):

```bash
gh secret set FTP_HOST --body "ftp.loungenie.com"
gh secret set FTP_USER --body "copilot@loungenie.com"
gh secret set FTP_PASS --body "<your-ftp-password>"
gh secret set CPANEL_TOKEN --body "<your-cpanel-token>"
gh secret set WP_REST_USER --body "copilot"
gh secret set WP_REST_PASS --body "<your-wp-app-password>"
gh secret set WP_SITE_URL --body "https://loungenie.com"
```

2. Create and protect a deploy branch that triggers the workflow: `deploy-staging`.
   - Go to GitHub → Settings → Branches → Add rule for `deploy-staging` and require pull request reviews before merging.

3. Use PRs to merge changes into `deploy-staging`. When a commit is pushed to `deploy-staging`, the workflow will run.

Notes and safety
- The workflow references the existing PowerShell scripts in `scripts/` and will fail if those scripts need interactive input.
- The workflow itself does not store secrets; you must configure repository secrets in GitHub.
- If you want manual approval before deploying, use protected branch rules and require a human reviewer to merge the PR into `deploy-staging`.

If you want, I can generate a one-line `gh` + `git` command bundle to create the branch, push it, and run the workflow dispatch once you have added secrets.
