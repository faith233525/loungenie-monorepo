#!/usr/bin/env python3
import base64
import json
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('Copilot:U7GM Z9qE QOq6 MQva IzcQ 6PU2'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

PAGE_ID = 5223

replacements = {
    'Updated Lock Hardware (Current)': 'Current Lock Views Across Properties',
    'STASH lock panel and service-ready hardware views': 'A tighter set of current front-face lock views from active waterpark and resort deployments',

    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-1-April-2023-scaled.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/24-0628_PS_TyphoonTexas_WaterPark.png',
    'LounGenie updated waterproof lock panel close-up': 'Typhoon Texas waterpark lock panel and front-face unit detail',
    'Updated lock panel close-up': 'Typhoon Texas front-face detail',

    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-2-scaled.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Wild-Rivers-4-April-2023-rotated.jpg',
    'LounGenie updated lock keypad and safe door detail': 'Wild Rivers lock keypad and front-face unit detail',
    'Safe door + waterproof keypad': 'Wild Rivers front-face lock view',

    'https://www.loungenie.com/wp-content/uploads/2026/03/balmoral-2.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/massanutten-3-scaled.jpg',
    'LounGenie current lock and panel detail at active deployment': 'Massanutten WaterPark front-face lock detail with full panel visible',
    'Current lock set detail': 'Massanutten front-face detail',

    'https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-1.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Splash-Kingdom.jpg',
    'LounGenie service side lock hardware view': 'Splash Kingdom lock panel and front-face cabana unit view',
    'Service-side lock hardware': 'Splash Kingdom lock detail',

    'https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-5.jpg': 'https://www.loungenie.com/wp-content/uploads/2026/03/balmoral-2.jpg',
    'LounGenie lock panel from alternate angle': 'Balmoral Resort Florida front-face lock detail by the pool deck',
    'Alternate lock angle': 'Balmoral front-face detail',

    'https://www.loungenie.com/wp-content/uploads/2026/03/balmoral-florida-cabana-1.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Wild-Rivers-2-April-2023-rotated.jpg',
    'LounGenie full panel with updated lock and charging area': 'Wild Rivers alternate front-face detail with full lock panel visible',
    'Full panel with updated lock': 'Wild Rivers alternate angle',
}

r = requests.get(f'{BASE}/pages/{PAGE_ID}?context=edit', headers=headers, timeout=30)
r.raise_for_status()
content = r.json().get('content', {}).get('raw', '')
updated = content
for old, new in replacements.items():
    updated = updated.replace(old, new)

if updated == content:
    print('no changes')
else:
    u = requests.post(f'{BASE}/pages/{PAGE_ID}', headers=headers, data=json.dumps({'content': updated, 'status': 'publish'}), timeout=45)
    if u.status_code not in (200, 201):
        raise SystemExit(f'Update failed: HTTP {u.status_code} {u.text[:220]}')
    print('gallery lock block refreshed')
