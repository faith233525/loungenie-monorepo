Kadence + Gutenberg install guide (enterprise-grade, safe)

Overview
- Recommended stack: Kadence theme + Kadence Blocks (free) + Gutenberg (core).
- Optional paid upgrades: Kadence Pro, image optimizer (ShortPixel/EWWW/Imagify), WP Rocket.

Quick steps (preferred: wp-cli available)
1. Ensure you have a staging backup and Application Password for a deploy user.
2. From the repo root, run (dry-run first):

```powershell
pwsh .\tools\install_kadence_wpcli.ps1 -WPPath . -DryRun
```

3. Inspect the printed commands. To run for real, re-run without `-DryRun`.

If wp-cli is not available
- Use the admin UI: Appearance → Themes → Add → search "Kadence" → Install + Activate.
- Plugins: Plugins → Add New → search and install `Kadence Blocks`, `Yoast SEO` (or `Rank Math`), `Imagify`/`ShortPixel`.

Importing header/footer template-parts (safe)
- We keep template-part artifacts under `artifacts/` as JSON. Use `scripts/wp_import_template_part.py` to build a CURL command for review.
- Dry-run:

```bash
python scripts/wp_import_template_part.py artifacts/lg9_header_template_part.json
```

- To actually post (careful): export env vars and run with `--apply`:

```powershell
$env:WP_URL='https://loungenie.com/staging'
$env:WP_USER='copilot'
$env:WP_APP_PASS='APP PASSWORD HERE'
python scripts/wp_import_template_part.py artifacts/lg9_header_template_part.json --apply
```

Notes & Best Practices
- Preserve Gutenberg block JSON; do not convert to static HTML.
- Run all operations on staging first; collect audit reports before touching production.
- For SEO: configure site title, meta templates, and generate XML sitemap (Yoast/Rank Math handles this).
- For performance: optimize images on upload, enable page caching, and use a CDN for assets.

Rollback
- Keep FTP backup (tools/run_wp_ftp_and_rest_safe.ps1) and artifact exports. If content import goes wrong, restore media from backups and revert template-parts via admin or wp-cli.

Contact
- If you want, I can run the dry-run imports and plugin installs for you — provide confirmation and whether to run in CI or locally.