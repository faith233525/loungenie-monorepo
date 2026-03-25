#!/usr/bin/env python3
import base64
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}

page_id = 5223
urls = [
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-1-April-2023-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-2-scaled.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/PoolSafe-Makai-Pool.jpg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/PoolSafe-Hilton.jpg',
    'https://www.loungenie.com/wp-content/uploads/2025/10/Westin-Hilton-Head-4-April-2023-scaled.jpg',
]

r = requests.get(f'{BASE}/pages/{page_id}?context=edit', headers=headers, timeout=30)
r.raise_for_status()
content = r.json().get('content', {}).get('raw', '')

for url in urls:
    idx = content.find(url)
    print('\nURL:', url)
    if idx == -1:
        print('NOT FOUND')
        continue
    start = max(0, idx - 220)
    end = min(len(content), idx + 320)
    print(content[start:end].replace('\n', ' '))
