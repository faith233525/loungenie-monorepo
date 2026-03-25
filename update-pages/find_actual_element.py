"""Find which page has the actual uagb-block-kwh5ph54 HTML element in its body."""
import urllib.request, re

pages_to_check = [
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

for label, url in pages_to_check:
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    with urllib.request.urlopen(req, timeout=20) as r:
        html = r.read().decode('utf-8', errors='replace')

    # Find actual HTML elements vs CSS references for the kwh5ph54 block
    # Look for the actual div/section element (not just CSS)
    # CSS occurrence is in <style> blocks, HTML occurrence is in <div/section>
    # Find all occurrences
    kwh_count = html.count('kwh5ph54')
    
    # Find if it appears as an HTML element (has class= nearby)
    has_element = False
    idx = 0
    while True:
        idx = html.find('kwh5ph54', idx)
        if idx == -1:
            break
        # Check context - is it an HTML element or CSS rule?
        before = html[max(0,idx-20):idx]
        if 'class=' in before or '<div' in before or '<section' in before:
            has_element = True
            print(f'{label}: ACTUAL ELEMENT FOUND at pos={idx}')
            print(f'  ...{html[max(0,idx-100):idx+200]}...')
        idx += 1
    
    if kwh_count > 0 and not has_element:
        # Only CSS references
        pass  # silent - all pages share global CSS
    elif kwh_count == 0:
        pass  # not on this page at all

    # Also check for services9.jpg HTML element context
    s9_idx = html.find('services9')
    if s9_idx != -1:
        before_s9 = html[max(0,s9_idx-200):s9_idx]
        # Is it in a <style> block or in an actual element?
        if 'background-image' in html[max(0,s9_idx-100):s9_idx]:
            print(f'{label}: services9.jpg in CSS at pos={s9_idx}')
        if '<img' in html[max(0,s9_idx-200):s9_idx] or 'src=' in html[s9_idx:s9_idx+200]:
            print(f'{label}: services9.jpg in HTML img tag at pos={s9_idx}')
