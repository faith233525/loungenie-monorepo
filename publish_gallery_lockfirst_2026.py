#!/usr/bin/env python3
import requests
import base64
import json

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
h = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}
base = 'https://www.loungenie.com/wp-json/wp/v2'

content = '''
<style>
.gx { max-width: 1240px; margin: 0 auto; padding: 0 20px 80px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #102033; }
.gx-hero { background: linear-gradient(120deg,#0c1b31 0%,#1d4269 100%); color:#fff; padding:68px 28px 56px; border-radius:0 0 14px 14px; margin:0 -20px 56px; text-align:center; }
.gx-hero h1 { margin:0 0 14px; font-size:clamp(2rem,4.4vw,3.1rem); font-weight:800; letter-spacing:-.5px; color:#fff; text-shadow:0 1px 2px rgba(0,0,0,.28); }
.gx-hero p { max-width:860px; margin:0 auto; opacity:.92; line-height:1.72; font-size:1.05rem; color:#f4f8ff; }
.gx-sec { margin:0 0 54px; }
.gx-head { display:flex; gap:12px; align-items:flex-end; justify-content:space-between; border-bottom:2px solid #d8e1ec; padding-bottom:12px; margin-bottom:18px; }
.gx-head h2 { margin:0; font-size:1.45rem; font-weight:800; color:#0b1f34; }
.gx-head p { margin:0; color:#3c546b; font-size:.92rem; font-weight:600; }
.gx-grid3, .gx-grid4 { display:grid; gap:14px; }
.gx-grid3 { grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); }
.gx-grid4 { grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); }
.gx-card { position:relative; overflow:hidden; border-radius:10px; background:#0e2036; min-height:260px; }
.gx-card img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .25s ease; }
.gx-card:hover img { transform:scale(1.03); }
.gx-cap { position:absolute; left:0; right:0; bottom:0; background:linear-gradient(to top,rgba(8,16,28,.88),transparent); color:#fff; padding:22px 10px 10px; font-size:.82rem; line-height:1.45; font-weight:600; }
.gx-divider { height:1px; background:linear-gradient(to right,transparent,#c8d5e4,transparent); margin:48px 0; }
.gx-cta { background:linear-gradient(120deg,#0c1b31 0%,#23466f 100%); color:#fff; border-radius:12px; padding:56px 22px; text-align:center; }
.gx-cta h2 { margin:0 0 12px; font-size:1.9rem; font-weight:800; color:#fff; }
.gx-cta p { margin:0 auto 22px; max-width:720px; line-height:1.7; opacity:.9; }
.gx-btn { display:inline-block; background:#f4b114; color:#0f2238 !important; text-decoration:none !important; font-weight:800; padding:13px 28px; border-radius:8px; }
@media (max-width:860px) { .gx-grid3 { grid-template-columns:1fr 1fr; } }
@media (max-width:560px) { .gx-grid3,.gx-grid4 { grid-template-columns:1fr; } .gx-hero { padding:54px 18px 44px; } }
</style>

<div class="gx">
  <section class="gx-hero">
    <h1>LounGenie 2026 Field Gallery</h1>
    <p>Curated with current production lock visuals and recent deployment photography. Each frame keeps LounGenie clearly visible and prioritizes unit clarity over background scenery.</p>
  </section>

  <section class="gx-sec">
    <div class="gx-head"><h2>Updated Lock Hardware (Current)</h2><p>STASH lock panel and service-ready hardware views</p></div>
    <div class="gx-grid3">
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2080.jpeg" alt="LounGenie updated waterproof lock panel close-up" loading="eager" decoding="async" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Updated lock panel close-up</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2081.jpeg" alt="LounGenie updated lock keypad and safe door detail" loading="eager" decoding="async" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Safe door + waterproof keypad</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2083.jpeg" alt="LounGenie current lock and panel detail at active deployment" loading="eager" decoding="async" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Current lock set detail</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2078-scaled.jpeg" alt="LounGenie service side lock hardware view" loading="lazy" decoding="async" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Service-side lock hardware</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2079-scaled.jpeg" alt="LounGenie lock panel from alternate angle" loading="lazy" decoding="async" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Alternate lock angle</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2089-scaled.jpeg" alt="LounGenie full panel with updated lock and charging area" loading="lazy" decoding="async" class="litespeed-no-lazyload skip-lazy no-lazyload"><div class="gx-cap">Full panel with updated lock</div></div>
    </div>
  </section>

  <div class="gx-divider"></div>

  <section class="gx-sec">
    <div class="gx-head"><h2>Recent Deployments (Unit Visible)</h2><p>2026-first deployment set with clear LounGenie framing</p></div>
    <div class="gx-grid4">
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3233-scaled-1.jpg" alt="Cowabunga Bay cabana with LounGenie unit clearly visible" loading="lazy" decoding="async"><div class="gx-cap">Cowabunga Bay interior</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3235-scaled-1.jpg" alt="Cowabunga Bay deployment showing LounGenie safe and panel" loading="lazy" decoding="async"><div class="gx-cap">Cowabunga Bay unit framing</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3239-scaled-1.jpg" alt="Waterpark cabana with LounGenie CHILL and STASH visible" loading="lazy" decoding="async"><div class="gx-cap">Waterpark CHILL + STASH</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3241-scaled-1.jpg" alt="LounGenie deployment in active cabana seating area" loading="lazy" decoding="async"><div class="gx-cap">Cabana deployment view</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Cowabunga-Bay-VIP-Pool-scaled.jpg" alt="Cowabunga Bay VIP pool with LounGenie units" loading="lazy" decoding="async"><div class="gx-cap">Cowabunga Bay VIP pool</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/CB-VIP-scaled.jpg" alt="Cowabunga Bay VIP area with LounGenie installation" loading="lazy" decoding="async"><div class="gx-cap">Cowabunga VIP seating</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/CB-Clam-1-scaled.webp" alt="Cowabunga clamshell seating with LounGenie" loading="lazy" decoding="async"><div class="gx-cap">Clamshell deployment</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Marriott-Gaylord-Texan-1-scaled.jpg" alt="Marriott Gaylord Texan with LounGenie installed" loading="lazy" decoding="async"><div class="gx-cap">Marriott Gaylord Texan</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Marriott-Gaylord-Texan-2-scaled.jpg" alt="Marriott Gaylord Texan deployment angle two" loading="lazy" decoding="async"><div class="gx-cap">Marriott angle 2</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Marriott-Gaylord-Texan-3-scaled.jpg" alt="Marriott Gaylord Texan deployment angle three" loading="lazy" decoding="async"><div class="gx-cap">Marriott angle 3</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Marriott-Gaylord-Texan-4-scaled.jpg" alt="Marriott Gaylord Texan deployment angle four" loading="lazy" decoding="async"><div class="gx-cap">Marriott angle 4</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Marriott-Gaylord-Texan-5-scaled.jpg" alt="Marriott Gaylord Texan deployment angle five" loading="lazy" decoding="async"><div class="gx-cap">Marriott angle 5</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Yas-Waterworld.jpg" alt="Yas Waterworld with LounGenie unit visible" loading="lazy" decoding="async"><div class="gx-cap">Yas Waterworld</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Yas-Waterworld-March-2020.jpg" alt="Yas Waterworld deployment showing LounGenie" loading="lazy" decoding="async"><div class="gx-cap">Yas Waterworld March set</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/waterpark-copy.jpg" alt="Waterpark deployment with LounGenie in premium seating" loading="lazy" decoding="async"><div class="gx-cap">Waterpark deployment</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/PoolSafe-Makai-Pool.jpg" alt="Makai pool deployment showing LounGenie" loading="lazy" decoding="async"><div class="gx-cap">Makai pool deployment</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/PoolSafe-Hilton.jpg" alt="Hilton property deployment showing LounGenie unit" loading="lazy" decoding="async"><div class="gx-cap">Hilton property deployment</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Margaritaville-Grand-Turk-3.jpg" alt="Margaritaville Grand Turk with LounGenie visible" loading="lazy" decoding="async"><div class="gx-cap">Margaritaville Grand Turk</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Sea-World-San-Diego-1.jpg" alt="SeaWorld San Diego deployment with LounGenie" loading="lazy" decoding="async"><div class="gx-cap">SeaWorld San Diego</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/CHIC-Hotel-Punta-Cana.jpg" alt="CHIC Hotel Punta Cana with LounGenie in premium seating" loading="lazy" decoding="async"><div class="gx-cap">CHIC Hotel Punta Cana</div></div>
      <div class="gx-card"><img src="https://www.loungenie.com/wp-content/uploads/2026/03/Waldorf-Landscape-scaled.jpg" alt="Waldorf deployment landscape featuring LounGenie unit" loading="lazy" decoding="async"><div class="gx-cap">Waldorf landscape deployment</div></div>
    </div>
  </section>

  <section class="gx-cta">
    <h2>See a Current-Lock Deployment Plan for Your Property</h2>
    <p>We will map the right unit mix, lock configuration, and installation approach to your premium seating footprint. PoolSafe handles installation and service with performance-based revenue share.</p>
    <a class="gx-btn" href="/contact-loungenie/">Book a Live Demo</a>
  </section>
</div>
'''

r = requests.post(f'{base}/pages/5223', headers=h, data=json.dumps({'content': content, 'status': 'publish'}), timeout=40)
if r.status_code not in (200, 201):
    raise SystemExit(f'Gallery update failed: HTTP {r.status_code} {r.text[:220]}')

print('Gallery rebuilt: 2026-first, lock-forward, no repeated SeaWorld frame, cleaned mappings.')
