#!/usr/bin/env python3
import requests, base64, json

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
h = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}
base = 'https://www.loungenie.com/wp-json/wp/v2'
up = 'https://www.loungenie.com/wp-content/uploads'

content = f'''
<style>
.gx {{max-width:1240px;margin:0 auto;padding:0 20px 80px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#102033;}}
.gx-hero {{background:linear-gradient(120deg,#0c1b31 0%,#1d4269 100%);color:#fff;padding:68px 28px 56px;border-radius:0 0 14px 14px;margin:0 -20px 56px;text-align:center;}}
.gx-hero h1 {{margin:0 0 14px;font-size:clamp(2rem,4.4vw,3.1rem);font-weight:800;letter-spacing:-.5px;color:#ffffff;text-shadow:0 1px 2px rgba(0,0,0,.28);}}
.gx-hero p {{max-width:860px;margin:0 auto;opacity:.9;line-height:1.7;font-size:1.05rem;}}
.gx-note {{margin-top:14px;font-size:.92rem;opacity:.85;}}
.gx-sec {{margin:0 0 54px;}}
.gx-head {{display:flex;gap:12px;align-items:flex-end;justify-content:space-between;border-bottom:2px solid #d8e1ec;padding-bottom:12px;margin-bottom:18px;}}
.gx-head h2 {{margin:0;font-size:1.45rem;font-weight:800;color:#0b1f34;letter-spacing:-.2px;}}
.gx-head p {{margin:0;color:#3f586f;font-size:.92rem;font-weight:600;}}
.gx-grid2,.gx-grid3,.gx-grid4 {{display:grid;gap:14px;}}
.gx-grid2 {{grid-template-columns:repeat(auto-fill,minmax(460px,1fr));}}
.gx-grid3 {{grid-template-columns:repeat(auto-fill,minmax(300px,1fr));}}
.gx-grid4 {{grid-template-columns:repeat(auto-fill,minmax(240px,1fr));}}
.gx-card {{position:relative;overflow:hidden;border-radius:10px;background:#0e2036;min-height:250px;}}
.gx-card img {{width:100%;height:100%;object-fit:cover;display:block;transition:transform .25s ease;}}
.gx-card:hover img {{transform:scale(1.03);}}
.gx-cap {{position:absolute;left:0;right:0;bottom:0;background:linear-gradient(to top,rgba(8,16,28,.88),transparent);color:#fff;padding:22px 10px 10px;font-size:.82rem;line-height:1.45;font-weight:600;}}
.gx-divider {{height:1px;background:linear-gradient(to right,transparent,#c8d5e4,transparent);margin:48px 0;}}
.gx-logo-chip {{display:inline-flex;align-items:center;gap:8px;background:#f4f8fc;border:1px solid #d7e3f0;border-radius:999px;padding:6px 10px;color:#234;}}
.gx-logo-chip img {{height:20px;width:auto;object-fit:contain;}}
.gx-cta {{background:linear-gradient(120deg,#0c1b31 0%,#23466f 100%);color:#fff;border-radius:12px;padding:56px 22px;text-align:center;}}
.gx-cta h2 {{margin:0 0 12px;font-size:1.9rem;font-weight:800;}}
.gx-cta p {{margin:0 auto 22px;max-width:720px;line-height:1.7;opacity:.88;}}
.gx-btn {{display:inline-block;background:#f4b114;color:#0f2238 !important;text-decoration:none !important;font-weight:800;padding:13px 28px;border-radius:8px;}}
@media (max-width:860px) {{.gx-grid2{{grid-template-columns:1fr;}} .gx-grid3{{grid-template-columns:1fr 1fr;}}}}
@media (max-width:560px) {{.gx-grid3,.gx-grid4{{grid-template-columns:1fr;}} .gx-hero{{padding:54px 18px 44px;}}}}
</style>

<div class="gx">
  <section class="gx-hero">
    <h1>LounGenie Installations Across Leading Properties</h1>
    <p>A curated field gallery with real deployment photos across resort, hotel, cruise, and waterpark environments. This page emphasizes property variety, full-unit visibility, and lock-panel detail so teams can evaluate fit and finish before rollout.</p>
    <p class="gx-note">Partner network includes major hospitality and attraction brands, including Six Flags properties, Westin Hilton Head, Hilton Waikoloa, and Margaritaville locations.</p>
  </section>

  <section class="gx-sec">
    <div class="gx-head"><h2>Hilton Waikoloa Village</h2><p>Kona Pool + Aloha Falls seating zones</p></div>
    <div class="gx-grid3">
      <div class="gx-card"><img src="{up}/2026/03/Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-4-scaled.jpg" alt="Hilton Waikoloa cabana row with LounGenie units installed" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Kona Pool cabana deployment</div></div>
      <div class="gx-card"><img src="{up}/2026/03/Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-2-scaled.jpg" alt="LounGenie safe and ice bucket installed at Hilton Waikoloa" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Aloha Falls interior setup</div></div>
      <div class="gx-card"><img src="{up}/2026/03/Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-3-scaled.jpg" alt="Hilton Waikoloa daybed area with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Daybed premium seating view</div></div>
      <div class="gx-card"><img src="{up}/2026/03/Hilton-Wakoloa-Village-2018-10-Aloha-Falls-Cabana-1-scaled.jpg" alt="Hilton Waikoloa cabana with LounGenie STASH CHARGE CHILL" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Full-unit framing</div></div>
      <div class="gx-card"><img src="{up}/2026/03/Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-6.jpg" alt="Close-up lock and charging panel at Hilton Waikoloa" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Safe + charge panel detail</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Hilton-Waikoloa-Village-2018-10-Kona-Pool-Cabanas-10-scaled.jpg" alt="Hilton Waikoloa pool cabana with LounGenie in operation" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Additional resort-angle install</div></div>
    </div>
  </section>

  <div class="gx-divider"></div>

  <section class="gx-sec">
    <div class="gx-head"><h2>Westin Hilton Head</h2><p>Expanded set from multiple install angles</p></div>
    <div class="gx-grid3">
      <div class="gx-card"><img src="{up}/2026/03/105-Westin__hhi_bjp_-_low_res.webp" alt="Westin Hilton Head poolside installation with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Poolside profile</div></div>
      <div class="gx-card"><img src="{up}/2025/12/Westin-Hilton-Head-3-April-2023-scaled-e1764703506863.jpg" alt="Westin Hilton Head cabana interior with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Interior framing</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Westin-Hilton-Head-1-April-2023-scaled.jpg" alt="Westin Hilton Head LounGenie install side view" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Cabana side profile</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Westin-Hilton-Head-2-scaled.jpg" alt="LounGenie unit installed in Westin Hilton Head pool cabana" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Cabana feature view</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Westin-Hilton-Head-4-April-2023-scaled.jpg" alt="Westin Hilton Head additional install angle with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Additional property angle</div></div>
      <div class="gx-card"><img src="{up}/2025/10/152-Westin__hhi_bjp_-_low_res.jpg" alt="Cabana interior at Westin Hilton Head with LounGenie amenities" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Interior service configuration</div></div>
    </div>
  </section>

  <div class="gx-divider"></div>

  <section class="gx-sec">
    <div class="gx-head"><h2>Water Park Portfolio</h2><p>Cowabunga Bay + Water World style deployments</p></div>
    <div class="gx-grid4">
      <div class="gx-card"><img src="{up}/2026/03/IMG_3233-scaled-1.jpg" alt="Cowabunga Bay cabana with LounGenie installed" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Cowabunga Bay interior</div></div>
      <div class="gx-card"><img src="{up}/2026/03/IMG_3235-scaled-1.jpg" alt="Cowabunga Bay amenity unit with safe and charging ports" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Guest-side unit view</div></div>
      <div class="gx-card"><img src="{up}/2026/03/IMG_3239-scaled-1.jpg" alt="Waterpark cabana with LounGenie CHILL and STASH" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Ice bucket + safe detail</div></div>
      <div class="gx-card"><img src="{up}/2026/03/CB-Clam-1-scaled.webp" alt="Cowabunga Bay clamshell seating with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Clamshell seating install</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Water-World-Cabana-1.jpg" alt="Water World cabana with LounGenie installed" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Water World cabana</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Water-World-Cabana-2.jpg" alt="Water World interior showing LounGenie unit" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Water World interior</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Water-World-Cabana-4.jpg" alt="Water park premium seating with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Premium seating angle</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Water-World-5.jpg" alt="Guest cabana environment with LounGenie in service" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Guest environment view</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Water-World-clamshell-in-the-sun.jpg" alt="Water World clamshell with LounGenie unit" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Clamshell in sun</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Water-World-clamshell-4.jpg" alt="Water World clamshell interior with LounGenie safe" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Clamshell interior detail</div></div>
      <div class="gx-card"><img src="{up}/2025/10/cowabunga-bay-cabana-4-scaled.jpg" alt="Cowabunga Bay resort-style cabana with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Cowabunga additional angle</div></div>
      <div class="gx-card"><img src="{up}/2025/10/cowabunga-bay-cabana-3-scaled.jpg" alt="Water park cabana system by LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Expanded waterpark set</div></div>
    </div>
  </section>

  <div class="gx-divider"></div>

  <section class="gx-sec">
    <div class="gx-head"><h2>Margaritaville Locations</h2><p>Ocho Rios + Negril visual set</p></div>
    <div style="margin-bottom:14px;"><span class="gx-logo-chip"><img src="{up}/2026/03/margaritaville-jimmy-buffetts-logo-png-transparent.png" alt="Margaritaville logo">Margaritaville properties</span></div>
    <div class="gx-grid3">
      <div class="gx-card"><img src="{up}/2025/10/Margaritaville-Ocho-Rios-VIP-area-Copy.jpg" alt="Margaritaville Ocho Rios VIP area with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Ocho Rios VIP area</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Margaritaville-Ocho-Rios-VIP-seating.jpg" alt="Margaritaville VIP seating with LounGenie amenity system" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">VIP seating angle</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Margaritaville-Ocho-Rios-Copy.jpg" alt="Luxury cabana with LounGenie at Margaritaville" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Cabana interior view</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Margaritaville-ICE-BLUE.jpg" alt="Margaritaville cabana interior showing LounGenie features" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Interior detail</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Margaritaville-Negril-VIP-seating.jpg" alt="Margaritaville Negril VIP seating with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Negril VIP seating</div></div>
      <div class="gx-card"><img src="{up}/2025/10/Margaritaville-Negril-VIP-seating-2.jpg" alt="Margaritaville Negril additional seating area with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Negril additional angle</div></div>
    </div>
  </section>

  <div class="gx-divider"></div>

  <section class="gx-sec">
    <div class="gx-head"><h2>SeaWorld + The Grove</h2><p>Theme park and resort showcase images</p></div>
    <div class="gx-grid3">
      <div class="gx-card"><img src="{up}/2026/03/Sea-World-San-Diego.jpg" alt="SeaWorld San Diego cabana interior with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">SeaWorld San Diego</div></div>
      <div class="gx-card"><img src="{up}/2026/03/Sea-World-San-Diego-Edited.webp" alt="SeaWorld edited photo showing LounGenie unit" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">SeaWorld detail frame</div></div>
      <div class="gx-card"><img src="{up}/2026/03/The-Grove-1.jpg" alt="The Grove Resort cabana with LounGenie" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">The Grove interior</div></div>
      <div class="gx-card"><img src="{up}/2026/03/The-Grove-5.jpg" alt="The Grove pool deck with LounGenie units" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">The Grove deck view</div></div>
      <div class="gx-card"><img src="{up}/2026/03/The-Grove-6.jpg" alt="The Grove side profile of installed LounGenie unit" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">The Grove side profile</div></div>
      <div class="gx-card"><img src="{up}/2026/03/The-Grove-7-scaled.jpg" alt="The Grove lock and charging panel close-up" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Lock and panel close-up</div></div>
    </div>
  </section>

  <div class="gx-divider"></div>

  <section class="gx-sec">
    <div class="gx-head"><h2>STASH Lock Detail Set</h2><p>Waterproof keypad and safe door detail</p></div>
    <div class="gx-grid3">
      <div class="gx-card"><img src="{up}/2026/03/IMG_2080.jpeg" alt="LounGenie waterproof keypad and safe panel detail" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">STASH close view 1</div></div>
      <div class="gx-card"><img src="{up}/2026/03/IMG_2081.jpeg" alt="LounGenie lock hardware and safe door detail" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">STASH close view 2</div></div>
      <div class="gx-card"><img src="{up}/2026/03/IMG_2083.jpeg" alt="LounGenie waterproof safe door detail in active cabana" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">STASH close view 3</div></div>
      <div class="gx-card"><img src="{up}/2026/03/IMG_2078-scaled.jpeg" alt="Lock panel and service-side hardware detail" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Service-side hardware</div></div>
      <div class="gx-card"><img src="{up}/2026/03/IMG_2079-scaled.jpeg" alt="Lock and keypad detail from alternate angle" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Alternate lock angle</div></div>
      <div class="gx-card"><img src="{up}/2026/03/IMG_2089-scaled.jpeg" alt="Full panel with lock, charging area, and service zone" loading="eager" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Full panel detail</div></div>
    </div>
  </section>

  <section class="gx-cta">
    <h2>Want a Property-Matched Gallery for Your Venue?</h2>
    <p>We can provide a curated visual set by venue type, premium seating format, and lock-detail requirements. PoolSafe handles deployment and service with zero upfront purchase cost and performance-based revenue share.</p>
    <a class="gx-btn" href="/contact-loungenie/">Book a Live Demo</a>
  </section>
</div>
'''

r = requests.post(f'{base}/pages/5223', headers=h, data=json.dumps({'content': content, 'status': 'publish'}), timeout=40)
if r.status_code not in (200, 201):
    raise SystemExit(f'Gallery update failed: HTTP {r.status_code} {r.text[:220]}')

print('Gallery page updated with expanded property coverage and larger curated image set.')
