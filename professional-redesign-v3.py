"""
LounGenie — Professional Redesign v3 (TwentyTwentyFour Edition)
================================================================
Theme: Twenty Twenty-Four (block theme / FSE)
Strategy:
  • Set every page to the "page-wide" template → removes constrained-layout,
    no sidebar, content gets full viewport width
  • Wrap all HTML in <!-- wp:html --> blocks to bypass wpautop
  • Add .alignfull CSS + negative margins to fully escape TT4's content padding
  • Remove Elementor body classes interference with CSS resets
  • Override TT4's global-padding & is-layout-constrained on our .lg wrapper
  • Build completely custom, world-class page designs for all 6 pages
  • Only verified stat: up to 30% increase in F&B sales
"""
import json, base64, urllib.request, urllib.error

AUTH    = base64.b64encode(b"copilot:7NiL OZ17 ApP3 tIgF 6zlT ug7u").decode()
BASE    = "https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2"
PAGES   = BASE + "/pages"
MENUS   = BASE + "/menu-items"
HEADERS = {"Authorization": f"Basic {AUTH}", "Content-Type": "application/json"}
IMG     = "https://loungenie.com/Loungenie%E2%84%A2/wp-content/uploads/2026/03/"

# ── Images ───────────────────────────────────────────────────────────────────
HERO_BG  = IMG + "hero9-bg-1.jpg"
HERO_FG  = IMG + "hero7-fg.jpg"
HIL1     = IMG + "Hilton-Wakoloa-Village-2018-10-Aloha-Falls-Cabana-1-scaled.jpg"
HIL2     = IMG + "Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-2-scaled.jpg"
HIL3     = IMG + "Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-3-scaled.jpg"
HIL4     = IMG + "Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-4-scaled.jpg"
HIL5     = IMG + "Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-6.jpg"
GROVE1   = IMG + "The-Grove-1.jpg"
GROVE5   = IMG + "The-Grove-5.jpg"
GROVE6   = IMG + "The-Grove-6.jpg"
GROVE7   = IMG + "The-Grove-7-scaled.jpg"
SEAWORLD = IMG + "Sea-World-San-Diego.jpg"
COWA1    = IMG + "38f4fc95-7925-4625-b0e8-5ba78771c037.jpg"
COWA2    = IMG + "a5ea38b9-4578-4356-a118-f168caa0ec90.jpg"
COWA3    = IMG + "IMG_3233-scaled-1.jpg"
COWA4    = IMG + "IMG_3235-scaled-1.jpg"
SOAKY    = IMG + "page_1145__mg_6277-copy-1-web.webp"
MASSA    = IMG + "page_1145_img_6227-copy-1-web.webp"
WESTIN   = IMG + "175-Westin__hhi_bjp_-_low_res-1.avif"
TYPHOON  = IMG + "1714017439507-e1773261343388.webp"
CONTACT  = IMG + "3-VOR-cabana-e1773774348955.jpg"
ABOUT_BG = IMG + "about-bg-free-img.jpg"
FEAT5    = IMG + "feature-5.jpg"
QR_ORDER = IMG + "e106d1a0-f868-46cd-92f8-457dc6a8f698.webp"
NANO     = IMG + "Nano_Banana_Pro_Show_the_full_unit_in_a_premium_poolside_beach_setting.webp"
GEMINI   = IMG + "Gemini_Generated_Image_xs1ghrxs1ghrxs1g.png"
MARG     = IMG + "margaritaville-jimmy-buffetts-logo-png-transparent.png"
RITZ     = IMG + "the-ritz-carlton-logo-png-transparent.webp"
NIAGARA  = IMG + "logo-NiagaraFalls_02.png"
LOGO1    = IMG + "logo-1.png"
LOGO4    = IMG + "logo-4.png"
VS1      = IMG + "Screenshot-2026-03-11-210110.webp"
VS2      = IMG + "Screenshot-2026-03-11-205705-1.webp"
VS3      = IMG + "Screenshot-2026-03-11-205434.webp"
VS4      = IMG + "Screenshot-2026-03-11-205758.webp"
VS5      = IMG + "Screenshot-2026-03-11-210240.webp"
VS6      = IMG + "Screenshot-2026-03-11-210839.webp"
VS7      = IMG + "Screenshot-2025-11-06-091447.webp"

