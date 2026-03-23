#!/usr/bin/env python3
import requests
import re
from collections import Counter
import base64

url = 'https://www.loungenie.com/cabana-installation-photos/'
html = requests.get(url, timeout=30, headers={'User-Agent': 'Mozilla/5.0'}).text
rendered_imgs = re.findall(r'<img[^>]+src="([^"]+)"', html, re.I)
print('rendered_img_count', len(rendered_imgs))
rc = Counter(rendered_imgs)
rendered_dupes = [(k, v) for k, v in rc.items() if v > 1]
print('rendered_duplicates', len(rendered_dupes))
for k, v in sorted(rendered_dupes, key=lambda x: -x[1]):
    print(v, k)

creds = base64.b64encode('Copilot:U7GM Z9qE QOq6 MQva IzcQ 6PU2'.encode()).decode()
h = {'Authorization': f'Basic {creds}'}
d = requests.get('https://www.loungenie.com/wp-json/wp/v2/pages/5223', headers=h, timeout=30).json()
content = d.get('content', {}).get('rendered', '')
source_imgs = re.findall(r'https://www\.loungenie\.com/wp-content/uploads/[^\s"\'<>]+\.(?:jpe?g|png|webp|avif)', content, re.I)
print('source_img_count', len(source_imgs))
sc = Counter(source_imgs)
source_dupes = [(k, v) for k, v in sc.items() if v > 1]
print('source_duplicates', len(source_dupes))
for k, v in sorted(source_dupes, key=lambda x: -x[1]):
    print(v, k)
