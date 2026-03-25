"""
LounGenie — Professional Redesign v2
- Fixes Astra transparent header (makes it sticky, opaque, glass-effect)
- Fixes nav menu labels (were showing full page titles)
- Styles Contact/Demo nav button as a proper CTA
- Uses real WordPress media images throughout
- Rewrites Videos page with click-to-play embeds
- Rewrites Gallery page with organised installation photo grid
Only verified stat: up to 30% increase in F&B sales.
"""

import urllib.request, urllib.error, json, base64

AUTH = base64.b64encode(b"copilot:7NiL OZ17 ApP3 tIgF 6zlT ug7u").decode()
BASE_URL = "https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2/pages"
MENU_URL = "https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2/menu-items"
HEADERS  = {"Authorization": f"Basic {AUTH}", "Content-Type": "application/json"}
IMG = "https://loungenie.com/Loungenie%E2%84%A2/wp-content/uploads/2026/03/"

# ─── Image shortcuts ────────────────────────────────────────────────────────
HERO_BG  = IMG + "hero9-bg-1.jpg"
HERO_FG  = IMG + "hero7-fg.jpg"
FEAT4    = IMG + "feature-4.jpg"
FEAT5    = IMG + "feature-5.jpg"
FEAT6    = IMG + "feature-6.jpg"
SVC2     = IMG + "services-2.jpg"
PP01     = IMG + "pp01.jpg"
PP02     = IMG + "pp02.jpg"
PP03     = IMG + "pp03.jpg"
PP04     = IMG + "pp04.jpg"
PP05     = IMG + "pp05.jpg"
PP06     = IMG + "pp06.jpg"
ABOUT_BG = IMG + "about-bg-free-img.jpg"
BEACH    = IMG + "beach.jpg"
CONTACT  = IMG + "3-VOR-cabana-e1773774348955.jpg"
BANNER   = IMG + "banner-1.jpg"
ARCH     = IMG + "architecture.jpg"
GEMINI   = IMG + "Gemini_Generated_Image_xs1ghrxs1ghrxs1g.png"
QR_ORDER = IMG + "e106d1a0-f868-46cd-92f8-457dc6a8f698.webp"
NANO     = IMG + "Nano_Banana_Pro_Show_the_full_unit_in_a_premium_poolside_beach_setting.webp"

# Real cabana installation photos
HIL1 = IMG + "Hilton-Wakoloa-Village-2018-10-Aloha-Falls-Cabana-1-scaled.jpg"
HIL2 = IMG + "Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-2-scaled.jpg"
HIL3 = IMG + "Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-3-scaled.jpg"
HIL4 = IMG + "Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-4-scaled.jpg"
HIL5 = IMG + "Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-6.jpg"
GROVE1 = IMG + "The-Grove-1.jpg"
GROVE5 = IMG + "The-Grove-5.jpg"
GROVE6 = IMG + "The-Grove-6.jpg"
GROVE7 = IMG + "The-Grove-7-scaled.jpg"
SEAWORLD = IMG + "Sea-World-San-Diego.jpg"
COWA1 = IMG + "38f4fc95-7925-4625-b0e8-5ba78771c037.jpg"
COWA2 = IMG + "a5ea38b9-4578-4356-a118-f168caa0ec90.jpg"
COWA3 = IMG + "IMG_3233-scaled-1.jpg"
COWA4 = IMG + "IMG_3235-scaled-1.jpg"
SOAKY = IMG + "page_1145__mg_6277-copy-1-web.webp"
MASSA = IMG + "page_1145_img_6227-copy-1-web.webp"
WESTIN= IMG + "175-Westin__hhi_bjp_-_low_res-1.avif"
TYPHOON = IMG + "1714017439507-e1773261343388.webp"

# Partner logos
MARG = IMG + "margaritaville-jimmy-buffetts-logo-png-transparent.png"
RITZ = IMG + "the-ritz-carlton-logo-png-transparent.webp"
NIAGARA = IMG + "logo-NiagaraFalls_02.png"
LOGO1 = IMG + "logo-1.png"
LOGO4 = IMG + "logo-4.png"

# Video screenshots
VS1 = IMG + "Screenshot-2026-03-11-210110.webp"       # smart cabana overview
VS2 = IMG + "Screenshot-2026-03-11-205705-1.webp"     # LounGenie 2.0
VS3 = IMG + "Screenshot-2026-03-11-205434.webp"        # Villatel Orlando
VS4 = IMG + "Screenshot-2026-03-11-205758.webp"        # Marriott
VS5 = IMG + "Screenshot-2026-03-11-210240.webp"        # cabanas/seating
VS6 = IMG + "Screenshot-2026-03-11-210839.webp"        # Cowabunga Bay
VS7 = IMG + "Screenshot-2025-11-06-091447.webp"        # The Grove


