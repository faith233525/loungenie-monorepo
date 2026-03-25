#!/usr/bin/env python3
"""
Remove exact duplicate top-level block groups from the Investors page on staging.
Saves before/after snapshots and a removal report to artifacts/.
"""
from pathlib import Path
import json
import hashlib
import sys

try:
    import requests
    from bs4 import BeautifulSoup, Comment
except Exception:
    raise SystemExit('Please install requests and beautifulsoup4')

BASE = 'https://loungenie.com/staging'
USER = 'copilot'
PASS = 'SBlI yPMK 5crY p3Lo FOtF M3Tw'
ART = Path(r'c:/Users/pools/Documents/wordpress-develop/artifacts')
ART.mkdir(parents=True, exist_ok=True)

session = requests.Session()
session.auth = (USER, PASS)
session.verify = False

# 1. Fetch the Investors page
print('Fetching investors page...')
r = session.get(f"{BASE}/wp-json/wp/v2/pages?slug=investors")
if not r.ok:
    print('Failed to fetch page list:', r.status_code, r.text)
    sys.exit(1)
data = r.json()
if not data:
    print('No page found for slug investors')
    sys.exit(1)
page = data[0]
page_id = page['id']
page_url = page.get('link') or f"{BASE}/?p={page_id}"
content_html = page.get('content', {}).get('rendered') or page.get('content')

# Save before snapshot
before_file = ART / 'investors_before.json'
before_file.write_text(json.dumps(page, indent=2), encoding='utf-8')
print('Saved before snapshot to', before_file)

# 2. Identify duplicates among top-level elements
soup = BeautifulSoup(content_html, 'html.parser')
# We will treat direct children of body-like wrapper. If the content is wrapped in a single container div, use its children.
container = None
# prefer known container classes
for cls in ['ir-content-wrap', 'ir-content-panel', 'wp-block-post-content', 'wp-block-group', 'ir-shell']:
    el = soup.select_one(f'.{cls}')
    if el:
        container = el
        break
if not container:
    # fallback to full parsed soup
    container = BeautifulSoup(content_html, 'html.parser')

children = [c for c in container.contents]
print(f'Found {len(children)} top-level child nodes in container')

hash_map = {}
keep_mask = [True] * len(children)
removed = []

for i, node in enumerate(children):
    # normalize node string for comparison
    # keep comments and tags; whitespace normalized
    s = str(node)
    norm = ' '.join(s.split())
    h = hashlib.sha256(norm.encode('utf-8')).hexdigest()
    if h in hash_map:
        # mark as duplicate - remove this node
        keep_mask[i] = False
        removed.append({'index': i, 'hash': h, 'snippet': norm[:800]})
    else:
        hash_map[h] = i

print('Duplicate groups found:', len(removed))
if not removed:
    print('No exact duplicate top-level blocks to remove. Exiting.')
    sys.exit(0)

# 3. Build new content keeping only first instances
new_nodes = []
for keep, node in zip(keep_mask, children):
    if keep:
        new_nodes.append(str(node))
    else:
        # skip duplicate
        pass
new_content = '\n'.join(new_nodes)

# 4. Save after snapshot (local) before updating remote
after_file = ART / 'investors_after_local.html'
after_file.write_text(new_content, encoding='utf-8')
print('Saved after (local) snapshot to', after_file)

# 5. Update page via REST (publish)
update_payload = {'content': new_content, 'status': 'publish'}
print('Updating page id', page_id)
upd = session.post(f"{BASE}/wp-json/wp/v2/pages/{page_id}", json=update_payload)
if not upd.ok:
    print('Failed to update page:', upd.status_code, upd.text)
    sys.exit(1)
updated_page = upd.json()
updated_file = ART / 'investors_after_remote.json'
updated_file.write_text(json.dumps(updated_page, indent=2), encoding='utf-8')
print('Saved after (remote) snapshot to', updated_file)

# 6. Verify duplicates are removed by re-parsing remote content
remote_content = updated_page.get('content', {}).get('rendered') or updated_page.get('content')
rsoup = BeautifulSoup(remote_content, 'html.parser')
if container is not None:
    # find same container in remote by class
    new_container = None
    for cls in ['ir-content-wrap', 'ir-content-panel', 'wp-block-post-content', 'wp-block-group', 'ir-shell']:
        el = rsoup.select_one(f'.{cls}')
        if el:
            new_container = el
            break
    if not new_container:
        new_container = rsoup
else:
    new_container = rsoup
new_children = [c for c in new_container.contents]

# recompute duplicates
new_hashes = {}
dups_remaining = []
for i, node in enumerate(new_children):
    s = str(node)
    norm = ' '.join(s.split())
    h = hashlib.sha256(norm.encode('utf-8')).hexdigest()
    if h in new_hashes:
        dups_remaining.append({'index': i, 'hash': h, 'snippet': norm[:400]})
    else:
        new_hashes[h] = i

report = {
    'page_id': page_id,
    'page_url': page_url,
    'removed_count': len(removed),
    'removed': removed,
    'duplicates_remaining': dups_remaining,
    'before_snapshot': str(before_file),
    'after_local': str(after_file),
    'after_remote': str(updated_file)
}
report_file = ART / 'investors-duplicates-removal-report.json'
report_file.write_text(json.dumps(report, indent=2), encoding='utf-8')
print('Report written to', report_file)

# Output summary
print('\nSummary:')
print('Page ID:', page_id)
print('Page URL:', page_url)
print('Removed duplicate top-level nodes:', len(removed))
print('Duplicates remaining after update:', len(dups_remaining))
print('Report:', report_file)
