"""Check investors page position 4860 and try to get videos page meta."""
import urllib.request, json, base64

user = 'admin'
pw = 'i6IM cqLZ vQDC pIRk nKFr g35i'
creds = base64.b64encode((user+':'+pw).encode()).decode()
headers = {'Authorization': 'Basic ' + creds}
base = 'https://www.loungenie.com/wp-json/wp/v2'

# 1. Investors page second uploads reference
req = urllib.request.Request(f'{base}/pages/5668?context=edit', headers=headers)
with urllib.request.urlopen(req) as r:
    data = json.loads(r.read())
raw = data['content']['raw']
print('=== Investors page (5668) uploads references ===')
idx = 0
while True:
    idx = raw.lower().find('uploads', idx)
    if idx == -1:
        break
    start = max(0, idx-50)
    end = min(len(raw), idx+150)
    print(f'  pos={idx}: {raw[start:end]}')
    idx += 1

# 2. Try to get meta for videos page (5285) via API
print('\n=== Videos page (5285) meta attempt ===')
req2 = urllib.request.Request(f'{base}/pages/5285?context=edit&_fields=id,meta,slug', headers=headers)
with urllib.request.urlopen(req2) as r:
    data2 = json.loads(r.read())
print('Keys:', list(data2.keys()))
if 'meta' in data2:
    meta = data2['meta']
    print('Meta keys:', list(meta.keys()) if isinstance(meta, dict) else type(meta))
    if isinstance(meta, dict):
        for k, v in meta.items():
            v_str = str(v)[:200]
            print(f'  {k}: {v_str}')

# 3. Try Elementor-specific meta endpoint
print('\n=== Trying /wp-json/elementor API ===')
try:
    req3 = urllib.request.Request('https://www.loungenie.com/wp-json/elementor/v1/globals', headers=headers)
    with urllib.request.urlopen(req3, timeout=10) as r:
        print('Elementor globals:', r.read()[:500])
except Exception as e:
    print(f'Elementor API error: {e}')
