#!/usr/bin/env python3
"""
Apply Kadence spacing/layout tweaks to pages by ID.
Wrap each top-level child inside the existing `ir-shell kadence-fullwidth` wrapper
with a group block that enforces padding and provides a consistent row class.

Leaves pages as DRAFT. Saves before/after snapshots in artifacts/.
"""
import json
from pathlib import Path
import re

try:
    import requests
    from bs4 import BeautifulSoup, Tag
except Exception:
    raise SystemExit('Install requests and beautifulsoup4')

BASE = 'https://loungenie.com/staging'
USER = 'copilot'
PASS = 'SBlI yPMK 5crY p3Lo FOtF M3Tw'
IDS = [9256,9257,9258,9259]
ART = Path(r'c:/Users/pools/Documents/wordpress-develop/artifacts')
ART.mkdir(parents=True, exist_ok=True)

session = requests.Session()
session.auth = (USER, PASS)
session.verify = False

# block wrappers
ROW_OPEN = '<!-- wp:group {"className":"kadence-row","layout":{"contentSize":"1200px"},"style":{"spacing":{"padding":{"top":"48px","bottom":"48px"}}}} -->\n<div class="wp-block-group kadence-row" style="padding-top:48px;padding-bottom:48px">\n'
ROW_CLOSE = '\n</div>\n<!-- /wp:group -->\n'

summary = []
for pid in IDS:
    print('Tweaking page', pid)
    r = session.get(f"{BASE}/wp-json/wp/v2/pages/{pid}")
    if not r.ok:
        summary.append({'id': pid, 'error': 'fetch_failed', 'status': r.status_code})
        continue
    page = r.json()
    before_file = ART / f'page_{pid}_before_kadence_tweaks.json'
    before_file.write_text(json.dumps(page, indent=2), encoding='utf-8')

    content = page.get('content', {}).get('raw') or page.get('content', {}).get('rendered') or page.get('content')
    soup = BeautifulSoup(content, 'html.parser')
    wrapper = soup.select_one('.ir-shell.kadence-fullwidth') or soup.select_one('.ir-shell')
    if not wrapper:
        # attempt to wrap whole content
        new_content = ROW_OPEN + str(soup) + ROW_CLOSE
        applied = True
    else:
        applied = False
        # iterate over top-level children and wrap those that are Tag
        children = [c for c in list(wrapper.contents)]
        new_html_parts = []
        for c in children:
            if isinstance(c, Tag):
                # skip if already has kadence-row class
                cls = c.get('class') or []
                if 'kadence-row' in cls:
                    new_html_parts.append(str(c))
                else:
                    new_html_parts.append(ROW_OPEN + str(c) + ROW_CLOSE)
                    applied = True
            else:
                # preserve strings/comments
                new_html_parts.append(str(c))
        # replace wrapper content
        wrapper.clear()
        for part in new_html_parts:
            # append parsed fragment
            frag = BeautifulSoup(part, 'html.parser')
            for node in frag.contents:
                wrapper.append(node)
        new_content = str(soup)

    after_local = ART / f'page_{pid}_after_kadence_tweaks_local.html'
    after_local.write_text(new_content, encoding='utf-8')

    if not applied:
        summary.append({'id': pid, 'updated': False, 'reason': 'already_tweaked_or_no_wrapper', 'before': str(before_file), 'after_local': str(after_local)})
        continue

    payload = {'content': new_content, 'status': 'draft'}
    upd = session.post(f"{BASE}/wp-json/wp/v2/pages/{pid}", json=payload)
    if not upd.ok:
        summary.append({'id': pid, 'updated': False, 'error': upd.text})
        continue
    updated = upd.json()
    after_remote = ART / f'page_{pid}_after_kadence_tweaks_remote.json'
    after_remote.write_text(json.dumps(updated, indent=2), encoding='utf-8')
    summary.append({'id': pid, 'updated': True, 'before': str(before_file), 'after_local': str(after_local), 'after_remote': str(after_remote)})

out = ART / 'apply_kadence_tweaks_summary.json'
out.write_text(json.dumps(summary, indent=2), encoding='utf-8')
print('Summary written to', out)
