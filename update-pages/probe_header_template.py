import requests, base64

b = 'https://www.loungenie.com/wp-json/wp/v2'
token = 'admin:i6IM cqLZ vQDC pIRk nKFr g35i'
h = {'Authorization': 'Basic ' + base64.b64encode(token.encode()).decode()}

# Fetch the header template-part
r = requests.get(
    f'{b}/template-parts/twentytwentyfour//header?context=edit',
    headers=h, timeout=30
)
j = r.json()
c = j.get('content', {}).get('raw', '') if isinstance(j.get('content'), dict) else j.get('content', '')
print(f'header template len={len(c)}')

# Find the ir-content-panel rule
idx = c.find('.ir-shell .ir-content-panel')
if idx != -1:
    print(f'Found at {idx}:')
    print(repr(c[idx:idx+200]))
else:
    print('NOT FOUND - search for margin-top: -64px')
    idx2 = c.find('margin-top: -64px')
    if idx2 != -1:
        print(repr(c[max(0,idx2-120):idx2+200]))
    else:
        idx3 = c.find('ir-content-panel')
        if idx3 != -1:
            print(repr(c[max(0,idx3-100):idx3+300]))
        else:
            print('Neither found in header template-part!')
