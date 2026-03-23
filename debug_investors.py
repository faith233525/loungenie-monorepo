"""Print full investors page content and search reusable blocks / widgets."""
import urllib.request, json, base64

user = 'admin'
pw = 'i6IM cqLZ vQDC pIRk nKFr g35i'
creds = base64.b64encode((user+':'+pw).encode()).decode()
headers = {'Authorization': 'Basic ' + creds}
base = 'https://www.loungenie.com/wp-json/wp/v2'

# 1. Full investors page raw content
req = urllib.request.Request(f'{base}/pages/5668?context=edit', headers=headers)
with urllib.request.urlopen(req) as r:
    data = json.loads(r.read())
raw = data['content']['raw']
print('=== FULL INVESTORS RAW CONTENT ===')
print(raw)
print('\n=== END ===')

# 2. Search reusable blocks (wp_block post type)
print('\n=== Reusable blocks search for 1746838260534 ===')
req2 = urllib.request.Request(f'{base}/blocks?per_page=50&context=edit&_fields=id,slug,title,content', headers=headers)
try:
    with urllib.request.urlopen(req2) as r:
        blocks = json.loads(r.read())
    print(f'Found {len(blocks)} reusable blocks')
    for b in blocks:
        raw_b = b['content']['raw']
        if '1746838260534' in raw_b or 'services9' in raw_b:
            print(f'  FOUND in block id={b["id"]}: {b["title"]["raw"]}')
            idx = raw_b.find('1746838260534')
            if idx != -1:
                print(raw_b[max(0,idx-100):idx+200])
except Exception as e:
    print(f'Error: {e}')
