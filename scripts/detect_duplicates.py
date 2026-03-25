#!/usr/bin/env python3
"""
Detect duplicate large blocks/sections in an HTML file.
Writes a report to artifacts/investors-duplicates.json
"""
from pathlib import Path
import hashlib
import json
from bs4 import BeautifulSoup

ART = Path(r'c:/Users/pools/Documents/wordpress-develop/artifacts')
ART.mkdir(parents=True, exist_ok=True)
IN = ART / 'investors_live.html'
OUT = ART / 'investors-duplicates.json'

if not IN.exists():
    raise SystemExit('investors_live.html not found')

html = IN.read_text(encoding='utf-8')
soup = BeautifulSoup(html, 'html.parser')

# Try to find the main content container
container = soup.select_one('.ir-content-wrap') or soup.select_one('.wp-block-post-content') or soup.body

blocks = []
for child in container.find_all(recursive=False):
    blocks.append(str(child))

hashes = {}
for i, b in enumerate(blocks):
    # normalize: remove extra whitespace
    norm = ' '.join(b.split())
    h = hashlib.sha256(norm.encode('utf-8')).hexdigest()
    hashes.setdefault(h, []).append({'index': i, 'snippet': norm[:800]})

dups = {h: v for h, v in hashes.items() if len(v) > 1}
report = {'total_blocks': len(blocks), 'duplicate_groups': dups}
OUT.write_text(json.dumps(report, indent=2), encoding='utf-8')
print('Report written to', OUT)
