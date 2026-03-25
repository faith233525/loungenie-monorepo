#!/usr/bin/env python3
"""
Wrap IR pages content in a full-width Gutenberg Group block suitable for Kadence layout.
Preserves all inner content verbatim. Saves before/after snapshots and updates pages as DRAFT.
"""
import json
from pathlib import Path
import sys

try:
    import requests
except Exception:
    raise SystemExit('Install requests: pip install requests')

BASE = 'https://loungenie.com/staging'
USER = 'copilot'
PASS = 'SBlI yPMK 5crY p3Lo FOtF M3Tw'
PAGES = ['investors','board','financials','press']
ART = Path(r'c:/Users/pools/Documents/wordpress-develop/artifacts')
ART.mkdir(parents=True, exist_ok=True)

session = requests.Session()
session.auth = (USER, PASS)
session.verify = False

WRAPPER_OPEN = '<!-- wp:group {"align":"full","className":"ir-shell kadence-fullwidth","layout":{"inherit":true}} -->\n<div class="wp-block-group alignfull ir-shell kadence-fullwidth">\n'
WRAPPER_CLOSE = '\n</div>\n<!-- /wp:group -->\n'

summary = []
for slug in PAGES:
    print('Processing', slug)
    r = session.get(f"{BASE}/wp-json/wp/v2/pages?slug={slug}")
    if not r.ok:
        print('Failed to fetch', slug, r.status_code)
        continue
    data = r.json()
    if not data:
        print('No page for', slug)
        continue
    page = data[0]
    page_id = page['id']
    before = ART / f"{slug}_before_kadence.json"
    before.write_text(json.dumps(page, indent=2), encoding='utf-8')

    content_html = page.get('content', {}).get('raw') or page.get('content', {}).get('rendered') or page.get('content')
    # If content already contains our wrapper class, skip
    if 'ir-shell kadence-fullwidth' in str(content_html):
        print('Already wrapped; skipping', slug)
        summary.append({'slug': slug, 'skipped': True})
        continue

    # Construct new block-wrapped content using raw HTML wrapper
    new_content = WRAPPER_OPEN + str(content_html) + WRAPPER_CLOSE

    # Save local after preview
    after_local = ART / f"{slug}_after_kadence_local.html"
    after_local.write_text(new_content, encoding='utf-8')

    # Update page as DRAFT
    payload = {'content': new_content, 'status': 'draft'}
    upd = session.post(f"{BASE}/wp-json/wp/v2/pages/{page_id}", json=payload)
    if not upd.ok:
        print('Failed to update', slug, upd.status_code, upd.text)
        summary.append({'slug': slug, 'updated': False, 'error': upd.text})
        continue
    updated_page = upd.json()
    after_remote = ART / f"{slug}_after_kadence_remote.json"
    after_remote.write_text(json.dumps(updated_page, indent=2), encoding='utf-8')

    summary.append({'slug': slug, 'page_id': page_id, 'before': str(before), 'after_local': str(after_local), 'after_remote': str(after_remote), 'updated': True})

out = ART / 'ir_kadence_wrap_summary.json'
out.write_text(json.dumps(summary, indent=2), encoding='utf-8')
print('Summary written to', out)