# ── Shared CSS ────────────────────────────────────────────────────────────────
# TT4 injects:
#   .entry-content.wp-block-post-content.has-global-padding → padding: 0 var(--wp--style--root--padding-right)
#   is-layout-constrained → max-width + margin:auto on children
# Strategy: use negative margins + width:100vw to break out, then re-contain ourselves
SHARED_CSS = """
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════════════
   TT4 ESCAPE: Break out of entry-content constrained padding
   TT4 adds padding-left/right via --wp--style--root--padding-right to
   .has-global-padding, and max-width via is-layout-constrained.
   We negate that entirely on our .lg wrapper so we get 100vw.
   ═══════════════════════════════════════════════════ */
.entry-content .lg,
.wp-block-post-content .lg {
  margin-left: calc(-1 * var(--wp--style--root--padding-right, 30px)) !important;
  margin-right: calc(-1 * var(--wp--style--root--padding-right, 30px)) !important;
  max-width: none !important;
  width: calc(100% + 2 * var(--wp--style--root--padding-right, 30px)) !important;
}
/* Also break out of page-wide template potential inner max-width */
.wp-block-post-content.is-layout-constrained > .lg {
  max-width: none !important;
}
/* Kill TT4 global font / spacing that might override our Inter */
.lg, .lg * {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
  box-sizing: border-box;
}
.lg p, .lg li, .lg a, .lg span, .lg div, .lg section, .lg h1, .lg h2, .lg h3, .lg h4 {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
}
.lg h1, .lg h2, .lg h3, .lg h4, .lg h5, .lg h6 {
  text-transform: none !important;
  font-style: normal !important;
  letter-spacing: normal !important;
}
/* TT4 block styles that leak in */
.lg p { margin-top: 0 !important; margin-bottom: 0 !important; }
.lg ul, .lg ol { margin: 0 !important; padding: 0 !important; }
.lg a { text-decoration: none !important; }
.lg img { display: block !important; max-width: 100% !important; border: 0 !important; }

/* ═══════════════════════════════════════════════════
   LAYOUT
   ═══════════════════════════════════════════════════ */
.lg { color: #1a2440; overflow-x: hidden; }
.lg .wrap    { max-width: 1200px; margin: 0 auto; padding: 0 40px; }
.lg .wrap-sm { max-width: 800px;  margin: 0 auto; padding: 0 40px; }
.lg .wrap-xs { max-width: 620px;  margin: 0 auto; padding: 0 40px; }
.lg .sec     { padding: 96px 0; }
.lg .sec-sm  { padding: 64px 0; }

/* ═══════════════════════════════════════════════════
   TYPOGRAPHY  
   ═══════════════════════════════════════════════════ */
.lg .eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700 !important; letter-spacing: 3px; text-transform: uppercase !important; color: #0077B6; margin-bottom: 14px; }
.lg .eyebrow::before { content: ""; display: inline-block; width: 20px; height: 2px; background: #0077B6; border-radius: 2px; }
.lg h1 { font-size: clamp(36px,5.5vw,72px) !important; font-weight: 900 !important; line-height: 1.05 !important; letter-spacing: -2.5px !important; margin: 0 !important; }
.lg h2 { font-size: clamp(26px,3.5vw,48px) !important; font-weight: 800 !important; line-height: 1.1 !important; letter-spacing: -1px !important; margin: 0 !important; }
.lg h3 { font-size: clamp(16px,1.8vw,21px) !important; font-weight: 700 !important; line-height: 1.3 !important; letter-spacing: -0.3px !important; margin: 0 !important; }
.lg h4 { font-size: 15px !important; font-weight: 700 !important; line-height: 1.4 !important; margin: 0 !important; }
.lg .lead  { font-size: clamp(17px,1.6vw,20px) !important; line-height: 1.7 !important; color: #4a5568; }
.lg .body  { font-size: 16px !important; line-height: 1.7 !important; color: #4a5568; }
.lg .muted { color: #7a8698; font-size: 15px !important; line-height: 1.65 !important; }
.lg .small { font-size: 13px !important; color: #9aa5b4; }

/* ═══════════════════════════════════════════════════
   BUTTONS
   ═══════════════════════════════════════════════════ */
.lg .btn { display: inline-flex !important; align-items: center !important; gap: 8px !important; padding: 15px 32px !important; border-radius: 12px !important; font-weight: 700 !important; font-size: 15px !important; transition: all .22s !important; cursor: pointer !important; border: none !important; line-height: 1 !important; white-space: nowrap !important; letter-spacing: -0.2px !important; }
.lg .btn-primary { background: linear-gradient(135deg,#0055a5,#0077B6,#00a0e3) !important; color: white !important; box-shadow: 0 4px 20px rgba(0,119,182,.35) !important; }
.lg .btn-primary:hover { transform: translateY(-2px) !important; box-shadow: 0 10px 32px rgba(0,119,182,.48) !important; color: white !important; }
.lg .btn-white { background: rgba(255,255,255,.97) !important; color: #0f2137 !important; box-shadow: 0 4px 20px rgba(0,0,0,.14) !important; }
.lg .btn-white:hover { transform: translateY(-2px) !important; box-shadow: 0 10px 32px rgba(0,0,0,.22) !important; color: #0077B6 !important; }
.lg .btn-ghost { background: transparent !important; color: white !important; border: 1.5px solid rgba(255,255,255,.55) !important; }
.lg .btn-ghost:hover { background: rgba(255,255,255,.1) !important; border-color: rgba(255,255,255,.9) !important; color: white !important; transform: translateY(-2px) !important; }
.lg .btn-outline { background: transparent !important; color: #0077B6 !important; border: 2px solid #0077B6 !important; }
.lg .btn-outline:hover { background: #0077B6 !important; color: white !important; }
.lg .btn-sm { padding: 10px 20px !important; font-size: 13.5px !important; border-radius: 9px !important; }

/* ═══════════════════════════════════════════════════
   CARDS
   ═══════════════════════════════════════════════════ */
.lg .card { background: white; border-radius: 18px; border: 1px solid #e8ecf2; transition: box-shadow .25s, transform .25s; overflow: hidden; }
.lg .card:hover { box-shadow: 0 20px 56px rgba(0,0,0,.1); transform: translateY(-4px); }
.lg .card-pad { padding: 36px 30px; }
.lg .card-pad-sm { padding: 24px 22px; }

/* Icon boxes */
.lg .icon-box { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 20px; flex-shrink: 0; }
.lg .ib-blue  { background: linear-gradient(135deg,#dbeeff,#b8dcff); }
.lg .ib-cyan  { background: linear-gradient(135deg,#d0f8ff,#a8efff); }
.lg .ib-green { background: linear-gradient(135deg,#d4f7e9,#b0f0d4); }
.lg .ib-gold  { background: linear-gradient(135deg,#fff4cc,#ffe08a); }
.lg .ib-rose  { background: linear-gradient(135deg,#ffe4e6,#fecdd3); }

/* ═══════════════════════════════════════════════════
   GRIDS
   ═══════════════════════════════════════════════════ */
.lg .grid-4 { display: grid; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); gap: 24px; }
.lg .grid-3 { display: grid; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); gap: 28px; }
.lg .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center; }
.lg .grid-2-auto { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

/* ═══════════════════════════════════════════════════
   UTILITIES
   ═══════════════════════════════════════════════════ */
.lg .divider { width: 48px; height: 4px; background: linear-gradient(90deg,#0077B6,#00c6fb); border-radius: 3px; margin-bottom: 28px; }
.lg .tc { text-align: center !important; }
.lg .mt4  { margin-top: 4px !important; } .lg .mt8  { margin-top: 8px !important; }
.lg .mt12 { margin-top: 12px !important; } .lg .mt16 { margin-top: 16px !important; }
.lg .mt20 { margin-top: 20px !important; } .lg .mt24 { margin-top: 24px !important; }
.lg .mt32 { margin-top: 32px !important; } .lg .mt40 { margin-top: 40px !important; }
.lg .mt48 { margin-top: 48px !important; } .lg .mt56 { margin-top: 56px !important; }
.lg .mb4  { margin-bottom: 4px !important; } .lg .mb8  { margin-bottom: 8px !important; }
.lg .mb12 { margin-bottom: 12px !important; } .lg .mb16 { margin-bottom: 16px !important; }
.lg .mb20 { margin-bottom: 20px !important; } .lg .mb24 { margin-bottom: 24px !important; }
.lg .mb32 { margin-bottom: 32px !important; } .lg .mb40 { margin-bottom: 40px !important; }

/* Checklist */
.lg .checks { list-style: none; display: flex; flex-direction: column; gap: 12px; }
.lg .checks li { display: flex; gap: 12px; align-items: flex-start; color: #374151; font-size: 15.5px !important; line-height: 1.6 !important; }
.lg .checks li::before { content: "✓"; flex-shrink: 0; width: 22px; height: 22px; background: linear-gradient(135deg,#0055a5,#0077B6); color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 900; margin-top: 2px; }

/* Pills */
.lg .pill { display: inline-block; padding: 5px 14px; border-radius: 100px; font-size: 11px !important; font-weight: 700 !important; letter-spacing: .8px; text-transform: uppercase !important; }
.lg .pill-blue  { background: #dbeeff; color: #0055a5; }
.lg .pill-green { background: #dcfce7; color: #15803d; }
.lg .pill-red   { background: #fee2e2; color: #b91c1c; }
.lg .pill-amber { background: #fef3c7; color: #b45309; }

/* Step numbers */
.lg .step-n { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg,#0055a5,#0077B6); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800 !important; flex-shrink: 0; box-shadow: 0 6px 20px rgba(0,85,165,.32); }

/* Award badge */
.lg .award { display: inline-flex; align-items: center; gap: 12px; background: linear-gradient(135deg,#fffbeb,#fef3c7); border: 1px solid #fde68a; border-radius: 12px; padding: 14px 20px; }

/* Logo strip */
.lg .logo-strip { display: flex; align-items: center; justify-content: center; gap: 40px; flex-wrap: wrap; }
.lg .logo-strip img { height: 32px !important; width: auto !important; max-width: 140px !important; object-fit: contain !important; filter: grayscale(100%) opacity(.45); transition: filter .25s; }
.lg .logo-strip img:hover { filter: grayscale(0%) opacity(1); }

/* Photo grid */
.lg .photo-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
.lg .photo-grid .photo-item { border-radius: 14px; overflow: hidden; aspect-ratio: 16/10; }
.lg .photo-grid .photo-item img { width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; transition: transform .45s; }
.lg .photo-grid .photo-item:hover img { transform: scale(1.06); }

/* ═══════════════════════════════════════════════════
   SECTIONS
   ═══════════════════════════════════════════════════ */
/* HERO */
.lg .hero { position: relative; min-height: 88vh; display: flex; align-items: center; background: #050c1e; overflow: hidden; }
.lg .hero-bg { position: absolute; inset: 0; background-size: cover; background-position: center 30%; }
.lg .hero-overlay { position: absolute; inset: 0; background: linear-gradient(110deg,rgba(4,9,26,.92) 0%,rgba(0,42,90,.78) 50%,rgba(0,90,166,.5) 100%); }
.lg .hero-inner { position: relative; z-index: 2; padding: 100px 0 88px; width: 100%; }
/* Hero text gradient */
.lg .hero-grad { background: linear-gradient(90deg,#60c8ff,#a8d8ff,#60c8ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; background-size: 200% auto; animation: shimmer 4s linear infinite; }
@keyframes shimmer { 0%{background-position:0% 50%} 100%{background-position:200% 50%} }

/* Inner hero (light bg) */
.lg .inn-hero { background: linear-gradient(160deg,#f0f6fc 0%,#fafcff 100%); padding: 80px 0 60px; border-bottom: 1px solid #e2e8f0; }

/* Stat banner */
.lg .stat-banner { background: linear-gradient(135deg,#050c1e 0%,#002a5a 50%,#004f99 100%); padding: 96px 0; text-align: center; }
.lg .big-stat { font-size: clamp(56px,9vw,100px) !important; font-weight: 900 !important; letter-spacing: -4px !important; line-height: 1 !important; background: linear-gradient(135deg,#ffffff,#a8d8ff,#ffffff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

/* Feature image */
.lg .feat-img { width: 100% !important; height: 360px !important; object-fit: cover !important; border-radius: 20px !important; box-shadow: 0 20px 60px rgba(0,0,0,.18) !important; display: block !important; }

/* Video card */
.lg .vid-card { border-radius: 16px; overflow: hidden; border: 1px solid #e8ecf2; transition: all .25s; background: white; box-shadow: 0 2px 16px rgba(0,0,0,.06); }
.lg .vid-card:hover { box-shadow: 0 16px 48px rgba(0,0,0,.14); transform: translateY(-4px); }
.lg .vid-thumb { position: relative; cursor: pointer; overflow: hidden; aspect-ratio: 16/9; }
.lg .vid-thumb img { width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; transition: transform .5s; }
.lg .vid-card:hover .vid-thumb img { transform: scale(1.05); }
.lg .vid-play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,.2); transition: background .25s; }
.lg .vid-card:hover .vid-play { background: rgba(0,0,0,.3); }
.lg .vid-play span { width: 60px; height: 60px; background: rgba(220,0,0,.94); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; box-shadow: 0 6px 24px rgba(0,0,0,.45); transition: transform .25s; }
.lg .vid-card:hover .vid-play span { transform: scale(1.1); }
.lg .vid-meta { padding: 18px 18px 20px; }
.lg .vid-meta h3 { font-size: 15px !important; font-weight: 700 !important; color: #1a2440 !important; line-height: 1.4 !important; margin-bottom: 5px !important; }
.lg .vid-meta p  { font-size: 13px !important; color: #7a8698; }

/* Divider line */
.lg .rule { border: 0; border-top: 1px solid #e8ecf2; margin: 0; }

/* Gallery grid (masonry-ish) */
.lg .gal-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
.lg .gal-item { border-radius: 12px; overflow: hidden; position: relative; cursor: pointer; }
.lg .gal-item img { width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; transition: transform .5s; }
.lg .gal-item:hover img { transform: scale(1.06); }
.lg .gal-item .cap { position: absolute; bottom: 0; left: 0; right: 0; padding: 10px 14px; background: linear-gradient(transparent, rgba(0,0,0,.65)); color: white; font-size: 12.5px; font-weight: 600; opacity: 0; transition: opacity .25s; }
.lg .gal-item:hover .cap { opacity: 1; }

/* ═══════════════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════════════ */
@media(max-width:960px) {
  .lg .grid-2 { grid-template-columns: 1fr; gap: 48px; }
  .lg .grid-2-auto { grid-template-columns: 1fr; }
  .lg .photo-grid { grid-template-columns: repeat(2,1fr); }
  .lg .gal-grid { grid-template-columns: repeat(2,1fr); }
}
@media(max-width:640px) {
  .lg .wrap, .lg .wrap-sm, .lg .wrap-xs { padding: 0 20px; }
  .lg .sec { padding: 64px 0; }
  .lg .hero { min-height: 80vh; }
  .lg .photo-grid { grid-template-columns: repeat(2,1fr); gap: 10px; }
  .lg .gal-grid { grid-template-columns: repeat(2,1fr); gap: 10px; }
  .lg .grid-4 { grid-template-columns: 1fr 1fr; }
  .lg .logo-strip { gap: 22px; }
  .lg .hero-inner { padding: 72px 0 64px; }
}
</style>
"""

