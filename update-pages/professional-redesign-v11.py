import base64
import json
import urllib.error
import urllib.request

AUTH = base64.b64encode(b"copilot:7NiL OZ17 ApP3 tIgF 6zlT ug7u").decode()
BASE = "https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2"
HEADERS = {"Authorization": f"Basic {AUTH}", "Content-Type": "application/json", "User-Agent": "Mozilla/5.0"}
PAGES = BASE + "/pages"
PARTS = BASE + "/template-parts"
NAVS = BASE + "/navigation"
TEMPLATES = BASE + "/templates"

ROOT = "https://loungenie.com/Loungenie%E2%84%A2"
UP = ROOT + "/wp-content/uploads/2026/03/"
UP25 = ROOT + "/wp-content/uploads/2025/10/"

IMG = {
    "logo": UP25 + "cropped-cropped-LounGenie-Logo.png",
    "hero": UP + "Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-4-scaled.jpg",
    "hero2": UP + "Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-2-scaled.jpg",
    "hero3": UP + "Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-3-scaled.jpg",
    "hero4": UP + "Hilton-Wakoloa-Village-2018-10-Aloha-Falls-Cabana-1-scaled.jpg",
    "grove": UP + "The-Grove-7-scaled.jpg",
    "grove2": UP + "The-Grove-5.jpg",
    "sea": UP + "Sea-World-San-Diego.jpg",
    "contact": UP + "3-VOR-cabana-e1773774348955.jpg",
    "park1": UP + "IMG_3241-scaled-1.jpg",
    "park2": UP + "IMG_3239-scaled-1.jpg",
    "park3": UP + "IMG_3235-scaled-1.jpg",
    "park4": UP + "IMG_3233-scaled-1.jpg",
    "marg": UP + "margaritaville-jimmy-buffetts-logo-png-transparent.png",
    "ritz": UP + "Ritz-Carlton-Logo-1965.webp",
    "niagara": UP + "logo-NiagaraFalls_02.png",
    "marriott": ROOT + "/wp-content/uploads/2026/02/Marriott_hotels_logo14.svg_.png",
    "partner1": ROOT + "/wp-content/uploads/2025/10/Hilton-Emblem-300x169.png",
    "partner2": ROOT + "/wp-content/uploads/2025/10/Westin_Hotels__Resorts_logo.svg-300x95.png",
}

