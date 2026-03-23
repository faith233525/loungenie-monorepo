#!/usr/bin/env python3
"""Check image URL patterns on the staging site pages."""
import base64
import re
import requests

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}

pages = [(4701, 'home'), (2989, 'features'), (5223, 'gallery')]
url_pattern = re.compile(r'https?://[^\s"\'<>]+\.(?:jpe?g|png|webp|avif)', re.I)

for pid, name in pages:
    r = requests.get(f'{BASE}/pages/{pid}?context=edit', headers=headers, timeout=30)
    r.raise_for_status()
    content = r.json().get('content', {}).get('raw', '')
    urls = sorted(set(url_pattern.findall(content)))
    print(f'\n=== {name} (id={pid}) ===')
    for u in urls:
        print(u)
