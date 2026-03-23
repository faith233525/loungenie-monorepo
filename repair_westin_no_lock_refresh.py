#!/usr/bin/env python3
import base64
import json
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

BROKEN = 'https://www.loungenie.com/wp-content/uploads/2026/03/175-Westin__hhi_bjp_-_low_res-1.avif'
WORKING = 'https://www.loungenie.com/wp-content/uploads/2025/10/175-Westin__hhi_bjp_-_low_res-1.avif'
DUP_ONE = 'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-scaled.jpg'
DUP_ONE_REPL = 'https://www.loungenie.com/wp-content/uploads/2026/03/105-Westin__hhi_bjp_-_low_res.webp'
DUP_TWO = 'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-4-April-2023-scaled.jpg'
DUP_TWO_REPL = 'https://www.loungenie.com/wp-content/uploads/2025/10/152-Westin__hhi_bjp_-_low_res.avif'

# Fix features broken path.
r = requests.get(f'{BASE}/pages/2989?context=edit', headers=headers, timeout=30)
r.raise_for_status()
features = r.json().get('content', {}).get('raw', '')
features_updated = features.replace(BROKEN, WORKING)
if features_updated != features:
    u = requests.post(f'{BASE}/pages/2989', headers=headers, data=json.dumps({'content': features_updated, 'status': 'publish'}), timeout=45)
    if u.status_code not in (200, 201):
        raise SystemExit(f'Features update failed: HTTP {u.status_code} {u.text[:220]}')
    print('features repaired')
else:
    print('features unchanged')

# Fix gallery broken path and remove duplicate Westin frames by replacing second occurrence of each.
r = requests.get(f'{BASE}/pages/5223?context=edit', headers=headers, timeout=30)
r.raise_for_status()
gallery = r.json().get('content', {}).get('raw', '')
updated = gallery.replace(BROKEN, WORKING)

for old, new in [(DUP_ONE, DUP_ONE_REPL), (DUP_TWO, DUP_TWO_REPL)]:
    first = updated.find(old)
    second = updated.find(old, first + 1) if first != -1 else -1
    if second != -1:
        updated = updated[:second] + new + updated[second + len(old):]

if updated != gallery:
    u = requests.post(f'{BASE}/pages/5223', headers=headers, data=json.dumps({'content': updated, 'status': 'publish'}), timeout=45)
    if u.status_code not in (200, 201):
        raise SystemExit(f'Gallery update failed: HTTP {u.status_code} {u.text[:220]}')
    print('gallery repaired and deduped')
else:
    print('gallery unchanged')
