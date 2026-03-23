import urllib.request
import json
import base64
import re

user='admin'
pw='i6IM cqLZ vQDC pIRk nKFr g35i'
auth='Basic '+base64.b64encode((user+':'+pw).encode()).decode()
headers={'Authorization':auth}
base='https://www.loungenie.com/wp-json/wp/v2'

for pid,name in [(4701,'home'),(2989,'features'),(5223,'gallery')]:
    req=urllib.request.Request(f'{base}/pages/{pid}?context=edit',headers=headers)
    with urllib.request.urlopen(req,timeout=30) as r:
        d=json.loads(r.read())
    raw=d['content']['raw']
    print(f'\n=== {name} {pid} len={len(raw)} ===')
    imgs=re.findall(r'<img[^>]+src="([^"]+)"[^>]*>',raw,re.I)
    for u in imgs:
        if '/uploads/' in u:
            print(' ',u)
    for phrase in ['Zero upfront','Revenue share','Request Demo','Book Demo','round']:
        print(phrase, raw.lower().find(phrase.lower()))
