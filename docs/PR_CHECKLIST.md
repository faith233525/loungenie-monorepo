PR Review Checklist — Safe Staging Deploy

Purpose: guide reviewers through verifying the dry-run artifacts and approving the guarded live deploy.

Before approving merge
- [ ] Confirm CI run artifacts exist and are attached to the workflow run (staging-artifacts).
  - duplicate_report.txt
  - audit_all_images.txt
  - audit_colors.txt
  - check_broken_references.txt
  - alt_suggestions.csv
  - oversized_images.csv
- [ ] Review `artifacts/duplicate_report.txt` and accept canonical choices in `artifacts/canonical_navigation_mapping.json`.
- [ ] Review `artifacts/alt_suggestions.csv` for obvious mislabels; spot-check top 20 images on home and gallery pages.
- [ ] Confirm `docs/DEPLOY_CHECKLIST.md` steps are followed and secrets are added to repo: `WP_URL`, `WP_USER`, `WP_APP_PASS`, `FTP_HOST`, `FTP_USER`, `FTP_PASS`.
- [ ] Ensure `staging` environment is protected and requires approvals.

If everything above is OK
- Merge the PR (squash or merge commit per repo policy).
- Trigger the workflow with `dry_run=false` to perform the live apply; wait for environment approval prompt and approve.

Post-merge smoke checks (after live apply)
- [ ] Confirm header/footer appear correctly across mobile/desktop (< 3 viewport checks).
- [ ] Confirm navigation items match canonical mapping and no duplicates are present.
- [ ] Confirm logo carousel loads and is responsive.
- [ ] Spot-check 10 pages for layout regressions and image loads.
- [ ] Run `scripts/check_broken_references.py` again and verify no new broken references.

Rollback plan
- If regressions found, revert the PR and re-run the orchestrator in dry-run locally to diagnose.
- Keep backups in `artifacts/` and FTPS backups before live writes.
