Remediation summary — offline audits

- Source artifacts scanned: 1,027 JSON payload files
- Files containing image references: 337
- Total image references found: 36,221
- Images missing ALT (heuristic): 35,412
- Duplicate artifacts report: artifacts/duplicate_report.txt

Top recommended actions (priority order):

1. Reconcile duplicate artifacts
   - Use `artifacts/duplicate_report.txt` to pick canonical artifact IDs/slugs/titles.
   - Produce a canonical `navigation_payload.json` mapping and stage for review before applying.

2. ALT text remediation (scriptable)
   - Strategy A (safe auto-fill): fill missing ALT with the image filename (human-friendly), page title, or surrounding block label.
   - Strategy B (defer): leave as-is and mark for manual review for critical pages (home, investor, press pages).

3. Image sizing & optimization
   - Detect images > 2000px on longest side or > 1.5MB and add to `artifacts/oversized_images.csv` (scriptable).
   - Suggest WebP non-destructive copies for staging only; keep original files as backups.

4. Template-part import
   - The REST attempted import returned: `rest_cannot_manage_templates` (401). To import template-parts you need an Application Password with template-management capability or an admin-level user.
   - Do not rotate App Passwords; either provide an admin App Password in CI secrets or run `tools/run_full_staging_sync.ps1` locally and approve live runs.

5. CI & gating
   - The new workflow `.github/workflows/dry_run_sync.yml` runs the orchestrator in dry-run and uploads artifacts. Add secrets and protect the `staging` environment to gate live runs.

6. Manual QA
   - After template-part apply (dry-run verified), QA header/footer appearance across breakpoints, publisher logos in the carousel, and Kadence block rendering fidelity.

Next artifacts produced by this run:
- `artifacts/duplicate_report.txt` (existing)
- `artifacts/image_audit.json` (existing)
- `artifacts/media_uploads.json` (existing, dry-run)
- `.github/workflows/dry_run_sync.yml` (new)
- `docs/DEPLOY_CHECKLIST.md` (new)
- `artifacts/remediation_summary.md` (new)

If you want, I can now:
- Propose a canonical mapping for navigation from `artifacts/duplicate_report.txt`.
- Add an ALT auto-fill script that generates a patchable CSV and a WP import payload for review.
- Create a branch and open a PR with these files and a short description (I can push if you grant me permission or you can push locally).
