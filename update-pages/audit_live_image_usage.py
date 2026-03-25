import urllib.request
import json
import base64
import re
from collections import Counter, defaultdict

USER = 'admin'
PW = 'U7GM Z9qE QOq6 MQva IzcQ 6PU2'
AUTH = 'Basic ' + base64.b64encode((USER + ':' + PW).encode()).decode()
HEADERS = {'Authorization': AUTH}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

PAGES = [
    (4701, 'home'),
    (2989, 'features'),
    (4862, 'about'),
    (5139, 'contact'),
    (5285, 'videos'),
    (5223, 'gallery'),
    (5668, 'investors'),
    (5651, 'board'),
    (5686, 'financials'),
    (5716, 'press'),
]

url_counts = Counter()
url_pages = defaultdict(list)

for pid, slug in PAGES:
    req = urllib.request.Request(f'{BASE}/pages/{pid}?context=edit', headers=HEADERS)
    with urllib.request.urlopen(req, timeout=30) as r:
        data = json.loads(r.read())
    raw = data['content']['raw']
    urls = re.findall(r'<img[^>]+src="([^"]+)"', raw, re.I)
    bg = re.findall(r'background(?:-image)?:\s*url\(["\']?([^"\')\s]+)', raw, re.I)
    all_urls = [u for u in (urls + bg) if '/wp-content/uploads/' in u]

    print(f'\n== {slug} ({pid}) ==')
    for u in all_urls:
        print(u)
        url_counts[u] += 1
        if slug not in url_pages[u]:
            url_pages[u].append(slug)

print('\n\n== DUPLICATES ACROSS PAGES ==')
for u, n in url_counts.most_common():
    if n > 1:
        print(f'{n}x | {u}')
        print('   pages:', ', '.join(url_pages[u]))
