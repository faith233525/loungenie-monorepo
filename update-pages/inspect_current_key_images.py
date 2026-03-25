#!/usr/bin/env python3
import base64
import re
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('Copilot:U7GM Z9qE QOq6 MQva IzcQ 6PU2'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}
pages = [(4701, 'home'), (2989, 'features'), (4862, 'about'), (5223, 'gallery')]
pattern = re.compile(r"https://www\.loungenie\.com/wp-content/uploads/[^\s\"'<>]+\.(?:jpe?g|png|webp|avif)", re.I)

for pid, name in pages:
    r = requests.get(f'{BASE}/pages/{pid}?context=edit', headers=headers, timeout=30)
    r.raise_for_status()
    content = r.json().get('content', {}).get('raw', '')
    print(f'\n{name}')
    for url in pattern.findall(content):
        print(url)