# ─── Shared CSS ─────────────────────────────────────────────────────────────
SHARED_CSS = """
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════════════
   HEADER — make Astra's transparent header sticky & opaque
   ═══════════════════════════════════════════════════ */
#masthead {
  position: sticky !important;
  top: 0 !important;
  z-index: 9999 !important;
  background: rgba(255,255,255,0.97) !important;
  backdrop-filter: blur(12px) !important;
  -webkit-backdrop-filter: blur(12px) !important;
  box-shadow: 0 1px 20px rgba(0,0,0,.09) !important;
}
.ast-primary-header-bar, .main-header-bar {
  min-height: auto !important;
  padding-top: 8px !important;
  padding-bottom: 8px !important;
}
#masthead .menu-link { color: #1a2440 !important; font-weight: 500 !important; font-size: 14.5px !important; font-family: 'Inter', sans-serif !important; }
#masthead .main-header-menu > .menu-item > .menu-link:hover { color: #0077B6 !important; }
.menu-item-5930 > .menu-link, .menu-item-5930 > a {
  background: #0077B6 !important; color: white !important;
  padding: 9px 20px !important; border-radius: 8px !important;
  font-weight: 700 !important; font-size: 13.5px !important;
  margin-left: 8px !important; display: inline-block !important;
}
.menu-item-5930 > .menu-link:hover, .menu-item-5930 > a:hover { background: #005a8b !important; }
.ast-theme-transparent-header .site-content { padding-top: 0 !important; }

/* ═══════════════════════════════════════════════════
   ASTRA OVERRIDE RESET
   Force Inter font — Astra injects Montserrat on h1-h6
   and text-transform:capitalize via .entry-content :where(h1...)
   ═══════════════════════════════════════════════════ */
.lg, .lg div, .lg section, .lg article, .lg span,
.lg p, .lg li, .lg a, .lg button, .lg label, .lg input, .lg select, .lg textarea {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
}
.lg h1, .lg h2, .lg h3, .lg h4, .lg h5, .lg h6 {
  font-family: 'Inter', sans-serif !important;
  text-transform: none !important;
  font-style: normal !important;
  letter-spacing: normal !important;
}
/* Astra adds margin-bottom:1.2em to every p — kills flex/grid spacing */
.lg p { margin-top: 0 !important; margin-bottom: 0 !important; }
.lg ul, .lg ol { margin: 0 !important; padding: 0 !important; }
.lg a { text-decoration: none !important; }
/* Astra sometimes forces img height:auto — override per-use with inline styles */
.lg img { display: block !important; max-width: 100% !important; border: 0 !important; vertical-align: bottom !important; }
/* Partner logo strip — constrain images that would otherwise render at full natural size */
.lg .logo-strip img { height: 36px !important; width: auto !important; max-width: 150px !important; object-fit: contain !important; }

/* ═══════════════════════════════════════════════════
   LAYOUT
   ═══════════════════════════════════════════════════ */
.lg { color: #1a2440; }
.lg *, .lg *::before, .lg *::after { box-sizing: border-box; }
.lg .wrap    { max-width: 1160px; margin: 0 auto; padding: 0 28px; }
.lg .wrap-sm { max-width: 780px;  margin: 0 auto; padding: 0 28px; }
.lg .sec     { padding: 80px 0; }
.lg .sec-sm  { padding: 60px 0; }

/* ═══════════════════════════════════════════════════
   TYPOGRAPHY
   ═══════════════════════════════════════════════════ */
.lg .eyebrow { display: inline-block; font-size: 11px; font-weight: 700 !important; letter-spacing: 2.5px; text-transform: uppercase !important; color: #0077B6; margin-bottom: 12px; }
.lg h1 { font-size: clamp(32px,5.5vw,58px) !important; font-weight: 900 !important; line-height: 1.1 !important; letter-spacing: -1.5px !important; }
.lg h2 { font-size: clamp(24px,3.5vw,40px) !important; font-weight: 800 !important; line-height: 1.15 !important; letter-spacing: -0.5px !important; }
.lg h3 { font-size: clamp(16px,2vw,20px) !important; font-weight: 700 !important; line-height: 1.35 !important; letter-spacing: 0 !important; }
.lg .lead  { font-size: clamp(16px,1.5vw,18px) !important; line-height: 1.7 !important; color: #4a5568; }
.lg .muted { color: #7a8698; font-size: 15px !important; line-height: 1.65 !important; }

/* ═══════════════════════════════════════════════════
   BUTTONS
   ═══════════════════════════════════════════════════ */
.lg .btn { display: inline-flex !important; align-items: center !important; gap: 8px; padding: 14px 28px; border-radius: 10px; font-weight: 700 !important; font-size: 15px !important; transition: all .2s; cursor: pointer; border: none; line-height: 1 !important; white-space: nowrap; }
.lg .btn-primary { background: linear-gradient(135deg,#0077B6,#0096d6) !important; color: white !important; box-shadow: 0 4px 16px rgba(0,119,182,.28); }
.lg .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 26px rgba(0,119,182,.40) !important; color: white !important; }
.lg .btn-white { background: white !important; color: #0077B6 !important; box-shadow: 0 4px 16px rgba(0,0,0,.12); }
.lg .btn-white:hover { transform: translateY(-2px); box-shadow: 0 8px 26px rgba(0,0,0,.20) !important; color: #0077B6 !important; }
.lg .btn-ghost { background: transparent !important; color: white !important; border: 2px solid rgba(255,255,255,.65); }
.lg .btn-ghost:hover { background: rgba(255,255,255,.12) !important; border-color: white; color: white !important; }
.lg .btn-outline { background: transparent !important; color: #0077B6 !important; border: 2px solid #0077B6; }
.lg .btn-outline:hover { background: #0077B6 !important; color: white !important; }

/* ═══════════════════════════════════════════════════
   CARDS
   ═══════════════════════════════════════════════════ */
.lg .card { background: white; border-radius: 16px; border: 1px solid #e8ecf2; transition: all .25s; overflow: hidden; }
.lg .card:hover { box-shadow: 0 16px 48px rgba(0,0,0,.09); transform: translateY(-3px); }
.lg .card-pad { padding: 32px 26px; }

/* Icon boxes */
.lg .icon-box { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 18px; flex-shrink: 0; }
.lg .ib-blue  { background: linear-gradient(135deg,#dbeeff,#b8dcff); }
.lg .ib-cyan  { background: linear-gradient(135deg,#d0f8ff,#a8efff); }
.lg .ib-green { background: linear-gradient(135deg,#d4f7e9,#b0f0d4); }
.lg .ib-gold  { background: linear-gradient(135deg,#fff4cc,#ffe08a); }

/* ═══════════════════════════════════════════════════
   GRIDS
   ═══════════════════════════════════════════════════ */
.lg .grid-4 { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 22px; }
.lg .grid-3 { display: grid; grid-template-columns: repeat(auto-fit,minmax(260px,1fr)); gap: 28px; }
.lg .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 56px; align-items: center; }

/* ═══════════════════════════════════════════════════
   MISC UTILITIES
   ═══════════════════════════════════════════════════ */
.lg .divider { width: 40px; height: 4px; background: linear-gradient(90deg,#0077B6,#00c6fb); border-radius: 3px; margin-bottom: 24px; }
.lg .mt8  { margin-top: 8px !important; }
.lg .mt14 { margin-top: 14px !important; }
.lg .mt18 { margin-top: 18px !important; }
.lg .mt22 { margin-top: 22px !important; }
.lg .mt28 { margin-top: 28px !important; }
.lg .mt36 { margin-top: 36px !important; }
.lg .mb8  { margin-bottom: 8px !important; }
.lg .mb14 { margin-bottom: 14px !important; }
.lg .mb18 { margin-bottom: 18px !important; }
.lg .mb22 { margin-bottom: 22px !important; }
.lg .mb28 { margin-bottom: 28px !important; }
.lg .mb36 { margin-bottom: 36px !important; }

/* Checklist */
.lg ul.checks { list-style: none; display: flex; flex-direction: column; gap: 10px; }
.lg ul.checks li { display: flex; gap: 10px; align-items: flex-start; color: #4a5568; font-size: 15px !important; line-height: 1.55 !important; }
.lg ul.checks li::before { content: "✓"; flex-shrink: 0; width: 22px; height: 22px; background: #0077B6; color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; margin-top: 1px; }

/* Pill badges */
.lg .pill-red   { display: inline-block; padding: 4px 12px; border-radius: 100px; font-size: 11px !important; font-weight: 700 !important; letter-spacing: .5px; text-transform: uppercase !important; background: #fee2e2; color: #b91c1c; }
.lg .pill-green { display: inline-block; padding: 4px 12px; border-radius: 100px; font-size: 11px !important; font-weight: 700 !important; letter-spacing: .5px; text-transform: uppercase !important; background: #dcfce7; color: #15803d; }

/* Step numbers */
.lg .step-n { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg,#0077B6,#00c6fb); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800 !important; flex-shrink: 0; box-shadow: 0 6px 16px rgba(0,119,182,.32); margin-bottom: 16px; }

/* Award badge */
.lg .award { display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg,#fffbeb,#fef3c7); border: 1px solid #fde68a; border-radius: 10px; padding: 12px 18px; }

/* ═══════════════════════════════════════════════════
   SECTIONS
   ═══════════════════════════════════════════════════ */
/* Full-bleed hero */
.lg .hero { position: relative; min-height: 72vh; display: flex; align-items: center; overflow: hidden; background: #0a1628; }
.lg .hero-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: .36; }
.lg .hero-overlay { position: absolute; inset: 0; background: linear-gradient(135deg,rgba(5,10,28,.88) 0%,rgba(0,55,100,.72) 55%,rgba(0,100,180,.45) 100%); }
.lg .hero-inner { position: relative; z-index: 2; padding: 80px 0 72px; width: 100%; }

/* Inner page hero (light) */
.lg .inn-hero { background: linear-gradient(135deg,#f0f6fc 0%,#ffffff 100%); padding: 72px 0 52px; border-bottom: 1px solid #e8ecf2; }

/* Stat banner */
.lg .stat-banner { background: linear-gradient(135deg,#0a1628 0%,#003770 55%,#0077B6 100%); color: white; text-align: center; padding: 80px 0; }
.lg .big-stat { font-size: clamp(52px,8vw,88px) !important; font-weight: 900 !important; letter-spacing: -3px !important; line-height: 1 !important; background: linear-gradient(135deg,#fff,#a8dcff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

/* Feature image (alternating layout) */
.lg .feat-img { width: 100%; height: 300px; object-fit: cover; border-radius: 16px; box-shadow: 0 14px 40px rgba(0,0,0,.13); display: block !important; }

/* Photo grid */
.lg .photo-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; }
.lg .photo-grid img { width: 100% !important; aspect-ratio: 16/10; object-fit: cover !important; border-radius: 10px; display: block !important; transition: transform .3s; height: auto !important; }
.lg .photo-grid img:hover { transform: scale(1.03); }

/* Logo strip */
.lg .logo-strip { display: flex; align-items: center; justify-content: center; gap: 36px; flex-wrap: wrap; padding: 20px 0; }
.lg .logo-strip img { height: 36px !important; width: auto !important; max-width: 150px !important; object-fit: contain !important; filter: grayscale(100%) opacity(.5); transition: filter .2s; }
.lg .logo-strip img:hover { filter: grayscale(0%) opacity(1); }

/* Video card */
.lg .vid-card { border-radius: 12px; overflow: hidden; border: 1px solid #e8ecf2; transition: all .25s; background: white; }
.lg .vid-card:hover { box-shadow: 0 12px 36px rgba(0,0,0,.12); transform: translateY(-3px); }
.lg .vid-thumb { position: relative; cursor: pointer; overflow: hidden; }
.lg .vid-thumb img { width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; transition: transform .4s; }
.lg .vid-card:hover .vid-thumb img { transform: scale(1.04); }
.lg .play-btn { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
.lg .play-btn span { width: 54px; height: 54px; background: rgba(220,0,0,.9); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 19px; color: white; box-shadow: 0 4px 16px rgba(0,0,0,.4); transition: transform .2s; }
.lg .vid-card:hover .play-btn span { transform: scale(1.12); }
.lg .vid-meta { padding: 16px 16px 18px; }
.lg .vid-meta h3 { font-size: 14.5px !important; font-weight: 700 !important; color: #1a2440 !important; line-height: 1.4 !important; margin-bottom: 4px !important; }
.lg .vid-meta p  { font-size: 12.5px !important; color: #7a8698; }

/* ═══════════════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════════════ */
@media(max-width:900px){
  .lg .grid-2 { grid-template-columns: 1fr; gap: 36px; }
  .lg .photo-grid { grid-template-columns: repeat(2,1fr); }
}
@media(max-width:600px){
  .lg .sec, .lg .sec-sm { padding: 52px 0; }
  .lg .hero { min-height: 65vh; }
  .lg .photo-grid { grid-template-columns: 1fr 1fr; }
  .lg .logo-strip { gap: 20px; }
  .lg .grid-4 { grid-template-columns: 1fr 1fr; }
}
</style>
"""

