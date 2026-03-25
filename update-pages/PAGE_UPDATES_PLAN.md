Feature branch: feature/automated-page-updates

Scope: Update site pages (Kadence/Gutenberg) page-by-page for improved design and responsive behavior (desktop/tablet/phone). Do NOT modify any `investors` pages.

Planned steps

1. Inventory
   - Generate a list of public pages (home, about, contact, features, gallery, board, press, etc.) and mark `investors/*` as skip.

2. Per-page plan (examples)
   - Home: Replace hero with Kadence hero block, reduce text width, update CTA styling, ensure responsive image sizes.
   - About: Convert long content to two-column blocks, add team member Kadence cards, ensure accessible headings.
   - Contact: Clean up form block, increase tap target sizes, ensure phone/email links.
   - Gallery: Use Kadence gallery block with lazy loading and appropriate srcset sizes.
   - Board: Reformat biographies into Kadence grid cards; keep content but modernize layout.

3. Implementation
   - Create `feature/automated-page-updates` branch locally.
   - Implement changes as draft posts or block JSON export files under `content/patches/`.
   - Commit changes with descriptive messages per page.

4. CI/CD
   - Add repo Secrets (do NOT paste here): `FTP_HOST`, `FTP_USER`, `FTP_PASS`, `WP_USER`, `WP_PASS`, `STAGING_URL`.
   - Push branch and open PR; run `.github/workflows/complete-deploy.yml` (workflow_dispatch) to deploy to staging.
   - Inspect artifacts `deploy-run-log` and `ftp-backup`, perform QA.

5. QA & iterate
   - Run image and accessibility audits (scripts already in repo).
   - Take phone/tablet screenshots and request review.

6. Merge & cleanup
   - After approval merge to `main`, run final deploy to staging.
   - Rotate any credentials when all work is complete.

Git commands (run locally in repo root):

```powershell
# create feature branch
git checkout -b feature/automated-page-updates
# review changes created by this agent
git add PAGE_UPDATES_PLAN.md
git commit -m "Add automated page updates plan"
# push to origin
git push -u origin feature/automated-page-updates
```

If you want me to produce and apply actual Gutenberg/Kadence block changes automatically, reply `auto-apply` and I will generate the per-page patch files under `content/patches/` for your review. If you want me to push commits and open the PR remotely, provide the GitHub repo remote name (e.g., `owner/repo`) and confirm I should use the repo to create the PR (I will need you to run the `git push` commands or provide a GitHub token via Actions secrets to create PRs).

Notes
- I will not change any `investors` pages.
- I will not use or store any credentials pasted into chat.
- Large media backups may take significant time in CI; consider running the FTP backup locally first to confirm.
