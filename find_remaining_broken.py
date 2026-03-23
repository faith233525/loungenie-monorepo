"""Deep search for remaining broken images and show HTML context."""
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

# Print slugs/ids for all pages
print('All pages:')
for p in pages:
    clen = len(p['content']['raw'])
    print(f"  {p['slug']:40s} id={p['id']}  raw_len={clen}")

# Search specifically for the missing ones
targets = ['services9', '1746838260534', '1719134653716']
for t in targets:
    print(f'\n=== Searching all pages for: {t} ===')
    for page in pages:
        raw = page['content']['raw']
        if t in raw:
            idx = raw.find(t)
            start = max(0, idx - 150)
            end = min(len(raw), idx + len(t) + 150)
            snippet = raw[start:end].replace('\n', ' ')
            print(f"  Found in {page['slug']} (id={page['id']})")
            print(f"  ...{snippet}...")