# ────────────────────────────────────────────────────────────────────────────
# HOME PAGE
# ────────────────────────────────────────────────────────────────────────────
def make_home():
    return SHARED_CSS + f"""
<div class="lg">

<!-- HERO -->
<section class="hero">
  <div class="hero-bg" style="background-image:url('{HERO_BG}');"></div>
  <div class="hero-overlay"></div>
  <div class="hero-inner wrap">
    <div style="max-width:660px;">
      <span class="eyebrow" style="color:#7dd3fc;">IAAPA Brass Ring Award Winner</span>
      <h1 style="color:white;margin-bottom:22px;">
        Increase Poolside<br>F&amp;B Sales<br>
        <span style="background:linear-gradient(90deg,#38bdf8,#93c5fd);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">by Up to 30%</span>
      </h1>
      <p class="lead" style="color:rgba(255,255,255,.82);max-width:540px;padding-bottom:38px;">
        LounGenie&#x2122; is the all-in-one poolside platform — smart ordering, secure storage, wireless charging, and premium amenities — at zero capital cost to your property.
      </p>
      <div style="display:flex;gap:16px;flex-wrap:wrap;">
        <a href="/contact" class="btn btn-white">&#x1f4c5;&nbsp; Schedule a Demo</a>
        <a href="/features" class="btn btn-ghost">See How It Works &rarr;</a>
      </div>
    </div>
  </div>
</section>

<!-- PARTNER LOGOS -->
<div style="background:white;border-bottom:1px solid #edf0f5;padding:28px 0;">
  <div class="wrap">
    <p style="text-align:center;font-size:12px;font-weight:700 !important;letter-spacing:2px;text-transform:uppercase !important;color:#9aa5b4;padding-bottom:22px;">Partner Properties</p>
    <div class="logo-strip">
      <img src="{MARG}" alt="Margaritaville">
      <img src="{RITZ}" alt="Ritz-Carlton">
      <img src="{NIAGARA}" alt="Niagara Falls">
      <img src="{LOGO1}" alt="Partner">
      <img src="{LOGO4}" alt="Partner">
    </div>
  </div>
</div>

<!-- WHY LOUNGENIE -->
<section class="sec" style="background:#f8fafc;">
  <div class="wrap">
    <div class="grid-2">
      <div>
        <span class="eyebrow">The Opportunity</span>
        <div class="divider"></div>
        <h2 style="color:#0f2137;margin-bottom:22px;">Guests Leave Early.<br>Revenue Walks Out With Them.</h2>
        <p class="lead" style="padding-bottom:28px;">Three things drive guests away from the pool and stop them ordering: dead phone batteries, nowhere safe to leave valuables, and the friction of walking to a bar. LounGenie eliminates all three.</p>
        <div style="display:flex;flex-direction:column;gap:18px;">
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="flex-shrink:0;width:42px;height:42px;background:#fee2e2;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;">&#x1f50b;</div>
            <div><strong style="display:block;margin-bottom:3px;color:#1a2440;">Dead Phone = Guest Exit</strong><span class="muted">Battery dies &rarr; guest leaves &rarr; F&amp;B order never happens.</span></div>
          </div>
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="flex-shrink:0;width:42px;height:42px;background:#fef3c7;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;">&#x1f9f3;</div>
            <div><strong style="display:block;margin-bottom:3px;color:#1a2440;">Unsecured Valuables = Anxiety</strong><span class="muted">Guests can't relax — they leave to secure phones, wallets &amp; keys.</span></div>
          </div>
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="flex-shrink:0;width:42px;height:42px;background:#dbeefe;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;">&#x1f6b6;</div>
            <div><strong style="display:block;margin-bottom:3px;color:#1a2440;">Ordering Friction = Skipped Sales</strong><span class="muted">If ordering means losing a chair, most guests simply go without.</span></div>
          </div>
        </div>
      </div>
      <div style="border-radius:20px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.18);">
        <img src="{HIL1}" alt="LounGenie cabana installation at Hilton resort" style="width:100%;height:400px;object-fit:cover;display:block;">
      </div>
    </div>
  </div>
</section>

<!-- PLATFORM FEATURES -->
<section class="sec" style="background:white;">
  <div class="wrap">
    <div style="text-align:center;max-width:580px;margin:0 auto 52px;">
      <span class="eyebrow">The Platform</span>
      <div class="divider" style="margin:0 auto 24px;"></div>
      <h2 style="color:#0f2137;">ORDER. STASH. CHARGE. CHILL.</h2>
      <p class="lead" style="padding-top:14px;">Four integrated modules that solve the real reasons guests leave pools early — and stop them from spending more.</p>
    </div>
    <div class="grid-4">
      <div class="card card-pad">
        <div class="icon-box ib-blue">&#x1f4f1;</div>
        <h3>ORDER</h3>
        <p class="muted" style="margin-top:10px;">Direct F&amp;B ordering from any poolside lounger. No walking. No losing a chair. Just more orders.</p>
        <a href="/features" style="display:inline-flex;align-items:center;gap:5px;margin-top:18px;font-size:13.5px;font-weight:600;color:#0077B6;">Details &rarr;</a>
      </div>
      <div class="card card-pad">
        <div class="icon-box ib-cyan">&#x1f4e6;</div>
        <h3>STASH</h3>
        <p class="muted" style="margin-top:10px;">Smart poolside storage for valuables. Guests feel safe staying all day instead of retreating to their room.</p>
        <a href="/features" style="display:inline-flex;align-items:center;gap:5px;margin-top:18px;font-size:13.5px;font-weight:600;color:#0077B6;">Details &rarr;</a>
      </div>
      <div class="card card-pad">
        <div class="icon-box ib-gold">&#x26a1;</div>
        <h3>CHARGE</h3>
        <p class="muted" style="margin-top:10px;">Wireless charging eliminates the #1 reason for early pool exits — dead phone batteries.</p>
        <a href="/features" style="display:inline-flex;align-items:center;gap:5px;margin-top:18px;font-size:13.5px;font-weight:600;color:#0077B6;">Details &rarr;</a>
      </div>
      <div class="card card-pad">
        <div class="icon-box ib-green">&#x1f9ca;</div>
        <h3>CHILL</h3>
        <p class="muted" style="margin-top:10px;">Premium poolside comfort amenities that create a resort atmosphere and encourage guests to linger.</p>
        <a href="/features" style="display:inline-flex;align-items:center;gap:5px;margin-top:18px;font-size:13.5px;font-weight:600;color:#0077B6;">Details &rarr;</a>
      </div>
    </div>
  </div>
</section>

<!-- STAT CALLOUT -->
<section class="stat-banner">
  <div class="wrap-sm">
    <span class="eyebrow" style="color:#7dd3fc;">Verified Result</span>
    <div class="big-stat" style="margin:12px 0 10px;">Up to 30%</div>
    <p style="font-size:20px;font-weight:600;opacity:.85;padding-bottom:16px;">increase in poolside food &amp; beverage sales</p>
    <p style="color:rgba(255,255,255,.62);font-size:16px;max-width:440px;margin:0 auto;padding-bottom:36px;line-height:1.7;">Properties see measurable F&amp;B revenue growth — driven by longer guest dwell time and frictionless ordering.</p>
    <a href="/contact" class="btn btn-white">See it at your property &rarr;</a>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="sec" style="background:#f8fafc;">
  <div class="wrap">
    <div style="text-align:center;max-width:520px;margin:0 auto 52px;">
      <span class="eyebrow">How It Works</span>
      <div class="divider" style="margin:0 auto 24px;"></div>
      <h2 style="color:#0f2137;">Zero Risk. Zero CapEx. Pure Revenue.</h2>
    </div>
    <div class="grid-3">
      <div style="text-align:center;padding:8px;">
        <div class="step-n" style="margin:0 auto 16px;">1</div>
        <h3 style="margin-bottom:10px;">We Install Everything</h3>
        <p class="muted">Full installation, setup, and onboarding at no capital cost to your property. We handle it all.</p>
      </div>
      <div style="text-align:center;padding:8px;">
        <div class="step-n" style="margin:0 auto 16px;">2</div>
        <h3 style="margin-bottom:10px;">Guests Engage</h3>
        <p class="muted">Guests order, charge, store, and relax — staying poolside longer and spending more throughout the day.</p>
      </div>
      <div style="text-align:center;padding:8px;">
        <div class="step-n" style="margin:0 auto 16px;">3</div>
        <h3 style="margin-bottom:10px;">Revenue Grows</h3>
        <p class="muted">F&amp;B sales increase. We share in the revenue generated — aligned incentives, pure upside for your property.</p>
      </div>
    </div>
  </div>
</section>

<!-- INSTALLATION GALLERY STRIP -->
<section style="background:white;padding:60px 0;">
  <div class="wrap">
    <div style="text-align:center;margin-bottom:36px;">
      <span class="eyebrow">Real Installations</span>
      <div class="divider" style="margin:0 auto 0;"></div>
    </div>
    <div class="photo-grid">
      <img src="{HIL4}" alt="LounGenie at Hilton Waikoloa resort pool cabana">
      <img src="{GROVE7}" alt="LounGenie at The Grove Resort">
      <img src="{HIL2}" alt="LounGenie Hilton Waikoloa cabana installation">
      <img src="{COWA1}" alt="LounGenie at Cowabunga Canyon water park cabana">
      <img src="{GROVE1}" alt="The Grove Resort cabana with LounGenie">
      <img src="{SEAWORLD}" alt="LounGenie at Sea World San Diego">
    </div>
    <div style="text-align:center;margin-top:32px;">
      <a href="/cabana-installation-photos" class="btn btn-outline">View Full Gallery &rarr;</a>
    </div>
  </div>
</section>

<!-- FINAL CTA -->
<section class="sec" style="background:#0f2137;">
  <div class="wrap-sm" style="text-align:center;">
    <div class="award" style="margin:0 auto 32px;display:inline-flex;">
      <span style="font-size:28px;">&#x1f3c6;</span>
      <div style="text-align:left;">
        <strong style="display:block;color:#92400e;font-size:14px;">IAAPA Brass Ring Award Winner</strong>
        <span style="font-size:12px;color:#a16207;">#1 Poolside Innovation Technology</span>
      </div>
    </div>
    <h2 style="color:white;margin-bottom:18px;">Ready to Grow Poolside Revenue?</h2>
    <p class="lead" style="margin:0 auto 36px;max-width:480px;color:rgba(255,255,255,.72);">No commitment. A straightforward conversation about your property and what's possible.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="/contact" class="btn btn-primary">&#x1f4c5;&nbsp; Schedule a Demo</a>
      <a href="/loungenie-videos" class="btn btn-ghost">Watch Videos &rarr;</a>
    </div>
  </div>
</section>

</div>
"""