# ════════════════════════════════════════════════════════════════════════════
# HOME PAGE
# ════════════════════════════════════════════════════════════════════════════
def make_home():
    return SHARED_CSS + f"""
<div class="lg">

<!-- ─── HERO ─────────────────────────────────────────────────────── -->
<section class="hero">
  <div class="hero-bg" style="background-image:url('{HERO_BG}');"></div>
  <div class="hero-overlay"></div>
  <div class="hero-inner wrap">
    <div style="max-width:700px;">
      <div class="award mb40" style="display:inline-flex;">
        <span style="font-size:22px;">&#x1f3c6;</span>
        <div style="text-align:left;">
          <strong style="display:block;color:#92400e;font-size:13px;font-weight:800;">IAAPA Brass Ring Award Winner</strong>
          <span style="font-size:12px;color:#a16207;">#1 Poolside Innovation Technology</span>
        </div>
      </div>
      <h1 style="color:white;margin-bottom:24px;">
        Increase Poolside<br>
        F&amp;B Revenue<br>
        <span class="hero-grad">by Up to 30%</span>
      </h1>
      <p class="lead mt20 mb40" style="color:rgba(255,255,255,.80);max-width:560px;">
        LounGenie&#x2122; is the all-in-one poolside platform — smart ordering, secure storage, wireless charging, and premium amenities. Zero capital cost to your property.
      </p>
      <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
        <a href="/Loungenie%E2%84%A2/contact" class="btn btn-white">&#x1f4c5;&nbsp; Schedule a Demo</a>
        <a href="/Loungenie%E2%84%A2/poolside-amenity-unit" class="btn btn-ghost">See Features &rarr;</a>
      </div>
    </div>
  </div>
</section>

<!-- ─── PARTNER LOGOS ─────────────────────────────────────────────── -->
<div style="background:white;border-bottom:1px solid #e8ecf2;padding:32px 0;">
  <div class="wrap">
    <p class="small tc mb20" style="letter-spacing:2.5px;text-transform:uppercase;font-weight:700;">Trusted By Leading Properties</p>
    <div class="logo-strip">
      <img src="{MARG}" alt="Margaritaville" loading="lazy">
      <img src="{RITZ}" alt="Ritz-Carlton" loading="lazy">
      <img src="{NIAGARA}" alt="Niagara Falls" loading="lazy">
      <img src="{LOGO1}" alt="Partner Property" loading="lazy">
      <img src="{LOGO4}" alt="Partner Property" loading="lazy">
    </div>
  </div>
</div>

<!-- ─── THE PROBLEM ───────────────────────────────────────────────── -->
<section class="sec" style="background:#f8fafc;">
  <div class="wrap">
    <div class="grid-2">
      <div>
        <span class="eyebrow">The Opportunity</span>
        <div class="divider"></div>
        <h2 style="color:#0a1628;margin-bottom:24px;">Guests Leave Early.<br>Revenue Leaves With Them.</h2>
        <p class="lead mb40">Three invisible forces drive guests off the pool deck before they've spent what they would have. LounGenie eliminates all three.</p>
        <div style="display:flex;flex-direction:column;gap:20px;">
          <div style="display:flex;gap:16px;align-items:flex-start;background:white;border-radius:14px;padding:20px;border:1px solid #e8ecf2;box-shadow:0 2px 12px rgba(0,0,0,.04);">
            <div style="flex-shrink:0;width:44px;height:44px;background:linear-gradient(135deg,#fee2e2,#fecaca);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;">&#x1f50b;</div>
            <div><strong style="display:block;color:#0a1628;font-size:15px;margin-bottom:4px;font-weight:700;">Dead Battery = Guest Exit</strong><span class="muted">Phone dies &rarr; guest leaves to find a charger &rarr; F&amp;B sale gone.</span></div>
          </div>
          <div style="display:flex;gap:16px;align-items:flex-start;background:white;border-radius:14px;padding:20px;border:1px solid #e8ecf2;box-shadow:0 2px 12px rgba(0,0,0,.04);">
            <div style="flex-shrink:0;width:44px;height:44px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;">&#x1f9f3;</div>
            <div><strong style="display:block;color:#0a1628;font-size:15px;margin-bottom:4px;font-weight:700;">No Secure Storage = Anxiety &amp; Early Departure</strong><span class="muted">Guests can't relax or leave their spot to order — valuables aren't safe.</span></div>
          </div>
          <div style="display:flex;gap:16px;align-items:flex-start;background:white;border-radius:14px;padding:20px;border:1px solid #e8ecf2;box-shadow:0 2px 12px rgba(0,0,0,.04);">
            <div style="flex-shrink:0;width:44px;height:44px;background:linear-gradient(135deg,#dbeeff,#bfdbfe);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;">&#x1f6b6;</div>
            <div><strong style="display:block;color:#0a1628;font-size:15px;margin-bottom:4px;font-weight:700;">Bar Trip = Skipped Order</strong><span class="muted">If ordering means losing a lounge chair, most guests go without.</span></div>
          </div>
        </div>
      </div>
      <div style="position:relative;">
        <div style="border-radius:24px;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,.22);">
          <img src="{HIL1}" alt="LounGenie smart cabana unit at Hilton Waikoloa resort" style="width:100%;height:480px;object-fit:cover;display:block;">
        </div>
        <div style="position:absolute;bottom:-20px;left:24px;background:white;border-radius:16px;padding:18px 22px;box-shadow:0 12px 40px rgba(0,0,0,.14);border:1px solid #e8ecf2;display:flex;align-items:center;gap:14px;">
          <div style="width:44px;height:44px;background:linear-gradient(135deg,#0055a5,#0077B6);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;color:white;flex-shrink:0;">&#x1f4b0;</div>
          <div><strong style="display:block;color:#0a1628;font-size:16px;margin-bottom:2px;">Zero CapEx</strong><span style="font-size:13px;color:#7a8698;">Full install at no cost to your property</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ─── PLATFORM FEATURES ─────────────────────────────────────────── -->
<section class="sec" style="background:white;">
  <div class="wrap">
    <div class="tc mb56" style="max-width:600px;margin-left:auto;margin-right:auto;">
      <span class="eyebrow">The Platform</span>
      <div class="divider" style="margin:0 auto 24px;"></div>
      <h2 style="color:#0a1628;margin-bottom:18px;">ORDER. STASH. CHARGE. CHILL.</h2>
      <p class="lead">Four integrated modules — each one targeting a specific reason guests leave pools early and stop spending.</p>
    </div>
    <div class="grid-4">
      <div class="card card-pad" style="border-top:3px solid #0077B6;">
        <div class="icon-box ib-blue">&#x1f4f1;</div>
        <h3 class="mb12">ORDER</h3>
        <p class="muted mb20">Direct F&amp;B ordering from any lounge chair via QR. No walking. No lost seats. Pure incremental revenue.</p>
        <a href="/Loungenie%E2%84%A2/poolside-amenity-unit" style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:700;color:#0077B6;">Learn more &rarr;</a>
      </div>
      <div class="card card-pad" style="border-top:3px solid #0099cc;">
        <div class="icon-box ib-cyan">&#x1f4e6;</div>
        <h3 class="mb12">STASH</h3>
        <p class="muted mb20">Waterproof smart storage keeps valuables safe poolside. Guests relax — and stay — instead of heading inside.</p>
        <a href="/Loungenie%E2%84%A2/poolside-amenity-unit" style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:700;color:#0077B6;">Learn more &rarr;</a>
      </div>
      <div class="card card-pad" style="border-top:3px solid #f59e0b;">
        <div class="icon-box ib-gold">&#x26a1;</div>
        <h3 class="mb12">CHARGE</h3>
        <p class="muted mb20">Wireless charging pads at every unit eliminate the #1 reason for early pool exits — phone battery anxiety.</p>
        <a href="/Loungenie%E2%84%A2/poolside-amenity-unit" style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:700;color:#0077B6;">Learn more &rarr;</a>
      </div>
      <div class="card card-pad" style="border-top:3px solid #10b981;">
        <div class="icon-box ib-green">&#x1f9ca;</div>
        <h3 class="mb12">CHILL</h3>
        <p class="muted mb20">Ice bucket, premium amenities, and resort-quality comfort — the environment that keeps guests poolside all day.</p>
        <a href="/Loungenie%E2%84%A2/poolside-amenity-unit" style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:700;color:#0077B6;">Learn more &rarr;</a>
      </div>
    </div>
  </div>
</section>

<!-- ─── STAT BANNER ────────────────────────────────────────────────── -->
<section class="stat-banner">
  <div class="wrap-xs tc">
    <span class="eyebrow mt0 mb16" style="color:#60c8ff;display:block;">Verified Result</span>
    <div class="big-stat mb12">Up to 30%</div>
    <p style="font-size:22px;font-weight:600;color:white;opacity:.88;margin-bottom:16px;">increase in poolside F&amp;B revenue</p>
    <p style="color:rgba(255,255,255,.58);font-size:16px;max-width:460px;margin:0 auto 44px;line-height:1.75;">Driven by longer dwell time and frictionless in-seat ordering. Properties see the lift consistently.</p>
    <a href="/Loungenie%E2%84%A2/contact" class="btn btn-white">See it at your property &rarr;</a>
  </div>
</section>

<!-- ─── HOW IT WORKS ───────────────────────────────────────────────── -->
<section class="sec" style="background:#f8fafc;">
  <div class="wrap">
    <div class="tc mb56" style="max-width:520px;margin-left:auto;margin-right:auto;">
      <span class="eyebrow">How It Works</span>
      <div class="divider" style="margin:0 auto 24px;"></div>
      <h2 style="color:#0a1628;">Zero Risk. Zero CapEx. Revenue Up.</h2>
    </div>
    <div class="grid-3">
      <div style="text-align:center;padding:0 12px;">
        <div class="step-n mb20" style="margin:0 auto;">1</div>
        <h3 class="mb12">We Install Everything</h3>
        <p class="body">Full installation, setup, and staff onboarding at zero capital cost. We own the equipment. You own the upside.</p>
      </div>
      <div style="text-align:center;padding:0 12px;">
        <div class="step-n mb20" style="margin:0 auto;">2</div>
        <h3 class="mb12">Guests Engage</h3>
        <p class="body">Guests order, charge devices, store valuables, and relax. They stay longer — and spend more throughout the day.</p>
      </div>
      <div style="text-align:center;padding:0 12px;">
        <div class="step-n mb20" style="margin:0 auto;">3</div>
        <h3 class="mb12">Revenue Grows</h3>
        <p class="body">F&amp;B sales increase measurably. We share in the revenue generated — aligned incentives, zero financial risk to you.</p>
      </div>
    </div>
  </div>
</section>

<!-- ─── REAL INSTALLATIONS ─────────────────────────────────────────── -->
<section style="background:white;padding:80px 0;">
  <div class="wrap">
    <div class="tc mb40">
      <span class="eyebrow">Real Installations</span>
      <div class="divider" style="margin:0 auto 0;"></div>
    </div>
    <div class="photo-grid">
      <div class="photo-item"><img src="{HIL4}" alt="LounGenie at Hilton Waikoloa Kona Pool" loading="lazy"></div>
      <div class="photo-item"><img src="{GROVE7}" alt="LounGenie at The Grove Resort" loading="lazy"></div>
      <div class="photo-item"><img src="{HIL2}" alt="LounGenie Hilton Waikoloa cabana" loading="lazy"></div>
      <div class="photo-item"><img src="{COWA1}" alt="LounGenie at Cowabunga Canyon" loading="lazy"></div>
      <div class="photo-item"><img src="{GROVE1}" alt="The Grove Resort cabana" loading="lazy"></div>
      <div class="photo-item"><img src="{SEAWORLD}" alt="LounGenie at Sea World San Diego" loading="lazy"></div>
    </div>
    <div class="tc mt40">
      <a href="/Loungenie%E2%84%A2/cabana-installation-photos" class="btn btn-outline">View Full Gallery &rarr;</a>
    </div>
  </div>
</section>

<!-- ─── FINAL CTA ──────────────────────────────────────────────────── -->
<section class="sec" style="background:#050c1e;position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 70% 50%,rgba(0,119,182,.35) 0%,transparent 65%);pointer-events:none;"></div>
  <div class="wrap-xs tc" style="position:relative;z-index:1;">
    <div class="award mb40" style="display:inline-flex;">
      <span style="font-size:26px;">&#x1f3c6;</span>
      <div style="text-align:left;">
        <strong style="display:block;color:#92400e;font-size:14px;">IAAPA Brass Ring Award Winner</strong>
        <span style="font-size:12.5px;color:#a16207;">#1 Poolside Innovation Technology</span>
      </div>
    </div>
    <h2 style="color:white;margin-bottom:20px;">Ready to Grow Poolside Revenue?</h2>
    <p class="lead mt0 mb40" style="color:rgba(255,255,255,.68);max-width:480px;margin-left:auto;margin-right:auto;">No pressure, no commitment — just a straight conversation about your property and what's possible.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="/Loungenie%E2%84%A2/contact" class="btn btn-primary">&#x1f4c5;&nbsp; Schedule a Demo</a>
      <a href="/Loungenie%E2%84%A2/loungenie-videos" class="btn btn-ghost">Watch Videos &rarr;</a>
    </div>
  </div>
</section>

</div>"""


