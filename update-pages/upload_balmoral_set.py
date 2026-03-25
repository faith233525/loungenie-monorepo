#!/usr/bin/env python3
import base64
import os
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
auth = {'Authorization': f'Basic {creds}'}

ROOT = r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos'
FILES = [
    ('balmoral 1.jpg', 'Balmoral Resort Florida cabana installation with LounGenie unit visible'),
    ('balmoral 2.jpg', 'Balmoral Resort poolside premium seating area with LounGenie deployment'),
    ('balmoral-florida-cabana-1.jpg', 'Balmoral Florida cabana view showing LounGenie in active guest area'),
]

for fname, alt_text in FILES:
    path = os.path.join(ROOT, fname)
    if not os.path.exists(path):
        print('MISSING', path)
        continue

    with open(path, 'rb') as f:
        data = f.read()

    headers = {
        'Authorization': auth['Authorization'],
        'Content-Disposition': f'attachment; filename="{fname}"',
        'Content-Type': 'image/jpeg',
    }

    r = requests.post(f'{BASE}/media', headers=headers, data=data, timeout=90)
    if r.status_code not in (200, 201):
        print('UPLOAD_FAIL', fname, r.status_code, r.text[:220])
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

    print('UPLOADED', fname, 'ID', media_id)
    print('URL', src)
    print('ALT', 'OK' if r2.status_code in (200, 201) else f'FAIL {r2.status_code}')
