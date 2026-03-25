#!/usr/bin/env python3
import requests, base64

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}
base = 'https://www.loungenie.com/wp-json/wp/v2'

page = 1
rows = []
while True:
    r = requests.get(f'{base}/media', headers=headers, params={'per_page': 100, 'page': page, 'orderby': 'date', 'order': 'desc'}, timeout=30)
    if r.status_code != 200:
        break
    items = r.json()
    if not items:
        break
    for it in items:
        src = it.get('source_url', '')
        if '/2026/' not in src:
            continue
        mime = it.get('mime_type', '')
        if not mime.startswith('image/'):
            continue
        fname = src.split('/')[-1]
        alt = it.get('alt_text', '') or it.get('title', {}).get('rendered', '')
        w = it.get('media_details', {}).get('width', 0)
        h = it.get('media_details', {}).get('height', 0)
        rows.append((it.get('id'), w*h, w, h, src, alt))
    if len(items) < 100:
        break
    page += 1

rows.sort(key=lambda x: x[1], reverse=True)
print('2026_image_count', len(rows))
for r in rows[:220]:
    print(f'ID={r[0]} {r[2]}x{r[3]} | {r[4]} | {r[5][:90]}')
