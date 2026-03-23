#!/usr/bin/env python3
import requests
import base64
import re
import json

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
PAGE_ID = 5223
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

# Preferred fresh replacements (2026 only, real deployment-oriented)
candidates = [
    'https://www.loungenie.com/wp-content/uploads/2026/03/Cowabunga-Bay-VIP-Pool-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Margaritaville-Grand-Turk-3.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Marriott-Gaylord-Texan-1-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Westin-Keirland-Arizona.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/westin-kierland-resort.jpeg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Westin-Las-Vegas-HiRes-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Yas-Waterworld.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Yas-Waterworld-March-2020.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Waldorf-Landscape-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/CHIC-Hotel-Punta-Cana.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3241-scaled-1.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/298872056_10158994266838325_2480796936934944436_n.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/211708765_1435082566848706_4544959861557533268_n.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/38248750_10155507900317751_710145023890423808_n.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/a5ea38b9-4578-4356-a118-f168caa0ec90.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/38f4fc95-7925-4625-b0e8-5ba78771c037.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Sea-World-San-Diego-Edited.webp',
    'https://www.loungenie.com/wp-content/uploads/2026/03/e106d1a0-f868-46cd-92f8-457dc6a8f698.webp',
    'https://www.loungenie.com/wp-content/uploads/2026/03/DSC0-1024x682-1.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/DSC05-1024x682-1.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/page_1145__mg_6277-copy-1-web.webp',
    'https://www.loungenie.com/wp-content/uploads/2026/03/page_1145_img_6227-copy-1-web.webp',
]

r = requests.get(f'{BASE}/pages/{PAGE_ID}', headers=headers, timeout=30)
r.raise_for_status()
page = r.json()
content = page.get('content', {}).get('rendered', '')

all_imgs = re.findall(r'https://www\.loungenie\.com/wp-content/uploads/[^\s"\'<>]+\.(?:jpe?g|png|webp|avif)', content, re.I)
current_set = set(all_imgs)
old_urls = sorted(set([u for u in all_imgs if '/2025/' in u or '/2024/' in u or '/2023/' in u]))

print('old_urls_found', len(old_urls))
for u in old_urls:
    print(' old:', u)

pool = [u for u in candidates if u not in current_set]
print('fresh_candidate_pool', len(pool))

if len(pool) < len(old_urls):
    print('WARNING: not enough unique fresh replacements; will replace as many as possible')

replace_map = {}
for old, new in zip(old_urls, pool):
    replace_map[old] = new

new_content = content
for old, new in replace_map.items():
    new_content = new_content.replace(old, new)

u = requests.post(f'{BASE}/pages/{PAGE_ID}', headers=headers, data=json.dumps({'content': new_content, 'status': 'publish'}), timeout=40)
if u.status_code not in (200, 201):
    raise SystemExit(f'Update failed: HTTP {u.status_code} {u.text[:260]}')

print('replaced_count', len(replace_map))
for old, new in replace_map.items():
    print('REPLACED')
    print('  OLD', old)
    print('  NEW', new)
