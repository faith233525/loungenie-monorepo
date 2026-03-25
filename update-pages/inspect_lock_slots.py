import urllib.request
import json
import base64
import re

USER='admin'
PW='i6IM cqLZ vQDC pIRk nKFr g35i'
AUTH='Basic '+base64.b64encode((USER+':'+PW).encode()).decode()
HEADERS={'Authorization':AUTH}
BASE='https://www.loungenie.com/wp-json/wp/v2'

for pid,slug in [(4701,'home'),(2989,'features'),(5223,'gallery')]:
    req=urllib.request.Request(f'{BASE}/pages/{pid}?context=edit',headers=HEADERS)
    with urllib.request.urlopen(req,timeout=30) as r:
        raw=json.loads(r.read())['content']['raw']

    print('\n===',slug,'===')
    for m in re.finditer(r'<img[^>]+>',raw,re.I):
        tag=m.group(0)
        if 'IMG_208' in tag or 'IMG_323' in tag or 'IMG_207' in tag:
            src=re.search(r'src="([^"]+)"',tag,re.I)
            alt=re.search(r'alt="([^"]*)"',tag,re.I)
            print('SRC:',src.group(1) if src else '?')
            print('ALT:',alt.group(1) if alt else '(none)')
            print('TAG:',tag)
            print('---')