# ────────────────────────────────────────────────────────────────────────────
# FEATURES PAGE
# ────────────────────────────────────────────────────────────────────────────
def make_features():
    return SHARED_CSS + f"""
<div class="lg">

<section class="inn-hero">
  <div class="wrap-sm" style="text-align:center;">
    <span class="eyebrow">The Platform</span>
    <div class="divider" style="margin:0 auto 24px;"></div>
    <h1 style="font-size:clamp(28px,4.5vw,48px);font-weight:900;color:#0f2137;letter-spacing:-1px;">Every Feature Is a<br>Revenue Driver</h1>
    <p class="lead" style="margin:18px auto 0;max-width:500px;">Each module targets a specific reason guests leave pools early or skip ordering — converting those lost moments into F&amp;B revenue.</p>
  </div>
</section>

<section class="sec" style="background:white;">
  <div class="wrap">

    <!-- FEATURE: CHARGE -->
    <div class="grid-2" style="margin-bottom:88px;">
      <div>
        <span class="pill-red" style="margin-bottom:16px;">THE PROBLEM</span>
        <h2 style="margin:18px 0 14px;color:#0f2137;font-size:clamp(22px,3vw,34px);">Dead Phone = Lost Guest</h2>
        <p class="lead" style="margin-bottom:28px;">When battery dies poolside, guests leave immediately to find a charger — and often don't return. Every departure is a missed F&amp;B sale.</p>
        <div style="background:linear-gradient(135deg,#16a34a,#15803d);border-radius:6px;display:inline-block;padding:5px 14px;margin-bottom:16px;">
          <span style="color:white;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">THE SOLUTION</span>
        </div>
        <h3 style="margin-bottom:14px;color:#0f2137;">CHARGE — Wireless Charging Stations</h3>
        <ul class="checks">
          <li>Guests stay poolside longer — phones stay charged</li>
          <li>Removes the #1 reason for early pool exits</li>
          <li>More dwell time means more opportunities to order</li>
          <li>White-label design fits any cabana or daybed setup</li>
        </ul>
      </div>
      <div>
        <img src="{FEAT5}" alt="LounGenie wireless charging station at pool" class="feat-img">
      </div>
    </div>

    <!-- FEATURE: ORDER -->
    <div class="grid-2" style="margin-bottom:88px;">
      <div>
        <img src="{QR_ORDER}" alt="LounGenie QR poolside ordering" class="feat-img">
      </div>
      <div>
        <span class="pill-red" style="margin-bottom:16px;">THE PROBLEM</span>
        <h2 style="margin:18px 0 14px;color:#0f2137;font-size:clamp(22px,3vw,34px);">Guests Skip Ordering to Keep Their Spot</h2>
        <p class="lead" style="margin-bottom:28px;">Walking to the bar means losing a chair and leaving belongings unattended. That friction silently kills F&amp;B sales throughout the day.</p>
        <div style="background:linear-gradient(135deg,#16a34a,#15803d);border-radius:6px;display:inline-block;padding:5px 14px;margin-bottom:16px;">
          <span style="color:white;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">THE SOLUTION</span>
        </div>
        <h3 style="margin-bottom:14px;color:#0f2137;">ORDER — Direct Poolside F&amp;B Ordering</h3>
        <ul class="checks">
          <li>Guests order from their lounge chair via QR code</li>
          <li>No lost chairs, no unattended belongings</li>
          <li>Properties see <strong>up to 30% increase in poolside F&amp;B sales</strong></li>
          <li>Integrates with your existing POS and kitchen workflow</li>
        </ul>
      </div>
    </div>

    <!-- FEATURE: STASH -->
    <div class="grid-2" style="margin-bottom:88px;">
      <div>
        <span class="pill-red" style="margin-bottom:16px;">THE PROBLEM</span>
        <h2 style="margin:18px 0 14px;color:#0f2137;font-size:clamp(22px,3vw,34px);">Valuables Anxiety Sends Guests Inside</h2>
        <p class="lead" style="margin-bottom:28px;">Guests regularly leave the pool to lock up phones, wallets, and keys. Each trip risks them not returning — and losing the F&amp;B spend.</p>
        <div style="background:linear-gradient(135deg,#16a34a,#15803d);border-radius:6px;display:inline-block;padding:5px 14px;margin-bottom:16px;">
          <span style="color:white;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">THE SOLUTION</span>
        </div>
        <h3 style="margin-bottom:14px;color:#0f2137;">STASH — Smart Poolside Storage</h3>
        <ul class="checks">
          <li>Waterproof safe at the poolside lounge</li>
          <li>Guests feel secure — no reason to leave</li>
          <li>Extended poolside time directly drives more orders</li>
          <li>Commercial-grade, tamper-resistant construction</li>
        </ul>
      </div>
      <div>
        <img src="{HIL4}" alt="LounGenie smart storage at Hilton resort cabana" class="feat-img">
      </div>
    </div>

    <!-- FEATURE: CHILL -->
    <div class="grid-2">
      <div>
        <img src="{GROVE5}" alt="LounGenie premium amenities at The Grove Resort" class="feat-img">
      </div>
      <div>
        <span class="pill-red" style="margin-bottom:16px;">THE PROBLEM</span>
        <h2 style="margin:18px 0 14px;color:#0f2137;font-size:clamp(22px,3vw,34px);">A Basic Pool Doesn&#x27;t Inspire Spending</h2>
        <p class="lead" style="margin-bottom:28px;">When the poolside experience feels ordinary, guests check in briefly and leave. A premium environment changes guest behaviour.</p>
        <div style="background:linear-gradient(135deg,#16a34a,#15803d);border-radius:6px;display:inline-block;padding:5px 14px;margin-bottom:16px;">
          <span style="color:white;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">THE SOLUTION</span>
        </div>
        <h3 style="margin-bottom:14px;color:#0f2137;">CHILL — Premium Comfort Amenities</h3>
        <ul class="checks">
          <li>Removable ice bucket and premium comfort items</li>
          <li>Resort-quality atmosphere that keeps guests relaxed longer</li>
          <li>Complements and amplifies your F&amp;B program</li>
          <li>Differentiates your property from nearby competitors</li>
        </ul>
      </div>
    </div>

  </div>
</section>

<!-- STAT -->
<section class="stat-banner">
  <div class="wrap-sm">
    <span class="eyebrow" style="color:#7dd3fc;">Combined Result</span>
    <div class="big-stat" style="margin:12px 0 10px;">Up to 30%</div>
    <p style="font-size:20px;font-weight:600;opacity:.85;margin-bottom:16px;">poolside F&amp;B revenue increase</p>
    <p style="color:rgba(255,255,255,.62);font-size:16px;max-width:420px;margin:0 auto 36px;line-height:1.7;">ORDER + STASH + CHARGE + CHILL working together deliver measurable, consistent revenue growth at your property.</p>
    <a href="/contact" class="btn btn-white">Request a Demo &rarr;</a>
  </div>
</section>

</div>
"""

