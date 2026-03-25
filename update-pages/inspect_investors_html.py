"""Fetch full rendered investors page and find the uagb-block-kwh5ph54 context."""
import urllib.request, re

url = 'https://www.loungenie.com/loungenie/investors/'
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
with urllib.request.urlopen(req, timeout=20) as r:
    html = r.read().decode('utf-8', errors='replace')

print(f'Total HTML length: {len(html)}')

# Find the uagb block
idx = html.find('kwh5ph54')
if idx != -1:
    start = max(0, idx - 500)
    end = min(len(html), idx + 500)
    print(f'\nContext around kwh5ph54:')
    print(html[start:end])

# Find all broken image references
for t in ['services9', '1746838260534', 'loungenie.com/wp-content/uploads/2025/10/17']:
    idx2 = html.find(t)
    if idx2 != -1:
        start2 = max(0, idx2-200)
        end2 = min(len(html), idx2+300)
        print(f'\n=== Found {t} ===')
        print(html[start2:end2])

# Check if our page content appears in the HTML
idx3 = html.find('lg9-hero')
print(f'\nlg9-hero found: {idx3 != -1}')
idx4 = html.find('elementor')
print(f'elementor found at: {idx4}')

# How big is the lg9 section vs elementor section?
lg9_count = html.count('lg9-')
elem_count = html.count('elementor-')
print(f'lg9- references: {lg9_count}')
print(f'elementor- references: {elem_count}')

# Check if there's content AFTER our lg9 content
lg9_end = html.rfind('</div>', html.find('</div>'), html.find('</body>'))
print(f'\nBody content structure:')
# Find the wp-content section
body_start = html.find('<body')
body_end = html.find('</body>')
# Look for major content divs
for div_class in ['entry-content', 'post-content', 'site-content', 'elementor-section', 'uagb']:
    idx5 = html.find(div_class)
    if idx5 != -1:
        print(f'  Found {div_class!r} at position {idx5}')