GLOBAL_STYLE = """
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --lg-bg: #f2f7fb;
  --lg-surface: #ffffff;
    --lg-ink: #0b1726;
    --lg-ink-soft: #44566b;
  --lg-line: #dbe6ef;
    --lg-blue: #004b93;
    --lg-cyan: #00a8dd;
    --lg-navy: #07111d;
    --lg-midnight: #0a1d33;
}
html, body { font-family: 'Manrope', sans-serif !important; }
h1, h2, h3, h4, .lg9-title, .lg9-title-md { font-family: 'Space Grotesk', 'Manrope', sans-serif !important; }
.page-id-4701 .wp-block-post-title,
.page-id-2989 .wp-block-post-title,
.page-id-4862 .wp-block-post-title,
.page-id-5139 .wp-block-post-title,
.page-id-5285 .wp-block-post-title,
.page-id-5223 .wp-block-post-title,
.page-id-5668 .wp-block-post-title,
.page-id-5651 .wp-block-post-title,
.page-id-5686 .wp-block-post-title,
.page-id-5716 .wp-block-post-title,
.page-id-4701 .entry-title,
.page-id-2989 .entry-title,
.page-id-4862 .entry-title,
.page-id-5139 .entry-title,
.page-id-5285 .entry-title,
.page-id-5223 .entry-title,
.page-id-5668 .entry-title,
.page-id-5651 .entry-title,
.page-id-5686 .entry-title,
.page-id-5716 .entry-title { display: none !important; }
.lg9 { color: var(--lg-ink); overflow-x: hidden; }
.lg9 * { box-sizing: border-box; }
.lg9 a { text-decoration: none; }
.lg9 img { display: block; max-width: 100%; }
.lg9-shell { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
.lg9-narrow { max-width: 900px; margin: 0 auto; padding: 0 24px; }
.lg9-kicker { text-transform: uppercase; letter-spacing: 1.9px; font-size: 11px; font-weight: 800; color: var(--lg-cyan); }
.lg9-title { font-size: clamp(2.2rem, 5.6vw, 5.2rem); line-height: 1.02; letter-spacing: -1.8px; font-weight: 800; margin: 0; }
.lg9-title-md { font-size: clamp(1.9rem, 3.9vw, 3.3rem); line-height: 1.08; letter-spacing: -1px; font-weight: 800; margin: 0; }
.lg9-copy { color: var(--lg-ink-soft); line-height: 1.8; font-size: 1.04rem; }
.lg9-btns { display: flex; gap: 14px; flex-wrap: wrap; }
.lg9-btn-primary, .lg9-btn-secondary { display:inline-flex; align-items:center; justify-content:center; padding:14px 22px; border-radius:12px; font-weight:800; font-size:15px; transition: transform .22s ease, box-shadow .22s ease; }
.lg9-btn-primary { background: linear-gradient(135deg, var(--lg-blue), var(--lg-cyan)); color:#fff; box-shadow: 0 14px 34px rgba(0,75,147,.28); }
.lg9-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 18px 40px rgba(0,75,147,.36); }
.lg9-btn-secondary { border:1px solid rgba(255,255,255,.32); color:#fff; background: rgba(255,255,255,.08); }
.lg9-card { background: var(--lg-surface); border:1px solid var(--lg-line); border-radius: 20px; box-shadow: 0 18px 44px rgba(13,27,42,.08); }
.lg9-media { overflow:hidden; border-radius: 20px; box-shadow: 0 22px 52px rgba(13,27,42,.14); }
.lg9-media img { width:100%; height:100%; object-fit:cover; }
.lg9-grid-2 { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 28px; }
.lg9-grid-3 { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; }
.lg9-logo-strip { display:grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 22px; align-items:center; }
.lg9-logo-strip img { max-height: 54px; width: auto; margin: 0 auto; filter: grayscale(100%) opacity(.75); }
.lg9-hero { position: relative; min-height: 84vh; display:flex; align-items:center; background:#09131f; }
.lg9-hero-bg { position:absolute; inset:0; }
.lg9-hero-bg img { width:100%; height:100%; object-fit:cover; opacity:.35; }
.lg9-hero-overlay { position:absolute; inset:0; background: linear-gradient(112deg, rgba(9,19,31,.94) 0%, rgba(14,34,56,.88) 52%, rgba(0,85,165,.48) 100%); }
.lg9-hero-inner { position:relative; z-index:2; width:100%; padding: 96px 0 88px; }
.lg9-stat-panel { background: rgba(255,255,255,.94); border:1px solid rgba(255,255,255,.4); border-radius: 24px; padding: 20px; backdrop-filter: blur(10px); }
.lg9-stat-big { font-size: clamp(2.6rem, 6vw, 4.8rem); line-height: 1; font-weight: 900; color: var(--lg-ink); }
.lg9-section { padding: 82px 0; }
.lg9-section-soft { padding: 82px 0; background: var(--lg-bg); }
.lg9-step { display:flex; gap: 14px; align-items:flex-start; }
.lg9-step-num { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg,var(--lg-blue),var(--lg-cyan)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; flex-shrink:0; }
.lg9-pill { display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; background:#e8f4ff; color: var(--lg-blue); font-size:12px; font-weight:800; }
.lg9-video-grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 22px; }
.lg9-video-card { background:#fff; border:1px solid var(--lg-line); border-radius:18px; overflow:hidden; box-shadow:0 16px 40px rgba(13,27,42,.08); }
.lg9-frame { aspect-ratio: 16 / 9; background:#09131f; }
.lg9-frame iframe { width:100%; height:100%; border:0; display:block; }
.lg9-gallery { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:18px; }
.lg9-gallery .lg9-media { aspect-ratio: 16 / 12; }
.lg9-topbar { background: linear-gradient(90deg, #061120, #113054); color: rgba(255,255,255,.85); font-size: 12px; letter-spacing: .3px; }
.lg9-topbar-inner { max-width:1280px; margin:0 auto; padding:8px 24px; display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; }
.wp-site-blocks > header.wp-block-template-part { position: sticky; top:0; z-index: 990; background: rgba(255,255,255,.94); border-bottom:1px solid #dce8f2; backdrop-filter: blur(13px); }
.wp-site-blocks > header .lg9-head { max-width:1280px; margin:0 auto; padding:12px 24px; display:flex; align-items:center; justify-content:space-between; gap:24px; }
.wp-site-blocks > header .lg9-head img { width: 236px; height:auto; image-rendering:-webkit-optimize-contrast; }
.wp-site-blocks > header .lg9-head-nav { flex:1 1 auto; display:flex; justify-content:flex-end; align-items:center; gap:18px; }
.wp-site-blocks > header .wp-block-navigation { margin:0; }
.wp-site-blocks > header .wp-block-navigation-item__content { padding:8px 10px; border-radius:8px; font-size:14px !important; font-weight:700 !important; color:var(--lg-ink) !important; transition: all .2s ease; }
.wp-site-blocks > header .wp-block-navigation-item__content:hover { background:#edf6ff; color:var(--lg-blue) !important; }
.wp-site-blocks > header .wp-block-navigation-submenu__toggle { color:var(--lg-ink) !important; }
.wp-site-blocks > footer .lg9-footer { background: radial-gradient(130% 160% at 0% 0%, #133b62 0%, #081624 52%, #050e1a 100%); color:rgba(255,255,255,.76); padding: 62px 24px 28px; }
.wp-site-blocks > footer .lg9-footer-grid { max-width:1280px; margin:0 auto; display:grid; grid-template-columns:1.3fr 1fr 1fr 1fr; gap:28px; }
.wp-site-blocks > footer .lg9-footer h3 { color:#fff; font-size:14px; text-transform:uppercase; letter-spacing:1.3px; margin-bottom:12px; }
.wp-site-blocks > footer .lg9-footer ul { list-style:none; padding:0; margin:0; }
.wp-site-blocks > footer .lg9-footer li, .wp-site-blocks > footer .lg9-footer p { margin:0; font-size:14px; line-height:1.8; }
.wp-site-blocks > footer .lg9-footer a { color:rgba(255,255,255,.88); }
.wp-site-blocks > footer .lg9-footer-base { max-width:1280px; margin:24px auto 0; padding-top:16px; border-top:1px solid rgba(255,255,255,.12); font-size:13px; color:rgba(255,255,255,.56); }
.lg9-footer-chip-wrap { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
.lg9-footer-chip { padding:7px 10px; border-radius:999px; border:1px solid rgba(255,255,255,.2); color:rgba(255,255,255,.88); font-size:12px; }
.ir-rich img { width:min(280px,100%); height:auto; border-radius:16px; }
.page-id-5668 .wp-block-post-content,
.page-id-5651 .wp-block-post-content,
.page-id-5686 .wp-block-post-content,
.page-id-5716 .wp-block-post-content { max-width: 1160px; margin: 0 auto; padding: 48px 24px 82px; }
.page-id-5668 .wp-block-post-content h1,
.page-id-5651 .wp-block-post-content h1,
.page-id-5686 .wp-block-post-content h1,
.page-id-5716 .wp-block-post-content h1,
.page-id-5668 .wp-block-post-content h2,
.page-id-5651 .wp-block-post-content h2,
.page-id-5686 .wp-block-post-content h2,
.page-id-5716 .wp-block-post-content h2,
.page-id-5668 .wp-block-post-content h3,
.page-id-5651 .wp-block-post-content h3,
.page-id-5686 .wp-block-post-content h3,
.page-id-5716 .wp-block-post-content h3 { color: var(--lg-ink); line-height: 1.15; letter-spacing: -.3px; }
.page-id-5668 .wp-block-post-content p,
.page-id-5651 .wp-block-post-content p,
.page-id-5686 .wp-block-post-content p,
.page-id-5716 .wp-block-post-content p,
.page-id-5668 .wp-block-post-content li,
.page-id-5651 .wp-block-post-content li,
.page-id-5686 .wp-block-post-content li,
.page-id-5716 .wp-block-post-content li { color: var(--lg-ink-soft); font-size: 16px; line-height: 1.75; }
.page-id-5668 .wp-block-post-content a,
.page-id-5651 .wp-block-post-content a,
.page-id-5686 .wp-block-post-content a,
.page-id-5716 .wp-block-post-content a { color: var(--lg-blue); font-weight: 700; }
.page-id-5668 .wp-block-post-content table,
.page-id-5651 .wp-block-post-content table,
.page-id-5686 .wp-block-post-content table,
.page-id-5716 .wp-block-post-content table { width: 100%; border-collapse: collapse; background: #fff; border:1px solid var(--lg-line); border-radius: 12px; overflow: hidden; }
.page-id-5668 .wp-block-post-content td,
.page-id-5651 .wp-block-post-content td,
.page-id-5686 .wp-block-post-content td,
.page-id-5716 .wp-block-post-content td,
.page-id-5668 .wp-block-post-content th,
.page-id-5651 .wp-block-post-content th,
.page-id-5686 .wp-block-post-content th,
.page-id-5716 .wp-block-post-content th { border-bottom: 1px solid var(--lg-line); padding: 12px 14px; text-align: left; }
@media (max-width: 1024px) {
  .lg9-logo-strip { grid-template-columns: repeat(3, minmax(0,1fr)); }
}
@media (max-width: 900px) {
  .lg9-grid-2, .lg9-grid-3, .lg9-video-grid, .lg9-gallery, .wp-site-blocks > footer .lg9-footer-grid { grid-template-columns:1fr; }
  .lg9-hero-inner .lg9-grid-2 { grid-template-columns:1fr; }
}
@media (max-width: 780px) {
  .lg9-shell, .lg9-narrow, .wp-site-blocks > header .lg9-head { padding-left:16px; padding-right:16px; }
  .wp-site-blocks > header .lg9-head { flex-wrap:wrap; }
    .wp-site-blocks > header .lg9-head img { width: 190px; }
    .lg9-topbar-inner { padding-left:16px; padding-right:16px; }
}
</style>
"""

