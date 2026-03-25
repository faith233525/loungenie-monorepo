import urllib.request
import re

pages = [
    ('home', 'https://www.loungenie.com/'),
    ('features', 'https://www.loungenie.com/poolside-amenity-unit/'),
    ('about', 'https://www.loungenie.com/hospitality-innovation/'),
    ('contact', 'https://www.loungenie.com/contact-loungenie/'),
    ('videos', 'https://www.loungenie.com/loungenie-videos/'),
    ('gallery', 'https://www.loungenie.com/cabana-installation-photos/'),
    ('investors', 'https://www.loungenie.com/investors/'),
    ('board', 'https://www.loungenie.com/board/'),
    ('financials', 'https://www.loungenie.com/financials/'),
    ('press', 'https://www.loungenie.com/press/'),
]

broken = []
for label, url in pages:
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    with urllib.request.urlopen(req, timeout=25) as r:
        html = r.read().decode('utf-8', errors='replace')

    img_src = re.findall(r'<img[^>]+src=["\']([^"\']+)["\']', html, re.I)
    data_src = re.findall(r'<img[^>]+data-src=["\']([^"\']+)["\']', html, re.I)
    bg_urls = re.findall(r'background(?:-image)?:\s*url\(["\']?([^"\')\s]+)', html, re.I)
    urls = set(img_src + data_src + bg_urls)

    for img in sorted(urls):
        if img.startswith('data:'):
            continue
        if img.startswith('//'):
            img = 'https:' + img
        elif img.startswith('/'):
            img = 'https://www.loungenie.com' + img

        try:
            req2 = urllib.request.Request(img, headers={'User-Agent': 'Mozilla/5.0'})
            with urllib.request.urlopen(req2, timeout=12) as r2:
                status = r2.status
            if status != 200:
                broken.append((label, 'HTTP ' + str(status), img))
        except Exception as e:
            s = str(e)
            if '404' in s:
                broken.append((label, 'HTTP 404', img))

print('broken_count', len(broken))
for label, err, img in broken:
    print(label, err, img)
