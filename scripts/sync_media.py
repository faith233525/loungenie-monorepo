#!/usr/bin/env python3
"""
Sync local media files to WordPress via REST API.

Features:
- Dry-run mode (default) that prints planned uploads
- Auto-detects source directory `artifacts/uploads` or `uploads`
- Uploads files via WP REST `wp/v2/media` using Application Passwords
- Writes upload mapping to `artifacts/media_uploads.json`

Usage:
  python scripts/sync_media.py --source artifacts/uploads --dry-run

Environment variables:
  WP_URL - base site URL, e.g. https://loungenie.com/staging
  WP_USER - WP username for application password
  WP_APP_PASS - application password
  DRY_RUN - if 'true' will not perform uploads

"""
import argparse
import base64
import json
import os
import sys
from pathlib import Path

try:
    import requests
except Exception:
    print('Missing dependency: requests. Install with: pip install -r requirements.txt')
    raise


def find_source(dir_hint: str = None):
    cand = []
    if dir_hint:
        cand.append(Path(dir_hint))
    cand.append(Path('artifacts') / 'uploads')
    cand.append(Path('uploads'))
    for p in cand:
        if p.exists() and p.is_dir():
            return p
    return None


def upload_file(wp_url, auth, filepath: Path):
    media_endpoint = wp_url.rstrip('/') + '/wp-json/wp/v2/media'
    headers = {'Content-Disposition': f'attachment; filename="{filepath.name}"'}
    with open(filepath, 'rb') as fh:
        files = {'file': (filepath.name, fh)}
        r = requests.post(media_endpoint, auth=auth, files=files, headers=headers)
    return r


def main():
    p = argparse.ArgumentParser(description='Sync media files to WP')
    p.add_argument('--source', default=None)
    p.add_argument('--dry-run', action='store_true')
    args = p.parse_args()

    src = find_source(args.source)
    if not src:
        print('No source upload directory found (looked in artifacts/uploads and uploads).')
        sys.exit(1)

    files = [f for f in sorted(src.glob('**/*')) if f.is_file()]
    if not files:
        print(f'No files found in {src}')
        return

    dry_env = os.environ.get('DRY_RUN', 'false').lower() == 'true'
    dry = args.dry_run or dry_env

    wp_url = os.environ.get('WP_URL')
    wp_user = os.environ.get('WP_USER')
    wp_app = os.environ.get('WP_APP_PASS')

    if not wp_url or not wp_user or not wp_app:
        print('Warning: WP_URL, WP_USER, or WP_APP_PASS not set. Running in dry-run only.')
        dry = True

    print(f'Found {len(files)} files in {src} — dry-run={dry}')

    uploads = []
    auth = (wp_user, wp_app) if not dry else None

    for fp in files:
        rel = fp.relative_to(src)
        print(f'Processing: {rel}')
        if dry:
            print(f'  Would upload: {fp.name} -> {wp_url or "<no-wp-url>"}')
            uploads.append({'file': str(rel), 'status': 'dry-run'})
            continue

        try:
            r = upload_file(wp_url, auth, fp)
        except Exception as e:
            print(f'  ERROR uploading {fp.name}: {e}')
            uploads.append({'file': str(rel), 'status': 'error', 'error': str(e)})
            continue

        if r.status_code in (200, 201):
            j = r.json()
            print(f'  Uploaded: id={j.get("id")} url={j.get("source_url")}')
            uploads.append({'file': str(rel), 'status': 'uploaded', 'id': j.get('id'), 'url': j.get('source_url')})
        else:
            print(f'  Failed: {r.status_code} {r.text}')
            uploads.append({'file': str(rel), 'status': 'failed', 'code': r.status_code, 'text': r.text})

    outdir = Path('artifacts')
    outdir.mkdir(parents=True, exist_ok=True)
    outpath = outdir / 'media_uploads.json'
    with open(outpath, 'w', encoding='utf-8') as fh:
        json.dump({'files': uploads}, fh, indent=2)
    print(f'Wrote report to {outpath}')


if __name__ == '__main__':
    main()
