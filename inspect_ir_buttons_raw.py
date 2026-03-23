import requests, base64, re

b = 'https://www.loungenie.com/wp-json/wp/v2'
token = 'admin:i6IM cqLZ vQDC pIRk nKFr g35i'
h = {'Authorization': 'Basic ' + base64.b64encode(token.encode()).decode()}

for pid, name in [(5668, 'investors'), (5651, 'board'), (5686, 'financials'), (5716, 'press')]:
    j = requests.get(f'{b}/pages/{pid}?context=edit', headers=h, timeout=30).json()
    c = j.get('content', {}).get('raw', '')
    print(f'\n=== {name} (pid={pid}) ===')

    # Find 'Latest Financials' context
    idx = c.find('Latest Financials')
    if idx != -1:
        start = max(0, idx - 300)
        end = min(len(c), idx + 400)
        print('[Latest Financials context]')
        print(c[start:end])
    else:
        print('  Latest Financials NOT FOUND')
    
    # find all occurrences of wp:buttons
    for m in re.finditer(r'<!-- wp:buttons', c):
        end2 = min(len(c), m.start() + 500)
        print(f'\n[wp:buttons at {m.start()}]')
        print(c[m.start():end2])