HEADER_TEMPLATE = (
    "<!-- wp:html -->" + GLOBAL_STYLE + "<!-- /wp:html -->"
    + "<!-- wp:html --><div class=\"lg9-topbar\"><div class=\"lg9-topbar-inner\"><span>Premium Smart Cabana Platform for Hotels, Resorts, and Waterparks</span><span><a href=\"mailto:info@poolsafeinc.com\" style=\"color:#b8e5ff;\">info@poolsafeinc.com</a></span></div></div><div class=\"lg9-head\"><div><a href=\"" + ROOT + "/\"><img src=\"" + IMG["logo"] + "\" alt=\"LounGenie\"></a></div><div class=\"lg9-head-nav\"><!-- /wp:html -->"
    + "<!-- wp:navigation {\"ref\":4,\"overlayMenu\":\"mobile\",\"layout\":{\"type\":\"flex\",\"justifyContent\":\"right\",\"orientation\":\"horizontal\"}} /-->"
    + "<!-- wp:html --><a href=\"" + ROOT + "/index.php/contact-loungenie/\" class=\"lg9-btn-primary\" style=\"padding:11px 16px;font-size:14px;\">Request Demo</a></div></div><!-- /wp:html -->"
)

FOOTER_TEMPLATE = (
    "<!-- wp:html --><footer class=\"lg9-footer\"><div class=\"lg9-footer-grid\">"
    + "<div><a href=\"" + ROOT + "/\"><img src=\"" + IMG["logo"] + "\" alt=\"LounGenie\" style=\"width:196px;height:auto;display:block;margin-bottom:14px\"></a><p>LounGenie is a modern poolside revenue platform for hotels, resorts, and water parks.</p><div class=\"lg9-footer-chip-wrap\"><span class=\"lg9-footer-chip\">Zero Upfront</span><span class=\"lg9-footer-chip\">Revenue Focused</span><span class=\"lg9-footer-chip\">Premium Guest UX</span></div></div>"
    + "<div><h3>Product</h3><ul><li><a href=\"" + ROOT + "/\">Home</a></li><li><a href=\"" + ROOT + "/index.php/poolside-amenity-unit/\">Features</a></li><li><a href=\"" + ROOT + "/index.php/cabana-installation-photos/\">Gallery</a></li><li><a href=\"" + ROOT + "/index.php/loungenie-videos/\">Videos</a></li></ul></div>"
    + "<div><h3>Company</h3><ul><li><a href=\"" + ROOT + "/index.php/hospitality-innovation/\">About</a></li><li><a href=\"" + ROOT + "/index.php/contact-loungenie/\">Contact</a></li><li><a href=\"mailto:info@poolsafeinc.com\">info@poolsafeinc.com</a></li><li><a href=\"tel:+14166302444\">+1 (416) 630-2444</a></li><li>906 Magnetic Drive, North York, ON M3J 2C4, Canada</li></ul><h3 style=\"margin-top:14px;\">Social</h3><ul><li><a href=\"https://www.instagram.com/poolsafeinc/\" target=\"_blank\" rel=\"noopener\">Instagram</a></li><li><a href=\"https://www.facebook.com/poolsafeinc\" target=\"_blank\" rel=\"noopener\">Facebook</a></li><li><a href=\"https://ca.linkedin.com/company/poolsafeinc\" target=\"_blank\" rel=\"noopener\">LinkedIn</a></li><li><a href=\"https://youtube.com/@poolsafeinc?si=r5Qb8P7rphTE83Ms\" target=\"_blank\" rel=\"noopener\">YouTube</a></li></ul></div>"
    + "<div><h3>Investors</h3><ul><li><a href=\"" + ROOT + "/index.php/investors/\">Investor Relations</a></li><li><a href=\"" + ROOT + "/index.php/board/\">Board</a></li><li><a href=\"" + ROOT + "/index.php/financials/\">Financials</a></li><li><a href=\"" + ROOT + "/index.php/press/\">Press</a></li></ul></div>"
    + "</div><div class=\"lg9-footer-base\">&copy; 2026 LounGenie / Pool Safe. All rights reserved.</div></footer><!-- /wp:html -->"
)

