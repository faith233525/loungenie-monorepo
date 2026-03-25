#!/usr/bin/env python3
import requests
import base64
import json
import re

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

r = requests.get(f'{BASE}/pages/4701', headers=headers, timeout=30)
r.raise_for_status()
content = r.json().get('content', {}).get('rendered', '')

# In duplicated aria-hidden logo set, switch eager to lazy to reduce unnecessary early requests.
pattern = re.compile(r'(<div class="lg9-logo-set" role="group" aria-hidden="true">)(.*?)(</div>)', re.S)
m = pattern.search(content)
if not m:
    print('No hidden duplicate logo set found; no changes applied.')
    raise SystemExit(0)

block = m.group(2)
block_new = block.replace(' loading="eager"', ' loading="lazy"')

new_content = content[:m.start(2)] + block_new + content[m.end(2):]

u = requests.post(f'{BASE}/pages/4701', headers=headers, data=json.dumps({'content': new_content, 'status': 'publish'}), timeout=40)
if u.status_code not in (200, 201):
    raise SystemExit(f'Update failed: HTTP {u.status_code} {u.text[:220]}')

print('Home logo loading tuned: duplicate hidden set now lazy-loaded.')