# ────────────────────────────────────────────────────────────────────────────
# ABOUT PAGE
# ────────────────────────────────────────────────────────────────────────────
def make_about():
    return SHARED_CSS + f"""
<div class="lg">

<section class="inn-hero" style="background:none;position:relative;overflow:hidden;padding:0;">
  <div style="position:absolute;inset:0;background-image:url('{ABOUT_BG}');background-size:cover;background-position:center;opacity:.2;"></div>
  <div style="position:relative;z-index:1;background:linear-gradient(135deg,#f0f6fc 0%,rgba(240,246,252,.92) 100%);padding:80px 0 56px;">
    <div class="wrap">
      <div class="grid-2">
        <div>
          <span class="eyebrow">About Us</span>
          <div class="divider"></div>
          <h1 style="font-size:clamp(28px,4.5vw,48px);font-weight:900;color:#0f2137;letter-spacing:-1px;margin-bottom:20px;">Turning Pool Decks Into<br>Revenue Centers</h1>
          <p class="lead" style="max-width:460px;margin-bottom:30px;">We build technology that transforms underutilised pool areas into consistent, measurable F&amp;B revenue — at zero capital risk to your property.</p>
          <div class="award">
            <span style="font-size:26px;">&#x1f3c6;</span>
            <div>
              <strong style="display:block;color:#92400e;font-size:14px;margin-bottom:2px;">IAAPA Brass Ring Award Winner</strong>
              <span style="font-size:12.5px;color:#a16207;">#1 Poolside Innovation Technology</span>
            </div>
          </div>
        </div>
        <div>
          <img src="{HIL3}" alt="LounGenie resort cabana installation" style="border-radius:20px;width:100%;height:400px;object-fit:cover;box-shadow:0 20px 56px rgba(0,0,0,.14);">
        </div>
      </div>
    </div>
  </div>
</section>

<section class="sec" style="background:white;">
  <div class="wrap-sm" style="text-align:center;">
    <span class="eyebrow">Our Mission</span>
    <div class="divider" style="margin:0 auto 24px;"></div>
    <h2 style="color:#0f2137;margin-bottom:20px;">We Exist to Make Every Pool Day More Profitable</h2>
    <p class="lead" style="max-width:620px;margin:0 auto;">Hospitality pool decks are underutilised revenue assets. Guests want to stay longer — they just need the right environment to do so. LounGenie creates that environment and captures the revenue it generates.</p>
  </div>
</section>

<section class="sec-sm" style="background:#f8fafc;padding-bottom:88px;">
  <div class="wrap">
    <div style="text-align:center;max-width:500px;margin:0 auto 44px;">
      <span class="eyebrow">Our Approach</span>
      <div class="divider" style="margin:0 auto 24px;"></div>
      <h2 style="color:#0f2137;">Built for Hospitality Properties</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;">
      <div class="card card-pad">
        <div class="icon-box ib-blue">&#x1f465;</div>
        <h3 style="margin-bottom:10px;">Guest-First Design</h3>
        <p class="muted">Every feature starts with a real guest frustration. Happy, comfortable guests stay longer and spend more.</p>
      </div>
      <div class="card card-pad">
        <div class="icon-box ib-gold">&#x1f4b8;</div>
        <h3 style="margin-bottom:10px;">Zero CapEx Model</h3>
        <p class="muted">We take on the investment. Your property gets full installation, maintenance, and the full revenue upside — zero financial risk.</p>
      </div>
      <div class="card card-pad">
        <div class="icon-box ib-green">&#x1f4bb;</div>
        <h3 style="margin-bottom:10px;">Seamless Integration</h3>
        <p class="muted">Works alongside your existing F&amp;B operation and POS. No overhaul of your team or systems required.</p>
      </div>
      <div class="card card-pad">
        <div class="icon-box ib-cyan">&#x1f4c8;</div>
        <h3 style="margin-bottom:10px;">Measurable Results</h3>
        <p class="muted">Up to 30% increase in poolside F&amp;B sales — driven by longer dwell time and frictionless ordering.</p>
      </div>
    </div>
  </div>
</section>

<section class="sec" style="background:white;">
  <div class="wrap">
    <div class="grid-2">
      <div>
        <img src="{GROVE7}" alt="LounGenie at The Grove Resort" style="border-radius:20px;width:100%;height:420px;object-fit:cover;box-shadow:0 20px 56px rgba(0,0,0,.12);">
      </div>
      <div>
        <span class="eyebrow">The Platform</span>
        <div class="divider"></div>
        <h2 style="color:#0f2137;margin-bottom:18px;">Four Modules. One Platform.</h2>
        <p class="lead" style="margin-bottom:22px;">ORDER. STASH. CHARGE. CHILL. Each module addresses a specific reason guests leave pools early or skip F&amp;B spending.</p>
        <p class="muted" style="margin-bottom:18px;line-height:1.8;">Together, they create an environment where guests feel comfortable staying all day — and have every reason to keep ordering. The platform works for cabanas, daybeds, clamshells, and premium lounge seating.</p>
        <p class="muted" style="line-height:1.8;margin-bottom:30px;">We operate on a pure revenue share model. There is no capital expenditure required from your property. We install, maintain, and support the full system. You gain the revenue lift.</p>
        <a href="/poolside-amenity-unit" class="btn btn-primary">Explore Features &rarr;</a>
      </div>
    </div>
  </div>
</section>

<section class="stat-banner sec-sm">
  <div class="wrap-sm" style="text-align:center;">
    <span class="eyebrow" style="color:#7dd3fc;">Let&#x27;s Connect</span>
    <h2 style="color:white;margin:14px 0 16px;">Ready to Learn More?</h2>
    <p style="color:rgba(255,255,255,.72);font-size:17px;max-width:420px;margin:0 auto 34px;line-height:1.7;">See exactly how LounGenie can work for your property. No pressure, no commitment.</p>
    <a href="/contact" class="btn btn-white">Schedule a Conversation &rarr;</a>
  </div>
</section>

</div>
"""

