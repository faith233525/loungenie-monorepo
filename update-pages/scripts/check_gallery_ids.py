import json

PAGE_JSON = 'gallery_5223_update_result.json'
MEDIA_JSON = 'media_lookup_5223.json'

with open(PAGE_JSON, 'r', encoding='utf-8') as f:
    page = json.load(f)

# collect all gallery ids from page content raw
raw = page.get('content', {}).get('raw', '')
import re
ids = []
for m in re.finditer(r'"ids"\s*:\s*\[([^\]]+)\]', raw):
    group = m.group(1)
    for part in group.split(','):
        try:
            ids.append(int(part.strip()))
        except:
            pass
ids = sorted(set(ids))

with open(MEDIA_JSON, 'r', encoding='utf-8') as f:
    media = json.load(f)
media_ids = set(item['id'] for item in media if item.get('id') )

missing = [i for i in ids if i not in media_ids]
print('Gallery IDs found in page:', ids)
print('Media IDs available:', sorted(media_ids))
if missing:
    print('Missing IDs (not found in media lookup):', missing)
else:
    print('All gallery IDs present in media lookup.')

# Also ensure Kadence blocks present
if 'wp:kadence' in raw:
    print('Kadence blocks present in page content.')
else:
    print('Kadence blocks NOT found.')
