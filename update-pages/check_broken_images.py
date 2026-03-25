import urllib.request, re, json

pages = [
    ('home',       'https://www.loungenie.com/loungenie/'),
    ('features',   'https://www.loungenie.com/loungenie/poolside-amenity-unit/'),
    ('about',      'https://www.loungenie.com/loungenie/hospitality-innovation/'),
    ('contact',    'https://www.loungenie.com/loungenie/contact-loungenie/'),
    ('videos',     'https://www.loungenie.com/loungenie/loungenie-videos/'),
    ('gallery',    'https://www.loungenie.com/loungenie/cabana-installation-photos/'),
    ('investors',  'https://www.loungenie.com/loungenie/investors/'),
    ('board',      'https://www.loungenie.com/loungenie/board/'),
    ('financials', 'https://www.loungenie.com/loungenie/financials/'),
    ('press',      'https://www.loungenie.com/loungenie/press/'),
]

broken = []

for label, url in pages:
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, timeout=20) as r:
            html = r.read().decode('utf-8', errors='replace')

        # All img src attributes
        imgs = re.findall(r'<img[^>]+src=["\']([^"\']+)["\']', html, re.I)
        # Also background-image URLs in style attrs
        bg_imgs = re.findall(r'background(?:-image)?:\s*url\(["\']?([^"\')\s]+)["\']?\)', html, re.I)
        all_imgs = list(set(imgs + bg_imgs))

        print(f'\n=== {label} ({len(all_imgs)} unique imgs) ===')
        for img in sorted(all_imgs):
            if img.startswith('data:'):
                continue
            # Make absolute
            if img.startswith('//'):
                img = 'https:' + img
            elif img.startswith('/'):
                img = 'https://www.loungenie.com' + img
            try:
                r2 = urllib.request.urlopen(
                    urllib.request.Request(img, headers={'User-Agent': 'Mozilla/5.0'}),
                    timeout=10
                )
                status = r2.status
                if status != 200:
                    print(f'  [HTTP {status}] {img}')
                    broken.append((label, img, f'HTTP {status}'))
                # else: OK, silent
            except urllib.error.HTTPError as e:
                print(f'  [HTTP {e.code}] {img}')
                broken.append((label, img, f'HTTP {e.code}'))
            except Exception as e:
                short = str(e)[:60]
                print(f'  [ERR: {short}] {img}')
                broken.append((label, img, short))

    except Exception as e:
        print(f'{label}: PAGE ERROR {e}')

print('\n\n=== SUMMARY OF BROKEN/MISSING IMAGES ===')
if broken:
    for label, img, err in broken:
        print(f'  [{label}] {err}  ->  {img}')
else:
    print('  All images OK!')
