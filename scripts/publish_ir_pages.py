#!/usr/bin/env python3
"""
Publish the given page IDs by setting status=publish via REST and verify live link.
Saves published page JSONs to artifacts/ and a summary.
"""
import requests
import json
from pathlib import Path

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
USER = 'copilot'
PASS = 'SBlI yPMK 5crY p3Lo FOtF M3Tw'
IDS = [9256,9257,9258,9259]
ART = Path(r'c:/Users/pools/Documents/wordpress-develop/artifacts')
ART.mkdir(parents=True, exist_ok=True)

session = requests.Session()
session.auth = (USER, PASS)
session.verify = False

summary = {'published': [], 'failed': []}
for pid in IDS:
    print('Publishing', pid)
    r = session.post(f"{BASE}/pages/{pid}", json={'status': 'publish'})
    if not r.ok:
        summary['failed'].append({'id': pid, 'status': r.status_code, 'text': r.text[:200]})
        continue
    page = r.json()
    out = ART / f'page_{pid}_published.json'
    out.write_text(json.dumps(page, indent=2), encoding='utf-8')
    # verify link
    link = page.get('link')
    verify = session.get(link)
    if verify.ok:
        summary['published'].append({'id': pid, 'link': link})
    else:
        summary['failed'].append({'id': pid, 'link': link, 'verify_status': verify.status_code})

out = ART / 'ir_publish_summary.json'
out.write_text(json.dumps(summary, indent=2), encoding='utf-8')
print('Publish summary written to', out)