PAGE_WIDE_TEMPLATE = """<!-- wp:template-part {\"slug\":\"header\",\"area\":\"header\",\"tagName\":\"header\",\"theme\":\"twentytwentyfour\"} /-->
<!-- wp:group {\"tagName\":\"main\",\"align\":\"full\",\"layout\":{\"type\":\"default\"}} -->
<main class=\"wp-block-group alignfull\">
  <!-- wp:post-content {\"align\":\"full\",\"layout\":{\"type\":\"default\"}} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {\"slug\":\"footer\",\"area\":\"footer\",\"tagName\":\"footer\",\"theme\":\"twentytwentyfour\"} /-->"""

NAVIGATION_RAW = """<!-- wp:navigation-link {\"label\":\"Home\",\"type\":\"page\",\"id\":4701,\"url\":\"/Loungenie%E2%84%A2/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"Features\",\"type\":\"page\",\"id\":2989,\"url\":\"/Loungenie%E2%84%A2/index.php/poolside-amenity-unit/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"Gallery\",\"type\":\"page\",\"id\":5223,\"url\":\"/Loungenie%E2%84%A2/index.php/cabana-installation-photos/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"Videos\",\"type\":\"page\",\"id\":5285,\"url\":\"/Loungenie%E2%84%A2/index.php/loungenie-videos/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"About\",\"type\":\"page\",\"id\":4862,\"url\":\"/Loungenie%E2%84%A2/index.php/hospitality-innovation/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"Contact\",\"type\":\"page\",\"id\":5139,\"url\":\"/Loungenie%E2%84%A2/index.php/contact-loungenie/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-submenu {\"label\":\"Investors\",\"url\":\"/Loungenie%E2%84%A2/index.php/investors/\",\"kind\":\"custom\",\"isTopLevelItem\":true} -->
<ul class=\"wp-block-navigation-submenu\"><!-- wp:navigation-link {\"label\":\"Investor Relations\",\"type\":\"page\",\"id\":5668,\"url\":\"/Loungenie%E2%84%A2/index.php/investors/\",\"kind\":\"post-type\",\"isTopLevelLink\":false} /-->
<!-- wp:navigation-link {\"label\":\"Board\",\"type\":\"page\",\"id\":5651,\"url\":\"/Loungenie%E2%84%A2/index.php/board/\",\"kind\":\"post-type\",\"isTopLevelLink\":false} /-->
<!-- wp:navigation-link {\"label\":\"Financials\",\"type\":\"page\",\"id\":5686,\"url\":\"/Loungenie%E2%84%A2/index.php/financials/\",\"kind\":\"post-type\",\"isTopLevelLink\":false} /-->
<!-- wp:navigation-link {\"label\":\"Press\",\"type\":\"page\",\"id\":5716,\"url\":\"/Loungenie%E2%84%A2/index.php/press/\",\"kind\":\"post-type\",\"isTopLevelLink\":false} /--></ul>
<!-- /wp:navigation-submenu -->"""

