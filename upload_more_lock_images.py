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
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos\IMG_2078.jpeg',
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos\IMG_2079.jpeg',
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos\IMG_2089.jpeg',
    r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos\IMG_2090.jpeg',
]


def find_existing(filename):
    q = urllib.parse.quote(filename)
    url = f"{WP_BASE}/media?search={q}&per_page=20&_fields=id,source_url"
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
        return existing['source_url'], 'existing'

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
    return data.get('source_url'), 'uploaded'

for p in files:
    url, st = upload_file(p)
    print(st, os.path.basename(p), url)
