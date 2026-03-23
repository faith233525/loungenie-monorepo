#!/usr/bin/env python3
"""
Comprehensive gallery page rebuild — full property-by-property grid with all
real installation photos, lock detail section, and CTA. Fixes Sea-World internal
duplicate. Expands from 8k → 25k+ chars of rich content.
"""
import requests, base64, json

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
hdrs = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'
UP = 'https://www.loungenie.com/wp-content/uploads'

CONTENT = f"""
<!-- Gallery Page — Comprehensive Install Photo Grid -->
<style>
.lg-page {{
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  color: #1a1a2e;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px 80px;
}}
.lg-hero {{
  background: linear-gradient(135deg, #0a192f 0%, #1a3a5c 100%);
  color: #fff;
  text-align: center;
  padding: 72px 40px 60px;
  margin: 0 -20px 60px;
  border-radius: 0 0 12px 12px;
}}
.lg-hero h1 {{
  font-size: clamp(2rem, 4vw, 2.8rem);
  font-weight: 800;
  margin: 0 0 16px;
  letter-spacing: -0.5px;
}}
.lg-hero p {{
  font-size: 1.15rem;
  opacity: 0.85;
  max-width: 680px;
  margin: 0 auto;
  line-height: 1.7;
}}
.lg-section {{
  margin-bottom: 64px;
}}
.lg-venue-header {{
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 24px;
  padding-bottom: 14px;
  border-bottom: 2px solid #e2e8f0;
}}
.lg-venue-tag {{
  background: #0a192f;
  color: #fff;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  padding: 4px 10px;
  border-radius: 3px;
}}
.lg-venue-name {{
  font-size: 1.4rem;
  font-weight: 700;
  color: #0a192f;
  margin: 0;
}}
.lg-venue-sub {{
  font-size: 0.9rem;
  color: #64748b;
  margin: 0;
}}
.lg-grid-2 {{
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
  gap: 16px;
}}
.lg-grid-3 {{
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 16px;
}}
.lg-grid-4 {{
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 14px;
}}
.lg-photo {{
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  background: #0a192f;
}}
.lg-photo img {{
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.3s ease;
}}
.lg-photo:hover img {{
  transform: scale(1.03);
}}
.lg-caption {{
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(to top, rgba(10,25,47,0.85) 0%, transparent 100%);
  color: #fff;
  font-size: 0.8rem;
  padding: 24px 12px 10px;
  line-height: 1.4;
}}
.lg-photo.tall {{
  min-height: 420px;
  max-height: 560px;
}}
.lg-photo.wide {{
  min-height: 340px;
  max-height: 420px;
}}
.lg-photo.sq {{
  min-height: 280px;
  max-height: 360px;
}}
.lg-spotlight {{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}}
.lg-spotlight .lg-photo {{
  min-height: 380px;
}}
.lg-divider {{
  height: 1px;
  background: linear-gradient(to right, transparent, #cbd5e1, transparent);
  margin: 48px 0;
}}
.lg-lock-section {{
  background: #f8fafc;
  border-radius: 12px;
  padding: 40px;
  margin-bottom: 60px;
}}
.lg-lock-header {{
  text-align: center;
  margin-bottom: 32px;
}}
.lg-lock-header h2 {{
  font-size: 1.7rem;
  font-weight: 800;
  color: #0a192f;
  margin: 0 0 10px;
}}
.lg-lock-header p {{
  color: #475569;
  max-width: 560px;
  margin: 0 auto;
  line-height: 1.6;
}}
.lg-cta {{
  background: linear-gradient(135deg, #0a192f 0%, #1e3a5f 100%);
  color: #fff;
  text-align: center;
  padding: 60px 40px;
  border-radius: 12px;
}}
.lg-cta h2 {{
  font-size: 1.9rem;
  font-weight: 800;
  margin: 0 0 14px;
}}
.lg-cta p {{
  opacity: 0.85;
  max-width: 560px;
  margin: 0 auto 28px;
  line-height: 1.7;
  font-size: 1.05rem;
}}
.lg-btn {{
  display: inline-block;
  background: #f59e0b;
  color: #0a192f !important;
  font-weight: 800;
  font-size: 1rem;
  text-decoration: none !important;
  padding: 14px 36px;
  border-radius: 6px;
  letter-spacing: 0.3px;
  transition: background 0.2s;
}}
.lg-btn:hover {{ background: #fbbf24; }}
@media (max-width: 640px) {{
  .lg-grid-2, .lg-spotlight {{ grid-template-columns: 1fr; }}
  .lg-grid-3 {{ grid-template-columns: 1fr 1fr; }}
  .lg-grid-4 {{ grid-template-columns: 1fr 1fr; }}
  .lg-lock-section {{ padding: 24px 16px; }}
  .lg-cta {{ padding: 40px 20px; }}
}}
</style>

<div class="lg-page">

<!-- HERO -->
<div class="lg-hero">
  <h1>LounGenie™ in the Field</h1>
  <p>Real installations at resorts, waterparks, and themed entertainment venues. Every photo from an active LounGenie™ deployment — fully installed, guest-ready units at properties across North America.</p>
</div>

<!-- HILTON WAIKOLOA VILLAGE -->
<div class="lg-section">
  <div class="lg-venue-header">
    <span class="lg-venue-tag">Resort</span>
    <div>
      <p class="lg-venue-name">Hilton Waikoloa Village</p>
      <p class="lg-venue-sub">Kailua-Kona, Hawaii · Kona Pool &amp; Aloha Falls Cabana Area</p>
    </div>
  </div>
  <div class="lg-spotlight">
    <div class="lg-photo wide litespeed-no-lazyload" style="min-height:380px">
      <img src="{UP}/2026/03/Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-4-scaled.jpg"
           alt="Hotel pool cabana row outfitted with LounGenie smart amenity units — Hilton Waikoloa Village"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Kona Pool — full cabana deployment, multiple LounGenie™ units in view</div>
    </div>
    <div class="lg-photo wide litespeed-no-lazyload" style="min-height:380px">
      <img src="{UP}/2026/03/Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-2-scaled.jpg"
           alt="LounGenie ice bucket and waterproof safe inside resort pool cabana — Hilton Waikoloa"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Aloha Falls — ice bucket (CHILL) and waterproof safe (STASH) installed</div>
    </div>
  </div>
  <div class="lg-grid-3">
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:300px">
      <img src="{UP}/2026/03/Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-6.jpg"
           alt="Close-up of LounGenie waterproof safe and USB charging ports — Hilton Waikoloa Kona Pool"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">STASH waterproof safe and CHARGE panel — close detail</div>
    </div>
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:300px">
      <img src="{UP}/2026/03/Hilton-Wakoloa-Village-2018-10-Aloha-Falls-Cabana-1-scaled.jpg"
           alt="Aloha Falls cabana interior with LounGenie amenity unit — Hilton Waikoloa Village"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Aloha Falls — full unit installed and guest-ready</div>
    </div>
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:300px">
      <img src="{UP}/2026/03/Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-3-scaled.jpg"
           alt="Resort daybed area with LounGenie amenity unit installed — Hilton Waikoloa"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Daybed seating area — LounGenie™ deployed and operational</div>
    </div>
  </div>
</div>

<div class="lg-divider"></div>

<!-- THE GROVE RESORT -->
<div class="lg-section">
  <div class="lg-venue-header">
    <span class="lg-venue-tag">Resort</span>
    <div>
      <p class="lg-venue-name">The Grove Resort &amp; Water Park</p>
      <p class="lg-venue-sub">Orlando, Florida · Poolside Cabana Deployment</p>
    </div>
  </div>
  <div class="lg-grid-3">
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:340px">
      <img src="{UP}/2026/03/The-Grove-1.jpg"
           alt="LounGenie smart system inside resort pool cabana at The Grove Resort Orlando"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Interior install — safe, charging, and ice bucket all visible</div>
    </div>
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:340px">
      <img src="{UP}/2026/03/The-Grove-5.jpg"
           alt="Pool deck cabana at The Grove Resort featuring LounGenie amenity technology"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Poolside deck view — cabana row with LounGenie™ active</div>
    </div>
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:340px">
      <img src="{UP}/2026/03/The-Grove-6.jpg"
           alt="The Grove Resort pool cabana equipped with LounGenie smart amenity system"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Cabinet panel and unit framing — guest-side view</div>
    </div>
  </div>
</div>

<div class="lg-divider"></div>

<!-- SEA WORLD SAN DIEGO -->
<div class="lg-section">
  <div class="lg-venue-header">
    <span class="lg-venue-tag">Theme Park</span>
    <div>
      <p class="lg-venue-name">SeaWorld San Diego</p>
      <p class="lg-venue-sub">San Diego, California · Premium Seating Area</p>
    </div>
  </div>
  <div class="lg-spotlight">
    <div class="lg-photo wide litespeed-no-lazyload" style="min-height:400px">
      <img src="{UP}/2026/03/Sea-World-San-Diego.jpg"
           alt="Full-service cabana interior with LounGenie amenity unit at SeaWorld San Diego"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">SeaWorld San Diego — cabana interior with full LounGenie™ install</div>
    </div>
    <div class="lg-photo wide litespeed-no-lazyload" style="min-height:400px">
      <img src="{UP}/2026/03/mc-mcowc-16683_Classic-Hor.jpg"
           alt="Cabana with LounGenie smart amenity unit in premium outdoor seating area"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Premium seating deployment — LounGenie™ Classic installed</div>
    </div>
  </div>
</div>

<div class="lg-divider"></div>

<!-- COWABUNGA BAY -->
<div class="lg-section">
  <div class="lg-venue-header">
    <span class="lg-venue-tag">Water Park</span>
    <div>
      <p class="lg-venue-name">Cowabunga Bay Water Park</p>
      <p class="lg-venue-sub">Henderson, Nevada · Cabana &amp; Clamshell Deployment</p>
    </div>
  </div>
  <div class="lg-grid-3">
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:340px">
      <img src="{UP}/2026/03/IMG_3233-scaled-1.jpg"
           alt="Cowabunga Bay water park cabana interior featuring LounGenie amenity unit"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Cabana interior — ice bucket, USB charging, and safe installed</div>
    </div>
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:340px">
      <img src="{UP}/2026/03/IMG_3239-scaled-1.jpg"
           alt="Water park cabana with LounGenie ice bucket and safe — Cowabunga Bay"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">CHILL ice bucket and STASH safe — waterpark grade install</div>
    </div>
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:340px">
      <img src="{UP}/2026/03/CB-Clam-1-scaled.webp"
           alt="LounGenie smart system in Cowabunga Bay water park clamshell seating area"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Clamshell deployment — LounGenie™ fits standard and premium seating formats</div>
    </div>
  </div>
</div>

<div class="lg-divider"></div>

<!-- WESTIN HILTON HEAD -->
<div class="lg-section">
  <div class="lg-venue-header">
    <span class="lg-venue-tag">Resort</span>
    <div>
      <p class="lg-venue-name">Westin Hilton Head Island Resort &amp; Spa</p>
      <p class="lg-venue-sub">Hilton Head Island, South Carolina · Poolside Installation</p>
    </div>
  </div>
  <div class="lg-spotlight">
    <div class="lg-photo wide litespeed-no-lazyload" style="min-height:380px">
      <img src="{UP}/2026/03/105-Westin__hhi_bjp_-_low_res.webp"
           alt="LounGenie hospitality innovation unit for poolside cabanas — Westin Hilton Head Island"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Westin Hilton Head — outdoor poolside install, guest-facing unit</div>
    </div>
    <div class="lg-photo wide litespeed-no-lazyload" style="min-height:380px">
      <img src="{UP}/2025/12/Westin-Hilton-Head-3-April-2023-scaled-e1764703506863.jpg"
           alt="LounGenie amenity unit installed at Westin Hilton Head Island Resort and Spa"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Hilton Head — unit in cabana structure, full side profile visible</div>
    </div>
  </div>
</div>

<div class="lg-divider"></div>

<!-- WATER WORLD -->
<div class="lg-section">
  <div class="lg-venue-header">
    <span class="lg-venue-tag">Water Park</span>
    <div>
      <p class="lg-venue-name">Schlitterbahn / Water World</p>
      <p class="lg-venue-sub">North America · Multi-Property Deployment</p>
    </div>
  </div>
  <div class="lg-grid-2">
    <div class="lg-photo wide litespeed-no-lazyload" style="min-height:360px">
      <img src="{UP}/2025/10/Water-World-Cabana-1.jpg"
           alt="Water World water park cabana equipped with LounGenie amenity unit"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Front exterior — LounGenie™ positioned within premium cabana structure</div>
    </div>
    <div class="lg-photo wide litespeed-no-lazyload" style="min-height:360px">
      <img src="{UP}/2025/10/Water-World-Cabana-2.jpg"
           alt="Interior of Water World water park cabana with LounGenie smart amenity system"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover"/>
      <div class="lg-caption">Side and interior angles — commercial-grade aluminum construction, built for continuous outdoor use</div>
    </div>
  </div>
</div>

<div class="lg-divider"></div>

<!-- LOCK DETAIL SECTION -->
<div class="lg-lock-section">
  <div class="lg-lock-header">
    <h2>STASH™ Lock &amp; Safe — Detail Views</h2>
    <p>The heart of LounGenie™. Waterproof keypad, re-closeable door, and solid-aluminum construction. Close-up shots from active deployments — exactly what guests interact with.</p>
  </div>
  <div class="lg-grid-3">
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:300px;border-radius:8px">
      <img src="{UP}/2026/03/IMG_2080.jpeg"
           alt="LounGenie waterproof safe — STASH lock panel close-up showing keypad and door seal"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover;border-radius:8px"/>
      <div class="lg-caption">STASH — waterproof keypad entry, tamper-evident door</div>
    </div>
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:300px;border-radius:8px">
      <img src="{UP}/2026/03/IMG_2081.jpeg"
           alt="LounGenie lock panel showing waterproof keypad and solid aluminum safe door"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover;border-radius:8px"/>
      <div class="lg-caption">Solid aluminum body — corrosion-proof construction for pool environments</div>
    </div>
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:300px;border-radius:8px">
      <img src="{UP}/2026/03/IMG_2083.jpeg"
           alt="Close-up of LounGenie waterproof safe door and keypad at pool cabana"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover;border-radius:8px"/>
      <div class="lg-caption">Safe door detail — flush-mount panel within unit frame</div>
    </div>
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:300px;border-radius:8px">
      <img src="{UP}/2026/03/IMG_2078-scaled.jpeg"
           alt="LounGenie STASH waterproof safe keypad and door — left side view"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover;border-radius:8px"/>
      <div class="lg-caption">Keypad angle — guest entry side, waterproof rated</div>
    </div>
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:300px;border-radius:8px">
      <img src="{UP}/2026/03/IMG_2079-scaled.jpeg"
           alt="LounGenie lock and safe installation detail — service panel side view"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover;border-radius:8px"/>
      <div class="lg-caption">Service-side access — maintenance-ready configuration</div>
    </div>
    <div class="lg-photo sq litespeed-no-lazyload" style="min-height:300px;border-radius:8px">
      <img src="{UP}/2026/03/IMG_2089-scaled.jpeg"
           alt="LounGenie safe and lock hardware — full unit panel at active resort deployment"
           loading="eager" class="litespeed-no-lazyload skip-lazy" style="width:100%;height:100%;object-fit:cover;border-radius:8px"/>
      <div class="lg-caption">Full panel view — lock, charging ports, and service zone in one frame</div>
    </div>
  </div>
</div>

<!-- CTA -->
<div class="lg-cta">
  <h2>See LounGenie™ at Your Property</h2>
  <p>Every installation is custom-configured to match your cabana dimensions, brand colors, and operational workflow. $0 upfront — PoolSafe handles delivery, installation, and ongoing service.</p>
  <a href="/contact-loungenie/" class="lg-btn">Book a Live Demo</a>
</div>

</div>
"""

print("Publishing comprehensive gallery page...")
r = requests.post(f'{BASE}/pages/5223', headers=hdrs,
                  data=json.dumps({'content': CONTENT, 'status': 'publish'}), timeout=30)
if r.status_code in (200, 201):
    d = r.json()
    print(f"  ✓ Gallery (5223) updated — content_len={len(d.get('content',{}).get('rendered',''))} chars")
    print(f"  link: {d.get('link','')}")
else:
    print(f"  ✗ HTTP {r.status_code}: {r.text[:300]}")
