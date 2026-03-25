#!/usr/bin/env python3
import base64
import json
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

PAGES = [
    (4701, 'home'),
    (2989, 'features'),
    (4862, 'about'),
    (5223, 'gallery'),
]

url_replacements = {
    'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2090-scaled.jpeg': 'https://www.loungenie.com/wp-content/uploads/2026/03/balmoral-1.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2078-scaled.jpeg': 'https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-1.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2079-scaled.jpeg': 'https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-5.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2080.jpeg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-1-April-2023-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2081.jpeg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-2-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2083.jpeg': 'https://www.loungenie.com/wp-content/uploads/2026/03/balmoral-2.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2089-scaled.jpeg': 'https://www.loungenie.com/wp-content/uploads/2026/03/balmoral-florida-cabana-1.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-7-scaled.jpg': 'https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-6.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Yas-Waterworld.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Yas-Waterworld-March-2020.jpg': 'https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-2.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/Sea-World-San-Diego-1.jpg': 'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-4-April-2023-scaled.jpg',
}

text_replacements = {
    'STASH Lock Detail Set': 'Resort Feature Detail Set',
    'Waterproof keypad and safe door detail': 'In-cabana details from active property installations',
    'Safe + charge panel detail': 'In-cabana detail view',
    'Lock and panel close-up': 'In-cabana feature close-up',
    'Lock panel and service-side hardware detail': 'In-cabana service-side hardware detail',
    'Lock and keypad detail from alternate angle': 'Alternate in-cabana feature angle',
    'lock-panel detail': 'in-cabana detail',
    'lock detail': 'feature detail',
    'lock-detail': 'feature-detail',
    'lock visuals': 'in-cabana visuals',
    'Lock &amp;': 'Feature &amp;',
}

for pid, slug in PAGES:
    r = requests.get(f'{BASE}/pages/{pid}', headers=headers, timeout=40)
    r.raise_for_status()
    raw = r.json().get('content', {}).get('raw', '')
    if not raw:
        raw = r.json().get('content', {}).get('rendered', '')

    updated = raw
    url_hits = 0
    for old, new in url_replacements.items():
        if old in updated:
            url_hits += updated.count(old)
            updated = updated.replace(old, new)

    text_hits = 0
    for old, new in text_replacements.items():
        if old in updated:
            text_hits += updated.count(old)
            updated = updated.replace(old, new)

    if updated == raw:
        print(slug, 'no changes')
        continue

    payload = {'content': updated, 'status': 'publish'}
    u = requests.post(f'{BASE}/pages/{pid}', headers=headers, data=json.dumps(payload), timeout=45)
    if u.status_code not in (200, 201):
        raise SystemExit(f'Update failed for {slug}: HTTP {u.status_code} {u.text[:220]}')

    print(slug, 'updated', 'url_replacements', url_hits, 'text_replacements', text_hits)
