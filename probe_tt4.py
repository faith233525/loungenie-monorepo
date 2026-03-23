import urllib.request, re

req = urllib.request.Request(
    'https://loungenie.com/Loungenie%E2%84%A2/',
    headers={'User-Agent': 'Mozilla/5.0'}
)
with urllib.request.urlopen(req, timeout=25) as r:
    html = r.read().decode('utf-8', errors='replace')

body_start = html.find('<body')
print('=== BODY TAG ===')
print(html[body_start:body_start+400])

print('\n=== MAIN WRAPPER ===')
idx = html.find('<main')
if idx > 0:
    print(html[idx:idx+300])

print('\n=== WP-SITE-BLOCKS ===')
idx = html.find('wp-site-blocks')
if idx > 0:
    print(html[max(0,idx-30):idx+300])
else:
    print('NOT FOUND')

print('\n=== ENTRY-CONTENT ===')
idx = html.find('entry-content')
if idx > 0:
    print(html[max(0,idx-80):idx+200])
else:
    print('NOT FOUND - TT4 probably uses different wrapper')

print('\n=== WP-BLOCK-POST-CONTENT ===')
idx = html.find('wp-block-post-content')
if idx > 0:
    print(html[max(0,idx-30):idx+300])
else:
    print('NOT FOUND')

print('\n=== FIRST 2000 chars after <body ===')
print(html[body_start:body_start+2000])

print('\n=== STYLESHEETS ===')
styles = re.findall(r"href=['\"]([^'\"]+\.css[^'\"]*)['\"]", html)
for s in styles[:15]:
    print(s)
