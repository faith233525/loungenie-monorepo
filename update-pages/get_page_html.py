#!/usr/bin/env python3
"""Get raw HTML content of videos, about, and contact pages for review."""
import requests, base64, re

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
hdrs = {'Authorization': f'Basic {creds}'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

for pid, name in [(5285, 'videos'), (4862, 'about'), (5139, 'contact')]:
    r = requests.get(f'{BASE}/pages/{pid}', headers=hdrs, timeout=20)
    d = r.json()
    html = d.get('content', {}).get('rendered', '')
    print(f"\n{'='*70}")
    print(f"PAGE: {name.upper()} (ID:{pid}) — {len(html)} chars")
    print(html[:4000])
    print("...")