HOME = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-hero"><div class="lg9-hero-bg"><img src="{IMG['hero']}" alt="LounGenie at a premium resort"></div><div class="lg9-hero-overlay"></div><div class="lg9-hero-inner"><div class="lg9-shell"><div class="lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker">Smart Cabana Revenue Platform</p><h1 class="lg9-title" style="color:#fff;margin:8px 0 18px;max-width:760px;">Turn every premium seat into a better guest experience and a stronger revenue engine.</h1><p style="color:rgba(255,255,255,.82);font-size:1.08rem;line-height:1.85;max-width:720px;margin:0 0 28px;">LounGenie combines ordering, storage, charging, and comfort into a single smart hospitality unit built for hotels, resorts, and water parks. It feels premium to guests and practical for operators.</p><div class="lg9-btns"><a class="lg9-btn-primary" href="{ROOT}/index.php/contact-loungenie/">Schedule a Demo</a><a class="lg9-btn-secondary" href="{ROOT}/index.php/poolside-amenity-unit/">Explore Features</a></div></div><div><div class="lg9-stat-panel"><div class="lg9-media" style="aspect-ratio:4/3;margin-bottom:16px;"><img src="{IMG['hero2']}" alt="LounGenie installed in a premium cabana"></div><div class="lg9-stat-big">Up to 30%</div><p style="margin:8px 0 0;color:#243447;font-weight:700;line-height:1.6;">Increase in poolside food and beverage revenue.</p></div></div></div></div></div></section><section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div style="text-align:center;max-width:860px;margin:0 auto 26px;"><p class="lg9-kicker">Trusted By Hospitality Leaders</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">A product that belongs in premium environments.</h2></div><div class="lg9-logo-strip"><img src="{IMG['marg']}" alt="Margaritaville"><img src="{IMG['ritz']}" alt="Ritz Carlton"><img src="{IMG['marriott']}" alt="Marriott"><img src="{IMG['partner1']}" alt="Hilton"><img src="{IMG['partner2']}" alt="Westin"><img src="{IMG['niagara']}" alt="Niagara Falls"></div></div></section><section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker">The Core Problem</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Pool decks lose revenue in the same predictable ways.</h2><p class="lg9-copy">Guests leave to charge phones. They skip orders because service is not immediate. They shorten their stay because valuables are exposed. Those are not aesthetic problems. They are revenue problems.</p><div style="display:grid;gap:14px;margin-top:22px;"><div class="lg9-card" style="padding:18px 20px;"><strong style="display:block;margin-bottom:6px;">Guests leave the deck</strong><span class="lg9-copy" style="font-size:15px;">Charging and storage friction reduces time spent poolside.</span></div><div class="lg9-card" style="padding:18px 20px;"><strong style="display:block;margin-bottom:6px;">Ordering is delayed</strong><span class="lg9-copy" style="font-size:15px;">If placing an order is inconvenient, the order often never happens.</span></div><div class="lg9-card" style="padding:18px 20px;"><strong style="display:block;margin-bottom:6px;">Premium seating underperforms</strong><span class="lg9-copy" style="font-size:15px;">The environment is valuable, but the revenue opportunity is under-activated.</span></div></div></div><div class="lg9-media" style="aspect-ratio:16/12;"><img src="{IMG['hero3']}" alt="Daybed area with LounGenie"></div></div></div></section><section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div style="max-width:760px;margin:0 auto 28px;text-align:center;"><p class="lg9-kicker">How It Works</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">One system. Four guest-facing advantages.</h2></div><div class="lg9-grid-3"><div class="lg9-card" style="padding:26px;"><p class="lg9-kicker" style="margin:0 0 8px;">Order</p><h3 style="font-size:1.35rem;margin:0 0 10px;">Capture demand at the seat.</h3><p class="lg9-copy">QR ordering and service interaction remove the lag between intent and purchase.</p></div><div class="lg9-card" style="padding:26px;"><p class="lg9-kicker" style="margin:0 0 8px;">Stash</p><h3 style="font-size:1.35rem;margin:0 0 10px;">Protect valuables poolside.</h3><p class="lg9-copy">Secure storage helps guests relax and stay on deck without worrying about their items.</p></div><div class="lg9-card" style="padding:26px;"><p class="lg9-kicker" style="margin:0 0 8px;">Charge</p><h3 style="font-size:1.35rem;margin:0 0 10px;">Reduce battery-driven departures.</h3><p class="lg9-copy">When devices stay powered, guests have fewer reasons to leave the experience.</p></div></div><div class="lg9-card" style="padding:26px;margin-top:22px;"><div class="lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker" style="margin:0 0 8px;">Chill</p><h3 style="font-size:1.7rem;margin:0 0 10px;">Support a true premium poolside atmosphere.</h3><p class="lg9-copy">Comfort features complete the experience and help premium seating feel genuinely elevated instead of merely reserved.</p></div><div class="lg9-media" style="aspect-ratio:16/10;"><img src="{IMG['grove2']}" alt="Premium poolside setup"></div></div></div></div></section><section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-grid-2" style="align-items:center;"><div class="lg9-media" style="aspect-ratio:16/11;"><img src="{IMG['hero4']}" alt="Guest journey with LounGenie"></div><div><p class="lg9-kicker">Guest Journey</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">What happens when a guest sits down?</h2><div style="display:grid;gap:18px;margin-top:18px;"><div class="lg9-step"><div class="lg9-step-num">1</div><div><strong style="display:block;margin-bottom:6px;">Secure belongings</strong><span class="lg9-copy" style="font-size:15px;">Valuables go into the waterproof safe.</span></div></div><div class="lg9-step"><div class="lg9-step-num">2</div><div><strong style="display:block;margin-bottom:6px;">Stay charged</strong><span class="lg9-copy" style="font-size:15px;">Phones and devices remain powered at the seat.</span></div></div><div class="lg9-step"><div class="lg9-step-num">3</div><div><strong style="display:block;margin-bottom:6px;">Order with less friction</strong><span class="lg9-copy" style="font-size:15px;">Food and beverage intent can be converted more easily.</span></div></div><div class="lg9-step"><div class="lg9-step-num">4</div><div><strong style="display:block;margin-bottom:6px;">Stay longer</strong><span class="lg9-copy" style="font-size:15px;">A better poolside experience creates more time and more opportunities to spend.</span></div></div></div></div></div></div></section><section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div style="display:flex;justify-content:space-between;gap:18px;align-items:end;flex-wrap:wrap;margin-bottom:24px;"><div><p class="lg9-kicker" style="margin:0 0 8px;">Installations</p><h2 class="lg9-title-md" style="margin:0;">Real photos from real poolside deployments.</h2></div><a href="{ROOT}/index.php/cabana-installation-photos/" style="color:var(--lg-blue);font-weight:800;">View gallery</a></div><div class="lg9-gallery"><div class="lg9-media"><img src="{IMG['hero']}" alt="Hilton Kona pool"></div><div class="lg9-media"><img src="{IMG['grove']}" alt="The Grove resort"></div><div class="lg9-media"><img src="{IMG['sea']}" alt="SeaWorld"></div></div></div></section><section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-card" style="padding:34px 32px;background:linear-gradient(135deg,#0d1b2a,#123559 55%,#0055a5);border:none;"><div class="lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker" style="color:#8ad8ff;margin:0 0 10px;">Commercial Model</p><h2 class="lg9-title-md" style="color:#fff;margin:0 0 14px;">We install it. We maintain it. You keep the upside.</h2><p style="color:rgba(255,255,255,.78);line-height:1.85;margin:0;">The rollout is designed to make adoption straightforward for hospitality operators who want a more modern guest product without unnecessary complexity.</p></div><div style="text-align:right;"><a class="lg9-btn-primary" href="{ROOT}/index.php/contact-loungenie/">Talk to LounGenie</a></div></div></div></div></section></div><!-- /wp:html -->"""

