#!/usr/bin/env python3
"""List all media library items for OG image candidates."""
import requests, base64

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
hdrs = {'Authorization': f'Basic {creds}'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

all_media = []
page = 1
while True:
    r = requests.get(f'{BASE}/media', headers=hdrs, 
                     params={'per_page': 100, 'orderby': 'date', 'order': 'desc', 'page': page}, 
                     timeout=30)
    if r.status_code != 200:
        break
    items = r.json()
    if not items:
        break
    all_media.extend(items)
    if len(items) < 100:
        break
    page += 1

print(f"Total media items: {len(all_media)}")
print()

# Group by type
images = [m for m in all_media if m.get('mime_type','').startswith('image/')]
print(f"Images: {len(images)}")
print()
for item in images:
    src = item.get('source_url', '')
    fname = src.split('/')[-1]
    alt = item.get('alt_text', '') or item.get('title', {}).get('rendered', '')
    date = item.get('date', '')[:10]
    w = item.get('media_details', {}).get('width', 0)
    h = item.get('media_details', {}).get('height', 0)
    print(f"  {date} | {fname[:55]:<55} | {w}x{h} | {alt[:40]}")
