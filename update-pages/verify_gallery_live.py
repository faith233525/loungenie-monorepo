#!/usr/bin/env python3
import requests,re
url='https://www.loungenie.com/cabana-installation-photos/'
h=requests.get(url,timeout=30,headers={'User-Agent':'Mozilla/5.0'}).text
imgs=re.findall(r'<img[^>]+src="([^"]+)"',h,re.I)
print('gallery_imgs',len(imgs))
bad=[]
for u in imgs:
    if u.startswith('http'):
        try:
            r=requests.head(u,timeout=10,allow_redirects=True)
            if r.status_code not in (200,301,302):
                bad.append((u,r.status_code))
        except Exception:
            bad.append((u,'ERR'))
print('gallery_broken',len(bad))
for b in bad[:20]:
    print(b)
for phrase in ['vandal','dual usb','insulated','connects to pos','works with any pos']:
    print(phrase,'FOUND' if phrase in h.lower() else 'ok')