# ════════════════════════════════════════════════════════════════════════════
# FEATURES PAGE
# ════════════════════════════════════════════════════════════════════════════
def make_features():
    return SHARED_CSS + f"""
<div class="lg">

<section class="inn-hero">
  <div class="wrap-sm tc">
    <span class="eyebrow">The Platform</span>
    <div class="divider" style="margin:0 auto 24px;"></div>
    <h1 style="font-size:clamp(32px,4.5vw,56px);color:#0a1628;margin-bottom:20px;">Every Feature Is a<br>Revenue Driver</h1>
    <p class="lead" style="max-width:520px;margin:0 auto;">Each module solves a specific reason guests leave the pool early or skip ordering — directly converting those moments into F&amp;B revenue.</p>
  </div>
</section>

<section class="sec" style="background:white;">
  <div class="wrap">

    <!-- ORDER -->
    <div class="grid-2" style="margin-bottom:96px;">
      <div>
        <img src="{QR_ORDER}" alt="LounGenie QR poolside ordering" class="feat-img">
      </div>
      <div>
        <span class="pill pill-red mb20">The Problem</span>
        <h2 style="color:#0a1628;margin:16px 0 14px;font-size:clamp(22px,2.8vw,36px);">Guests Skip Ordering to<br>Keep Their Seat</h2>
        <p class="lead mb24">Walking to the bar means losing a premium lounge chair and leaving valuables unattended. That friction silently kills F&amp;B sales all day.</p>
        <div style="background:linear-gradient(135deg,#15803d,#16a34a);border-radius:8px;display:inline-block;padding:5px 16px;margin-bottom:20px;"><span style="color:white;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;">The Solution</span></div>
        <h3 style="margin-bottom:16px;color:#0a1628;">ORDER — Direct Poolside F&amp;B Ordering</h3>
        <ul class="checks">
          <li>Guests order from their lounge chair via QR code — no walking, no lost seats</li>
          <li>Properties see <strong>up to 30% increase in poolside F&amp;B sales</strong></li>
          <li>Integrates with your existing POS and kitchen ticket workflow</li>
          <li>Menu updates managed remotely — no hardware changes required</li>
        </ul>
      </div>
    </div>

    <!-- STASH -->
    <div class="grid-2" style="margin-bottom:96px;">
      <div>
        <span class="pill pill-red mb20">The Problem</span>
        <h2 style="color:#0a1628;margin:16px 0 14px;font-size:clamp(22px,2.8vw,36px);">Valuables Anxiety Sends<br>Guests Back Inside</h2>
        <p class="lead mb24">Guests regularly leave the pool to return phones, wallets, and keys to their room. Each trip risks them not returning — and losing an F&amp;B spend.</p>
        <div style="background:linear-gradient(135deg,#15803d,#16a34a);border-radius:8px;display:inline-block;padding:5px 16px;margin-bottom:20px;"><span style="color:white;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;">The Solution</span></div>
        <h3 style="margin-bottom:16px;color:#0a1628;">STASH — Smart Poolside Storage</h3>
        <ul class="checks">
          <li>Waterproof safe at every poolside unit — guests never leave to secure belongings</li>
          <li>Eliminates the anxiety that truncates pool visits</li>
          <li>Extended dwell time directly correlates to more F&amp;B orders</li>
          <li>Commercial-grade, tamper-resistant construction for any pool environment</li>
        </ul>
      </div>
      <div>
        <img src="{HIL4}" alt="LounGenie smart storage cabana at Hilton Waikoloa" class="feat-img">
      </div>
    </div>

    <!-- CHARGE -->
    <div class="grid-2" style="margin-bottom:96px;">
      <div>
        <img src="{FEAT5}" alt="LounGenie wireless charging station poolside" class="feat-img">
      </div>
      <div>
        <span class="pill pill-red mb20">The Problem</span>
        <h2 style="color:#0a1628;margin:16px 0 14px;font-size:clamp(22px,2.8vw,36px);">Dead Phone =<br>Guest Gone</h2>
        <p class="lead mb24">When a battery dies poolside, guests leave immediately to find a charger. They often head back to the room — and don't come back. Every departure is a missed sale.</p>
        <div style="background:linear-gradient(135deg,#15803d,#16a34a);border-radius:8px;display:inline-block;padding:5px 16px;margin-bottom:20px;"><span style="color:white;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;">The Solution</span></div>
        <h3 style="margin-bottom:16px;color:#0a1628;">CHARGE — Wireless Charging Stations</h3>
        <ul class="checks">
          <li>Guests stay poolside longer — phones stay charged throughout the day</li>
          <li>Removes the #1 reason for early pool exits</li>
          <li>More dwell time means more opportunities to order F&amp;B</li>
          <li>Discreet, weather-resistant design fits any cabana or daybed setup</li>
        </ul>
      </div>
    </div>

    <!-- CHILL -->
    <div class="grid-2">
      <div>
        <span class="pill pill-red mb20">The Problem</span>
        <h2 style="color:#0a1628;margin:16px 0 14px;font-size:clamp(22px,2.8vw,36px);">A Basic Pool Doesn't<br>Inspire Spending</h2>
        <p class="lead mb24">When the poolside experience feels ordinary, guests check in briefly and leave. A premium environment fundamentally changes guest behaviour and dwell time.</p>
        <div style="background:linear-gradient(135deg,#15803d,#16a34a);border-radius:8px;display:inline-block;padding:5px 16px;margin-bottom:20px;"><span style="color:white;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;">The Solution</span></div>
        <h3 style="margin-bottom:16px;color:#0a1628;">CHILL — Premium Comfort Amenities</h3>
        <ul class="checks">
          <li>Ice bucket, premium comfort items, and resort-quality presentation</li>
          <li>Creates the five-star atmosphere that keeps guests relaxing and ordering</li>
          <li>Differentiates your property from nearby competitors</li>
          <li>Complements and amplifies your F&amp;B program naturally</li>
        </ul>
      </div>
      <div>
        <img src="{GROVE5}" alt="LounGenie premium amenities at The Grove Resort" class="feat-img">
      </div>
    </div>

  </div>
</section>

<!-- NANO UNIT SHOWCASE -->
<section class="sec" style="background:#f8fafc;">
  <div class="wrap">
    <div class="grid-2" style="gap:72px;">
      <div style="border-radius:24px;overflow:hidden;box-shadow:0 24px 72px rgba(0,0,0,.16);">
        <img src="{NANO}" alt="LounGenie Nano Banana Pro smart poolside unit" style="width:100%;height:480px;object-fit:cover;display:block;">
      </div>
      <div>
        <span class="eyebrow">The Unit</span>
        <div class="divider"></div>
        <h2 style="color:#0a1628;margin-bottom:20px;">Built for Every<br>Poolside Environment</h2>
        <p class="body mb24">The LounGenie unit is a single, sleek commercial-grade piece that hosts all four modules — ORDER, STASH, CHARGE, and CHILL — in one weatherproof form factor.</p>
        <p class="body mb32">It fits free-standing cabanas, daybeds, clamshells, premium lounge seating, and any fixed poolside furniture. White-label finishes match any property's aesthetic.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:36px;">
          <div style="background:white;border-radius:14px;padding:18px;border:1px solid #e8ecf2;">
            <div style="font-size:22px;margin-bottom:8px;">&#x2614;</div>
            <strong style="display:block;color:#0a1628;font-size:14px;margin-bottom:4px;">Weatherproof</strong>
            <span class="muted">Designed for outdoor pool environments year-round</span>
          </div>
          <div style="background:white;border-radius:14px;padding:18px;border:1px solid #e8ecf2;">
            <div style="font-size:22px;margin-bottom:8px;">&#x1f527;</div>
            <strong style="display:block;color:#0a1628;font-size:14px;margin-bottom:4px;">We Install It</strong>
            <span class="muted">Full installation at zero cost to your property</span>
          </div>
          <div style="background:white;border-radius:14px;padding:18px;border:1px solid #e8ecf2;">
            <div style="font-size:22px;margin-bottom:8px;">&#x1f4bb;</div>
            <strong style="display:block;color:#0a1628;font-size:14px;margin-bottom:4px;">POS Integration</strong>
            <span class="muted">Works alongside your existing F&amp;B operation</span>
          </div>
          <div style="background:white;border-radius:14px;padding:18px;border:1px solid #e8ecf2;">
            <div style="font-size:22px;margin-bottom:8px;">&#x1f3a8;</div>
            <strong style="display:block;color:#0a1628;font-size:14px;margin-bottom:4px;">White Label</strong>
            <span class="muted">Custom finishes to match your property brand</span>
          </div>
        </div>
        <a href="/Loungenie%E2%84%A2/contact" class="btn btn-primary">Request a Demo &rarr;</a>
      </div>
    </div>
  </div>
</section>

<section class="stat-banner">
  <div class="wrap-xs tc">
    <span class="eyebrow mb16" style="color:#60c8ff;display:block;">Combined Result</span>
    <div class="big-stat mb12">Up to 30%</div>
    <p style="font-size:20px;font-weight:600;color:white;opacity:.88;margin-bottom:16px;">poolside F&amp;B revenue increase</p>
    <p style="color:rgba(255,255,255,.58);font-size:16px;max-width:420px;margin:0 auto 44px;line-height:1.75;">ORDER + STASH + CHARGE + CHILL working together. No statistics invented — this is the real result from real properties.</p>
    <a href="/Loungenie%E2%84%A2/contact" class="btn btn-white">Request a Demo &rarr;</a>
  </div>
</section>

</div>"""


