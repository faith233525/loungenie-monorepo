import urllib.request
import json
import base64
import re

user = 'admin'
password = 'i6IM cqLZ vQDC pIRk nKFr g35i'
headers = {
    'Authorization': 'Basic ' + base64.b64encode((user + ':' + password).encode()).decode(),
    'User-Agent': 'Mozilla/5.0',
}

try:
    req = urllib.request.Request('https://www.loungenie.com/wp-json/wp/v2/settings', headers=headers)
    with urllib.request.urlopen(req, timeout=20) as r:
        settings = json.loads(r.read())
    print('show_on_front:', settings.get('show_on_front'))
    print('page_on_front:', settings.get('page_on_front'))
    print('page_for_posts:', settings.get('page_for_posts'))
except Exception as e:
    print('settings_error:', e)

for url in ['https://www.loungenie.com/', 'https://www.loungenie.com/loungenie/']:
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, timeout=20) as r:
            html = r.read().decode('utf-8', errors='replace')
        m = re.search(r'body class="([^"]+)"', html, re.I)
        print('\nURL:', url)
        if m:
            body_classes = m.group(1)
            print('body_classes:', body_classes[:800])
            pid = re.search(r'page-id-(\d+)', body_classes)
            print('page_id_from_body:', pid.group(1) if pid else 'none')
        else:
            print('body_classes: none')
    except Exception as e:
        print('url_error', url, e)
