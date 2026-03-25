import requests, base64, re

b = 'https://loungenie.com/staging/wp-json/wp/v2'
h = {'Authorization': 'Basic ' + base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()}
j = requests.get(f'{b}/pages/5668?context=edit', headers=h, timeout=30).json()
c = j.get('content', {}).get('raw', '')

print('=== TOTAL LENGTH:', len(c))

# Find last ir-editable-content occurrence (the rendered copy inside)
marker = 'ir-editable-content'
positions = [m.start() for m in re.finditer(marker, c)]
print('ir-editable-content positions:', positions)

# Show content of the LAST (innermost rendered copy) editable area
if positions:
    last = positions[-1]
    print()
    print('=== CONTENT FROM LAST ir-editable-content ===')
    print(c[last:last+4000])