HOME_EXTRA = f"""
<section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div style="max-width:860px;margin:0 auto 24px;text-align:center;"><p class="lg9-kicker">One System. Every Venue Type.</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Built for hotels, resorts, waterparks, cruise lines, surf parks, country clubs, and municipal aquatic centers.</h2><p class="lg9-copy">From 10 cabanas to 500 premium seating areas, the modular platform scales while keeping the guest experience consistent and premium.</p></div><div class="lg9-grid-3"><div class="lg9-card" style="padding:24px;"><h3 style="margin:0 0 8px;font-size:1.2rem;">Hotels + Resorts</h3><p class="lg9-copy">Support premium daybeds, cabanas, and reserved lounge zones with less service friction.</p></div><div class="lg9-card" style="padding:24px;"><h3 style="margin:0 0 8px;font-size:1.2rem;">Waterparks + Attractions</h3><p class="lg9-copy">Handle high guest volume with better ordering flow and stronger seat-level convenience.</p></div><div class="lg9-card" style="padding:24px;"><h3 style="margin:0 0 8px;font-size:1.2rem;">Cruise + Specialty Venues</h3><p class="lg9-copy">Weather-rated units and modular deployment simplify premium outdoor operations in diverse environments.</p></div></div></div></section>
<section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-grid-2" style="align-items:start;"><div><p class="lg9-kicker">Why Properties Make More Money</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Two behavioral shifts drive stronger revenue performance.</h2><div class="lg9-card" style="padding:22px;margin-bottom:14px;"><h3 style="margin:0 0 8px;font-size:1.25rem;">Guests stay longer.</h3><p class="lg9-copy">When valuables are secure and devices stay charged, guests stop leaving the deck and ordering windows increase.</p></div><div class="lg9-card" style="padding:22px;"><h3 style="margin:0 0 8px;font-size:1.25rem;">Ordering becomes instant.</h3><p class="lg9-copy">Seat-level ordering and service interaction remove delay between purchase intent and completed order.</p></div></div><div class="lg9-card" style="padding:24px;"><p class="lg9-kicker" style="margin:0 0 10px;">Partner Feedback</p><blockquote style="margin:0;font-size:1.06rem;line-height:1.8;color:#1c2e43;">\"The LounGenie has enhanced our guest experience with secure storage, charging ports, and the F&B call button. Our guests love the convenience.\"</blockquote><p style="margin:12px 0 0;color:#4a6079;font-weight:700;">Raymond Weissert, General Manager - The Grove Waterpark and Resort</p><hr style="border:none;border-top:1px solid #dbe6ef;margin:16px 0;"><blockquote style="margin:0;font-size:1.06rem;line-height:1.8;color:#1c2e43;">\"The addition of the LounGenie to our cabanas took the experience to the next level.\"</blockquote><p style="margin:12px 0 0;color:#4a6079;font-weight:700;">Kamiya Woodard, Director of Guest Experience - Orlando World Center Marriott</p></div></div></div></section>
<section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div style="max-width:860px;margin:0 auto 24px;text-align:center;"><p class="lg9-kicker">Comparison</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Standard cabana vs. LounGenie-equipped cabana.</h2></div><div class="lg9-card" style="padding:0;overflow:hidden;"><table style="width:100%;border-collapse:collapse;"><thead><tr style="background:#eff6fd;"><th style="padding:14px;border-bottom:1px solid #dbe6ef;text-align:left;">Category</th><th style="padding:14px;border-bottom:1px solid #dbe6ef;text-align:left;">Standard</th><th style="padding:14px;border-bottom:1px solid #dbe6ef;text-align:left;">With LounGenie</th></tr></thead><tbody><tr><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Valuables</td><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Guest leaves early or worries</td><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Waterproof safe at seat</td></tr><tr><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Charging</td><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Guest leaves to recharge</td><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Solar-powered USB at seat</td></tr><tr><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Ordering</td><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Flag server or walk to bar</td><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">F&B call button and/or QR workflow</td></tr><tr><td style="padding:12px 14px;">Commercial Model</td><td style="padding:12px 14px;">Passive furniture</td><td style="padding:12px 14px;">Revenue-focused platform, $0 upfront model</td></tr></tbody></table></div></div></section>
<section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-grid-2" style="align-items:start;"><div><p class="lg9-kicker">FAQ</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Frequently asked questions from operators.</h2><div class="lg9-card" style="padding:20px;margin-bottom:12px;"><strong>Do smart cabana units require electrical wiring?</strong><p class="lg9-copy" style="margin:8px 0 0;">No. LounGenie uses solar-powered charging and is designed for fast deployment without trenching and permits.</p></div><div class="lg9-card" style="padding:20px;margin-bottom:12px;"><strong>How do guests place orders?</strong><p class="lg9-copy" style="margin:8px 0 0;">Depending on configuration, guests can use QR ordering and/or a service communication button for staff response.</p></div><div class="lg9-card" style="padding:20px;"><strong>Who handles maintenance?</strong><p class="lg9-copy" style="margin:8px 0 0;">Pool Safe handles installation, maintenance, and ongoing service as part of the operating model.</p></div></div><div class="lg9-card" style="padding:30px;background:linear-gradient(135deg,#0e2138,#0f365a 55%,#0055a5);color:#fff;border:none;"><p class="lg9-kicker" style="color:#91ddff;margin:0 0 10px;">Ready To Start</p><h3 style="font-size:2rem;margin:0 0 12px;line-height:1.1;">Turn your pool deck into a profit center.</h3><p style="margin:0 0 18px;line-height:1.85;color:rgba(255,255,255,.82);">We assess your layout, install on site, and help you capture incremental guest spend from day one.</p><div class="lg9-btns"><a class="lg9-btn-primary" href="{ROOT}/index.php/contact-loungenie/">Get Your Revenue Plan</a><a class="lg9-btn-secondary" href="{ROOT}/index.php/poolside-amenity-unit/">See Full Feature Set</a></div></div></div></div></section>
"""

HOME = HOME.replace("</div><!-- /wp:html -->", HOME_EXTRA + "</div><!-- /wp:html -->")

