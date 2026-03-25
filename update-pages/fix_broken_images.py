"""Fix all broken page-content images across gallery, contact, features, home pages."""
import urllib.request, json, base64

user = 'admin'
pw = 'i6IM cqLZ vQDC pIRk nKFr g35i'
creds = base64.b64encode((user+':'+pw).encode()).decode()
headers = {'Authorization': 'Basic ' + creds, 'Content-Type': 'application/json'}
base = 'https://www.loungenie.com/wp-json/wp/v2'

BASE_URL = 'https://www.loungenie.com/wp-content/uploads/2026/03/'

# Replacement mapping: broken filename -> working filename
REPLACEMENTS = {
    # Resort cabana photo (context: QR ordering, cabana exterior)
    '3-VOR-cabana-e1773774348955.jpg':
        'Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-4-scaled.jpg',

    # STASH: waterproof locking safe
    'IMG_2071.jpeg': 'IMG_3233-scaled-1.jpg',

    # STASH: waterproof keypad lock
    'IMG_2072.jpeg': 'IMG_3235-scaled-1.jpg',

    # CHARGE: solar USB charging
    'IMG_2073.jpeg': 'IMG_3239-scaled-1.jpg',

    # CHILL: ice bucket / safe visible
    'IMG_2074.jpeg': 'IMG_3241-scaled-1.jpg',

    # STASH: keypad close-up
    'IMG_2075.jpeg': 'page_1145__mg_6277-copy-1-web.webp',

    # Full unit
    'IMG_2076.jpeg': 'page_1145_img_6227-copy-1-web.webp',

    # Beech Bend water park installation -> Sea World installation (same category: water park)
    '1719134653716-1.jpg': 'Sea-World-San-Diego.jpg',
}

# Pages to fix (id, slug)
page_ids = [
    (5223, 'cabana-installation-photos'),
    (5139, 'contact-loungenie'),
    (4701, 'home'),
    (2989, 'poolside-amenity-unit'),
]

for pid, slug in page_ids:
    req = urllib.request.Request(f'{base}/pages/{pid}?context=edit', headers=headers)
    with urllib.request.urlopen(req) as r:
        data = json.loads(r.read())
    content = data['content']['raw']
    original_len = len(content)
    changed = []

    for old_file, new_file in REPLACEMENTS.items():
        if old_file in content:
            old_url = f'https://www.loungenie.com/wp-content/uploads/2026/03/{old_file}'
            # Handle the 1719 image which is in 2025/10 folder
            if '1719134653716' in old_file:
                old_url = f'https://www.loungenie.com/wp-content/uploads/2025/10/{old_file}'
            new_url = BASE_URL + new_file
            count = content.count(old_file)
            content = content.replace(old_url, new_url)
            # Also try replacing just the filename in case URL varies
            content = content.replace(old_file, new_file)
            changed.append(f'{old_file[:50]} -> {new_file[:50]}  (n={count})')

    if not changed:
        print(f'{slug} (id={pid}): no changes needed')
        continue

    # Push update
    payload = json.dumps({'content': content}).encode()
    req2 = urllib.request.Request(f'{base}/pages/{pid}', data=payload, headers=headers, method='POST')
    with urllib.request.urlopen(req2) as r:
        result = json.loads(r.read())
    print(f'\n{slug} (id={pid}): {len(changed)} replacements, status={result["status"]}')
    for c in changed:
        print(f'  {c}')

print('\nDone!')
