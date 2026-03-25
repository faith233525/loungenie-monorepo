import requests, base64, re

b = 'https://www.loungenie.com/wp-json/wp/v2'
token = 'admin:i6IM cqLZ vQDC pIRk nKFr g35i'
h = {'Authorization': 'Basic ' + base64.b64encode(token.encode()).decode()}

j = requests.get(f'{b}/pages/5668?context=edit', headers=h, timeout=30).json()
c = j.get('content', {}).get('raw', '')

# Find 'ir-content-panel' in CSS blocks
for m in re.finditer(r'ir-content-panel', c):
    start = max(0, m.start()-60)
    end = min(len(c), m.start()+300)
    print(f'[pos {m.start()}]')
    print(repr(c[start:end]))
    print()
