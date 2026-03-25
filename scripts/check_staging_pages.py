#!/usr/bin/env python3
import hashlib
import requests

STAGING = 'https://loungenie.com/staging'
PAGES = {
    4862: ('About', 'Hospitality Innovation'),
    5139: ('Contact', 'Contact LounGenie'),
    5285: ('Videos', 'LounGenie Videos'),
    4701: ('Home', 'Why It Works'),
    2989: ('Features', 'Tier structure for every property type'),
    5223: ('Gallery', 'What to look for in each clip'),
    5668: ('Investors', 'Investors'),
}

old_domain_markers = [
    'https://www.loungenie.com/',
    'https://www.loungenie.com/index.php/',
]

session = requests.Session()
results = []
for pid, (name, expected) in PAGES.items():
    url = f"{STAGING}/?page_id={pid}"
    try:
        r = session.get(url, timeout=30)
        r.raise_for_status()
        text = r.text
        h = hashlib.sha256(text.encode()).hexdigest()
        ok = expected in text
        old_links_found = [m for m in old_domain_markers if m in text]
        results.append((pid, name, url, ok, h, old_links_found))
    except Exception as e:
        results.append((pid, name, url, False, str(e), ['request_error']))

for pid, name, url, ok, h, old_links in results:
    print(f"{name} ({pid}): {url}")
    if old_links == ['request_error']:
        print(f"  ERROR fetching page: {h}")
    else:
        print(f"  SHA256: {h}")
        print(f"  Expected text present: {ok}")
        if old_links:
            print(f"  Old domain links found: {old_links}")
        else:
            print(f"  No old-domain links found")
    print()

# Save concise summary to logs
import os
os.makedirs('logs', exist_ok=True)
with open('logs/staging_check_summary.txt', 'w', encoding='utf-8') as fh:
    for pid, name, url, ok, h, old_links in results:
        fh.write(f"{name} ({pid}) {url} SHA:{h} expected:{ok} old_links:{old_links}\n")
print('Summary saved to logs/staging_check_summary.txt')
