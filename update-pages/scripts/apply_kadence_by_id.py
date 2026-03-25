#!/usr/bin/env python3
"""
Wrap pages by ID in a full-width Gutenberg Group block (Kadence-ready).
Targets draft IDs: 9256-9259 (Investors, Board, Financials, Press)
Saves before/after snapshots to artifacts/ and updates pages as DRAFT.
"""
import json
from pathlib import Path
import requests

BASE = 'https://loungenie.com/staging'
USER = 'copilot'
PASS = 'SBlI yPMK 5crY p3Lo FOtF M3Tw'
IDS = [9256,9257,9258,9259]
ART = Path(r'c:/Users/pools/Documents/wordpress-develop/artifacts')
ART.mkdir(parents=True, exist_ok=True)

session = requests.Session()
session.auth = (USER, PASS)
session.verify = False

WRAPPER_OPEN = '<!-- wp:group {"align":"full","className":"ir-shell kadence-fullwidth","layout":{"inherit":true}} -->\n<div class="wp-block-group alignfull ir-shell kadence-fullwidth">\n'
WRAPPER_CLOSE = '\n</div>\n<!-- /wp:group -->\n'

summary = []
for pid in IDS:
    print('Processing page id', pid)
    r = session.get(f"{BASE}/wp-json/wp/v2/pages/{pid}")
    if not r.ok:
        print('Failed to fetch', pid, r.status_code)
        summary.append({'id': pid, 'error': True})
        continue
    page = r.json()
    before_file = ART / f'page_{pid}_before.json'
    before_file.write_text(json.dumps(page, indent=2), encoding='utf-8')
    content = page.get('content', {}).get('raw') or page.get('content', {}).get('rendered') or page.get('content')
    if 'ir-shell kadence-fullwidth' in str(content):
        print('Already wrapped:', pid)
        summary.append({'id': pid, 'skipped': True})
        continue
    new_content = WRAPPER_OPEN + str(content) + WRAPPER_CLOSE
    after_local = ART / f'page_{pid}_after_local.html'
    after_local.write_text(new_content, encoding='utf-8')
    payload = {'content': new_content, 'status': 'draft'}
    upd = session.post(f"{BASE}/wp-json/wp/v2/pages/{pid}", json=payload)
    if not upd.ok:
        print('Failed to update', pid, upd.status_code, upd.text)
        summary.append({'id': pid, 'updated': False, 'error': upd.text})
        continue
    updated = upd.json()
    after_remote = ART / f'page_{pid}_after_remote.json'
    after_remote.write_text(json.dumps(updated, indent=2), encoding='utf-8')
    summary.append({'id': pid, 'updated': True, 'before': str(before_file), 'after_local': str(after_local), 'after_remote': str(after_remote)})

out = ART / 'apply_kadence_by_id_summary.json'
out.write_text(json.dumps(summary, indent=2), encoding='utf-8')
print('Summary written to', out)
