#!/usr/bin/env python3
import requests,re
home='https://www.loungenie.com/'
html=requests.get(home,timeout=30,headers={'User-Agent':'Mozilla/5.0'}).text
imgs=re.findall(r'<img[^>]+>',html,re.I)
print('home_img_tags',len(imgs))
keys=('logo-','hilton','marriott','westin','ritz','sixflags','atlantis','margaritaville')
for tag in imgs:
    s=re.search(r'src="([^"]+)"',tag,re.I)
    d=re.search(r'data-src="([^"]+)"',tag,re.I)
    a=re.search(r'alt="([^"]*)"',tag,re.I)
    u=(s.group(1) if s else (d.group(1) if d else ''))
    lu=u.lower()
    if any(k in lu for k in keys):
        print('URL:',u)
        print('ALT:',a.group(1) if a else '')
        print('TAG:',tag[:260])
        print('-'*40)
