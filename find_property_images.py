#!/usr/bin/env python3
import requests, base64
creds=base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
h={'Authorization':f'Basic {creds}'}
base='https://www.loungenie.com/wp-json/wp/v2'
keywords=['six','flags','westin','hilton head','hilton-head','margaritaville','water world','water-world','seaworld','cowabunga','grove','waikoloa']
page=1
matches=[]
while True:
    r=requests.get(f'{base}/media',headers=h,params={'per_page':100,'page':page,'orderby':'date','order':'desc'},timeout=30)
    if r.status_code!=200:
        break
    items=r.json()
    if not items:
        break
    for it in items:
        src=it.get('source_url','')
        fname=src.split('/')[-1].lower()
        alt=(it.get('alt_text','') or it.get('title',{}).get('rendered','') or '').lower()
        text=fname+' '+alt
        if any(k in text for k in keywords):
            w=it.get('media_details',{}).get('width',0)
            hgt=it.get('media_details',{}).get('height',0)
            matches.append((it.get('id'),w*hgt,w,hgt,src,it.get('alt_text','') or it.get('title',{}).get('rendered','')))
    if len(items)<100:
        break
    page+=1

matches.sort(key=lambda x:x[1],reverse=True)
print('matches',len(matches))
for m in matches[:120]:
    print(f'ID={m[0]} {m[2]}x{m[3]} | {m[4]} | {m[5][:80]}')
