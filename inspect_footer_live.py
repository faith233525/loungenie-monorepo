import requests, re

html = requests.get('https://www.loungenie.com/?v=ftr_inspect1', timeout=30).text

# Find the footer region
idx = html.find('lg9-footer')
if idx != -1:
    print('[lg9-footer region]')
    print(html[max(0, idx-200):idx+3000])
else:
    print('lg9-footer NOT FOUND')
    # Check footer tag
    idx2 = html.find('<footer')
    if idx2 != -1:
        print(html[idx2:idx2+2000])
    else:
        print('no <footer> tag either')

# Also check prefooter
print('\n\n[prefooter region]')
idx3 = html.find('lg9-prefooter')
if idx3 != -1:
    print(html[idx3:idx3+1500])
