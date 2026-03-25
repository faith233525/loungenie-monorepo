#!/usr/bin/env python3
"""Dump raw content of staging pages for inspection."""
import base64
import requests

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}

for pid, name in [(4701, 'home'), (2989, 'features'), (5223, 'gallery')]:
    r = requests.get(f'{BASE}/pages/{pid}?context=edit', headers=headers, timeout=30)
    r.raise_for_status()
    content = r.json().get('content', {}).get('raw', '')
    out_file = f'staging_raw_{name}.txt'
    with open(out_file, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'{name}: {len(content)} chars -> {out_file}')
