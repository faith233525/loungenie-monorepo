#!/usr/bin/env python3
import base64
import json
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}
BROKEN = 'https://www.loungenie.com/wp-content/uploads/2025/10/175-Westin__hhi_bjp_-_low_res-1.avif'
FEATURES_REPL = 'https://www.loungenie.com/wp-content/uploads/2026/03/105-Westin__hhi_bjp_-_low_res.webp'
GALLERY_REPL = 'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-1-April-2023-scaled.jpg'

for page_id, name, repl in [
    (2989, 'features', FEATURES_REPL),
    (5223, 'gallery', GALLERY_REPL),
]:
    r = requests.get(f'{BASE}/pages/{page_id}?context=edit', headers=headers, timeout=30)
    r.raise_for_status()
    content = r.json().get('content', {}).get('raw', '')
    updated = content.replace(BROKEN, repl)
    if updated == content:
        print(name, 'no changes')
        continue
    u = requests.post(f'{BASE}/pages/{page_id}', headers=headers, data=json.dumps({'content': updated, 'status': 'publish'}), timeout=45)
    if u.status_code not in (200, 201):
        raise SystemExit(f'Update failed for {name}: HTTP {u.status_code} {u.text[:220]}')
    print(name, 'updated')
