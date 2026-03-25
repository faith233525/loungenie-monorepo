import requests, base64

b = 'https://www.loungenie.com/wp-json/wp/v2'
token = 'admin:i6IM cqLZ vQDC pIRk nKFr g35i'
h = {'Authorization': 'Basic ' + base64.b64encode(token.encode()).decode()}

r = requests.get(
    f'{b}/template-parts/twentytwentyfour//header?context=edit',
    headers=h, timeout=30
)
j = r.json()
c = j.get('content', {}).get('raw', '') if isinstance(j.get('content'), dict) else j.get('content', '')

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

if old in c:
    c = c.replace(old, new)
    r2 = requests.post(
        f'{b}/template-parts/twentytwentyfour//header',
        headers={**h, 'Content-Type': 'application/json'},
        json={'content': c, 'status': 'publish'},
        timeout=30,
    )
    print(f'header_patched status={r2.status_code}')
else:
    print('Pattern not found!')
    idx = c.find('.ir-shell .ir-content-panel')
    if idx != -1:
        print(repr(c[idx:idx+200]))
