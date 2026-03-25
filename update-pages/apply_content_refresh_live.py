import urllib.request
import json
import base64

USER = 'admin'
PW = 'i6IM cqLZ vQDC pIRk nKFr g35i'
AUTH = 'Basic ' + base64.b64encode((USER + ':' + PW).encode()).decode()
HEADERS = {'Authorization': AUTH, 'Content-Type': 'application/json'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

PAGES = [4701, 2989, 5223]

replacements = {
    # New lock / unit photos from review folder
    'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3233-scaled-1.jpg': 'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2080.jpeg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3235-scaled-1.jpg': 'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2081.jpeg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3239-scaled-1.jpg': 'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2083.jpeg',
    'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3241-scaled-1.jpg': 'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2083.jpeg',

    # Partner logos refreshed and generalized
    'https://www.loungenie.com/wp-content/uploads/2026/02/Carnival-Cruise-Emblem-1-scaled.webp': 'https://www.loungenie.com/wp-content/uploads/2026/03/logo-hilton.webp',
    'https://www.loungenie.com/wp-content/uploads/2025/10/logo-color.png': 'https://www.loungenie.com/wp-content/uploads/2026/03/logo-marriott.webp',
    'https://www.loungenie.com/wp-content/uploads/2025/10/logos-pc-black-horizontal.png': 'https://www.loungenie.com/wp-content/uploads/2026/03/logo-westin.webp',
    'https://www.loungenie.com/wp-content/uploads/2025/10/R-1-scaled.png': 'https://www.loungenie.com/wp-content/uploads/2026/03/logo-ritz.webp',
    'https://www.loungenie.com/wp-content/uploads/2025/10/cowabunga-vegas-logo-300x173.png.webp': 'https://www.loungenie.com/wp-content/uploads/2026/03/logo-sixflags.webp',
}

alt_replacements = {
    'Carnival Cruise Lines': 'Hospitality Partner',
    'Palace Entertainment': 'Hospitality Partner',
    'PYEK': 'Hospitality Partner',
    'Hyatt Hotels': 'Hospitality Partner',
    'Cowabunga Bay': 'Hospitality Partner',
}

copy_replacements = {
    'Zero upfront cost &middot; Revenue share &middot; PoolSafe installs and services.': 'No upfront purchase cost &middot; Performance-based revenue share &middot; PoolSafe handles install and service.',
    'Zero upfront cost · Revenue share · PoolSafe installs and services.': 'No upfront purchase cost · Performance-based revenue share · PoolSafe handles install and service.',
}

for pid in PAGES:
    req = urllib.request.Request(f'{BASE}/pages/{pid}?context=edit', headers=HEADERS)
    with urllib.request.urlopen(req, timeout=40) as r:
        page = json.loads(r.read())

    raw = page['content']['raw']
    updated = raw

    # URL and copy swaps
    for old, new in replacements.items():
        updated = updated.replace(old, new)
    for old, new in alt_replacements.items():
        updated = updated.replace(old, new)
    for old, new in copy_replacements.items():
        updated = updated.replace(old, new)

    # Improve framing: prioritize full-unit visibility
    updated = updated.replace('object-fit:cover', 'object-fit:contain')
    updated = updated.replace('.lg9-media {', '.lg9-media { background:#f5f8fb;')

    # Avoid duplicate background insertion
    updated = updated.replace('.lg9-media { background:#f5f8fb; background:#f5f8fb;', '.lg9-media { background:#f5f8fb;')

    if updated == raw:
        print(f'page {pid}: no content change')
        continue

    payload = json.dumps({'content': updated}).encode()
    req2 = urllib.request.Request(f'{BASE}/pages/{pid}', data=payload, headers=HEADERS, method='POST')
    with urllib.request.urlopen(req2, timeout=40) as r2:
        res = json.loads(r2.read())

    print(f"page {pid}: updated status={res.get('status')} rendered_len={len(res['content']['rendered'])}")

print('done')
