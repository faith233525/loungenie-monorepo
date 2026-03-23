#!/usr/bin/env python3
import base64
import json
import mimetypes
import os
import re
import urllib.parse
import urllib.request

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
USER = 'admin'
APP_PASSWORD = 'i6IM cqLZ vQDC pIRk nKFr g35i'
AUTH = 'Basic ' + base64.b64encode(f'{USER}:{APP_PASSWORD}'.encode()).decode()

PHOTO_ROOT = r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos'
ASSETS = [
    {
        'source_name': '1746838260488.jpg',
        'upload_name': 'six-flags-hurricane-harbor-cabana.jpg',
        'alt_text': 'Six Flags Hurricane Harbor private cabana deployment with LounGenie positioned at the seating area',
    },
    {
        'source_name': '1746838260534.jpg',
        'upload_name': 'six-flags-hurricane-harbor-lineup-angle.jpg',
        'alt_text': 'Six Flags Hurricane Harbor new-lock LounGenie units staged for deployment',
    },
    {
        'source_name': '1746838260021.jpg',
        'upload_name': 'six-flags-hurricane-harbor-lineup-front.jpg',
        'alt_text': 'Six Flags Hurricane Harbor current-production LounGenie units showing the updated lock hardware',
    },
]


def request_json(url, method='GET', data=None, extra_headers=None, timeout=60):
    headers = {'Authorization': AUTH}
    if extra_headers:
        headers.update(extra_headers)
    req = urllib.request.Request(url, data=data, headers=headers, method=method)
    with urllib.request.urlopen(req, timeout=timeout) as response:
        body = response.read()
    return json.loads(body) if body else {}


def find_existing(filename):
    query = urllib.parse.quote(filename)
    url = f'{BASE}/media?search={query}&per_page=20&_fields=id,source_url'
    items = request_json(url)
    for item in items:
        source_url = (item.get('source_url') or '').lower()
        if filename.lower() in source_url:
            return item
    return None


def update_alt_text(media_id, alt_text):
    payload = json.dumps({'alt_text': alt_text}).encode()
    return request_json(
        f'{BASE}/media/{media_id}',
        method='POST',
        data=payload,
        extra_headers={'Content-Type': 'application/json'},
        timeout=60,
    )


def upload_or_reuse(asset):
    existing = find_existing(asset['upload_name'])
    if existing:
        update_alt_text(existing['id'], asset['alt_text'])
        return existing['source_url'], 'existing'

    source_path = os.path.join(PHOTO_ROOT, asset['source_name'])
    if not os.path.exists(source_path):
        raise FileNotFoundError(source_path)

    with open(source_path, 'rb') as file_handle:
        payload = file_handle.read()

    mime_type = mimetypes.guess_type(asset['upload_name'])[0] or 'application/octet-stream'
    upload = request_json(
        f'{BASE}/media',
        method='POST',
        data=payload,
        extra_headers={
            'Content-Type': mime_type,
            'Content-Disposition': f'attachment; filename="{asset["upload_name"]}"',
        },
        timeout=120,
    )
    update_alt_text(upload['id'], asset['alt_text'])
    return upload['source_url'], 'uploaded'


def get_page(page_id):
    return request_json(f'{BASE}/pages/{page_id}?context=edit', timeout=60)


def update_page(page_id, content):
    payload = json.dumps({'content': content, 'status': 'publish'}).encode()
    return request_json(
        f'{BASE}/pages/{page_id}',
        method='POST',
        data=payload,
        extra_headers={'Content-Type': 'application/json'},
        timeout=120,
    )


def build_gallery_section(cabana_url, angle_url, front_url):
    return f'''<section class="gx-sec">
<div class="gx-head">
<h2>Six Flags Hurricane Harbor Deployment Views</h2>
<p>Recent Six Flags Hurricane Harbor deployment views showing the newer lock hardware and current production units in the field</p>
</div>
<div class="gx-grid3">
<div class="gx-card"><img src="{cabana_url}" alt="Six Flags Hurricane Harbor private cabana deployment with LounGenie positioned at the seating area" loading="eager" decoding="async" class="litespeed-no-lazyload skip-lazy no-lazyload"></p>
<div class="gx-cap">Private cabana view</div>
</div>
<div class="gx-card"><img src="{angle_url}" alt="Six Flags Hurricane Harbor new-lock LounGenie units staged for deployment" loading="eager" decoding="async" class="litespeed-no-lazyload skip-lazy no-lazyload"></p>
<div class="gx-cap">New-lock unit lineup</div>
</div>
<div class="gx-card"><img src="{front_url}" alt="Six Flags Hurricane Harbor current-production LounGenie units showing the updated lock hardware" loading="eager" decoding="async" class="litespeed-no-lazyload skip-lazy no-lazyload"></p>
<div class="gx-cap">Updated lock hardware</div>
</div></div>
</section>'''


def refresh_features(page_content, cabana_url):
    updated = page_content.replace(
        'https://www.loungenie.com/wp-content/uploads/2025/10/Water-World-staging-2-scaled.jpg',
        cabana_url,
    )
    updated = updated.replace(
        'Water World shaded premium seating deployment with LounGenie positioned beside private cabanas',
        'Six Flags Hurricane Harbor private cabana deployment with LounGenie positioned at the seating area',
    )
    if updated == page_content:
        raise RuntimeError('features page replacement did not match current content')
    return updated


def refresh_gallery(page_content, cabana_url, angle_url, front_url):
    pattern = re.compile(
        r'<section class="gx-sec">\s*<div class="gx-head">\s*<h2>Water World Deployment Views</h2>.*?</section>',
        re.S,
    )
    replacement = build_gallery_section(cabana_url, angle_url, front_url)
    updated, count = pattern.subn(replacement, page_content, count=1)
    if count != 1:
        raise RuntimeError('gallery Water World section not found')
    return updated


def main():
    uploaded_urls = {}
    for asset in ASSETS:
        source_url, status = upload_or_reuse(asset)
        uploaded_urls[asset['upload_name']] = source_url
        print(status.upper(), asset['upload_name'], source_url)

    features = get_page(2989)
    updated_features = refresh_features(
        features.get('content', {}).get('raw', ''),
        uploaded_urls['six-flags-hurricane-harbor-cabana.jpg'],
    )
    update_page(2989, updated_features)
    print('UPDATED features')

    gallery = get_page(5223)
    updated_gallery = refresh_gallery(
        gallery.get('content', {}).get('raw', ''),
        uploaded_urls['six-flags-hurricane-harbor-cabana.jpg'],
        uploaded_urls['six-flags-hurricane-harbor-lineup-angle.jpg'],
        uploaded_urls['six-flags-hurricane-harbor-lineup-front.jpg'],
    )
    update_page(5223, updated_gallery)
    print('UPDATED gallery')


if __name__ == '__main__':
    main()
