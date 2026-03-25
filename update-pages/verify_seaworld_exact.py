#!/usr/bin/env python3
import requests
import base64
import re

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}'}

c = requests.get('https://www.loungenie.com/wp-json/wp/v2/pages/5223', headers=headers, timeout=30).json().get('content', {}).get('rendered', '')
imgs = re.findall(r'<img[^>]+src="([^"]+)"', c, re.I)
sw = [u for u in imgs if 'Sea-World-San-Diego' in u]
print('sea_world_img_tags', len(sw))
for u in sw:
    print(u)
