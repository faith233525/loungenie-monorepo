#!/usr/bin/env python3
import base64
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}
checks = [
    (2989, 'features', ['STASH', 'waterproof keypad', 'safe door', 'Westin-Hilton-Head-1-April-2023-scaled.jpg']),
    (5223, 'gallery', ['Current Lock Views Across Properties', '24-0628_PS_TyphoonTexas_WaterPark.png', 'Wild-Rivers-4-April-2023-rotated.jpg'])
]
for pid, name, terms in checks:
    r = requests.get(f'{BASE}/pages/{pid}?context=edit', headers=headers, timeout=30)
    r.raise_for_status()
    content = r.json().get('content', {}).get('raw', '')
    print(f'\n=== {name} ===')
    for term in terms:
        idx = content.lower().find(term.lower())
        print(f'\nTERM: {term}')
        if idx == -1:
            print('NOT FOUND')
            continue
        start = max(0, idx - 260)
        end = min(len(content), idx + 520)
        print(content[start:end].replace('\n', ' '))
