#!/usr/bin/env python3
import requests
import base64
import os

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
auth = {'Authorization': f'Basic {creds}'}

root = r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos\2018-19 Pictures'

files = [
    ('Marriott Gaylord Texan 2.jpg', 'Marriott Gaylord Texan pool cabana with LounGenie installation'),
    ('Marriott Gaylord Texan 3.jpg', 'Marriott Gaylord Texan premium seating area with LounGenie unit'),
    ('Marriott Gaylord Texan 4.jpg', 'Marriott Gaylord Texan deployment showing LounGenie in use'),
    ('Marriott Gaylord Texan 5.jpg', 'Marriott Gaylord Texan hospitality installation with LounGenie'),
    ('Hilton Waikoloa Village daybed.jpg', 'Hilton Waikoloa Village daybed area with LounGenie unit installed'),
    ('Sea World San Diego.JPG', 'SeaWorld San Diego cabana deployment with LounGenie amenity system'),
    ('waterpark-copy.jpg', 'Waterpark cabana setting featuring LounGenie poolside amenity unit'),
    ('PoolSafe-Makai Pool.jpg', 'Makai pool deployment showing LounGenie in premium seating area'),
    ('PoolSafe-Hilton.jpg', 'Hilton poolside deployment featuring LounGenie smart cabana unit'),
    ('CB VIP.jpg', 'Cowabunga Bay VIP area with LounGenie installation'),
]

uploaded = []
for fname, alt_text in files:
    p = os.path.join(root, fname)
    if not os.path.exists(p):
        print('MISSING', p)
        continue

    with open(p, 'rb') as f:
        data = f.read()

    mime = 'image/jpeg'
    low = fname.lower()
    if low.endswith('.png'):
        mime = 'image/png'
    elif low.endswith('.webp'):
        mime = 'image/webp'
    elif low.endswith('.avif'):
        mime = 'image/avif'

    headers = {
        'Authorization': auth['Authorization'],
        'Content-Disposition': f'attachment; filename="{fname}"',
        'Content-Type': mime,
    }

    r = requests.post(f'{BASE}/media', headers=headers, data=data, timeout=70)
    if r.status_code not in (200, 201):
        print('UPLOAD_FAIL', fname, r.status_code, r.text[:180])
        continue

    media = r.json()
    media_id = media.get('id')
    src = media.get('source_url', '')

    r2 = requests.post(
        f'{BASE}/media/{media_id}',
        headers={'Authorization': auth['Authorization'], 'Content-Type': 'application/json'},
        json={'alt_text': alt_text},
        timeout=30,
    )

    print('UPLOADED', fname, 'ID', media_id, 'ALT', 'OK' if r2.status_code in (200, 201) else 'FAIL')
    uploaded.append((fname, src))

print('\nUPLOADED_TOTAL', len(uploaded))
for fname, src in uploaded:
    print('::', fname, '|', src)
