#!/usr/bin/env python3
import requests
import base64
import re

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}
base = 'https://www.loungenie.com/wp-json/wp/v2'

d = requests.get(f'{base}/pages/5223', headers=headers, timeout=30).json()
content = d.get('content', {}).get('rendered', '')

# Extract ordered image blocks with nearby caption
pattern = re.compile(r'<img[^>]+src="([^"]+)"[^>]*alt="([^"]*)"[^>]*>(.*?)</div>', re.I | re.S)
rows = pattern.findall(content)
print('ordered_rows', len(rows))
for i, (src, alt, tail) in enumerate(rows, start=1):
    cap_match = re.search(r'<div class="gx-cap">(.*?)</div>', tail, re.I | re.S)
    cap = cap_match.group(1).strip() if cap_match else ''
    print(f'{i:02d} | {src} | ALT: {alt[:90]} | CAP: {cap[:90]}')