# ════════════════════════════════════════════════════════════════════════════
# ABOUT PAGE
# ════════════════════════════════════════════════════════════════════════════
def make_about():
    return SHARED_CSS + f"""
<div class="lg">

<section style="position:relative;overflow:hidden;background:#050c1e;padding:84px 0 68px;">
  <div style="position:absolute;inset:0;background-image:url('{ABOUT_BG}');background-size:cover;background-position:center;opacity:.12;"></div>
  <div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(5,12,30,.9) 0%,rgba(0,42,90,.8) 100%);"></div>
  <div class="wrap" style="position:relative;z-index:1;">
    <div class="grid-2">
      <div>
        <span class="eyebrow" style="color:#60c8ff;">About LounGenie</span>
        <div class="divider"></div>
        <h1 style="color:white;font-size:clamp(32px,4.5vw,56px);margin-bottom:22px;">Turning Pool Decks Into Revenue Centers</h1>
        <p class="lead mb32" style="color:rgba(255,255,255,.75);">We build technology that transforms underutilised poolside areas into consistent, measurable F&amp;B revenue — with zero capital risk to your property.</p>
        <div class="award" style="display:inline-flex;">
          <span style="font-size:28px;">&#x1f3c6;</span>
          <div>
            <strong style="display:block;color:#92400e;font-size:14px;margin-bottom:3px;">IAAPA Brass Ring Award Winner</strong>
            <span style="font-size:12.5px;color:#a16207;">#1 Poolside Innovation Technology</span>
          </div>
        </div>
      </div>
      <div>
        <img src="{HIL3}" alt="LounGenie resort cabana installation" style="border-radius:24px;width:100%;height:420px;object-fit:cover;box-shadow:0 24px 72px rgba(0,0,0,.4);display:block;">
      </div>
    </div>
  </div>
</section>

<section class="sec" style="background:white;">
  <div class="wrap-sm tc">
    <span class="eyebrow">Our Mission</span>
    <div class="divider" style="margin:0 auto 24px;"></div>
    <h2 style="color:#0a1628;margin-bottom:20px;">We Exist to Make Every Pool Day More Profitable</h2>
    <p class="lead" style="max-width:640px;margin:0 auto;">Hospitality pool decks are the most under-monetised asset in the property. Guests want to stay longer — they just need the right environment. LounGenie creates that environment and captures the revenue it generates.</p>
  </div>
</section>

<section class="sec" style="background:#f8fafc;padding-top:0;padding-bottom:88px;">
  <div class="wrap">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;">
      <div class="card card-pad">
        <div class="icon-box ib-blue">&#x1f465;</div>
        <h3 class="mb12">Guest-First Design</h3>
        <p class="body">Every feature starts with a real guest frustration. Happy, comfortable guests stay longer and spend more — that's the foundation of the platform.</p>
      </div>
      <div class="card card-pad">
        <div class="icon-box ib-gold">&#x1f4b8;</div>
        <h3 class="mb12">Zero CapEx Model</h3>
        <p class="body">We take on the full investment. Your property gets installation, maintenance, and support — and the full revenue uplift — with zero financial risk.</p>
      </div>
      <div class="card card-pad">
        <div class="icon-box ib-green">&#x1f4bb;</div>
        <h3 class="mb12">Seamless Integration</h3>
        <p class="body">Works alongside your existing F&amp;B operation and POS system. No overhaul of your team, no new workflows, no disruption.</p>
      </div>
      <div class="card card-pad">
        <div class="icon-box ib-cyan">&#x1f4c8;</div>
        <h3 class="mb12">Measurable Results</h3>
        <p class="body">Up to 30% increase in poolside F&amp;B sales — consistently. Driven by longer guest dwell time and frictionless in-seat ordering.</p>
      </div>
    </div>
  </div>
</section>

<section class="sec" style="background:white;">
  <div class="wrap">
    <div class="grid-2">
      <div>
        <img src="{GROVE7}" alt="LounGenie at The Grove Resort" style="border-radius:24px;width:100%;height:460px;object-fit:cover;box-shadow:0 24px 72px rgba(0,0,0,.14);display:block;">
      </div>
      <div>
        <span class="eyebrow">The Platform</span>
        <div class="divider"></div>
        <h2 style="color:#0a1628;margin-bottom:20px;">Four Modules.<br>One Commercial Unit.<br>Measurable Uplift.</h2>
        <p class="body mb20">ORDER. STASH. CHARGE. CHILL. Every module addresses a real, specific reason guests leave pools early or skip F&amp;B spending.</p>
        <p class="body mb20">The platform is purpose-built for cabanas, daybeds, clamshells, and premium lounge seating at hotels, resorts, and water parks.</p>
        <p class="body mb36">We install everything. We maintain everything. Our model is pure revenue share — we only succeed when your property does.</p>
        <a href="/Loungenie%E2%84%A2/poolside-amenity-unit" class="btn btn-primary">Explore the Features &rarr;</a>
      </div>
    </div>
  </div>
</section>

<section class="stat-banner sec-sm">
  <div class="wrap-xs tc">
    <span class="eyebrow mb16" style="color:#60c8ff;display:block;">Let's Connect</span>
    <h2 style="color:white;margin-bottom:16px;">Ready to Learn More?</h2>
    <p style="color:rgba(255,255,255,.65);font-size:18px;max-width:420px;margin:0 auto 36px;line-height:1.7;">See exactly how LounGenie can work for your property. No pressure, no commitment.</p>
    <a href="/Loungenie%E2%84%A2/contact" class="btn btn-white">Schedule a Conversation &rarr;</a>
  </div>
</section>

</div>"""


