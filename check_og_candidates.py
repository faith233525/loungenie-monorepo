#!/usr/bin/env python3
"""Check featured media and find media IDs for OG image candidates."""
import requests, base64, json

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
hdrs = {'Authorization': f'Basic {creds}'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

# Check featured_media for pages needing OG images
print("Featured media status:")
for pid, name in [(5285,'videos'),(5686,'financials'),(5716,'press'),(4862,'about')]:
    r = requests.get(f'{BASE}/pages/{pid}', headers=hdrs, 
                     params={'_fields':'id,slug,featured_media'}, timeout=20)
    d = r.json()
    print(f"  {name} ({pid}): featured_media={d.get('featured_media')}")

# Find IDs for candidate OG images
img_candidates = [
    'Screenshot-2026-03-11-210110',
    'Sea-World-San-Diego-Edited',
    'CB-Clam-1-scaled',
    'mc-mcowc-16683_Classic-Hor',
    'Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-2-scaled',
    'The-Grove-7-scaled',
    'Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-3-scaled',
    'Water-World-Cabana',
    'Sea-World-San-Diego.jpg',
    'IMG_9627',
    'IMG_9628',
    'IMG_9613',
    '105-Westin',
]
print("\nMedia IDs for candidates:")
page = 1
found = {}
while len(found) < len(img_candidates):
    r2 = requests.get(f'{BASE}/media', headers=hdrs,
                      params={'per_page': 100, 'orderby': 'date', 'order': 'desc', 'page': page}, 
                      timeout=30)
    items = r2.json()
    if not items:
        break
    for item in items:
        src = item.get('source_url', '')
        fname = src.split('/')[-1]
        for nm in img_candidates:
            if nm in fname and nm not in found:
                found[nm] = {'id': item['id'], 'url': src, 'w': item.get('media_details',{}).get('width',0), 'h': item.get('media_details',{}).get('height',0)}
                print(f"  {fname}: ID={item['id']} ({item.get('media_details',{}).get('width',0)}x{item.get('media_details',{}).get('height',0)})")
    if len(items) < 100:
        break
    page += 1
