import urllib.request
import json
import base64

USER = 'admin'
PW = 'i6IM cqLZ vQDC pIRk nKFr g35i'
AUTH = 'Basic ' + base64.b64encode((USER + ':' + PW).encode()).decode()
HEADERS = {'Authorization': AUTH, 'Content-Type': 'application/json'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

NEW_2080 = 'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2080.jpeg'
NEW_2081 = 'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2081.jpeg'
NEW_2078 = 'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2078-scaled.jpeg'
NEW_2079 = 'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2079-scaled.jpeg'
NEW_2089 = 'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2089-scaled.jpeg'
NEW_2090 = 'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2090-scaled.jpeg'


def update_page(pid, edit_fn):
    req = urllib.request.Request(f'{BASE}/pages/{pid}?context=edit', headers=HEADERS)
    with urllib.request.urlopen(req, timeout=30) as r:
        page = json.loads(r.read())
    raw = page['content']['raw']
    updated = edit_fn(raw)
    if updated == raw:
        print(f'page {pid}: no change')
        return
    payload = json.dumps({'content': updated}).encode()
    req2 = urllib.request.Request(f'{BASE}/pages/{pid}', data=payload, headers=HEADERS, method='POST')
    with urllib.request.urlopen(req2, timeout=30) as r2:
        out = json.loads(r2.read())
    print(f"page {pid}: updated ({out.get('status')})")


def edit_features(raw):
    # Features currently repeats IMG_2083 for CHARGE and CHILL; split into unique images.
    # Replace first IMG_2083 -> IMG_2078 (CHARGE slot), second -> IMG_2089 (CHILL slot).
    target = 'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2083.jpeg'
    first = raw.find(target)
    if first != -1:
        raw = raw[:first] + NEW_2078 + raw[first + len(target):]
        second = raw.find(target)
        if second != -1:
            raw = raw[:second] + NEW_2089 + raw[second + len(target):]

    # tighten alt text to reflect lock visibility where applicable
    raw = raw.replace('LounGenie CHARGE — solar-powered USB charging ports at the cabana seat',
                      'LounGenie lock compartment and charging area at the cabana seat')
    raw = raw.replace('LounGenie CHILL — removable ice bucket with locking safe visible in the cabana unit',
                      'LounGenie removable ice bucket with lock panel visible in the unit')
    return raw


def edit_home(raw):
    # Home has one IMG_2083; switch to a different lock-visible shot.
    raw = raw.replace('https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2083.jpeg', NEW_2090)
    raw = raw.replace('LounGenie CHILL — removable ice bucket with locking safe visible in the cabana unit',
                      'LounGenie lock panel and removable ice bucket visible in-cabana')
    return raw


def edit_gallery(raw):
    # Gallery has IMG_2080, 2081, and two IMG_2083s; make all four unique lock-centric images.
    target = 'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2083.jpeg'
    first = raw.find(target)
    if first != -1:
        raw = raw[:first] + NEW_2079 + raw[first + len(target):]
        second = raw.find(target)
        if second != -1:
            raw = raw[:second] + NEW_2089 + raw[second + len(target):]

    # Also refresh one of the repeated pool scenic images in gallery with another lock-view to reduce repetition.
    raw = raw.replace('https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-2.jpg', NEW_2090)

    raw = raw.replace('LounGenie CHARGE — solar-powered USB charging ports at the cabana seat',
                      'LounGenie lock and charging panel detail at the cabana seat')
    raw = raw.replace('LounGenie CHILL — removable ice bucket with locking safe visible in the cabana unit',
                      'LounGenie lock panel with removable ice bucket and safe door visible')
    return raw


update_page(2989, edit_features)
update_page(4701, edit_home)
update_page(5223, edit_gallery)
print('done')
