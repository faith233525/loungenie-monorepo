#!/usr/bin/env python3
import requests,re
home='https://www.loungenie.com/'
html=requests.get(home,timeout=30,headers={'User-Agent':'Mozilla/5.0'}).text
logos=[
 'logo-hilton.webp',
 'logo-marriott.webp',
 'logo-westin.webp',
 'logo-ritz.webp',
 'logo-sixflags.webp',
 'margaritaville-jimmy-buffetts-logo-png-transparent.png',
 'logo-color.png',
 'tt-logo-300x121.png.webp',
 'wildrivers-logo-2x.png',
 'logo-atlantis.webp'
]
print('home_img_tags',len(re.findall(r'<img[^>]+>',html,re.I)))
found=0
for n in logos:
    ok=n in html
    print(n,'FOUND' if ok else 'MISSING')
    if ok:
        found+=1
print('found_total',found,'of',len(logos))

# quick URL status check
for n in logos:
    m=re.search(r'https://www\.loungenie\.com/wp-content/uploads/[^"\']*'+re.escape(n),html,re.I)
    if not m:
        continue
    u=m.group(0)
    try:
        r=requests.get(u,timeout=20,allow_redirects=True)
        print('URL',n,r.status_code,len(r.content),r.headers.get('content-type',''))
    except Exception as e:
        print('URL',n,'ERR',e)
