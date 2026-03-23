#!/usr/bin/env python3
import requests, base64, re

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}
base = 'https://www.loungenie.com/wp-json/wp/v2'
pages = [(5668,'investors'),(5651,'board'),(5686,'financials'),(5716,'press')]

for pid,name in pages:
    c = requests.get(f'{base}/pages/{pid}', headers=headers, timeout=30).json().get('content', {}).get('rendered', '')
    links = re.findall(r'<a[^>]+href="([^"]+)"[^>]*>(.*?)</a>', c, re.I | re.S)
    print(f"\n{name} links={len(links)}")
    for href,text in links[:15]:
        t = re.sub(r'<[^>]+>','',text).strip()
        print(' ',href,'|',t[:90])
