"""Search WP media library for images that could replace the broken ones."""
import urllib.request, json, base64, re

user = 'admin'
pw = 'i6IM cqLZ vQDC pIRk nKFr g35i'
creds = base64.b64encode((user+':'+pw).encode()).decode()
headers = {'Authorization': 'Basic ' + creds}
base = 'https://www.loungenie.com/wp-json/wp/v2'

broken_names = [
    'services9',
    '3-VOR-cabana',
    'IMG_2071', 'IMG_2072', 'IMG_2073', 'IMG_2074', 'IMG_2075', 'IMG_2076',
    '1719134653716-1',
    '1746838260534-1',
]

print('=== Searching media library for broken image filenames ===\n')
for name in broken_names:
    url = base + '/media?search=' + urllib.parse.quote(name) + '&per_page=5&_fields=id,source_url,slug,title'
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req) as r:
        items = json.loads(r.read())
    if items:
        for it in items:
            print(f'  FOUND [{name}] id={it["id"]}  {it["source_url"]}')
    else:
        print(f'  NOT IN LIBRARY: {name}')

import urllib.parse

# Also list all media from 2026/03 to see what IS uploaded
print('\n=== All media uploaded in 2026/03 ===')
url = base + '/media?per_page=100&_fields=id,source_url&mime_type=image'
req = urllib.request.Request(url, headers=headers)
with urllib.request.urlopen(req) as r:
    all_media = json.loads(r.read())

march_media = [m for m in all_media if '2026/03' in m['source_url']]
print(f'Found {len(march_media)} images in 2026/03:')
for m in march_media:
    print(f'  id={m["id"]}  {m["source_url"]}')

# Also check 2025/10 for the older ones
oct_media = [m for m in all_media if '2025/10' in m['source_url']]
print(f'\nFound {len(oct_media)} images in 2025/10:')
for m in oct_media:
    print(f'  id={m["id"]}  {m["source_url"]}')
