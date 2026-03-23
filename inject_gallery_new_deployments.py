#!/usr/bin/env python3
import requests
import base64
import json
import re

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
PAGE_ID = 5223
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

r = requests.get(f'{BASE}/pages/{PAGE_ID}', headers=headers, timeout=30)
r.raise_for_status()
content = r.json().get('content', {}).get('rendered', '')

section = '''
<div class="gx-divider"></div>

<!-- GLOBAL DEPLOYMENT EXPANSION START -->
<section class="gx-sec">
  <div class="gx-head"><h2>Additional Global Deployments</h2><p>Fresh property shots from your wider LounGenie photo library</p></div>
  <div class="gx-grid3">
    <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Cowabunga-Bay-VIP-Pool-scaled.jpg" alt="Cowabunga Bay VIP pool cabana with LounGenie amenity unit installed" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Cowabunga Bay VIP pool</div></div>
    <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Margaritaville-Grand-Turk-3.jpg" alt="Margaritaville Grand Turk premium seating area with LounGenie deployment" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Margaritaville Grand Turk</div></div>
    <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Marriott-Gaylord-Texan-1-scaled.jpg" alt="Marriott Gaylord Texan poolside cabana setup with LounGenie unit" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Marriott Gaylord Texan</div></div>
    <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Westin-Keirland-Arizona.jpg" alt="Westin Kierland Arizona poolside hospitality deployment with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Westin Kierland Arizona</div></div>
    <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/westin-kierland-resort.jpeg" alt="Westin Kierland resort cabana environment with LounGenie unit" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Westin Kierland resort</div></div>
    <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Westin-Las-Vegas-HiRes-scaled.jpg" alt="Westin Las Vegas poolside premium seating area with LounGenie installation" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Westin Las Vegas</div></div>
    <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Yas-Waterworld.jpg" alt="Yas Waterworld premium cabana environment featuring LounGenie amenity system" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Yas Waterworld</div></div>
    <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Yas-Waterworld-March-2020.jpg" alt="Yas Waterworld deployment photo showing LounGenie in waterpark setting" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Yas Waterworld March 2020</div></div>
    <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Waldorf-Landscape-scaled.jpg" alt="Waldorf pool deck landscape with LounGenie deployment" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Waldorf landscape view</div></div>
    <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/CHIC-Hotel-Punta-Cana.jpg" alt="CHIC Hotel Punta Cana premium seating zone with LounGenie amenity unit" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">CHIC Hotel Punta Cana</div></div>
  </div>
</section>
<!-- GLOBAL DEPLOYMENT EXPANSION END -->
'''

if '<!-- GLOBAL DEPLOYMENT EXPANSION START -->' in content:
    content = re.sub(r'<!-- GLOBAL DEPLOYMENT EXPANSION START -->.*?<!-- GLOBAL DEPLOYMENT EXPANSION END -->', section.strip(), content, flags=re.DOTALL)
else:
    anchor = '<section class="gx-sec">\n    <div class="gx-head"><h2>STASH Lock Detail Set</h2>'
    if anchor in content:
        content = content.replace(anchor, section + '\n\n' + anchor)
    else:
        # fallback: append before CTA
        cta = '<section class="gx-cta">'
        if cta in content:
            content = content.replace(cta, section + '\n\n' + cta)
        else:
            content += section

u = requests.post(f'{BASE}/pages/{PAGE_ID}', headers=headers, data=json.dumps({'content': content, 'status': 'publish'}), timeout=40)
if u.status_code not in (200, 201):
    raise SystemExit(f'Update failed: HTTP {u.status_code} {u.text[:250]}')

print('Gallery updated with additional global deployment section (10 new images).')
