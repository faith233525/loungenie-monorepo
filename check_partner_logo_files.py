#!/usr/bin/env python3
import requests
base='https://www.loungenie.com/wp-content/uploads/2026/03/'
names=['logo-hilton.webp','logo-marriott.webp','logo-westin.webp','logo-ritz.webp','logo-sixflags.webp','logo-atlantis.webp']
for n in names:
    u=base+n
    try:
        r=requests.get(u,timeout=20,allow_redirects=True)
        print(n,r.status_code,len(r.content),r.headers.get('content-type',''))
    except Exception as e:
        print(n,'ERR',e)
