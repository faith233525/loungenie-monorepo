import requests
import json
import re
import os
from urllib.parse import urlparse

# Configuration
PAGE_JSON = os.path.join('gallery_5223_update_result.json')
MEDIA_LOOKUP = os.path.join('media_lookup_5223.json')
BACKUP = MEDIA_LOOKUP + '.bak'
WP_MEDIA_ENDPOINT = 'https://loungenie.com/staging/wp-json/wp/v2/media'
USER = 'copilot'
APP_PASSWORD = '0lVZlpeKN5YyWsb1ss5a4Rtx'  # provided by user (spaces removed)

# Helpers
def basename_from_url(url):
    p = urlparse(url)
    return os.path.basename(p.path)

def normalize_name(name):
    # remove common size suffixes like -scaled, -123x456, -scaled.jpeg -> keep base filename
    name = name.replace('%20',' ')
    # split extension
    m = re.match(r"^(?P<base>.+?)(?:-scaled|-scaled\.|-\d+x\d+)?(\.[a-zA-Z0-9]+)$", name)
    if m:
        return m.group('base') + m.group(2)
    # fallback: remove -scaled occurrences
    name = re.sub(r'-scaled','', name)
    name = re.sub(r'-\d+x\d+','', name)
    return name

# Load page and media lookup
with open(PAGE_JSON, 'r', encoding='utf-8') as f:
    page = json.load(f)

if os.path.exists(MEDIA_LOOKUP):
    with open(MEDIA_LOOKUP, 'r', encoding='utf-8') as f:
        media_lookup = json.load(f)
else:
    media_lookup = []

# backup existing lookup
with open(BACKUP, 'w', encoding='utf-8') as f:
    json.dump(media_lookup, f, indent=2)
print(f'Backup written to {BACKUP}')

# extract filenames from page galleries (ids and URLs)
raw = page.get('content', {}).get('raw', '')
filenames = set()
# find src="..." occurrences
for m in re.finditer(r'src=\"([^\"]+)\"', raw):
    url = m.group(1)
    filenames.add(basename_from_url(url))
# also include ids referenced in galleries (we'll map ids back to filenames later if needed)
for m in re.finditer(r'"ids"\s*:\s*\[([^\]]+)\]', raw):
    group = m.group(1)
    for part in group.split(','):
        s = part.strip()
        if s:
            # try to find matching filename in media_lookup
            # keep numeric ids separately
            pass

filenames = sorted(filenames)
print('Found filenames in page content:', filenames)

# Build a dict from existing lookup for quick id->entry and filename->entry
id_map = {item['id']: item for item in media_lookup if item.get('id')}
name_map = {item['filename']: item for item in media_lookup}

session = requests.Session()
session.auth = (USER, APP_PASSWORD)

updated = list(media_lookup)  # start with existing
seen_files = set(name_map.keys())

for fn in filenames:
    if fn in name_map and name_map[fn].get('id'):
        continue
    # try normalized names
    norm = normalize_name(fn)
    candidates = []
    # Query WP media search for base name and for filename variants
    queries = [fn, norm]
    if '.' in fn:
        base_no_ext = os.path.splitext(fn)[0]
        queries.append(base_no_ext)
    queries = list(dict.fromkeys(queries))
    found = None
    for q in queries:
        print('Searching media for:', q)
        try:
            r = session.get(WP_MEDIA_ENDPOINT, params={'search': q, 'per_page': 20}, timeout=15)
            r.raise_for_status()
            items = r.json()
        except Exception as e:
            print('Media query error for', q, e)
            items = []
        for it in items:
            src = it.get('source_url','')
            candidate_name = basename_from_url(src)
            # normalize candidate name
            if normalize_name(candidate_name).startswith(normalize_name(fn).rsplit('.',1)[0]):
                candidates.append({'id': it.get('id'), 'source_url': src, 'title': it.get('title', {}).get('rendered')})
        if candidates:
            # prefer the most recent by id
            candidates = sorted(candidates, key=lambda x: x['id'], reverse=True)
            found = candidates[0]
            break
    if not found:
        print('No media match found for', fn)
        updated.append({'filename': fn, 'id': None, 'source_url': None, 'title': None})
    else:
        print('Matched', fn, '->', found['id'], found['source_url'])
        updated.append({'filename': fn, 'id': found['id'], 'source_url': found['source_url'], 'title': found.get('title')})

# Deduplicate updated by filename (keep last)
out_map = {}
for item in updated:
    out_map[item['filename']] = item
out_list = list(out_map.values())

# write updated lookup
with open(MEDIA_LOOKUP, 'w', encoding='utf-8') as f:
    json.dump(out_list, f, indent=2)
print('Updated media lookup written to', MEDIA_LOOKUP)

# produce clean mapping gallery filename -> id for only those used in page
clean_map = {}
for item in out_list:
    if item['filename'] in filenames:
        clean_map[item['filename']] = item['id']

with open('gallery_5223_clean_map.json', 'w', encoding='utf-8') as f:
    json.dump(clean_map, f, indent=2)
print('Clean mapping written to gallery_5223_clean_map.json')

# report any remaining missing
missing = [k for k,v in clean_map.items() if v is None]
if missing:
    print('Remaining unmatched filenames:', missing)
else:
    print('All filenames matched to media IDs.')

print('Done.')
