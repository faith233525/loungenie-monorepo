#!/usr/bin/env python3
"""Export site content (pages, media list, users) for the IR page IDs to artifacts/.
"""
import json
from pathlib import Path
import requests

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
USER = 'copilot'
PASS = 'SBlI yPMK 5crY p3Lo FOtF M3Tw'
IDS = [9256,9257,9258,9259]
ART = Path(r'c:/Users/pools/Documents/wordpress-develop/artifacts')
ART.mkdir(parents=True, exist_ok=True)

session = requests.Session()
session.auth = (USER, PASS)
session.verify = False

summary = {'pages': [], 'media_urls': []}
for pid in IDS:
    r = session.get(f"{BASE}/pages/{pid}")
    if not r.ok:
        print('Failed to fetch page', pid, r.status_code)
        continue
    page = r.json()
    out = ART / f'page_{pid}_export.json'
    out.write_text(json.dumps(page, indent=2), encoding='utf-8')
    summary['pages'].append(str(out))
    # collect media urls from rendered content
    rendered = page.get('content', {}).get('rendered', '')
    # simple regex for uploads paths
    import re
    urls = re.findall(r'https?:?//[^\s"\']*/wp-content/uploads/[^"\'\s<>]+', rendered)
    for u in urls:
        if u not in summary['media_urls']:
            summary['media_urls'].append(u)

# fetch media endpoints for listed urls (query by search)
media_list = []
for url in summary['media_urls']:
    # try to find media by searching for filename
    fname = url.split('/')[-1]
    r = session.get(f"{BASE}/media?search={fname}&per_page=5")
    if r.ok:
        media_list.extend(r.json())

mout = ART / 'ir_media_list.json'
mout.write_text(json.dumps(media_list, indent=2), encoding='utf-8')
summary['media_file'] = str(mout)

out = ART / 'ir_site_export_summary.json'
out.write_text(json.dumps(summary, indent=2), encoding='utf-8')
print('Exported site content summary to', out)
