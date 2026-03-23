#!/usr/bin/env python3
import requests, base64

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}
base = 'https://www.loungenie.com/wp-json/wp/v2'

keywords = [
    'logo', 'six', 'margaritaville', 'westin', 'marriott', 'hilton', 'ritz', 'atlantis',
    'palace', 'water world', 'cowabunga', 'typhoon', 'wild rivers', 'owa', 'holiday world'
]

items = []
page = 1
while True:
    r = requests.get(f'{base}/media', headers=headers, params={'per_page': 100, 'page': page, 'orderby': 'date', 'order': 'desc'}, timeout=30)
    if r.status_code != 200:
        break
    data = r.json()
    if not data:
        break
    items.extend(data)
    if len(data) < 100:
        break
    page += 1

matches = []
for it in items:
    src = it.get('source_url', '')
    if not src:
        continue
    fname = src.split('/')[-1]
    alt = it.get('alt_text', '') or it.get('title', {}).get('rendered', '')
    hay = (fname + ' ' + alt).lower()
    if any(k in hay for k in keywords):
        w = it.get('media_details', {}).get('width', 0)
        h = it.get('media_details', {}).get('height', 0)
        matches.append((it.get('id'), w * h, w, h, src, alt, it.get('mime_type', '')))

matches.sort(key=lambda x: x[1], reverse=True)
print('matches', len(matches))
for m in matches[:200]:
    print(f"ID={m[0]} | {m[2]}x{m[3]} | {m[6]:16s} | {m[4]} | {m[5][:80]}")
