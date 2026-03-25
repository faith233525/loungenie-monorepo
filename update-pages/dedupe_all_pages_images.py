import urllib.request
import json
import base64

USER='admin'
PW='i6IM cqLZ vQDC pIRk nKFr g35i'
AUTH='Basic '+base64.b64encode((USER+':'+PW).encode()).decode()
HEADERS={'Authorization':AUTH,'Content-Type':'application/json'}
BASE='https://www.loungenie.com/wp-json/wp/v2'

# Page IDs
HOME=4701
FEATURES=2989
ABOUT=4862
CONTACT=5139
INVESTORS=5668
GALLERY=5223

# Old repeated images
HILTON6='https://www.loungenie.com/wp-content/uploads/2026/03/Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-6.jpg'
HILTON4='https://www.loungenie.com/wp-content/uploads/2026/03/Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-4-scaled.jpg'
GROVE1='https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-1.jpg'

# New unique replacements
A1='https://www.loungenie.com/wp-content/uploads/2026/03/Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-3-scaled.jpg'
A2='https://www.loungenie.com/wp-content/uploads/2026/03/Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-2-scaled.jpg'
A3='https://www.loungenie.com/wp-content/uploads/2026/03/Hilton-Wakoloa-Village-2018-10-Aloha-Falls-Cabana-1-scaled.jpg'
SEA='https://www.loungenie.com/wp-content/uploads/2026/03/Sea-World-San-Diego.jpg'
TG2='https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-2.jpg'
TG5='https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-5.jpg'
TG6C='https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-6-Copy.jpg'
EX1='https://www.loungenie.com/wp-content/uploads/2026/03/38f4fc95-7925-4625-b0e8-5ba78771c037.jpg'


def load_page(pid):
    req=urllib.request.Request(f'{BASE}/pages/{pid}?context=edit',headers=HEADERS)
    with urllib.request.urlopen(req,timeout=40) as r:
        return json.loads(r.read())


def save_page(pid, content):
    payload=json.dumps({'content':content}).encode()
    req=urllib.request.Request(f'{BASE}/pages/{pid}',data=payload,headers=HEADERS,method='POST')
    with urllib.request.urlopen(req,timeout=40) as r:
        return json.loads(r.read())


def replace_nth(s, old, new, n):
    idx=-1
    start=0
    for _ in range(n):
        idx=s.find(old,start)
        if idx==-1:
            return s
        start=idx+len(old)
    return s[:idx]+new+s[idx+len(old):]

updates=[]

# About: make hero/secondary unique (remove HILTON6)
p=load_page(ABOUT); raw=p['content']['raw']; upd=raw
upd=replace_nth(upd,HILTON6,A1,1)
if upd!=raw:
    save_page(ABOUT,upd); updates.append('about')

# Contact: replace HILTON4 with unique
p=load_page(CONTACT); raw=p['content']['raw']; upd=raw.replace(HILTON4,EX1)
if upd!=raw:
    save_page(CONTACT,upd); updates.append('contact')

# Investors: replace HILTON6 with unique
p=load_page(INVESTORS); raw=p['content']['raw']; upd=raw.replace(HILTON6,A2)
if upd!=raw:
    save_page(INVESTORS,upd); updates.append('investors')

# Features: reduce repeats (GROVE1->TG2, HILTON4->A3)
p=load_page(FEATURES); raw=p['content']['raw']; upd=raw
upd=upd.replace(GROVE1,TG2)
upd=upd.replace(HILTON4,A3)
if upd!=raw:
    save_page(FEATURES,upd); updates.append('features')

# Home: keep hero but diversify secondary scenic slots
p=load_page(HOME); raw=p['content']['raw']; upd=raw
upd=replace_nth(upd,GROVE1,TG5,1)
upd=replace_nth(upd,'https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-6.jpg',TG6C,1)
if upd!=raw:
    save_page(HOME,upd); updates.append('home')

# Gallery: keep broad mix but avoid reusing main hero pair too much
p=load_page(GALLERY); raw=p['content']['raw']; upd=raw
upd=replace_nth(upd,HILTON6,SEA,1)
upd=replace_nth(upd,HILTON4,A1,1)
if upd!=raw:
    save_page(GALLERY,upd); updates.append('gallery')

print('updated_pages',updates)
