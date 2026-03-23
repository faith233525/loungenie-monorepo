import requests
import base64

b = 'https://loungenie.com/staging/wp-json/wp/v2'
h = {'Authorization': 'Basic ' + base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()}

slugs = [
    'home',
    'poolside-amenity-unit',
    'hospitality-innovation',
    'contact-loungenie',
    'loungenie-videos',
    'cabana-installation-photos',
    'investors',
    'board',
    'financials',
    'press',
]

for slug in slugs:
    r = requests.get(
        f'{b}/pages',
        headers=h,
        params={'slug': slug, 'context': 'edit', 'per_page': 1},
        timeout=30,
    )
    arr = r.json()
    if not arr:
        print(f'{slug}: NOT_FOUND')
        continue
    p = arr[0]
    c = p.get('content', {}).get('raw', '')
    print(
        f"{slug}: id={p['id']} len={len(c)} "
        f"wp_html={c.count('<!-- wp:html -->')} "
        f"groups={c.count('<!-- wp:group')} "
        f"images={c.count('<!-- wp:image')} "
        f"cols={c.count('<!-- wp:columns')} "
        f"headings={c.count('<!-- wp:heading')}"
    )
