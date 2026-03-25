import json
import os

ROOT = os.path.dirname(os.path.dirname(__file__))
PAGES_FILE = os.path.join(ROOT, 'pages.json')
OUT_DIR = os.path.join(ROOT, 'artifacts')

os.makedirs(OUT_DIR, exist_ok=True)

with open(PAGES_FILE, 'r', encoding='utf-8') as f:
    pages = json.load(f)

count = 0
created = []
for p in pages:
    pid = p.get('id')
    if not pid:
        continue
    title = None
    content = None
    t = p.get('title')
    c = p.get('content')
    if isinstance(t, dict):
        title = t.get('raw') or t.get('rendered')
    else:
        title = t
    if isinstance(c, dict):
        content = c.get('raw') or c.get('rendered')
    else:
        content = c
    payload = {
        'title': {'raw': title or ''},
        'content': {'raw': content or ''},
        'status': p.get('status', 'publish'),
        'slug': p.get('slug')
    }
    out_path = os.path.join(OUT_DIR, f'page_{pid}_update.json')
    with open(out_path, 'w', encoding='utf-8') as out:
        json.dump(payload, out, ensure_ascii=False, indent=2)
    created.append(out_path)
    count += 1

print(f'Generated {count} payloads:')
for c in created:
    print(c)
