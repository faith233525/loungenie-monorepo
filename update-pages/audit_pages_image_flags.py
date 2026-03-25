#!/usr/bin/env python3
import requests
import base64
import re

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}
base = 'https://www.loungenie.com/wp-json/wp/v2'
pages = [
    (4701, 'home'), (2989, 'features'), (4862, 'about'), (5139, 'contact'),
    (5285, 'videos'), (5223, 'gallery'), (5668, 'investors'), (5651, 'board'),
    (5686, 'financials'), (5716, 'press')
]

flags = ['yas', 'img_208', 'img_207', 'img_209', 'lock', 'safe', 'gemini', 'nano_banana', 'render', 'generated', 'the-grove-7', 'westin-hilton-head-', 'sea-world-san-diego-1']

for pid, name in pages:
    c = requests.get(f'{base}/pages/{pid}', headers=headers, timeout=30).json().get('content', {}).get('rendered', '')
    imgs = re.findall(r'https://www\.loungenie\.com/wp-content/uploads/[^\s"\'<>]+\.(?:jpe?g|png|webp|avif)', c, re.I)
    print(f'\n{name} imgs {len(imgs)}')
    for u in sorted(set(imgs)):
        lu = u.lower()
        if any(k in lu for k in flags):
            print(' ', u)
