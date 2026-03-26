Deploy Rules and Automation (LG9)
================================

1. Branches
   - `main` / `portal`: development branches. Work here.
   - `deploy-staging`: protected branch — only merge PRs (no direct pushes).

2. Build & PR
   - Changes to `wp-content/plugins/lg-block-patterns/` or `assets/css/` trigger `auto-build-and-pr.yml` which rebuilds `dist/lg-block-patterns.zip` and opens a PR to `deploy-staging`.

3. Approval & Auto-merge
   - PRs labeled `automated` that target `deploy-staging` and have at least one approval will be auto-merged by CI.
   - The `auto-deploy-staging.yml` workflow runs on pushes to `deploy-staging` and performs the actual upload (requires repository secrets).

4. Required Reviews
   - `CODEOWNERS` enforces that `@faith233525` reviews changes to plugin assets and workflows.

5. Secrets & Safety
   - Repository secrets (FTP, WP REST app password, CPANEL token) must be set by repository admins; automation will not run without them.
   - Never paste secrets in chat; use `gh secret set` or GitHub UI.

6. Fallback
   - If CI upload fails, a manual upload of `dist/lg-block-patterns.zip` via WP Admin is supported; then run the LiteSpeed notifier script to rebuild aggregated CSS.

7. Assistant Authority
   - The assistant will create build PRs and auto-merge labeled PRs. To complete deploys, an authorized user must add repo secrets and/or run the setup script once locally.
