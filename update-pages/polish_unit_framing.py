import urllib.request
import json
import base64
import re

USER='admin'
PW='i6IM cqLZ vQDC pIRk nKFr g35i'
AUTH='Basic '+base64.b64encode((USER+':'+PW).encode()).decode()
HEADERS={'Authorization':AUTH,'Content-Type':'application/json'}
BASE='https://www.loungenie.com/wp-json/wp/v2'

PAGES=[4701,2989,5223]
UNIT_FILES=['IMG_2080.jpeg','IMG_2081.jpeg','IMG_2083.jpeg']
OLD_FILES=['IMG_3233-scaled-1.jpg','IMG_3235-scaled-1.jpg','IMG_3239-scaled-1.jpg','IMG_3241-scaled-1.jpg','IMG_2071.jpeg','IMG_2072.jpeg','IMG_2073.jpeg','IMG_2074.jpeg']


def patch_tag(tag):
    low=tag.lower()
    if not any(f.lower() in low for f in UNIT_FILES):
        return tag

    style_add='object-fit:contain;object-position:center;background:#f5f8fb;'
    if 'style=' in low:
        tag=re.sub(r'style\s*=\s*"([^"]*)"', lambda m: 'style="'+m.group(1)+';'+style_add+'"', tag, count=1, flags=re.I)
    else:
        tag=tag[:-1]+' style="width:100%;height:100%;'+style_add+'">'

    if 'loading=' not in tag.lower():
        tag=tag[:-1]+' loading="eager">'
    return tag

for pid in PAGES:
    req=urllib.request.Request(f'{BASE}/pages/{pid}?context=edit',headers=HEADERS)
    with urllib.request.urlopen(req,timeout=40) as r:
        page=json.loads(r.read())
    raw=page['content']['raw']

    updated=raw
    updated=re.sub(r'<img\b[^>]*>', lambda m: patch_tag(m.group(0)), updated, flags=re.I)

    # Safety: hard-remove old lock image references if any remain in these pages
    for old in OLD_FILES:
        updated=updated.replace(old,'IMG_2083.jpeg')

    if updated==raw:
        print(f'page {pid}: no change')
        continue

    payload=json.dumps({'content':updated}).encode()
    req2=urllib.request.Request(f'{BASE}/pages/{pid}',data=payload,headers=HEADERS,method='POST')
    with urllib.request.urlopen(req2,timeout=40) as r2:
        res=json.loads(r2.read())
    print(f"page {pid}: updated status={res.get('status')}")

print('done')
