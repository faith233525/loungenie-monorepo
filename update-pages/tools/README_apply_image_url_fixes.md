apply_image_url_fixes.sh — README

What this script does

- Exports a timestamped DB backup using `wp db export`.
- Runs WP-CLI `search-replace` patterns in dry-run mode and writes results to a timestamped file.
- If run with `--apply`, performs the replacements, flushes WP cache and transients.

Where to run

- SSH into the web server and `cd` to the WordPress site root (the directory containing `wp-config.php`).
- Ensure `wp` (WP-CLI) is installed and accessible.

Usage

Dry-run only (recommended first):

```bash
bash tools/apply_image_url_fixes.sh
```

Apply changes (only after reviewing dry-run output):

```bash
bash tools/apply_image_url_fixes.sh --apply
```

Files created by the script

- DB backup: `loungenie-db-backup-<timestamp>.sql` (in the current directory)
- Dry-run output: `loungenie-searchreplace-dryrun-<timestamp>.txt`
- Apply log (if --apply): `loungenie-searchreplace-apply-<timestamp>.txt`

Rollback

If you need to restore the DB backup:

```bash
wp db import loungenie-db-backup-<timestamp>.sql
wp cache flush
```

Notes & cautions

- The script uses `--skip-columns=guid` to avoid changing attachment GUIDs. If you need GUID changes, modify the script intentionally.
- If you use a CDN (Cloudflare, etc.), purge the CDN after applying changes.
- After applying, purge LiteSpeed and CDN caches and re-run the `tools/crawl_images_headers.py` locally to confirm 404s are fixed.
