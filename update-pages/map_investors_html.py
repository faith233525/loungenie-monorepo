"""Map the structure of the investors page rendered HTML."""
import urllib.request, re

url = 'https://www.loungenie.com/loungenie/investors/'
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
with urllib.request.urlopen(req, timeout=20) as r:
    html = r.read().decode('utf-8', errors='replace')

# Find key positions
positions = {}
for label, target in [
    ('entry-content', 'entry-content'),
    ('uagb-block-kwh5ph54', 'kwh5ph54'),
    ('lg9-hero', 'lg9-hero'),
    ('lg9 div open', '<div class="lg9">'),
    ('closing body', '</body>'),
    ('1746838260534', '1746838260534'),
    ('ir-shell', 'ir-shell'),
    ('ir-source', 'ir-source'),
    ('elementor-canvas', 'elementor-canvas'),
    ('elementor-section', 'elementor-section'),
    ('wp-block-uagb', 'wp-block-uagb-container'),
]:
    idx = html.find(target)
    if idx != -1:
        positions[label] = idx

print('=== Page positions ===')
for label, pos in sorted(positions.items(), key=lambda x: x[1]):
    print(f'  {pos:7d}  {label}')

# Show what's immediately inside entry-content (first 800 chars after it)
ec_pos = html.find('class="entry-content')
if ec_pos != -1:
    # Find the > after this
    gt_pos = html.find('>', ec_pos)
    if gt_pos != -1:
        print(f'\n=== First 800 chars of entry-content ===')
        print(html[gt_pos+1:gt_pos+801])

# Show what's at and around the UAGB block
uagb_pos = html.find('wp-block-uagb-container')
if uagb_pos != -1:
    print(f'\n=== Content around wp-block-uagb-container ===')
    print(html[max(0,uagb_pos-100):uagb_pos+400])

# Show what's right before the lg9 div
lg9_pos = html.find('<div class="lg9">')
if lg9_pos != -1:
    print(f'\n=== 200 chars before lg9 div ===')
    print(html[max(0,lg9_pos-200):lg9_pos+200])
