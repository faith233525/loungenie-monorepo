import requests, base64, re

b = 'https://www.loungenie.com/wp-json/wp/v2'
token = 'admin:i6IM cqLZ vQDC pIRk nKFr g35i'
h = {'Authorization': 'Basic ' + base64.b64encode(token.encode()).decode()}

for pid, name in [(5668, 'investors'), (5651, 'board'), (5686, 'financials'), (5716, 'press')]:
    j = requests.get(f'{b}/pages/{pid}?context=edit', headers=h, timeout=30).json()
    c = j.get('content', {}).get('raw', '')
    print(f'\n=== {name} (pid={pid}) len={len(c)} ===')

    # Show 600 chars around ir-actions
    idx = c.find('ir-actions')
    if idx != -1:
        # find the block comment before it
        start = max(0, idx - 200)
        end = min(len(c), idx + 600)
        print(c[start:end])
    else:
        print('  NO ir-actions found')
