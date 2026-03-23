#!/usr/bin/env python3
import base64
import json
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

FEATURES_MAP = {
    'https://www.loungenie.com/wp-content/uploads/2026/03/105-Westin__hhi_bjp_-_low_res.webp': 'https://www.loungenie.com/wp-content/uploads/2025/10/Water-World-staging-2-scaled.jpg',
    'Westin Hilton Head deployment showing LounGenie positioned beside premium loungers': 'Water World shaded premium seating deployment with LounGenie positioned beside private cabanas',
}

GALLERY_MAP = {
    'Westin Hilton Head Deployment Views': 'Water World Deployment Views',
    'Recent Westin Hilton Head deployment views that keep the unit in context without relying on older lock-detail framing': 'Recent Water World deployment views that keep the unit in context without relying on older lock-detail framing',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-1-April-2023-scaled.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Water-World-staging-3-scaled.jpg',
    'Westin Hilton Head premium lounger deployment with LounGenie in poolside service position': 'Water World row of shaded rental seating with LounGenie units aligned in service position',
    'Poolside lounger placement': 'Shaded seating row',
    'https://www.loungenie.com/wp-content/uploads/2025/10/152-Westin__hhi_bjp_-_low_res.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Water-World-staging-4-scaled.jpg',
    'Westin Hilton Head cabana interior with LounGenie integrated into the premium seating setup': 'Water World deployment showing multiple shaded seating bays with LounGenie units in context',
    'Cabana interior setup': 'Multi-bay deployment',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-scaled.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Water-World-Cabana-1.jpg',
    'Westin Hilton Head pool deck installation with unit visible in guest circulation space': 'Water World private cabana installation with LounGenie visible at the guest seating area',
    'Pool deck deployment': 'Private cabana view',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-4-April-2023-scaled.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Water-World-Cabana-2.jpg',
    'Westin Hilton Head resort seating zone with LounGenie alongside premium loungers': 'Water World venue cabana row with LounGenie integrated into each rental bay',
    'Resort seating zone': 'Cabana row view',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-2-scaled.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Water-World-staging-1.jpg',
    'Westin Hilton Head side-angle deployment showing guest-facing placement': 'Water World side-angle deployment showing the unit beside blue tented cabanas',
    'Side-angle deployment': 'Blue cabana angle',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-1-scaled.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Water-World-5.jpg',
    'Westin Hilton Head in-cabana deployment with the unit tucked beside premium seating': 'Water World guest-area deployment with LounGenie visible beside active seating',
    'In-cabana deployment': 'Guest-area deployment',
}

for page_id, name, replacements in [
    (2989, 'features', FEATURES_MAP),
    (5223, 'gallery', GALLERY_MAP),
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