# ────────────────────────────────────────────────────────────────────────────
# CONTACT PAGE
# ────────────────────────────────────────────────────────────────────────────
def make_contact():
    return SHARED_CSS + """
<div class="lg">

<section class="inn-hero">
  <div class="wrap" style="max-width:700px;">
    <span class="eyebrow">Get In Touch</span>
    <div class="divider"></div>
    <h1 style="font-size:clamp(28px,4.5vw,48px);font-weight:900;color:#0f2137;letter-spacing:-1px;margin-bottom:18px;">Let&#x27;s Talk About<br>Your Pool Deck</h1>
    <p class="lead">Find out how LounGenie can help your property increase poolside F&amp;B revenue by up to 30% — at zero capital cost.</p>
  </div>
</section>

<section class="sec" style="background:#f8fafc;">
  <div class="wrap">
    <div class="grid-2" style="align-items:start;gap:56px;">

      <!-- FORM -->
      <div class="card" style="padding:44px 38px;">
        <h2 style="color:#0f2137;font-size:22px;font-weight:700;margin-bottom:6px;">Request a Demo</h2>
        <p class="muted" style="margin-bottom:30px;">We&#x27;ll respond within one business day.</p>
        <form>
          <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13.5px;font-weight:600;color:#1a2440;margin-bottom:7px;">Name <span style="color:#e53e3e;">*</span></label>
            <input type="text" required placeholder="Your name" style="display:block;width:100%;padding:12px 15px;border:1.5px solid #d1d9e0;border-radius:8px;font-size:15px;font-family:inherit;color:#1a2440;background:white;transition:border-color .2s;outline:none;">
          </div>
          <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13.5px;font-weight:600;color:#1a2440;margin-bottom:7px;">Work Email <span style="color:#e53e3e;">*</span></label>
            <input type="email" required placeholder="you@yourproperty.com" style="display:block;width:100%;padding:12px 15px;border:1.5px solid #d1d9e0;border-radius:8px;font-size:15px;font-family:inherit;color:#1a2440;background:white;transition:border-color .2s;outline:none;">
          </div>
          <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13.5px;font-weight:600;color:#1a2440;margin-bottom:7px;">Company / Property <span style="color:#e53e3e;">*</span></label>
            <input type="text" required placeholder="Hotel or property name" style="display:block;width:100%;padding:12px 15px;border:1.5px solid #d1d9e0;border-radius:8px;font-size:15px;font-family:inherit;color:#1a2440;background:white;transition:border-color .2s;outline:none;">
          </div>
          <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13.5px;font-weight:600;color:#1a2440;margin-bottom:7px;">Phone (optional)</label>
            <input type="tel" placeholder="Best number to reach you" style="display:block;width:100%;padding:12px 15px;border:1.5px solid #d1d9e0;border-radius:8px;font-size:15px;font-family:inherit;color:#1a2440;background:white;transition:border-color .2s;outline:none;">
          </div>
          <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13.5px;font-weight:600;color:#1a2440;margin-bottom:7px;">Number of Pool Locations</label>
            <select style="display:block;width:100%;padding:12px 15px;border:1.5px solid #d1d9e0;border-radius:8px;font-size:15px;font-family:inherit;color:#1a2440;background:white;">
              <option value="">Select...</option>
              <option>1–5 locations</option>
              <option>6–15 locations</option>
              <option>16–50 locations</option>
              <option>50+ locations</option>
            </select>
          </div>
          <div style="margin-bottom:28px;">
            <label style="display:block;font-size:13.5px;font-weight:600;color:#1a2440;margin-bottom:7px;">Anything you&#x27;d like us to know? (optional)</label>
            <textarea rows="4" placeholder="Your property, timeline, or any questions..." style="display:block;width:100%;padding:12px 15px;border:1.5px solid #d1d9e0;border-radius:8px;font-size:15px;font-family:inherit;color:#1a2440;background:white;resize:vertical;outline:none;"></textarea>
          </div>
          <button type="submit" style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:17px;background:linear-gradient(135deg,#0077B6,#0096d6);color:white;border:none;border-radius:10px;font-size:16px;font-weight:700;font-family:inherit;cursor:pointer;box-shadow:0 4px 18px rgba(0,119,182,.3);">
            &#x1f4e8;&nbsp; Send Request
          </button>
        </form>
      </div>

      <!-- VALUE PANEL -->
      <div>
        <div style="background:linear-gradient(135deg,#0a1628,#003770);border-radius:18px;padding:40px 36px;margin-bottom:24px;color:white;">
          <div style="font-size:36px;margin-bottom:14px;">&#x1f3c6;</div>
          <h3 style="font-size:20px;font-weight:700;color:white;margin-bottom:10px;">IAAPA Brass Ring Award</h3>
          <p style="color:rgba(255,255,255,.72);font-size:15px;line-height:1.6;">Recognised as the #1 Poolside Innovation Technology in the hospitality industry.</p>
        </div>
        <div style="display:flex;flex-direction:column;gap:16px;">
          <div class="card" style="display:flex;gap:14px;align-items:flex-start;padding:22px;">
            <div style="flex-shrink:0;width:42px;height:42px;background:linear-gradient(135deg,#dbeeff,#b8dcff);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;">&#x1f4b0;</div>
            <div><strong style="display:block;margin-bottom:3px;color:#1a2440;font-size:15px;">Zero CapEx Required</strong><span class="muted">Full installation at zero upfront cost. Revenue share model only.</span></div>
          </div>
          <div class="card" style="display:flex;gap:14px;align-items:flex-start;padding:22px;">
            <div style="flex-shrink:0;width:42px;height:42px;background:linear-gradient(135deg,#d4f7e9,#b0f0d4);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;">&#x1f4c8;</div>
            <div><strong style="display:block;margin-bottom:3px;color:#1a2440;font-size:15px;">Up to 30% More F&amp;B Revenue</strong><span class="muted">The only stat we use — because it&#x27;s verified and real.</span></div>
          </div>
          <div class="card" style="display:flex;gap:14px;align-items:flex-start;padding:22px;">
            <div style="flex-shrink:0;width:42px;height:42px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;">&#x26a1;</div>
            <div><strong style="display:block;margin-bottom:3px;color:#1a2440;font-size:15px;">Fast, Seamless Deployment</strong><span class="muted">Works alongside your existing F&amp;B operation with zero disruption.</span></div>
          </div>
        </div>
        <div style="margin-top:20px;padding:20px;border-radius:12px;background:white;border:1px solid #e8ecf2;text-align:center;">
          <p class="muted" style="margin-bottom:6px;">Prefer email?</p>
          <a href="mailto:info@poolsafe.com" style="color:#0077B6;font-weight:700;font-size:16px;">info@poolsafe.com</a>
        </div>
      </div>

    </div>
  </div>
</section>

</div>
"""

