"""Find pages with broken images and show context around each broken img."""
import urllib.request, json, base64, re

user = 'admin'
pw = 'i6IM cqLZ vQDC pIRk nKFr g35i'
creds = base64.b64encode((user+':'+pw).encode()).decode()
headers = {'Authorization': 'Basic ' + creds}
base = 'https://www.loungenie.com/wp-json/wp/v2'

# Fetch all pages
req = urllib.request.Request(base + '/pages?per_page=100&context=edit&_fields=id,slug,title,content', headers=headers)
with urllib.request.urlopen(req) as r:
    pages = json.loads(r.read())

print(f'Found {len(pages)} pages\n')

broken_files = [
    'services9.jpg',
    '3-VOR-cabana-e1773774348955.jpg',
    'IMG_2071.jpeg', 'IMG_2072.jpeg', 'IMG_2073.jpeg',
    'IMG_2074.jpeg', 'IMG_2075.jpeg', 'IMG_2076.jpeg',
    '1719134653716-1.jpg',
    '1746838260534-1.jpg',
]

for page in pages:
    slug = page['slug']
    pid = page['id']
    raw = page['content']['raw']
    found = [f for f in broken_files if f in raw]
    if not found:
        continue
    print(f'\n=== Page: {slug} (id={pid}) ===')
    for f in found:
        # Find the surrounding img tag
        idx = raw.find(f)
        start = max(0, idx - 200)
        end = min(len(raw), idx + len(f) + 200)
        snippet = raw[start:end].replace('\n', ' ')
        print(f'\n  FILE: {f}')
        print(f'  CONTEXT: ...{snippet}...')
