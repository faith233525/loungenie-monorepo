"""
Validate media_lookup.json against a WordPress site.
Writes outputs/validate_media_report.json with the status of each mapped attachment ID.

Usage:
  python scripts/validate_media_lookup.py --media-file media_lookup.json

Auth:
- If `WP_USERNAME` and `WP_APP_PASSWORD` env vars are set they will be used for Basic auth.
- `WP_SITE_URL` env var can override the site URL; otherwise pass `--site-url`.
"""
import os
import json
import argparse
import requests
from pathlib import Path

OUT_DIR = Path('outputs')
OUT_DIR.mkdir(exist_ok=True)


def check_media(site_url, mid, auth=None):
    url = site_url.rstrip('/') + f"/wp-json/wp/v2/media/{mid}"
    try:
        r = requests.get(url, auth=auth, timeout=15)
        return r.status_code, r.json() if r.status_code == 200 else None
    except Exception as e:
        return None, str(e)


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--media-file', '-m', default='media_lookup.json')
    p.add_argument('--site-url', '-s', default=os.environ.get('WP_SITE_URL'))
    args = p.parse_args()

    if not args.site_url:
        print('ERROR: site URL required via --site-url or WP_SITE_URL env var')
        return

    media_path = Path(args.media_file)
    if not media_path.exists():
        print(f'ERROR: media file not found: {media_path}')
        return

    with media_path.open('r', encoding='utf-8') as fh:
        mapping = json.load(fh)

    username = os.environ.get('WP_USERNAME')
    app_password = os.environ.get('WP_APP_PASSWORD')
    auth = (username, app_password) if username and app_password else None

    report = {'site_url': args.site_url, 'checked': [], 'missing': [], 'errors': []}

    for filename, mid in mapping.items():
        status, data = check_media(args.site_url, mid, auth=auth)
        entry = {'filename': filename, 'attachment_id': mid, 'status': status}
        if status == 200:
            entry['source_url'] = data.get('source_url') if isinstance(data, dict) else None
            report['checked'].append(entry)
        elif status is None:
            entry['error'] = data
            report['errors'].append(entry)
        else:
            report['missing'].append(entry)

    out_path = OUT_DIR / 'validate_media_report.json'
    with out_path.open('w', encoding='utf-8') as fh:
        json.dump(report, fh, indent=2)

    print(f"Validation complete. {len(report['checked'])} OK, {len(report['missing'])} missing, {len(report['errors'])} errors.")
    print(f"Report saved to {out_path}")


if __name__ == '__main__':
    main()
