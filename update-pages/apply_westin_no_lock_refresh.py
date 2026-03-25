#!/usr/bin/env python3
import base64
import json
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

WESTIN_SET = [
    'https://www.loungenie.com/wp-content/uploads/2026/03/175-Westin__hhi_bjp_-_low_res-1.avif',
    'https://www.loungenie.com/wp-content/uploads/2025/10/152-Westin__hhi_bjp_-_low_res.jpg',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-4-April-2023-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-2-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-1-scaled.jpg',
]

GALLERY_REPLACEMENTS = {
    'Current Lock Views Across Properties': 'Westin Hilton Head Deployment Views',
    'A tighter set of current front-face lock views from active waterpark and resort deployments': 'Recent Westin Hilton Head deployment views that keep the unit in context without relying on older lock-detail framing',
    'https://www.loungenie.com/wp-content/uploads/2025/10/24-0628_PS_TyphoonTexas_WaterPark.png': WESTIN_SET[0],
    'Typhoon Texas waterpark lock panel and front-face unit detail': 'Westin Hilton Head premium lounger deployment with LounGenie in poolside service position',
    'Typhoon Texas front-face detail': 'Poolside lounger placement',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Wild-Rivers-4-April-2023-rotated.jpg': WESTIN_SET[1],
    'Wild Rivers lock keypad and front-face unit detail': 'Westin Hilton Head cabana interior with LounGenie integrated into the premium seating setup',
    'Wild Rivers front-face lock view': 'Cabana interior setup',
    'https://www.loungenie.com/wp-content/uploads/2025/10/massanutten-3-scaled.jpg': WESTIN_SET[2],
    'Massanutten WaterPark front-face lock detail with full panel visible': 'Westin Hilton Head pool deck installation with unit visible in guest circulation space',
    'Massanutten front-face detail': 'Pool deck deployment',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Splash-Kingdom.jpg': WESTIN_SET[3],
    'Splash Kingdom lock panel and front-face cabana unit view': 'Westin Hilton Head resort seating zone with LounGenie alongside premium loungers',
    'Splash Kingdom lock detail': 'Resort seating zone',
    'https://www.loungenie.com/wp-content/uploads/2026/03/balmoral-2.jpg': WESTIN_SET[4],
    'Balmoral Resort Florida front-face lock detail by the pool deck': 'Westin Hilton Head side-angle deployment showing guest-facing placement',
    'Balmoral front-face detail': 'Side-angle deployment',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Wild-Rivers-2-April-2023-rotated.jpg': WESTIN_SET[5],
    'Wild Rivers alternate front-face detail with full lock panel visible': 'Westin Hilton Head in-cabana deployment with the unit tucked beside premium seating',
    'Wild Rivers alternate angle': 'In-cabana deployment',
    'front-face': 'deployment',
    'lock panel': 'unit',
}

FEATURES_REPLACEMENTS = {
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-1-April-2023-scaled.jpg': WESTIN_SET[0],
    'alt="LounGenie STASH — built-in waterproof locking safe with waterproof keypad"': 'alt="Westin Hilton Head deployment showing LounGenie positioned beside premium loungers"',
}

for page_id, name, replacements in [
    (2989, 'features', FEATURES_REPLACEMENTS),
    (5223, 'gallery', GALLERY_REPLACEMENTS),
]:
    r = requests.get(f'{BASE}/pages/{page_id}?context=edit', headers=headers, timeout=30)
    r.raise_for_status()
    content = r.json().get('content', {}).get('raw', '')
    updated = content
    for old, new in replacements.items():
        updated = updated.replace(old, new)
    if updated == content:
        print(name, 'no changes')
        continue
    u = requests.post(f'{BASE}/pages/{page_id}', headers=headers, data=json.dumps({'content': updated, 'status': 'publish'}), timeout=45)
    if u.status_code not in (200, 201):
        raise SystemExit(f'Update failed for {name}: HTTP {u.status_code} {u.text[:220]}')
    print(name, 'updated')
