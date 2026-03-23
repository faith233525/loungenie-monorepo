#!/usr/bin/env python3
"""Check schema markup and navigation menus."""
import requests, base64, re, json

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
hdrs = {'Authorization': f'Basic {creds}'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

# [1] Check schema on rendered homepage
print("[1] SCHEMA MARKUP CHECK (homepage)")
resp = requests.get('https://www.loungenie.com/', headers={'User-Agent': 'Mozilla/5.0'}, timeout=20)
html = resp.text

# Find all JSON-LD scripts
schemas = re.findall(r'<script type="application/ld\+json"[^>]*>(.*?)</script>', html, re.DOTALL|re.I)
if schemas:
    print(f"  Found {len(schemas)} JSON-LD schema block(s):")
    for i, s in enumerate(schemas):
        try:
            d = json.loads(s.strip())
            schema_type = d.get('@type', 'unknown') if isinstance(d, dict) else 'array'
            print(f"  Schema {i+1}: @type={schema_type}")
            if isinstance(d, dict) and '@type' in d:
                print(f"    keys: {list(d.keys())[:8]}")
        except:
            print(f"  Schema {i+1}: (parse error)")
else:
    print("  ✗ No JSON-LD schema found on homepage!")

# Check schema on features and about
for url, name in [('https://www.loungenie.com/poolside-amenity-unit/', 'features'),
                   ('https://www.loungenie.com/hospitality-innovation/', 'about')]:
    resp2 = requests.get(url, headers={'User-Agent': 'Mozilla/5.0'}, timeout=20)
    schemas2 = re.findall(r'<script type="application/ld\+json"[^>]*>(.*?)</script>', resp2.text, re.DOTALL|re.I)
    print(f"  {name}: {len(schemas2)} schema block(s)")

# [2] Check WordPress menus
print("\n[2] NAVIGATION MENUS")
r = requests.get(f'{BASE}/../../wp-json', headers=hdrs, timeout=20)
# Use menus REST endpoint
r_menus = requests.get('https://www.loungenie.com/wp-json/wp/v2/menus', headers=hdrs, timeout=20)
if r_menus.status_code == 200:
    menus = r_menus.json()
    print(f"  Menus found: {len(menus)}")
    for m in menus:
        print(f"    ID={m['id']} | {m.get('name','')} | {m.get('slug','')} | items={m.get('count',0)}")
else:
    print(f"  Menus endpoint: HTTP {r_menus.status_code}")
    # Try alternative approach
    r_nav = requests.get('https://www.loungenie.com/wp-json/wp/v2/menu-items', headers=hdrs, 
                         params={'per_page': 50}, timeout=20)
    if r_nav.status_code == 200:
        items = r_nav.json()
        print(f"  Menu items found: {len(items)}")
        for item in items:
            title = item.get('title', {}).get('rendered', '') if isinstance(item.get('title'), dict) else str(item.get('title',''))
            url_val = item.get('url', '')
            parent = item.get('parent', 0)
            order = item.get('menu_order', 0)
            print(f"    [{order}] {title:<30} → {url_val} (parent:{parent})")
    else:
        print(f"  menu-items: HTTP {r_nav.status_code}")

# [3] Check OG images specifically for home and about
print("\n[3] OG IMAGE DETAIL — home and about")
for url, name in [('https://www.loungenie.com/', 'home'),
                   ('https://www.loungenie.com/hospitality-innovation/', 'about')]:
    resp3 = requests.get(url, headers={'User-Agent': 'Mozilla/5.0'}, timeout=20)
    html3 = resp3.text
    m_og = re.search(r'<meta property="og:image" content="([^"]+)"', html3, re.I)
    m_tw = re.search(r'<meta name="twitter:image" content="([^"]+)"', html3, re.I)
    print(f"  {name}: og={m_og.group(1).split('/')[-1][:50] if m_og else 'NONE'}")
    print(f"  {name}: tw={m_tw.group(1).split('/')[-1][:50] if m_tw else 'NONE'}")
