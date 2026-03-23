import requests
import json
import os
from urllib.parse import urlparse

PAGE_JSON = 'gallery_5223_update_result.json'
MEDIA_LOOKUP = 'media_lookup_5223.json'
BACKUP = MEDIA_LOOKUP + '.bak2'
WP_MEDIA_ENDPOINT = 'https://loungenie.com/staging/wp-json/wp/v2/media'
USER = 'copilot'
APP_PASSWORD = '0lVZlpeKN5YyWsb1ss5a4Rtx'

def basename_from_url(url):
    return os.path.basename(urlparse(url).path)

with open(PAGE_JSON, 'r', encoding='utf-8') as f:
    page = json.load(f)
raw = page.get('content', {}).get('raw','')

# extract numeric ids from gallery blocks
import re
ids = set()
for m in re.finditer(r'"ids"\s*:\s*\[([^\]]+)\]', raw):
    parts = m.group(1).split(',')
    for p in parts:
        s = p.strip()
        if s.isdigit():
            ids.add(int(s))
ids = sorted(ids)
print('Gallery numeric IDs:', ids)

# load existing lookup
if os.path.exists(MEDIA_LOOKUP):
    with open(MEDIA_LOOKUP,'r',encoding='utf-8') as f:
        media_lookup = json.load(f)
else:
    media_lookup = []

# backup
with open(BACKUP,'w',encoding='utf-8') as f:
    json.dump(media_lookup,f,indent=2)
print('Backup written to', BACKUP)

id_map = {item['id']: item for item in media_lookup if item.get('id')}
updated = list(media_lookup)

session = requests.Session()
session.auth = (USER, APP_PASSWORD)

for mid in ids:
    if mid in id_map:
        continue
    url = f'{WP_MEDIA_ENDPOINT}/{mid}'
    try:
        r = session.get(url, timeout=15)
        if r.status_code == 200:
            it = r.json()
            src = it.get('source_url')
            fn = basename_from_url(src) if src else None
            title = it.get('title',{}).get('rendered')
            entry = {'filename': fn, 'id': mid, 'source_url': src, 'title': title}
            updated.append(entry)
            print('Found media', mid, '->', fn)
        else:
            print('Media', mid, 'not found (', r.status_code, r.text[:200], ')')
            updated.append({'filename': None, 'id': mid, 'source_url': None, 'title': None})
    except Exception as e:
        print('Error fetching', mid, e)
        updated.append({'filename': None, 'id': mid, 'source_url': None, 'title': None})

# dedupe by filename if present
out_map = {}
for item in updated:
    key = item.get('filename') or f"id_{item.get('id')}"
    out_map[key] = item
out_list = list(out_map.values())

with open(MEDIA_LOOKUP,'w',encoding='utf-8') as f:
    json.dump(out_list, f, indent=2)
print('Updated media_lookup written to', MEDIA_LOOKUP)

# build clean mapping filename->id for gallery ids
clean = {}
for item in out_list:
    if item.get('id') in ids:
        clean[item.get('filename')]=item.get('id')

with open('gallery_5223_clean_map.json','w',encoding='utf-8') as f:
    json.dump(clean,f,indent=2)
print('Clean map written to gallery_5223_clean_map.json')

# report any ids still unresolved
unresolved = [mid for mid in ids if not any(it.get('id')==mid for it in out_list)]
if unresolved:
    print('Unresolved IDs:', unresolved)
else:
    print('All IDs resolved.')
