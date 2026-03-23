#!/usr/bin/env python3
import base64
import json
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

r = requests.get(f'{BASE}/pages/5223?context=edit', headers=headers, timeout=30)
r.raise_for_status()
content = r.json().get('content', {}).get('raw', '')

replacements = {
    'alt="Yas Waterworld with LounGenie unit visible"': 'alt="Westin Hilton Head with LounGenie unit visible"',
    'alt="Yas Waterworld deployment showing LounGenie"': 'alt="The Grove Resort deployment showing LounGenie"',
    '>Yas Waterworld<': '>Westin Hilton Head<',
    '>Yas Waterworld March set<': '>The Grove Resort view<',
    'yas waterworld with loungenie unit visible': 'Westin Hilton Head with LounGenie unit visible',
    'yas waterworld deployment showing loungenie': 'The Grove Resort deployment showing LounGenie',
    'yas waterworld march set': 'The Grove Resort view',
    'yas waterworld': 'Westin Hilton Head',
}

updated = content
for old, new in replacements.items():
    updated = updated.replace(old, new)

if updated == content:
    print('no changes')
else:
    u = requests.post(f'{BASE}/pages/5223', headers=headers, data=json.dumps({'content': updated, 'status': 'publish'}), timeout=40)
    if u.status_code not in (200, 201):
        raise SystemExit(f'Update failed: HTTP {u.status_code} {u.text[:220]}')
    print('gallery text updated')