# ════════════════════════════════════════════════════════════════════════════
# CONTACT PAGE
# ════════════════════════════════════════════════════════════════════════════
def make_contact():
    return SHARED_CSS + f"""
<div class="lg">

<section class="inn-hero">
  <div class="wrap" style="max-width:720px;">
    <span class="eyebrow">Get In Touch</span>
    <div class="divider"></div>
    <h1 style="font-size:clamp(32px,4.5vw,56px);color:#0a1628;margin-bottom:20px;">Let's Talk About<br>Your Pool Deck</h1>
    <p class="lead">Find out how LounGenie can help your property increase poolside F&amp;B revenue by up to 30% — at zero capital cost to you.</p>
  </div>
</section>

<section class="sec" style="background:#f8fafc;">
  <div class="wrap">
    <div class="grid-2" style="align-items:start;gap:60px;">

      <!-- FORM -->
      <div class="card" style="padding:48px 42px;">
        <h2 style="color:#0a1628;font-size:24px;font-weight:800;margin-bottom:8px;">Request a Demo</h2>
        <p class="muted mb32">We'll respond within one business day.</p>
        <form action="https://formsubmit.co/info@poolsafe.com" method="POST">
          <input type="hidden" name="_captcha" value="false">
          <input type="hidden" name="_subject" value="New Demo Request - LounGenie">
          <input type="hidden" name="_next" value="https://loungenie.com/Loungenie%E2%84%A2/contact?sent=1">
          <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:8px;text-transform:uppercase;letter-spacing:.8px;">Name <span style="color:#e53e3e;">*</span></label>
            <input type="text" name="name" required placeholder="Your full name" style="display:block;width:100%;padding:13px 16px;border:1.5px solid #d1d9e0;border-radius:10px;font-size:15px;font-family:inherit;color:#1a2440;background:white;outline:none;transition:border-color .2s;" onfocus="this.style.borderColor='#0077B6'" onblur="this.style.borderColor='#d1d9e0'">
          </div>
          <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:8px;text-transform:uppercase;letter-spacing:.8px;">Work Email <span style="color:#e53e3e;">*</span></label>
            <input type="email" name="email" required placeholder="you@yourproperty.com" style="display:block;width:100%;padding:13px 16px;border:1.5px solid #d1d9e0;border-radius:10px;font-size:15px;font-family:inherit;color:#1a2440;background:white;outline:none;transition:border-color .2s;" onfocus="this.style.borderColor='#0077B6'" onblur="this.style.borderColor='#d1d9e0'">
          </div>
          <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:8px;text-transform:uppercase;letter-spacing:.8px;">Property / Company <span style="color:#e53e3e;">*</span></label>
            <input type="text" name="property" required placeholder="Hotel or property name" style="display:block;width:100%;padding:13px 16px;border:1.5px solid #d1d9e0;border-radius:10px;font-size:15px;font-family:inherit;color:#1a2440;background:white;outline:none;transition:border-color .2s;" onfocus="this.style.borderColor='#0077B6'" onblur="this.style.borderColor='#d1d9e0'">
          </div>
          <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:8px;text-transform:uppercase;letter-spacing:.8px;">Phone (optional)</label>
            <input type="tel" name="phone" placeholder="Best number to call" style="display:block;width:100%;padding:13px 16px;border:1.5px solid #d1d9e0;border-radius:10px;font-size:15px;font-family:inherit;color:#1a2440;background:white;outline:none;transition:border-color .2s;" onfocus="this.style.borderColor='#0077B6'" onblur="this.style.borderColor='#d1d9e0'">
          </div>
          <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:8px;text-transform:uppercase;letter-spacing:.8px;">Number of Pool Locations</label>
            <select name="locations" style="display:block;width:100%;padding:13px 16px;border:1.5px solid #d1d9e0;border-radius:10px;font-size:15px;font-family:inherit;color:#1a2440;background:white;outline:none;">
              <option value="">Select...</option>
              <option>1–5 locations</option>
              <option>6–15 locations</option>
              <option>16–50 locations</option>
              <option>50+ locations</option>
            </select>
          </div>
          <div style="margin-bottom:32px;">
            <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:8px;text-transform:uppercase;letter-spacing:.8px;">Anything else? (optional)</label>
            <textarea name="message" rows="4" placeholder="Your property, timeline, questions..." style="display:block;width:100%;padding:13px 16px;border:1.5px solid #d1d9e0;border-radius:10px;font-size:15px;font-family:inherit;color:#1a2440;background:white;resize:vertical;outline:none;transition:border-color .2s;" onfocus="this.style.borderColor='#0077B6'" onblur="this.style.borderColor='#d1d9e0'"></textarea>
          </div>
          <button type="submit" style="display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:18px;background:linear-gradient(135deg,#0055a5,#0077B6,#00a0e3);color:white;border:none;border-radius:12px;font-size:16px;font-weight:800;font-family:inherit;cursor:pointer;box-shadow:0 6px 24px rgba(0,85,165,.35);transition:transform .2s, box-shadow .2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 12px 36px rgba(0,85,165,.45)'" onmouseout="this.style.transform='';this.style.boxShadow='0 6px 24px rgba(0,85,165,.35)'">
            &#x1f4e8;&nbsp; Send Request
          </button>
        </form>
      </div>

      <!-- VALUE PANEL -->
      <div>
        <div style="background:linear-gradient(135deg,#050c1e,#002a5a,#004f99);border-radius:22px;padding:42px 38px;margin-bottom:24px;color:white;box-shadow:0 20px 60px rgba(0,40,100,.3);">
          <div style="font-size:40px;margin-bottom:16px;">&#x1f3c6;</div>
          <h3 style="font-size:22px;font-weight:800;color:white;margin-bottom:10px;">IAAPA Brass Ring Award</h3>
          <p style="color:rgba(255,255,255,.7);font-size:15.5px;line-height:1.65;">Recognised as the #1 Poolside Innovation Technology in the global hospitality industry.</p>
        </div>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <div class="card card-pad-sm" style="display:flex;gap:14px;align-items:flex-start;">
            <div style="flex-shrink:0;width:42px;height:42px;" class="icon-box ib-blue">&#x1f4b0;</div>
            <div><strong style="display:block;color:#0a1628;font-size:15px;margin-bottom:4px;">Zero CapEx Required</strong><span class="muted">Full installation at zero upfront cost. Revenue share model only.</span></div>
          </div>
          <div class="card card-pad-sm" style="display:flex;gap:14px;align-items:flex-start;">
            <div style="flex-shrink:0;width:42px;height:42px;" class="icon-box ib-green">&#x1f4c8;</div>
            <div><strong style="display:block;color:#0a1628;font-size:15px;margin-bottom:4px;">Up to 30% F&amp;B Revenue Uplift</strong><span class="muted">The only stat we publish — because it's real and verified.</span></div>
          </div>
          <div class="card card-pad-sm" style="display:flex;gap:14px;align-items:flex-start;">
            <div style="flex-shrink:0;width:42px;height:42px;" class="icon-box ib-gold">&#x26a1;</div>
            <div><strong style="display:block;color:#0a1628;font-size:15px;margin-bottom:4px;">Fast, Seamless Deployment</strong><span class="muted">Works alongside your existing operation — zero disruption to your team.</span></div>
          </div>
        </div>
        <div style="margin-top:20px;padding:22px;border-radius:14px;background:linear-gradient(135deg,#f0f6fc,#fafcff);border:1px solid #e2e8f0;text-align:center;">
          <p class="muted mb12">Prefer email?</p>
          <a href="mailto:info@poolsafe.com" style="color:#0077B6;font-weight:800;font-size:17px;">info@poolsafe.com</a>
        </div>
      </div>

    </div>
  </div>
</section>

</div>"""


