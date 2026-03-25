import os
import json
import base64
import mimetypes
import urllib.request
import urllib.parse

WP_BASE = 'https://www.loungenie.com/wp-json/wp/v2'
USER = 'admin'
APP_PASSWORD = 'i6IM cqLZ vQDC pIRk nKFr g35i'
AUTH = 'Basic ' + base64.b64encode((USER + ':' + APP_PASSWORD).encode()).decode()

files = [
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\logo-hilton.webp',
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\logo-marriott.webp',
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\logo-westin.webp',
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\logo-ritz.webp',
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\logo-sixflags.webp',
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\logo-atlantis.webp',
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos\IMG_2080.jpeg',
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos\IMG_2081.jpeg',
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos\IMG_2083.jpeg',
]


def find_existing(filename):
    q = urllib.parse.quote(filename)
    url = f"{WP_BASE}/media?search={q}&per_page=20&_fields=id,source_url,title"
    req = urllib.request.Request(url, headers={'Authorization': AUTH})
    with urllib.request.urlopen(req, timeout=30) as r:
        items = json.loads(r.read())
    for item in items:
        src = (item.get('source_url') or '').lower()
        if filename.lower() in src:
            return item
    return None


def upload_file(path):
    filename = os.path.basename(path)
    existing = find_existing(filename)
    if existing:
        return {'filename': filename, 'id': existing['id'], 'url': existing['source_url'], 'status': 'existing'}

    with open(path, 'rb') as f:
        payload = f.read()

    mime = mimetypes.guess_type(filename)[0] or 'application/octet-stream'
    headers = {
        'Authorization': AUTH,
        'Content-Type': mime,
        'Content-Disposition': f'attachment; filename="{filename}"',
    }
    req = urllib.request.Request(f"{WP_BASE}/media", data=payload, headers=headers, method='POST')
    with urllib.request.urlopen(req, timeout=60) as r:
        data = json.loads(r.read())
    return {'filename': filename, 'id': data.get('id'), 'url': data.get('source_url'), 'status': 'uploaded'}


results = []
for p in files:
    try:
        res = upload_file(p)
        results.append(res)
        print(f"{res['status']:8s} | {res['filename']} | id={res['id']} | {res['url']}")
    except Exception as e:
        print(f"ERROR    | {os.path.basename(p)} | {e}")

print('\nJSON_MAP=')
print(json.dumps({r['filename']: r['url'] for r in results}, indent=2))
