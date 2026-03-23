import requests, re

html = requests.get('https://loungenie.com/staging/?v=ftr_inspect2', timeout=30).text

# Find <footer tag
idx = html.find('<footer')
if idx != -1:
    print('[<footer> tag found]')
    print(html[idx:idx+4000])
else:
    print('NO <footer> tag found')
    # Find the footer template-part in the body
    idx2 = html.rfind('</main>')
    if idx2 != -1:
        print(f'Content after </main> (last {len(html)-idx2} chars):')
        print(html[idx2:idx2+3000])
    else:
        idx3 = html.rfind('lg9-prefooter')
        if idx3 != -1:
            print('[lg9-prefooter HTML]')
            print(html[idx3:idx3+4000])