FEATURES = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-section-soft" style="padding-top:74px;padding-bottom:58px;"><div class="lg9-narrow" style="text-align:center;"><p class="lg9-kicker">Product Features</p><h1 class="lg9-title-md" style="margin:10px 0 14px;">A modern smart hospitality product, not a generic poolside accessory.</h1><p class="lg9-copy">LounGenie is designed to work as a system: every feature solves a guest problem and supports better commercial outcomes.</p></div></section><section class="lg9-section" style="padding-top:0;background:#fff;"><div class="lg9-shell" style="display:flex;flex-direction:column;gap:24px;"><div class="lg9-card" style="padding:22px;"><div class="lg9-grid-2" style="align-items:center;"><div class="lg9-media" style="aspect-ratio:16/11;"><img src="{IMG['hero2']}" alt="Order feature"></div><div><span class="lg9-pill">ORDER</span><h2 class="lg9-title-md" style="font-size:2rem;margin:12px 0;">Ordering becomes more immediate and more natural.</h2><p class="lg9-copy">Instead of forcing guests to flag staff or leave the chair, LounGenie helps properties reduce the gap between “I want something” and “I placed the order.”</p><ul style="color:#455468;line-height:1.9;"><li>Supports faster conversion from intent to order</li><li>Reduces friction around premium seating</li><li>Feels consistent with a modern guest journey</li></ul></div></div></div><div class="lg9-card" style="padding:22px;"><div class="lg9-grid-2" style="align-items:center;"><div><span class="lg9-pill">STASH</span><h2 class="lg9-title-md" style="font-size:2rem;margin:12px 0;">Guests relax longer when valuables feel secure.</h2><p class="lg9-copy">Secure storage reduces one of the biggest hidden reasons people interrupt their own poolside experience.</p><ul style="color:#455468;line-height:1.9;"><li>Encourages longer stay duration</li><li>Improves peace of mind</li><li>Supports a more premium environment</li></ul></div><div class="lg9-media" style="aspect-ratio:16/11;"><img src="{IMG['hero']}" alt="Stash feature"></div></div></div><div class="lg9-card" style="padding:22px;"><div class="lg9-grid-2" style="align-items:center;"><div class="lg9-media" style="aspect-ratio:16/11;"><img src="{IMG['grove2']}" alt="Charge and chill"></div><div><span class="lg9-pill">CHARGE + CHILL</span><h2 class="lg9-title-md" style="font-size:2rem;margin:12px 0;">The experience feels better because the details are handled.</h2><p class="lg9-copy">Power and comfort matter. When those needs are solved at the seat, the guest experience feels smoother, more premium, and more complete.</p><ul style="color:#455468;line-height:1.9;"><li>Reduces departures caused by charging needs</li><li>Supports all-day comfort</li><li>Improves premium-seat value perception</li></ul></div></div></div></div></section><section class="lg9-section-soft"><div class="lg9-shell"><div style="max-width:760px;margin:0 auto 24px;text-align:center;"><p class="lg9-kicker">Configurations</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Three ways to match the platform to the property.</h2></div><div class="lg9-grid-3"><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">Classic</p><h3 style="margin:0 0 10px;font-size:1.35rem;">Essential comfort and security</h3><p class="lg9-copy">A practical entry point for properties upgrading core poolside amenities.</p></div><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">F&amp;B Communication</p><h3 style="margin:0 0 10px;font-size:1.35rem;">Reduce dead runs for staff</h3><p class="lg9-copy">Adds a stronger service interaction model for properties focused on poolside response efficiency.</p></div><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">2.0</p><h3 style="margin:0 0 10px;font-size:1.35rem;">Maximum revenue impact</h3><p class="lg9-copy">The most complete configuration for operators looking to modernize ordering and guest convenience at the highest level.</p></div></div></div></section></div><!-- /wp:html -->"""

