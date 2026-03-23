#!/usr/bin/env python3
"""Restore About page from known-good prior rich revision on staging."""
import base64
import json
import re
import requests

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
HEADERS = {'Authorization': f'Basic {AUTH}', 'Content-Type': 'application/json'}
PAGE_ID = 4862
REV_ID = 8811


def get_revision_content() -> str:
    r = requests.get(f'{BASE}/pages/{PAGE_ID}/revisions/{REV_ID}', headers=HEADERS, timeout=30)
    r.raise_for_status()
    content = r.json().get('content', {})
    return content.get('raw', '') or content.get('rendered', '') or ''


def normalize_internal_links(html: str) -> str:
    # Keep media URLs untouched; only normalize navigational links.
    replacements = {
        'https://www.loungenie.com/contact-loungenie/': 'https://loungenie.com/staging/contact-loungenie/',
        'https://www.loungenie.com/poolside-amenity-unit/': 'https://loungenie.com/staging/poolside-amenity-unit/',
        'https://www.loungenie.com/cabana-installation-photos/': 'https://loungenie.com/staging/cabana-installation-photos/',
        'https://www.loungenie.com/hospitality-innovation/': 'https://loungenie.com/staging/hospitality-innovation/',
        'https://www.loungenie.com/loungenie-videos/': 'https://loungenie.com/staging/loungenie-videos/',
        'https://www.loungenie.com/': 'https://loungenie.com/staging/',
    }

    out = html
    for old, new in replacements.items():
        out = out.replace(old, new)

    # Fix occasional index.php links.
    out = re.sub(r'https://loungenie\.com/staging/index\.php/', 'https://loungenie.com/staging/', out)
    return out


def update_page(content: str) -> None:
    payload = json.dumps({'content': content, 'status': 'publish'})
    r = requests.post(f'{BASE}/pages/{PAGE_ID}', headers=HEADERS, data=payload, timeout=60)
    if r.status_code not in (200, 201):
        raise SystemExit(f'Update failed: HTTP {r.status_code} {r.text[:400]}')


def main() -> None:
    prev = get_revision_content()
    if not prev:
        raise SystemExit('Revision content is empty; aborting.')

    staged = normalize_internal_links(prev)
    update_page(staged)
    print(f'About page restored from revision {REV_ID}.')
    print('New length:', len(staged))


if __name__ == '__main__':
    main()
