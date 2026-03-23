#!/usr/bin/env python3
import requests
import base64
import os

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}

root = r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos\2018-19 Pictures'

candidates = [
    ('Cowabunga Bay VIP Pool.jpg', 'Cowabunga Bay VIP pool cabana with LounGenie amenity unit installed'),
    ('Margaritaville Grand Turk 3.jpg', 'Margaritaville Grand Turk premium seating area with LounGenie deployment'),
    ('Marriott Gaylord Texan 1.jpg', 'Marriott Gaylord Texan poolside cabana setup with LounGenie unit'),
    ('Westin Keirland - Arizona.jpg', 'Westin Kierland Arizona poolside hospitality deployment with LounGenie'),
    ('westin kierland resort.jpeg', 'Westin Kierland resort cabana environment with LounGenie unit'),
    ('Westin Las Vegas HiRes.jpg', 'Westin Las Vegas poolside premium seating area with LounGenie installation'),
    ('Yas Waterworld.jpg', 'Yas Waterworld premium cabana environment featuring LounGenie amenity system'),
    ('Yas Waterworld - March 2020.jpg', 'Yas Waterworld deployment photo showing LounGenie in waterpark setting'),
    ('Waldorf Landscape.jpg', 'Waldorf pool deck landscape with LounGenie deployment'),
    ('CHIC Hotel - Punta Cana.jpg', 'CHIC Hotel Punta Cana premium seating zone with LounGenie amenity unit'),
]

uploaded = []
for fname, alt_text in candidates:
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

    up_headers = {
        'Authorization': headers['Authorization'],
        'Content-Disposition': f'attachment; filename="{fname}"',
        'Content-Type': mime,
    }

    r = requests.post(f'{BASE}/media', headers=up_headers, data=data, timeout=60)
    if r.status_code not in (200, 201):
        print('UPLOAD_FAIL', fname, r.status_code, r.text[:180])
        continue

    media = r.json()
    media_id = media.get('id')
    src = media.get('source_url', '')
    print('UPLOADED', fname, 'ID', media_id)

    # Update alt text
    r2 = requests.post(f'{BASE}/media/{media_id}', headers={**headers, 'Content-Type': 'application/json'}, json={'alt_text': alt_text}, timeout=30)
    if r2.status_code not in (200, 201):
        print('ALT_FAIL', fname, r2.status_code)
    else:
        print('ALT_OK', fname)

    uploaded.append((fname, media_id, src, alt_text))

print('\nUPLOADED_TOTAL', len(uploaded))
for row in uploaded:
    print('::', row[0], '|', row[1], '|', row[2], '|', row[3])
