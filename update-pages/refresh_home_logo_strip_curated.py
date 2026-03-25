#!/usr/bin/env python3
import base64
import json
import re
import requests

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

r = requests.get(f'{BASE}/pages/4701?context=edit', headers=headers, timeout=30)
r.raise_for_status()
content = r.json().get('content', {}).get('raw', '')

new_section = '''<!-- PARTNER LOGO STRIP -->
<!-- wp:html -->
<section class="lg9-section" style="background:#fff;">
  <div style="text-align:center;max-width:920px;margin:0 auto 26px;padding:0 24px;">
    <p class="lg9-kicker">Selected Property Logos</p>
    <h2 class="lg9-title-md" style="margin:10px 0 10px;line-height:1.35;color:#10253c;">Representative Properties and Brands Featured Across Recent Deployment Examples.</h2>
    <p style="margin:0;color:#445a70;font-size:14px;line-height:1.6;">This is a curated subset of properties and brands shown across LounGenie deployment imagery and case-study content. It is not an exhaustive list of every venue, portfolio, or relationship.</p>
  </div>
  <div class="lg9-logo-rail" aria-label="Selected property and brand logos">
    <div class="lg9-logo-fade lg9-logo-fade-left" aria-hidden="true"></div>
    <div class="lg9-logo-fade lg9-logo-fade-right" aria-hidden="true"></div>
    <div class="lg9-logo-track" role="list">
      <div class="lg9-logo-set" role="group" aria-label="Selected property logos set 1">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/logo-hilton.webp" alt="Hilton" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="high">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/logo-marriott.webp" alt="Marriott" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="high">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/logo-westin.webp" alt="Westin" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="high">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/logo-sixflags.webp" alt="Six Flags" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="high">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/margaritaville-jimmy-buffetts-logo-png-transparent.png" alt="Margaritaville" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="high">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/logo-atlantis.webp" alt="Atlantis" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="high">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2025/10/tt-logo-300x121.png.webp" alt="Typhoon Texas" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="high">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2025/10/wildrivers-logo-2x.png" alt="Wild Rivers" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="high">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2025/10/Untitled-design-34.png" alt="The Grove Resort and Spa" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="high">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2025/10/Splash-Kingdom.jpg" alt="Splash Kingdom" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="high">
      </div>
      <div class="lg9-logo-set" role="group" aria-hidden="true">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/logo-hilton.webp" alt="" loading="lazy" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="low">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/logo-marriott.webp" alt="" loading="lazy" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="low">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/logo-westin.webp" alt="" loading="lazy" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="low">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/logo-sixflags.webp" alt="" loading="lazy" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="low">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/margaritaville-jimmy-buffetts-logo-png-transparent.png" alt="" loading="lazy" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="low">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/logo-atlantis.webp" alt="" loading="lazy" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="low">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2025/10/tt-logo-300x121.png.webp" alt="" loading="lazy" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="low">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2025/10/wildrivers-logo-2x.png" alt="" loading="lazy" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="low">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2025/10/Untitled-design-34.png" alt="" loading="lazy" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="low">
        <img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2025/10/Splash-Kingdom.jpg" alt="" loading="lazy" class="litespeed-no-lazyload skip-lazy no-lazyload" fetchpriority="low">
      </div>
    </div>
  </div>
  <style>
    .page-id-4701 .lg9-logo-rail { position:relative; max-width:1160px; margin:0 auto; overflow:hidden; }
    .page-id-4701 .lg9-logo-track { display:flex; width:max-content; animation: lg9LogoScroll 42s linear infinite; }
    .page-id-4701 .lg9-logo-rail:hover .lg9-logo-track { animation-play-state: paused; }
    .page-id-4701 .lg9-logo-set { display:flex; align-items:center; gap:26px; padding:6px 13px; }
    .page-id-4701 .lg9-logo-set img { width:auto; height:auto; max-height:56px; max-width:132px; object-fit:contain; opacity:.95; filter:saturate(.92); }
    .page-id-4701 .lg9-logo-fade { position:absolute; top:0; bottom:0; width:64px; z-index:2; pointer-events:none; }
    .page-id-4701 .lg9-logo-fade-left { left:0; background:linear-gradient(to right,#fff,rgba(255,255,255,0)); }
    .page-id-4701 .lg9-logo-fade-right { right:0; background:linear-gradient(to left,#fff,rgba(255,255,255,0)); }
    @keyframes lg9LogoScroll { 0% { transform:translateX(0); } 100% { transform:translateX(-50%); } }
    @media (max-width: 900px) {
      .page-id-4701 .lg9-logo-track { animation:none; width:100%; }
      .page-id-4701 .lg9-logo-set { display:grid; grid-template-columns:repeat(3,minmax(90px,1fr)); gap:14px; width:100%; }
      .page-id-4701 .lg9-logo-set[aria-hidden="true"] { display:none; }
      .page-id-4701 .lg9-logo-fade { display:none; }
    }
    @media (max-width: 560px) {
      .page-id-4701 .lg9-logo-set { grid-template-columns:repeat(2,minmax(90px,1fr)); }
      .page-id-4701 .lg9-logo-set img { max-height:48px; max-width:120px; }
    }
  </style>
</section>
<!-- /wp:html -->'''

pattern = re.compile(r'<!-- PARTNER LOGO STRIP -->.*?<!-- THE PROBLEM -->', re.S)
new_content, count = pattern.subn(new_section + '\n\n<!-- THE PROBLEM -->', content)
if count != 1:
    raise SystemExit(f'Could not uniquely replace partner logo strip (matches={count})')

u = requests.post(f'{BASE}/pages/4701', headers=headers, data=json.dumps({'content': new_content, 'status': 'publish'}), timeout=40)
if u.status_code not in (200, 201):
    raise SystemExit(f'Update failed: HTTP {u.status_code} {u.text[:220]}')

print('Home logo strip refreshed with a more cautious curated subset.')