# ────────────────────────────────────────────────────────────────────────────
# VIDEOS PAGE
# ────────────────────────────────────────────────────────────────────────────
def video_card(vid_id, thumb_url, title, subtitle, big=False):
    w = "100%" if big else "100%"
    h = "420px" if big else "220px"
    return f"""
<div class="vid-card" style="{'grid-column:1/-1;' if big else ''}">
  <div class="vid-thumb" style="aspect-ratio:16/9;"
       onclick="var f=document.createElement('iframe');f.src='https://www.youtube.com/embed/{vid_id}?autoplay=1';f.allow='accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture';f.allowFullscreen=true;f.style='width:100%;height:100%;border:0;';this.innerHTML='';this.appendChild(f);" title="Play {title}">
    <img src="{thumb_url}" alt="{title}" loading="lazy">
    <div class="play-btn"><span>&#x25b6;</span></div>
  </div>
  <div class="vid-meta">
    <h3>{title}</h3>
    <p>{subtitle}</p>
  </div>
</div>"""

def make_videos():
    return SHARED_CSS + f"""
<div class="lg">

<section class="inn-hero">
  <div class="wrap-sm" style="text-align:center;">
    <span class="eyebrow">See It In Action</span>
    <div class="divider" style="margin:0 auto 24px;"></div>
    <h1 style="font-size:clamp(28px,4.5vw,48px);font-weight:900;color:#0f2137;letter-spacing:-1px;">LounGenie in Action</h1>
    <p class="lead" style="margin:18px auto 0;max-width:520px;">Watch the LounGenie platform at work — from flagship resorts to water parks and premium poolside venues.</p>
  </div>
</section>

<!-- FEATURED VIDEO -->
<section class="sec" style="background:#0f2137;">
  <div class="wrap">
    <div style="text-align:center;margin-bottom:32px;">
      <span class="eyebrow" style="color:#7dd3fc;">Featured</span>
      <h2 style="color:white;margin-top:8px;">ORDER. STASH. CHARGE. CHILL.</h2>
      <p style="color:rgba(255,255,255,.65);font-size:16px;max-width:460px;margin:12px auto 0;line-height:1.6;">See the full LounGenie 2.0 platform in action — poolside ordering, smart storage, wireless charging, and premium amenities in one commercial-grade unit.</p>
    </div>
    <div style="max-width:780px;margin:0 auto;border-radius:18px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.4);">
      <div style="position:relative;aspect-ratio:16/9;cursor:pointer;background:#000;"
           onclick="var f=document.createElement('iframe');f.src='https://www.youtube.com/embed/EZ2CfBU30Ho?autoplay=1';f.allow='accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture';f.allowFullscreen=true;f.style='width:100%;height:100%;border:0;';this.innerHTML='';this.style.paddingBottom='0';this.appendChild(f);" title="Play LounGenie Overview">
        <img src="{VS1}" alt="LounGenie smart cabana system overview" style="width:100%;height:100%;object-fit:cover;display:block;" loading="lazy">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,.32);"></div>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">
          <div style="width:80px;height:80px;background:rgba(255,0,0,.92);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;color:white;box-shadow:0 8px 28px rgba(0,0,0,.5);">&#x25b6;</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- VIDEO GRID -->
<section class="sec" style="background:#f8fafc;">
  <div class="wrap">
    <div style="text-align:center;max-width:520px;margin:0 auto 44px;">
      <span class="eyebrow">More Videos</span>
      <div class="divider" style="margin:0 auto 24px;"></div>
      <h2 style="color:#0f2137;">Real Properties. Real Results.</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;">
      {video_card("bdVikQssTFc", VS2, "LounGenie 2.0 — Smarter Guest Ordering", "QR ordering built directly into the poolside experience")}
      {video_card("Pmvd2P8e1ew", VS5, "LounGenie — Built for Every Setting", "From resort cabanas to premium daybeds and clamshells")}
      {video_card("rPOsl_9R8dk", VS3, "LounGenie at Villatel Orlando Resort", "Smart cabana service in an upscale resort environment")}
      {video_card("M48NYM06JgY", VS4, "LounGenie at Orlando World Center Marriott", "ORDER, STASH, CHARGE, and CHILL at a premier Marriott resort")}
      {video_card("PhV1JVo9POI", VS7, "LounGenie at The Grove Resort &amp; Waterpark", "Stronger guest convenience and better F&amp;B performance")}
      {video_card("3Rjba7pWs_I", VS6, "LounGenie at Cowabunga Vegas", "High-traffic waterpark environment — fast service, maximum dwell")}
    </div>
  </div>
</section>

<section class="sec" style="background:white;padding:72px 0;">
  <div class="wrap-sm" style="text-align:center;">
    <h2 style="color:#0f2137;margin-bottom:18px;">See LounGenie at Your Property</h2>
    <p class="lead" style="margin:0 auto 34px;max-width:480px;">Watch any of the videos above, then talk to us about how LounGenie can work for your pool environment.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="/contact" class="btn btn-primary">&#x1f4c5;&nbsp; Request a Demo</a>
      <a href="/cabana-installation-photos" class="btn btn-outline">View Photo Gallery &rarr;</a>
    </div>
  </div>
</section>

</div>
"""

# ────────────────────────────────────────────────────────────────────────────
# GALLERY PAGE
# ────────────────────────────────────────────────────────────────────────────
def gallery_photo(url, alt, caption=""):
    return f"""<div style="position:relative;overflow:hidden;border-radius:12px;aspect-ratio:16/10;background:#e8ecf2;">
  <img src="{url}" alt="{alt}" loading="lazy" style="width:100%;height:100%;object-fit:cover;transition:transform .4s;">
  {'<div style="position:absolute;bottom:0;left:0;right:0;padding:10px 12px;background:linear-gradient(transparent,rgba(0,0,0,.55));color:white;font-size:12.5px;font-weight:500;line-height:1.4;">' + caption + '</div>' if caption else ''}
</div>"""

