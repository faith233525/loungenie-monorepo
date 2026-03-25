#!/usr/bin/env python3
import base64
import json
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}
PAGE_ID = 5223
DUP = 'https://www.loungenie.com/wp-content/uploads/2025/10/massanutten-3-scaled.jpg'
REPL = 'https://www.loungenie.com/wp-content/uploads/2026/03/balmoral-2.jpg'

r = requests.get(f'{BASE}/pages/{PAGE_ID}?context=edit', headers=headers, timeout=30)
r.raise_for_status()
content = r.json().get('content', {}).get('raw', '')
first = content.find(DUP)
second = content.find(DUP, first + 1)
if first == -1 or second == -1:
    print('duplicate not found twice')
else:
    updated = content[:second] + REPL + content[second + len(DUP):]
    u = requests.post(f'{BASE}/pages/{PAGE_ID}', headers=headers, data=json.dumps({'content': updated, 'status': 'publish'}), timeout=45)
    if u.status_code not in (200, 201):
        raise SystemExit(f'Update failed: HTTP {u.status_code} {u.text[:220]}')
    print('second duplicate replaced with balmoral-2')
