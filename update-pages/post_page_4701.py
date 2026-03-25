import requests
import json
import sys

HTML_PATH = 'home_4701_kadence.html'
OUT_JSON = 'page_4701_update.json'
URL = 'https://loungenie.com/staging/wp-json/wp/v2/pages/4701'
USER = 'copilot'
# App password provided by user (spaces removed)
APP_PASSWORD = '0lVZlpeKN5YyWsb1ss5a4Rtx'

try:
    with open(HTML_PATH, 'r', encoding='utf-8') as f:
        html = f.read()
except Exception as e:
    print('ERROR: cannot read', HTML_PATH, e)
    sys.exit(2)

payload = {'content': {'raw': html}}

try:
    r = requests.patch(URL, json=payload, auth=(USER, APP_PASSWORD), timeout=30)
except Exception as e:
    print('REQUEST ERROR:', e)
    sys.exit(2)

with open(OUT_JSON, 'w', encoding='utf-8') as out:
    out.write(r.text)

print('HTTP', r.status_code, r.reason)
if r.status_code >= 400:
    print('Update failed; response saved to', OUT_JSON)
    if r.status_code in (401, 403):
        print('Auth failed (401/403). Verify application password and user privileges.')
    sys.exit(1)

print('Update successful; response saved to', OUT_JSON)
