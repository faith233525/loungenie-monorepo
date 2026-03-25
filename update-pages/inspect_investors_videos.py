"""Print raw content of investors page and videos page to find image block structure."""
import urllib.request, json, base64

user = 'admin'
pw = 'i6IM cqLZ vQDC pIRk nKFr g35i'
creds = base64.b64encode((user+':'+pw).encode()).decode()
headers = {'Authorization': 'Basic ' + creds}
base = 'https://www.loungenie.com/wp-json/wp/v2'

for pid, label in [(5668, 'investors'), (5285, 'videos')]:
    req = urllib.request.Request(f'{base}/pages/{pid}?context=edit', headers=headers)
    with urllib.request.urlopen(req) as r:
        data = json.loads(r.read())
    raw = data['content']['raw']
    print(f'\n===== {label} (id={pid}, len={len(raw)}) =====')
    # Print in 2000-char chunks to see full content
    # Search for image-related keywords
    for kw in ['background', 'image', 'url(', 'uploads', '1746', 'services9', 'uagb', 'elementor']:
        indices = []
        idx = 0
        while True:
            idx = raw.lower().find(kw.lower(), idx)
            if idx == -1:
                break
            indices.append(idx)
            idx += 1
        if indices:
            print(f'\n  Keyword "{kw}" found at {len(indices)} positions: {indices[:5]}')
            # Show first occurrence context
            idx = indices[0]
            start = max(0, idx - 100)
            end = min(len(raw), idx + 200)
            print(f'  First context: ...{raw[start:end]}...')
