import requests, base64, re

b = 'https://www.loungenie.com/wp-json/wp/v2'
token = 'admin:i6IM cqLZ vQDC pIRk nKFr g35i'
h = {'Authorization': 'Basic ' + base64.b64encode(token.encode()).decode()}

j = requests.get(f'{b}/pages/5668?context=edit', headers=h, timeout=30).json()
c = j.get('content', {}).get('raw', '')

# Find where the GLOBAL_STYLE CSS block is — search for margin-top: -64px
idx = c.find('margin-top: -64px')
if idx != -1:
    print(repr(c[max(0, idx-200):idx+300]))
else:
    print('margin-top: -64px NOT FOUND')
    # Check how the page starts
    print('\nFirst 400 chars:')
    print(repr(c[:400]))