def make_gallery():
    return SHARED_CSS + f"""
<style>
.gallery-grid {{ display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }}
.gallery-grid .span2 {{ grid-column:span 2; }}
.gallery-grid > div > img {{ transition:transform .4s; }}
.gallery-grid > div:hover > img {{ transform:scale(1.04); }}
@media(max-width:700px){{ .gallery-grid{{ grid-template-columns:repeat(2,1fr); }} .gallery-grid .span2{{ grid-column:span 1; }} }}
</style>
<div class="lg">

<section class="inn-hero">
  <div class="wrap-sm" style="text-align:center;">
    <span class="eyebrow">Installation Photos</span>
    <div class="divider" style="margin:0 auto 24px;"></div>
    <h1 style="font-size:clamp(28px,4.5vw,48px);font-weight:900;color:#0f2137;letter-spacing:-1px;">LounGenie in the Real World</h1>
    <p class="lead" style="margin:18px auto 0;max-width:520px;">See how leading hotels, resorts, and water parks deploy LounGenie to elevate the poolside guest experience and drive F&amp;B revenue.</p>
  </div>
</section>

<!-- NAV TABS -->
<div style="background:white;border-bottom:1px solid #e8ecf2;padding:20px 0;position:sticky;top:72px;z-index:100;">
  <div class="wrap" style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
    <a href="#hotels" style="padding:8px 18px;border-radius:100px;border:1.5px solid #0077B6;color:#0077B6;font-size:14px;font-weight:600;text-decoration:none;background:white;transition:all .2s;">Hotels &amp; Resorts</a>
    <a href="#waterparks" style="padding:8px 18px;border-radius:100px;border:1.5px solid #e8ecf2;color:#4a5568;font-size:14px;font-weight:600;text-decoration:none;background:white;transition:all .2s;">Water Parks</a>
  </div>
</div>

<!-- HOTELS & RESORTS -->
<section class="sec" style="background:#f8fafc;" id="hotels">
  <div class="wrap">
    <div style="margin-bottom:40px;">
      <span class="eyebrow">Hotels &amp; Resorts</span>
      <div class="divider"></div>
      <h2 style="color:#0f2137;">Premium Hotel &amp; Resort Installations</h2>
      <p class="lead" style="margin-top:12px;max-width:560px;">From beachfront cabanas to pool clamshells, LounGenie integrates seamlessly into any premium poolside furniture and setting.</p>
    </div>

    <!-- Hilton Waikoloa -->
    <div style="margin-bottom:60px;">
      <h3 style="color:#0f2137;margin-bottom:20px;font-size:18px;font-weight:700;border-left:3px solid #0077B6;padding-left:14px;">Hilton Waikoloa Village</h3>
      <div class="gallery-grid">
        {gallery_photo(HIL1, "Hilton Waikoloa cabana with LounGenie smart amenity unit", "Aloha Falls Cabana")}
        {gallery_photo(HIL2, "Hilton Waikoloa cabana interior featuring LounGenie waterproof safe", "Cabana with smart storage")}
        {gallery_photo(HIL3, "Hilton Waikoloa resort daybed area with LounGenie unit", "Daybed area installation")}
        {gallery_photo(HIL4, "Hilton Waikoloa Kona Pool cabanas", "Kona Pool Cabanas")}
        {gallery_photo(HIL5, "Hilton Waikoloa pool side cabana with LounGenie", "Poolside cabana")}
      </div>
    </div>

    <!-- The Grove -->
    <div style="margin-bottom:60px;">
      <h3 style="color:#0f2137;margin-bottom:20px;font-size:18px;font-weight:700;border-left:3px solid #0077B6;padding-left:14px;">The Grove Resort &amp; Waterpark</h3>
      <div class="gallery-grid">
        {gallery_photo(GROVE7, "The Grove Resort cabana with LounGenie smart amenity unit installed", "Cabana installation")}
        {gallery_photo(GROVE1, "The Grove pool deck cabana featuring LounGenie waterproof safe", "Poolside cabana")}
        {gallery_photo(GROVE5, "Pool deck cabana at The Grove Resort", "Pool deck setup")}
        {gallery_photo(GROVE6, "The Grove Resort poolside with LounGenie guest amenities", "Guest amenity setup")}
      </div>
    </div>

    <!-- Sea World + Other -->
    <div style="margin-bottom:60px;">
      <h3 style="color:#0f2137;margin-bottom:20px;font-size:18px;font-weight:700;border-left:3px solid #0077B6;padding-left:14px;">Other Hotel &amp; Resort Installations</h3>
      <div class="gallery-grid">
        {gallery_photo(SEAWORLD, "Sea World San Diego full-service hotel cabana with LounGenie", "Sea World San Diego")}
        {gallery_photo(WESTIN, "Westin Hilton Head cabana with LounGenie smart amenity unit", "Westin Hilton Head")}
        {gallery_photo(CONTACT, "Resort cabana with LounGenie amenity unit installed", "VOR Resort Cabana")}
        {gallery_photo(SOAKY, "Soaky Mountain premium seating area with LounGenie", "Soaky Mountain")}
        {gallery_photo(MASSA, "Massanutten premium cabana setup with LounGenie", "Massanutten")}
        {gallery_photo(TYPHOON, "Typhoon Texas premium cabana featuring LounGenie", "Typhoon Texas")}
      </div>
    </div>
  </div>
</section>

<!-- WATER PARKS -->
<section class="sec" style="background:white;" id="waterparks">
  <div class="wrap">
    <div style="margin-bottom:40px;">
      <span class="eyebrow">Water Parks</span>
      <div class="divider"></div>
      <h2 style="color:#0f2137;">Water Park Installations</h2>
      <p class="lead" style="margin-top:12px;max-width:560px;">High-traffic water park environments — fast service, guest convenience, and premium cabana value at scale.</p>
    </div>
    <div class="gallery-grid">
      {gallery_photo(COWA1, "Cowabunga Canyon cabana with LounGenie smart amenity unit", "Cowabunga Canyon")}
      {gallery_photo(COWA2, "Cowabunga Canyon cabana interior showing LounGenie ice bucket", "Interior setup")}
      {gallery_photo(COWA3, "Cowabunga Bay cabana interior featuring LounGenie smart unit", "Cowabunga Bay")}
      {gallery_photo(COWA4, "Cowabunga Canyon poolside cabana with LounGenie waterproof safe", "Poolside cabana")}
      {gallery_photo(PP01, "LounGenie poolside installation", "")}
      {gallery_photo(PP02, "LounGenie poolside installation", "")}
      {gallery_photo(PP03, "LounGenie poolside installation", "")}
      {gallery_photo(PP04, "LounGenie poolside installation", "")}
    </div>
  </div>
</section>

<!-- CTA -->
<section class="sec" style="background:#0f2137;padding:72px 0;">
  <div class="wrap-sm" style="text-align:center;">
    <h2 style="color:white;margin-bottom:16px;">Ready to See LounGenie at Your Property?</h2>
    <p style="color:rgba(255,255,255,.68);font-size:17px;max-width:420px;margin:0 auto 34px;line-height:1.7;">Join the hotels, resorts, and water parks already driving more poolside revenue with LounGenie.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="/contact" class="btn btn-primary">&#x1f4c5;&nbsp; Request a Demo</a>
      <a href="/loungenie-videos" class="btn btn-ghost">Watch Videos &rarr;</a>
    </div>
  </div>
</section>

</div>
"""

# ────────────────────────────────────────────────────────────────────────────
# HELPERS
# ────────────────────────────────────────────────────────────────────────────
def update_page(page_id, title, html):
    # Wrap in Gutenberg raw HTML block — bypasses wpautop which inserts <br> tags
    # after every newline and breaks flex/grid layouts
    wrapped = "<!-- wp:html -->\n" + html + "\n<!-- /wp:html -->"
    payload = json.dumps({"title": title, "content": wrapped, "status": "publish"}).encode()
    req = urllib.request.Request(
        f"{BASE_URL}/{page_id}", data=payload, method="POST",
        headers={**HEADERS, "Content-Length": str(len(payload))},
    )
    try:
        with urllib.request.urlopen(req, timeout=45) as r:
            data = json.loads(r.read())
            return True, data.get("link", "")
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}: {e.read().decode()[:300]}"
    except Exception as e:
        return False, str(e)

def fix_menu_label(item_id, new_title):
    payload = json.dumps({"title": new_title}).encode()
    req = urllib.request.Request(
        f"{MENU_URL}/{item_id}", data=payload, method="POST",
        headers={**HEADERS, "Content-Length": str(len(payload))},
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as r:
            return True, ""
    except Exception as e:
        return False, str(e)

# ────────────────────────────────────────────────────────────────────────────
# MAIN
# ────────────────────────────────────────────────────────────────────────────
print("="*60)
print("LounGenie Professional Redesign v2")
print("="*60)

# ── Step 1: Fix nav menu labels ──────────────────────────────────────────────
print("\n[1/3] Fixing navigation menu labels...")
menu_fixes = [
    (5150, "Home"),
    (3870, "Features"),
    (5161, "About"),
    (5930, "Contact"),
]
for item_id, label in menu_fixes:
    ok, err = fix_menu_label(item_id, label)
    status = "✓" if ok else f"✗ {err}"
    print(f"  [{status}] Menu item {item_id} → '{label}'")

# ── Step 2: Update pages ─────────────────────────────────────────────────────
print("\n[2/3] Updating pages with real media and professional design...")
pages = [
    (4701, "Home | LounGenie Poolside Revenue Platform",          make_home()),
    (2989, "Features | LounGenie Poolside Revenue Platform",       make_features()),
    (4862, "About | Pool Safe Enterprise & LounGenie",             make_about()),
    (5139, "Contact | LounGenie",                                  make_contact()),
    (5285, "Videos | LounGenie in Action",                         make_videos()),
    (5223, "Gallery | LounGenie Installation Photos",              make_gallery()),
]
for pid, title, html in pages:
    ok, result = update_page(pid, title, html)
    status = "✓" if ok else "✗ FAILED"
    name = title.split("|")[0].strip()
    print(f"  [{status}] {name:12s} (ID {pid})  {result if ok else result}")

print("\n[3/3] Done.")
print("\nWhat changed:")
print("  • Nav labels fixed: 'LounGenie — Increase... 30%' → 'Home', etc.")
print("  • Header: sticky, frosted-glass, visible on all pages")
print("  • 'Contact' nav item styled as a CTA button")
print("  • All pages now use real WordPress media images")
print("  • Videos page: click-to-play YouTube embeds with real thumbnails")
print("  • Gallery page: organised by Hotels/Resorts + Water Parks")
