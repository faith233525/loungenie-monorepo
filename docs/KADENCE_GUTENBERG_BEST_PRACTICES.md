# Kadence + Gutenberg Best Practices

Purpose: provide clear, repeatable, non-destructive steps for restoring and improving header/navigation and page template-parts while preserving Gutenberg / Kadence block markup.

Principles
- Always operate on a dry-run first and produce artifacts for review.
- Preserve raw block markup (do not convert to HTML-only unless necessary).
- Use WP REST template-parts endpoints for imports when the target site permits `manage_templates` capability.
- Prefer smaller, incremental PRs that modify template-parts and theme CSS; avoid mass content rewrites.

Recommended Workflow
1. Run orchestrator in dry-run to gather artifacts (`artifacts/`) and logs.
2. Review `artifacts/canonical_navigation_mapping.json` and `artifacts/remediation_summary.md`.
3. Create a feature branch `feature/staging-remediations` and commit PR-ready files:
   - JSON exports of template-parts in `wp-rest-imports/`
   - CSS and Kadence block pattern updates in `assets/`
   - A short patch/notes file describing the intended REST import and page updates in `patches/`
4. Open PR for review. CI can run the dry-run workflow and attach artifacts to the PR.
5. After review, enable live run by setting repository secret `ALLOW_LIVE=true` and required credentials (or run locally with stored secrets).

Template-part Import Notes
- Use `wp/v2/template-parts` or theme-specific REST endpoints to import template-parts. If the REST responds with `rest_cannot_manage_templates`, you need an App Password with template capabilities or an admin-level credential.
- When importing, send the raw `content` field containing the Gutenberg block markup (do not inline-serialize blocks into HTML).

Kadence-specific tips
- For header/navigation duplicates: export current `header` and `navigation` template-parts, dedupe menu items locally (see `scripts/find_artifact_duplicates.py`), then reimport.
- Use Kadence `kc-row`/`kc-column` block classes and avoid manual inline styles where possible — use theme CSS variables.

Accessibility
- Ensure every image block includes `alt` text; audit suggests many missing alts. Use `artifacts/alt_suggestions.csv` for batch remediation.
- Check color contrast for header text against background using `audit_colors.py` outputs.

Rollback
- Keep backups of original template-part JSON in `backups/template-parts/` before any live apply.

Files produced by orchestrator (inspect before applying):
- `artifacts/image_audit.json`
- `artifacts/alt_suggestions.csv`
- `artifacts/canonical_navigation_mapping.json`
- `artifacts/remediation_summary.md`

Contact: maintainers should run local orchestrator if secrets are not stored in the repo.
