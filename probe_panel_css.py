import requests, base64, re

b = 'https://www.loungenie.com/wp-json/wp/v2'
token = 'admin:i6IM cqLZ vQDC pIRk nKFr g35i'
h = {'Authorization': 'Basic ' + base64.b64encode(token.encode()).decode()}

j = requests.get(f'{b}/pages/5668?context=edit', headers=h, timeout=30).json()
c = j.get('content', {}).get('raw', '')
idx = c.find('.ir-shell .ir-content-panel')
if idx != -1:
    print(repr(c[idx:idx+200]))
else:
    print('NOT FOUND')
    # show relevant area
    idx2 = c.find('ir-content-panel')
    if idx2 != -1:
        print(repr(c[max(0,idx2-30):idx2+200]))
