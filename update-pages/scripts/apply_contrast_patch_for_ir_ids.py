#!/usr/bin/env python3
"""
Inject a scoped contrast CSS into specific page IDs and leave them as DRAFT (so layout remains intact).
Saves before/after remote JSON to artifacts/ and reports number of pages changed.
"""
import requests
import json
from pathlib import Path

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
USER = 'copilot'
PASS = 'SBlI yPMK 5crY p3Lo FOtF M3Tw'
IDS = [9256,9257,9258,9259]
ART = Path(r'c:/Users/pools/Documents/wordpress-develop/artifacts')
ART.mkdir(parents=True, exist_ok=True)

session = requests.Session()
session.auth = (USER, PASS)
session.verify = False

patch_css = '''\n<style id="ir-contrast-patch">\n/* IR contrast patch: scoped to IR pages only */\n.ir-shell h1, .ir-shell h2, .ir-shell h3, .ir-shell h4 { color: #0d2430 !important; }\n.ir-shell p, .ir-shell li, .ir-shell a { color: #21384e !important; }\n.ir-shell .lg9 [class*="hero"] h1, .ir-shell .lg9 [class*="hero"] h2 { color: #fff !important; text-shadow: 0 1px 2px rgba(0,0,0,.32); }\n</style>\n'''

summary = {'updated': [], 'skipped': []}
for pid in IDS:
    r = session.get(f"{BASE}/pages/{pid}")
    if not r.ok:
        summary['skipped'].append({'id': pid, 'error': 'fetch_failed', 'status': r.status_code})
        continue
    page = r.json()
    before = ART / f'page_{pid}_before_contrast.json'
    before.write_text(json.dumps(page, indent=2), encoding='utf-8')
    content = page.get('content', {}).get('raw') or page.get('content', {}).get('rendered') or page.get('content', '')
    if 'id="ir-contrast-patch"' in content:
        summary['skipped'].append({'id': pid, 'reason': 'already_patched'})
        continue
    # insert patch at top of content to ensure precedence
    new_content = patch_css + '\n' + content
    payload = {'content': new_content, 'status': 'draft'}
    u = session.post(f"{BASE}/pages/{pid}", json=payload)
    if not u.ok:
        summary['skipped'].append({'id': pid, 'error': 'update_failed', 'status': u.status_code, 'text': u.text[:200]})
        continue
    updated = u.json()
    after = ART / f'page_{pid}_after_contrast.json'
    after.write_text(json.dumps(updated, indent=2), encoding='utf-8')
    summary['updated'].append(pid)

out = ART / 'ir_contrast_patch_summary.json'
out.write_text(json.dumps(summary, indent=2), encoding='utf-8')
print('Wrote summary to', out)
