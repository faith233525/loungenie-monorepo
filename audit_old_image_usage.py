#!/usr/bin/env python3
import requests, base64, re

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}
base = 'https://www.loungenie.com/wp-json/wp/v2'

pages = [
    (4701, 'home'),
    (2989, 'features'),
    (4862, 'about'),
    (5139, 'contact'),
    (5285, 'videos'),
    (5223, 'gallery'),
]

for pid, name in pages:
    d = requests.get(f'{base}/pages/{pid}', headers=headers, timeout=30).json()
    c = d.get('content', {}).get('rendered', '')
    imgs = re.findall(r'https://www\.loungenie\.com/wp-content/uploads/[^\s"\'<>]+\.(?:jpe?g|png|webp|avif)', c, re.I)
    old = [u for u in imgs if '/2025/' in u or '/2024/' in u or '/2023/' in u]
    print(f'\n{name}: total={len(imgs)} old={len(old)}')
    for u in sorted(set(old)):
        print(' ', u)
