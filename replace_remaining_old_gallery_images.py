#!/usr/bin/env python3
import requests
import base64
import re
import json

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
PAGE_ID = 5223
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

replacement_pool = [
    'https://www.loungenie.com/wp-content/uploads/2026/03/Marriott-Gaylord-Texan-2-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Marriott-Gaylord-Texan-3-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Marriott-Gaylord-Texan-4-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Marriott-Gaylord-Texan-5-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Hilton-Waikoloa-Village-daybed-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Sea-World-San-Diego-1.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/waterpark-copy.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/PoolSafe-Makai-Pool.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/PoolSafe-Hilton.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/CB-VIP-scaled.jpg',
]

r = requests.get(f'{BASE}/pages/{PAGE_ID}', headers=headers, timeout=30)
r.raise_for_status()
content = r.json().get('content', {}).get('rendered', '')
imgs = re.findall(r'https://www\.loungenie\.com/wp-content/uploads/[^\s"\'<>]+\.(?:jpe?g|png|webp|avif)', content, re.I)
old_urls = sorted(set([u for u in imgs if '/2025/' in u or '/2024/' in u or '/2023/' in u]))
current = set(imgs)

print('remaining_old', len(old_urls))
for u in old_urls:
    print(' old:', u)

pool = [u for u in replacement_pool if u not in current]
print('replacement_pool_available', len(pool))

replace_map = {}
for old, new in zip(old_urls, pool):
    replace_map[old] = new

new_content = content
for old, new in replace_map.items():
    new_content = new_content.replace(old, new)

u = requests.post(f'{BASE}/pages/{PAGE_ID}', headers=headers, data=json.dumps({'content': new_content, 'status': 'publish'}), timeout=40)
if u.status_code not in (200, 201):
    raise SystemExit(f'Update failed: HTTP {u.status_code} {u.text[:220]}')

print('replaced', len(replace_map))
for old, new in replace_map.items():
    print('  OLD', old)
    print('  NEW', new)
