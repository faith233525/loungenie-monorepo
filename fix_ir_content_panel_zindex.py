import requests, base64

b = 'https://www.loungenie.com/wp-json/wp/v2'
token = 'admin:i6IM cqLZ vQDC pIRk nKFr g35i'
h = {'Authorization': 'Basic ' + base64.b64encode(token.encode()).decode()}

old = (
    '.ir-shell .ir-content-panel {\n'
    '    margin-top: -64px;\n'
)
new = (
    '.ir-shell .ir-content-panel {\n'
    '    position: relative;\n'
    '    z-index: 1;\n'
    '    margin-top: -64px;\n'
)

pages = [(5668, 'investors'), (5651, 'board'), (5686, 'financials'), (5716, 'press')]

for pid, name in pages:
    j = requests.get(f'{b}/pages/{pid}?context=edit', headers=h, timeout=30).json()
    c = j.get('content', {}).get('raw', '')
    if old in c:
        c = c.replace(old, new)
        r = requests.post(
            f'{b}/pages/{pid}',
            headers={**h, 'Content-Type': 'application/json'},
            json={'content': c},
            timeout=30,
        )
        print(f'{name}: fixed, status={r.status_code}')
    else:
        print(f'{name}: pattern not found')
        # Try to find what's there
        idx = c.find('.ir-shell .ir-content-panel')
        if idx != -1:
            print(f'  actual: {repr(c[idx:idx+120])}')