ABOUT = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-section" style="background:linear-gradient(135deg,#09131f,#123559 55%,#0055a5);color:#fff;"><div class="lg9-shell lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker" style="color:#8ad8ff;">About</p><h1 class="lg9-title" style="margin:10px 0 16px;color:#fff;">Built around how guests actually behave poolside.</h1><p style="color:rgba(255,255,255,.8);line-height:1.9;font-size:1.05rem;max-width:680px;">LounGenie exists to help hospitality operators upgrade the guest experience with a product that looks modern, fits premium environments, and supports better revenue performance.</p></div><div class="lg9-media" style="aspect-ratio:16/11;"><img src="{IMG['hero3']}" alt="Modern daybed environment"></div></div></section><section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-grid-3"><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">Model</p><h3 style="margin:0 0 10px;font-size:1.3rem;">Designed for operator practicality</h3><p class="lg9-copy">The deployment model is intended to reduce adoption friction while keeping the product standard high.</p></div><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">Experience</p><h3 style="margin:0 0 10px;font-size:1.3rem;">Premium by design</h3><p class="lg9-copy">The visual and functional system is built to feel at home in resorts, hotels, and upscale cabana environments.</p></div><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">Results</p><h3 style="margin:0 0 10px;font-size:1.3rem;">Grounded in real operator outcomes</h3><p class="lg9-copy">The platform is presented around measured results and practical guest behavior, not inflated claims.</p></div></div></div></section></div><!-- /wp:html -->"""

CONTACT = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-section-soft"><div class="lg9-shell lg9-grid-2" style="align-items:start;"><div><p class="lg9-kicker">Contact</p><h1 class="lg9-title-md" style="margin:10px 0 16px;">Talk to us about your property.</h1><p class="lg9-copy">If you want to understand how LounGenie fits your current cabanas, daybeds, or premium seating areas, we can walk through the opportunity clearly and directly.</p><div class="lg9-media" style="aspect-ratio:16/11;margin-top:24px;"><img src="{IMG['contact']}" alt="Poolside cabana contact image"></div><div class="lg9-card" style="padding:18px 20px;margin-top:18px;"><strong style="display:block;margin-bottom:6px;">Email</strong><a href="mailto:info@poolsafeinc.com" style="color:var(--lg-blue);font-weight:800;">info@poolsafeinc.com</a></div></div><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">Request Demo</p><h2 style="margin:0 0 14px;font-size:1.8rem;">Start the conversation.</h2><p class="lg9-copy" style="margin-bottom:20px;">Share your details and we’ll follow up regarding fit, rollout, and next steps.</p><form action="https://formsubmit.co/info@poolsafeinc.com" method="POST" style="display:grid;gap:14px;"><input type="hidden" name="_captcha" value="false"><input type="hidden" name="_subject" value="New Demo Request - LounGenie"><label style="font-size:13px;font-weight:700;">Name<input name="name" required style="width:100%;margin-top:6px;padding:12px 14px;border:1px solid #d7e1eb;border-radius:10px"></label><label style="font-size:13px;font-weight:700;">Email<input type="email" name="email" required style="width:100%;margin-top:6px;padding:12px 14px;border:1px solid #d7e1eb;border-radius:10px"></label><label style="font-size:13px;font-weight:700;">Property<input name="property" required style="width:100%;margin-top:6px;padding:12px 14px;border:1px solid #d7e1eb;border-radius:10px"></label><label style="font-size:13px;font-weight:700;">Message<textarea name="message" rows="5" style="width:100%;margin-top:6px;padding:12px 14px;border:1px solid #d7e1eb;border-radius:10px"></textarea></label><button type="submit" class="lg9-btn-primary" style="border:0;cursor:pointer;">Send Request</button></form></div></div></section></div><!-- /wp:html -->"""

VIDEOS = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-section" style="background:linear-gradient(135deg,#09131f,#123559 55%,#0055a5);color:#fff;"><div class="lg9-narrow" style="text-align:center;"><p class="lg9-kicker" style="color:#8ad8ff;">Videos</p><h1 class="lg9-title-md" style="margin:10px 0 14px;color:#fff;">Product demos and real-world installations.</h1><p style="color:rgba(255,255,255,.8);line-height:1.8;font-size:1.04rem;">This page now uses real video iframes rather than broken linked embeds.</p></div></section><section class="lg9-section-soft" style="padding-top:40px;"><div class="lg9-shell"><div class="lg9-video-grid"><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/EZ2CfBU30Ho" title="LounGenie Overview" allowfullscreen></iframe></div><div style="padding:18px 18px 20px;"><strong style="display:block;margin-bottom:6px;">LounGenie Overview</strong><span class="lg9-copy" style="font-size:15px;">A short look at the platform and how it appears poolside.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/M48NYM06JgY" title="Marriott Demo" allowfullscreen></iframe></div><div style="padding:18px 18px 20px;"><strong style="display:block;margin-bottom:6px;">Orlando World Center Marriott</strong><span class="lg9-copy" style="font-size:15px;">Installation footage and a guest-experience context.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/PhV1JVo9POI" title="The Grove Demo" allowfullscreen></iframe></div><div style="padding:18px 18px 20px;"><strong style="display:block;margin-bottom:6px;">The Grove Resort</strong><span class="lg9-copy" style="font-size:15px;">A resort example showing how the product fits premium environments.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/3Rjba7pWs_I" title="Waterpark Demo" allowfullscreen></iframe></div><div style="padding:18px 18px 20px;"><strong style="display:block;margin-bottom:6px;">Water Park Installation</strong><span class="lg9-copy" style="font-size:15px;">A more energetic deployment context with high guest traffic.</span></div></div></div></div></section></div><!-- /wp:html -->"""

GALLERY = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-section" style="padding-bottom:46px;background:#fff;"><div class="lg9-narrow" style="text-align:center;"><p class="lg9-kicker">Gallery</p><h1 class="lg9-title-md" style="margin:10px 0 14px;">Real imagery chosen to feel sharp, premium, and modern.</h1><p class="lg9-copy">This gallery now uses a tighter grid and more controlled aspect ratios so the imagery reads as intentional rather than oversized.</p></div></section><section class="lg9-section-soft" style="padding-top:32px;"><div class="lg9-shell"><div class="lg9-gallery"><div class="lg9-media"><img src="{IMG['hero4']}" alt="Hilton installation"></div><div class="lg9-media"><img src="{IMG['hero3']}" alt="Daybed area"></div><div class="lg9-media"><img src="{IMG['grove']}" alt="The Grove"></div><div class="lg9-media"><img src="{IMG['park1']}" alt="Water park seating"></div><div class="lg9-media"><img src="{IMG['park2']}" alt="Water park safe and ice bucket"></div><div class="lg9-media"><img src="{IMG['park3']}" alt="Water park cabana safe"></div><div class="lg9-media"><img src="{IMG['park4']}" alt="Cabana interior smart unit"></div><div class="lg9-media"><img src="{IMG['grove2']}" alt="Resort comfort setup"></div><div class="lg9-media"><img src="{IMG['sea']}" alt="SeaWorld installation"></div></div></div></section></div><!-- /wp:html -->"""

PAGE_SEO = {
    4701: {"title": "LounGenie | Smart Poolside Revenue Platform", "excerpt": "A modern poolside revenue platform for hotels, resorts, and water parks with ordering, storage, charging, and guest comfort built into one premium unit.", "content": HOME},
    2989: {"title": "LounGenie Features | Smart Hospitality Product", "excerpt": "Explore the LounGenie platform features and configurations designed to improve guest convenience and poolside revenue performance.", "content": FEATURES},
    4862: {"title": "About LounGenie | Modern Hospitality Innovation", "excerpt": "Learn how LounGenie approaches guest experience, premium design, and operator practicality in one hospitality product platform.", "content": ABOUT},
    5139: {"title": "Contact LounGenie | Request a Demo", "excerpt": "Contact LounGenie to discuss your property and request a demo of the modern poolside revenue platform.", "content": CONTACT},
    5285: {"title": "LounGenie Videos | Demos and Installations", "excerpt": "Watch LounGenie product demos and real installation footage across resort and water park environments.", "content": VIDEOS},
    5223: {"title": "LounGenie Gallery | Installation Photos", "excerpt": "Browse installation photos that show how LounGenie fits premium hotels, resorts, and water park environments.", "content": GALLERY},
}


def post_json(url, payload):
    data = json.dumps(payload).encode()
    req = urllib.request.Request(url, method="POST", data=data, headers={**HEADERS, "Content-Length": str(len(data))})
    with urllib.request.urlopen(req, timeout=90) as r:
        return json.loads(r.read())


def update_header():
    return post_json(f"{PARTS}/twentytwentyfour//header", {"content": HEADER_TEMPLATE, "status": "publish"})


def update_footer():
    return post_json(f"{PARTS}/twentytwentyfour//footer", {"content": FOOTER_TEMPLATE, "status": "publish"})


def update_nav():
    return post_json(f"{NAVS}/4", {"content": NAVIGATION_RAW, "status": "publish"})


def update_template():
    return post_json(f"{TEMPLATES}/twentytwentyfour//page-wide", {"content": PAGE_WIDE_TEMPLATE, "status": "publish"})


def update_page(page_id, payload):
    return post_json(f"{PAGES}/{page_id}", {"title": payload['title'], "excerpt": payload['excerpt'], "content": payload['content'], "status": "publish", "template": "page-wide"})


print("=" * 74)
print("LounGenie v9 | Functional Fixes + Modern Design Rebuild")
print("=" * 74)

print("\n[1/3] Updating header, footer, nav, and full-width template...")
for label, fn in [("header", update_header), ("footer", update_footer), ("navigation", update_nav), ("page-wide template", update_template)]:
    try:
        fn()
        print(f"  ✓ {label}")
    except Exception as e:
        print(f"  ✗ {label}: {e}")

print("\n[2/3] Rebuilding sales pages...")
for page_id, payload in PAGE_SEO.items():
    try:
        result = update_page(page_id, payload)
        print(f"  ✓ {page_id} {result.get('link','')}")
    except Exception as e:
        print(f"  ✗ {page_id}: {e}")

print("\n[3/3] Done.")
print("- Removed giant template-level featured images.")
print("- Replaced broken linked video blocks with real iframe embeds.")
print("- Rebuilt sales pages with stronger modern layout, typography, and media control.")
print("- Kept investor navigation and investor section structure in place.")