# ════════════════════════════════════════════════════════════════════════════
# VIDEOS PAGE
# ════════════════════════════════════════════════════════════════════════════
def video_card(vid_id, thumb, title, desc, featured=False):
    span = 'grid-column:1/-1;' if featured else ''
    return f"""<div class="vid-card" style="{span}">
  <div class="vid-thumb" onclick="var f=document.createElement('iframe');f.src='https://www.youtube.com/embed/{vid_id}?autoplay=1&rel=0';f.allow='accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;fullscreen';f.allowFullscreen=true;f.style.cssText='width:100%;height:100%;border:0;position:absolute;top:0;left:0;';this.style.paddingBottom='0';this.innerHTML='';this.appendChild(f);" title="Play {title}" style="{'aspect-ratio:21/9;' if featured else 'aspect-ratio:16/9;'}position:relative;">
    <img src="{thumb}" alt="{title}" loading="lazy" style="position:{'absolute' if featured else 'relative'};{'inset:0;' if featured else ''}width:100%;height:100%;object-fit:cover;display:block;">
    <div class="vid-play"><span>&#x25b6;</span></div>
  </div>
  <div class="vid-meta">
    <h3>{title}</h3>
    <p>{desc}</p>
  </div>
</div>"""

def make_videos():
    return SHARED_CSS + f"""
<div class="lg">

<section class="inn-hero">
  <div class="wrap-sm tc">
    <span class="eyebrow">See It In Action</span>
    <div class="divider" style="margin:0 auto 24px;"></div>
    <h1 style="font-size:clamp(32px,4.5vw,56px);color:#0a1628;margin-bottom:20px;">LounGenie in Action</h1>
    <p class="lead" style="max-width:540px;margin:0 auto;">Watch the platform at work — from flagship resorts to high-traffic water parks and premium poolside venues around the world.</p>
  </div>
</section>

<!-- FEATURED VIDEO -->
<section style="background:#050c1e;padding:80px 0;">
  <div class="wrap">
    <div class="tc mb32">
      <span class="eyebrow" style="color:#60c8ff;">Featured</span>
      <h2 style="color:white;margin-top:8px;margin-bottom:12px;">ORDER. STASH. CHARGE. CHILL.</h2>
      <p style="color:rgba(255,255,255,.6);font-size:17px;max-width:480px;margin:0 auto;line-height:1.65;">The full LounGenie 2.0 platform — poolside ordering, smart storage, wireless charging, and premium amenities in one commercial-grade unit.</p>
    </div>
    <div style="max-width:860px;margin:0 auto;border-radius:22px;overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,.55);">
      <div style="position:relative;aspect-ratio:16/9;cursor:pointer;background:#000;" onclick="var f=document.createElement('iframe');f.src='https://www.youtube.com/embed/EZ2CfBU30Ho?autoplay=1&rel=0';f.allow='accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;fullscreen';f.allowFullscreen=true;f.style.cssText='width:100%;height:100%;border:0;position:absolute;top:0;left:0;';this.innerHTML='';this.appendChild(f);" title="Play LounGenie Overview">
        <img src="{VS1}" alt="LounGenie smart cabana system — full platform overview" style="width:100%;height:100%;object-fit:cover;display:block;position:absolute;inset:0;" loading="lazy">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,.25);"></div>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">
          <div style="width:88px;height:88px;background:rgba(204,0,0,.92);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:34px;color:white;box-shadow:0 10px 36px rgba(0,0,0,.55);transition:transform .2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform=''">&#x25b6;</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- VIDEO GRID -->
<section class="sec" style="background:#f8fafc;">
  <div class="wrap">
    <div class="tc mb48" style="max-width:520px;margin-left:auto;margin-right:auto;">
      <span class="eyebrow">More Videos</span>
      <div class="divider" style="margin:0 auto 24px;"></div>
      <h2 style="color:#0a1628;">Real Properties. Real Results.</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:28px;">
      {video_card("bdVikQssTFc", VS2, "LounGenie 2.0 — Smarter Guest Ordering", "QR ordering built directly into the poolside experience")}
      {video_card("Pmvd2P8e1ew", VS5, "Built for Every Setting", "From resort cabanas to premium daybeds and clamshells")}
      {video_card("rPOsl_9R8dk", VS3, "Villatel Orlando Resort", "Smart cabana service in an upscale Orlando resort setting")}
      {video_card("M48NYM06JgY", VS4, "Orlando World Center Marriott", "ORDER, STASH, CHARGE and CHILL at a premier Marriott")}
      {video_card("PhV1JVo9POI", VS7, "The Grove Resort &amp; Waterpark", "Enhanced guest convenience and stronger F&amp;B performance")}
      {video_card("3Rjba7pWs_I", VS6, "Cowabunga Vegas", "High-traffic water park — fast service and maximum dwell time")}
    </div>
  </div>
</section>

<section style="background:white;padding:80px 0;">
  <div class="wrap-xs tc">
    <h2 style="color:#0a1628;margin-bottom:20px;">See LounGenie at Your Property</h2>
    <p class="lead mb40" style="max-width:480px;margin-left:auto;margin-right:auto;">Watch the videos — then talk to us about how LounGenie fits your specific pool environment and F&amp;B operation.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="/Loungenie%E2%84%A2/contact" class="btn btn-primary">&#x1f4c5;&nbsp; Request a Demo</a>
      <a href="/Loungenie%E2%84%A2/cabana-installation-photos" class="btn btn-outline">View Photo Gallery &rarr;</a>
    </div>
  </div>
</section>

</div>"""


# ════════════════════════════════════════════════════════════════════════════
# GALLERY PAGE
# ════════════════════════════════════════════════════════════════════════════
def gp(url, alt, cap=""):
    caption_html = f'<div class="cap">{cap}</div>' if cap else ''
    return f"""<div class="gal-item" style="aspect-ratio:16/10;">
  <img src="{url}" alt="{alt}" loading="lazy">
  {caption_html}
</div>"""

