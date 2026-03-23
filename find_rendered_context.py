"""Fetch rendered HTML and find context for services9.jpg and investors broken image."""
import urllib.request, re

pages_to_check = [
    ('home',      'https://www.loungenie.com/loungenie/'),
    ('investors', 'https://www.loungenie.com/loungenie/investors/'),
    ('videos',    'https://www.loungenie.com/loungenie/loungenie-videos/'),
]

targets = ['services9', '1746838260534', '1719134653716']

for label, url in pages_to_check:
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    with urllib.request.urlopen(req, timeout=20) as r:
        html = r.read().decode('utf-8', errors='replace')
    print(f'\n=== {label} ===')
    for t in targets:
        idx = html.find(t)
        if idx != -1:
            start = max(0, idx - 200)
            end = min(len(html), idx + len(t) + 200)
            snippet = html[start:end].replace('\n', ' ')
            print(f'  TARGET: {t}')
            print(f'  ...{snippet}...')
        else:
            print(f'  NOT FOUND: {t}')
