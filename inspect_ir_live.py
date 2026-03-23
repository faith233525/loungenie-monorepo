import requests, base64, re

b = 'https://www.loungenie.com/wp-json/wp/v2'
token = 'admin:i6IM cqLZ vQDC pIRk nKFr g35i'
h = {'Authorization': 'Basic ' + base64.b64encode(token.encode()).decode()}

ROOT = 'https://www.loungenie.com'

for pid, name in [(5651, 'board'), (5686, 'financials'), (5716, 'press')]:
    j = requests.get(f'{b}/pages/{pid}?context=edit', headers=h, timeout=30).json()
    c = j.get('content', {}).get('raw', '')

    # Find ir-actions div
    idx = c.find('<div class="ir-actions">')
    if idx != -1:
        print(f'{name} ir-actions div:\n  {repr(c[idx:idx+350])}\n')
    else:
        print(f'{name}: NO ir-actions div found')

    # Find toolbar-note
    idx2 = c.find('ir-toolbar-note')
    if idx2 != -1:
        print(f'{name} toolbar-note context:\n  {repr(c[idx2-80:idx2+220])}\n')
    else:
        print(f'{name}: NO ir-toolbar-note found')