def make_gallery():
    return SHARED_CSS + f"""
<div class="lg">

<section class="inn-hero">
  <div class="wrap-sm tc">
    <span class="eyebrow">Installation Photos</span>
    <div class="divider" style="margin:0 auto 24px;"></div>
    <h1 style="font-size:clamp(32px,4.5vw,56px);color:#0a1628;margin-bottom:20px;">LounGenie in the Real World</h1>
    <p class="lead" style="max-width:560px;margin:0 auto;">See how leading hotels, resorts, and water parks deploy LounGenie to elevate the guest experience and drive measurable F&amp;B revenue.</p>
  </div>
</section>

<!-- NAV TABS -->
<div style="background:white;border-bottom:1px solid #e2e8f0;padding:18px 0;position:sticky;top:0;z-index:200;box-shadow:0 1px 16px rgba(0,0,0,.06);">
  <div class="wrap" style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
    <a href="#hotels" style="padding:9px 22px;border-radius:100px;border:2px solid #0077B6;color:#0077B6;font-size:14px;font-weight:700;text-decoration:none;background:white;transition:all .2s;" onmouseover="this.style.background='#0077B6';this.style.color='white'" onmouseout="this.style.background='white';this.style.color='#0077B6'">Hotels &amp; Resorts</a>
    <a href="#waterparks" style="padding:9px 22px;border-radius:100px;border:2px solid #e2e8f0;color:#4a5568;font-size:14px;font-weight:700;text-decoration:none;background:white;transition:all .2s;" onmouseover="this.style.background='#0077B6';this.style.color='white';this.style.borderColor='#0077B6'" onmouseout="this.style.background='white';this.style.color='#4a5568';this.style.borderColor='#e2e8f0'">Water Parks</a>
  </div>
</div>

<!-- HOTELS & RESORTS -->
<section class="sec" style="background:#f8fafc;" id="hotels">
  <div class="wrap">
    <div style="margin-bottom:48px;">
      <span class="eyebrow">Hotels &amp; Resorts</span>
      <div class="divider"></div>
      <h2 style="color:#0a1628;margin-bottom:14px;">Premium Hotel &amp; Resort Installations</h2>
      <p class="body" style="max-width:580px;">From beachfront cabanas to pool clamshells, LounGenie integrates seamlessly into any premium poolside environment.</p>
    </div>

    <!-- Hilton Waikoloa -->
    <div style="margin-bottom:64px;">
      <h3 style="color:#0a1628;margin-bottom:20px;font-size:19px;border-left:3px solid #0077B6;padding-left:14px;">Hilton Waikoloa Village</h3>
      <div class="gal-grid">
        {gp(HIL1, "Hilton Waikoloa Village Aloha Falls Cabana with LounGenie", "Aloha Falls Cabana")}
        {gp(HIL2, "Hilton Waikoloa cabana with LounGenie smart storage", "Cabana with smart storage")}
        {gp(HIL3, "Hilton Waikoloa daybed area with LounGenie unit", "Daybed area installation")}
        {gp(HIL4, "Hilton Waikoloa Kona Pool Cabanas with LounGenie", "Kona Pool Cabanas")}
        {gp(HIL5, "Hilton Waikoloa poolside cabana", "Poolside setup")}
      </div>
    </div>

    <!-- The Grove -->
    <div style="margin-bottom:64px;">
      <h3 style="color:#0a1628;margin-bottom:20px;font-size:19px;border-left:3px solid #0077B6;padding-left:14px;">The Grove Resort &amp; Waterpark</h3>
      <div class="gal-grid">
        {gp(GROVE7, "The Grove Resort cabana with LounGenie unit", "Cabana installation")}
        {gp(GROVE1, "The Grove pool deck cabana", "Pool deck setup")}
        {gp(GROVE5, "Pool deck setup at The Grove Resort", "Resort cabana")}
        {gp(GROVE6, "The Grove Resort poolside guest amenities", "Guest amenities")}
      </div>
    </div>

    <!-- Other Hotels -->
    <div>
      <h3 style="color:#0a1628;margin-bottom:20px;font-size:19px;border-left:3px solid #0077B6;padding-left:14px;">More Hotel &amp; Resort Installations</h3>
      <div class="gal-grid">
        {gp(SEAWORLD, "Sea World San Diego hotel cabana with LounGenie", "Sea World San Diego")}
        {gp(WESTIN, "Westin Hilton Head cabana with LounGenie", "Westin Hilton Head")}
        {gp(CONTACT, "VOR Resort cabana with LounGenie", "VOR Resort Cabana")}
        {gp(SOAKY, "Soaky Mountain premium seating with LounGenie", "Soaky Mountain")}
        {gp(MASSA, "Massanutten premium cabana with LounGenie", "Massanutten")}
        {gp(TYPHOON, "Typhoon Texas cabana with LounGenie", "Typhoon Texas")}
      </div>
    </div>
  </div>
</section>

<!-- WATER PARKS -->
<section class="sec" style="background:white;" id="waterparks">
  <div class="wrap">
    <div style="margin-bottom:48px;">
      <span class="eyebrow">Water Parks</span>
      <div class="divider"></div>
      <h2 style="color:#0a1628;margin-bottom:14px;">Water Park Installations</h2>
      <p class="body" style="max-width:560px;">High-traffic environments — fast service, maximum dwell time, and premium cabana value at scale.</p>
    </div>
    <div class="gal-grid">
      {gp(COWA1, "Cowabunga Canyon cabana with LounGenie smart unit", "Cowabunga Canyon")}
      {gp(COWA2, "Cowabunga Canyon cabana interior with LounGenie", "Interior setup")}
      {gp(COWA3, "Cowabunga Bay cabana with LounGenie", "Cowabunga Bay")}
      {gp(COWA4, "Cowabunga Canyon poolside cabana", "Poolside cabana")}
    </div>
  </div>
</section>

<!-- CTA -->
<section style="background:#050c1e;padding:80px 0;">
  <div class="wrap-xs tc">
    <h2 style="color:white;margin-bottom:18px;">Ready to See LounGenie at Your Property?</h2>
    <p style="color:rgba(255,255,255,.62);font-size:17px;max-width:420px;margin:0 auto 36px;line-height:1.7;">Join the hotels, resorts, and water parks already driving more poolside revenue with LounGenie.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="/Loungenie%E2%84%A2/contact" class="btn btn-primary">&#x1f4c5;&nbsp; Request a Demo</a>
      <a href="/Loungenie%E2%84%A2/loungenie-videos" class="btn btn-ghost">Watch Videos &rarr;</a>
    </div>
  </div>
</section>

</div>"""


# ════════════════════════════════════════════════════════════════════════════
# HELPERS
# ════════════════════════════════════════════════════════════════════════════
def update_page(page_id, title, html, template="page-wide"):
    wrapped = "<!-- wp:html -->\n" + html + "\n<!-- /wp:html -->"
    payload = json.dumps({
        "title": title,
        "content": wrapped,
        "status": "publish",
        "template": template
    }).encode()
    req = urllib.request.Request(
        f"{PAGES}/{page_id}", data=payload, method="POST",
        headers={**HEADERS, "Content-Length": str(len(payload))}
    )
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            data = json.loads(r.read())
            return True, data.get("link", "")
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}: {e.read().decode()[:400]}"
    except Exception as e:
        return False, str(e)

def fix_menu(item_id, new_title):
    payload = json.dumps({"title": new_title}).encode()
    req = urllib.request.Request(
        f"{MENUS}/{item_id}", data=payload, method="POST",
        headers={**HEADERS, "Content-Length": str(len(payload))}
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as r:
            return True
    except Exception:
        return False


# ════════════════════════════════════════════════════════════════════════════
# MAIN
# ════════════════════════════════════════════════════════════════════════════
print("=" * 64)
print("LounGenie Professional Redesign v3  |  TT4 Full-Width")
print("=" * 64)

print("\n[1/3] Fixing nav menu labels...")
for item_id, label in [(5150,"Home"),(3870,"Features"),(5161,"About"),(5930,"Contact")]:
    ok = fix_menu(item_id, label)
    print(f"  {'✓' if ok else '✗'} Menu {item_id} → '{label}'")

print("\n[2/3] Publishing pages (template: page-wide)...")
pages = [
    (4701, "Home | LounGenie Poolside Revenue Platform",       make_home()),
    (2989, "Features | LounGenie Poolside Revenue Platform",    make_features()),
    (4862, "About | LounGenie & Pool Safe Enterprise",          make_about()),
    (5139, "Contact | LounGenie",                               make_contact()),
    (5285, "Videos | LounGenie in Action",                      make_videos()),
    (5223, "Gallery | LounGenie Installation Photos",           make_gallery()),
]
for pid, title, html in pages:
    ok, result = update_page(pid, title, html)
    name = title.split("|")[0].strip()
    print(f"  {'✓' if ok else '✗ FAILED'} {name:18s} (ID {pid})  {result if ok else result[:120]}")

print("\n[3/3] Done.")
print("""
Changes in v3:
  • Theme: TwentyTwentyFour (block theme, FSE)
  • Template: page-wide on all 6 pages (full viewport width, no sidebar)
  • TT4 CSS escape: negative margins break out of has-global-padding
  • Redesigned hero: 88vh, animated gradient text shimmer, card float
  • Contact form: real HTML form with formsubmit.co action
  • Sticky gallery nav tabs
  • Richer card designs, better spacing throughout
  • All Inter font, all real WP media images
""")
