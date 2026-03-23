import base64
import argparse
from datetime import datetime, timezone
import json
from pathlib import Path
import re
import urllib.error
import urllib.request

AUTH = base64.b64encode(b"Copilot:U7GM Z9qE QOq6 MQva IzcQ 6PU2").decode()
USER_AGENT = "Mozilla/5.0"
BASE = "https://loungenie.com/staging/wp-json/wp/v2"
SOURCE_BASE = "https://loungenie.com/staging/wp-json/wp/v2"
HEADERS = {"Authorization": f"Basic {AUTH}", "Content-Type": "application/json", "User-Agent": USER_AGENT}
PAGES = BASE + "/pages"
PARTS = BASE + "/template-parts"
NAVS = BASE + "/navigation"
TEMPLATES = BASE + "/templates"

ROOT = "https://loungenie.com/staging"
# Images reference the main site's uploads (same server, safe to use)
UP = "https://loungenie.com/staging/wp-content/uploads/2026/03/"
UP25 = "https://loungenie.com/staging/wp-content/uploads/2025/10/"
PAGE_TEMPLATE_DIR = Path(__file__).with_name("page-templates")
BACKUP_DIR = Path(__file__).with_name("backups")
CORE_PAGE_IDS = {4701, 2989, 4862, 5139, 5285, 5223}


def render_page_template(filename, fallback):
    template_path = PAGE_TEMPLATE_DIR / filename
    if not template_path.exists():
        return fallback

    context = {"GLOBAL_STYLE": GLOBAL_STYLE, "ROOT": ROOT}
    for key, value in IMG.items():
        context[f"IMG_{key}"] = value

    try:
        content = template_path.read_text(encoding="utf-8")
        return re.sub(r"\{([A-Za-z0-9_]+)\}", lambda match: context.get(match.group(1), match.group(0)), content)
    except Exception as e:
        print(f"  ! template load failed for {filename}, using fallback: {e}")
        return fallback


def render_token_template(filename, fallback, tokens):
    template_path = PAGE_TEMPLATE_DIR / filename
    if template_path.exists():
        try:
            content = template_path.read_text(encoding="utf-8")
        except Exception as e:
            print(f"  ! template load failed for {filename}, using fallback: {e}")
            content = fallback
    else:
        content = fallback

    for key, value in tokens.items():
        content = content.replace(f"[[{key}]]", value)
    return content


def extract_ir_editable_content(content):
    # If this page was previously wrapped by an IR shell, keep only the editable body.
    m = re.search(
        r'<div[^>]*id=["\']ir-editable-content["\'][^>]*>(.*?)</div>\s*(?:<!--\s*/wp:group\s*-->)?',
        content,
        re.IGNORECASE | re.DOTALL,
    )
    if m:
        inner = m.group(1)
    else:
        inner = content

    # Remove required lock blocks before re-injecting canonical required blocks.
    inner = re.sub(
        r'<!--\s*wp:group\s*\{[^}]*"className"\s*:\s*"ir-required-block[^\"]*"[^}]*\}\s*-->.*?<!--\s*/wp:group\s*-->',
        '',
        inner,
        flags=re.IGNORECASE | re.DOTALL,
    )
    return inner.strip()

IMG = {
    "logo": UP25 + "cropped-cropped-LounGenie-Logo.png",
    "hero": UP + "lg-home-hero-the-grove-7-scaled.jpg",
    "hero2": UP + "lg-contact-owc-cabana-scaled.jpg",
    "hero3": UP + "lg-home-daybed-hilton-scaled.jpg",
    "hero4": UP + "lg-about-westin-hilton-head-scaled.jpg",
    "grove": UP + "The-Grove-6.jpg",
    "grove2": UP + "The-Grove-2.jpg",
    "sea": UP + "Sea-World-San-Diego.jpg",
    "contact": UP + "lg-contact-owc-cabana-scaled.jpg",
    "park1": UP + "IMG_3241-scaled-1.jpg",
    "park2": UP + "IMG_3239-scaled-1.jpg",
    "park3": UP + "IMG_3235-scaled-1.jpg",
    "park4": UP + "IMG_3233-scaled-1.jpg",
    "marg": UP + "margaritaville-jimmy-buffetts-logo-png-transparent.png",
    "ritz": UP + "Ritz-Carlton-Logo-1965.webp",
    "niagara": UP25 + "R-1-scaled.png",
    "marriott": ROOT + "/wp-content/uploads/2026/02/Marriott_hotels_logo14.svg_.png",
    "partner1": ROOT + "/wp-content/uploads/2025/10/Hilton-Emblem-300x169.png",
    "partner2": ROOT + "/wp-content/uploads/2025/10/Westin_Hotels__Resorts_logo.svg-300x95.png",
    "boardhero": UP + "mc-mcowc-16683_Classic-Hor.jpg",
    "financehero": UP + "page_1145_img_6227-copy-1-web.webp",
    "presshero": UP + "page_1145__mg_6277-copy-1-web.webp",
    "lifestyle1": UP + "lg-gallery-hilton-kona-cabana-4-scaled.jpg",
    "lifestyle2": UP + "lg-gallery-sea-world-san-diego.jpg",
    "lifestyle3": UP + "IMG_2078.jpeg",
    "gallery_water1": UP + "lg-gallery-water-world-cabana-1.jpg",
    "gallery_water2": UP + "lg-gallery-cowabunga-cabana-1-scaled.jpg",
    "gallery_water3": UP + "IMG_2089.jpeg",
    "gallery_water4": UP + "lg-gallery-soaky-10-scaled.jpg",
    "gallery_water5": UP + "IMG_2090.jpeg",
    "gallery_water6": UP + "IMG_2080.jpeg",
    "gallery_water7": UP + "IMG_2081.jpeg",
    "gallery_detail1": UP + "a5ea38b9-4578-4356-a118-f168caa0ec90.jpg",
    "gallery_detail2": UP + "38f4fc95-7925-4625-b0e8-5ba78771c037.jpg",
    "gallery_detail3": UP + "IMG_2079.jpeg",
    # Brand logos for trust strip
    "carnival": ROOT + "/wp-content/uploads/2026/02/Carnival-Cruise-Emblem-1-scaled.webp",
    "holiday": ROOT + "/wp-content/uploads/2026/03/logo-holiday-inn.webp",
    "splash": ROOT + "/wp-content/uploads/2026/03/logo-splash-kingdom.webp",
    "hyatt":    UP25 + "R-1-scaled.png",
    "pyek":     UP25 + "logos-pc-black-horizontal.png",
    "palace":   UP25 + "logo-color.png",
    "atlantis": UP25 + "Picture4-300x68.png.webp",
    "cowabunga": UP25 + "cowabunga-vegas-logo-300x173.png.webp",
    "typhoon":  UP25 + "tt-logo-300x121.png.webp",
    # New safe/lock photos (uploaded 2026-03)
    "safe1":  UP + "IMG_3233-scaled-1.jpg",
    "safe2":  UP + "IMG_3235-scaled-1.jpg",
    "safe3":  UP + "IMG_3239-scaled-1.jpg",
    "safe4":  UP + "IMG_3241-scaled-1.jpg",
    "safe5":  UP + "page_1145__mg_6277-copy-1-web.webp",
    "safe6":  UP + "page_1145_img_6227-copy-1-web.webp",
    "safe7":  UP + "IMG_2077.jpeg",
    "safe8":  UP + "IMG_2078.jpeg",
    "safe9":  UP + "IMG_2079.jpeg",
    "safe10": UP + "IMG_2080.jpeg",
    "safe11": UP + "IMG_2080-1.jpeg",
    "safe12": UP + "IMG_2081.jpeg",
    "safe13": UP + "IMG_2083.jpeg",
    "safe14": UP + "IMG_2089.jpeg",
    "safe15": UP + "IMG_2090.jpeg",
}

GLOBAL_STYLE = """
<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800;900&display=swap');
:root {
    --lg-bg: #f2f7fb;
  --lg-surface: #ffffff;
    --lg-ink: #0b1726;
        --lg-ink-soft: #2f455c;
  --lg-line: #dbe6ef;
        --lg-blue: #0052ab;
        --lg-cyan: #00a9dd;
        --lg-navy: #041428;
        --lg-midnight: #082340;
}
html, body { font-family: 'Manrope', sans-serif !important; font-size: 16px; -webkit-font-smoothing: antialiased; }
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
.lg9 h1, .lg9 h2, .lg9 h3, .lg9 h4, .lg9-title, .lg9-title-md { color: var(--lg-ink); }
.lg9-shell { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
.lg9-narrow { max-width: 900px; margin: 0 auto; padding: 0 24px; }
.lg9-kicker { text-transform: uppercase; letter-spacing: 2.4px; font-size: 11.5px; font-weight: 800; color: var(--lg-cyan); }
.lg9-title { font-size: clamp(2.4rem, 5.8vw, 5.4rem); line-height: 1.0; letter-spacing: -2px; font-weight: 800; margin: 0; }
.lg9-title-md { font-size: clamp(2rem, 4.2vw, 3.5rem); line-height: 1.06; letter-spacing: -1.2px; font-weight: 800; margin: 0; }
.lg9-copy { color: var(--lg-ink-soft); line-height: 1.82; font-size: 1.08rem; }
.lg9-hero-lead { font-size: clamp(1.02rem, 1.6vw, 1.15rem); line-height: 1.88; max-width: 720px; }
.lg9-divider { height: 56px; background: linear-gradient(180deg, transparent 0%, rgba(0,75,147,.08) 100%); clip-path: polygon(0 25%, 100% 0, 100% 100%, 0 100%); }
.lg9-btns { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; }
.lg9-btn-primary, .lg9-btn-secondary { position:relative; overflow:hidden; display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:14px 22px; min-height: 50px; border-radius:12px; font-weight:800; font-size:15px; line-height:1.1; transition: transform .22s ease, box-shadow .22s ease, background .22s ease; }
.lg9-btn-primary::before, .lg9-btn-secondary::before { content:""; position:absolute; top:0; left:-120%; width:90%; height:100%; background:linear-gradient(105deg, transparent 0%, rgba(255,255,255,.22) 48%, transparent 100%); transition:left .45s ease; }
.lg9-btn-primary:hover::before, .lg9-btn-secondary:hover::before { left:130%; }
.lg9-btn-primary { background: linear-gradient(135deg, #0050a8 0%, #0078c7 52%, #00a9dd 100%); color:#fff; box-shadow: 0 18px 42px rgba(0, 68, 146, .42), 0 0 0 1px rgba(255,255,255,.08) inset; }
.lg9-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 24px 52px rgba(0, 68, 146, .56), 0 0 26px rgba(0,169,221,.35); }
.lg9-btn-secondary { border:1px solid rgba(255,255,255,.58); color:#fff; background: rgba(255,255,255,.14); box-shadow: 0 10px 24px rgba(4,20,40,.22); }
.lg9-btn-secondary:hover { background: rgba(255,255,255,.24); box-shadow: 0 14px 30px rgba(4,20,40,.3); }
.lg9-card { background: var(--lg-surface); border:1px solid var(--lg-line); border-radius: 20px; box-shadow: 0 18px 44px rgba(13,27,42,.08); transition: transform .22s ease, box-shadow .22s ease; }
.lg9-card > :first-child { margin-top: 0 !important; }
.lg9-card > :last-child { margin-bottom: 0 !important; }
.lg9-card:hover { transform: translateY(-3px); box-shadow: 0 22px 50px rgba(13,27,42,.12); }
.lg9-card h1, .lg9-card h2, .lg9-card h3, .lg9-card h4 { color: var(--lg-ink); }
.lg9-media { overflow:hidden; border-radius: 20px; box-shadow: 0 22px 52px rgba(13,27,42,.14); }
.lg9-media img { width:100%; height:100%; object-fit:cover; transition: transform .55s ease; }
.lg9-media:hover img { transform: scale(1.04); }
.lg9-grid-2 { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: clamp(22px, 3vw, 28px); }
.lg9-grid-3 { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: clamp(18px, 2.2vw, 22px); }
.lg9-logo-strip { display:flex; flex-wrap:wrap; justify-content:center; gap:20px 36px; align-items:center; }
.lg9-logo-strip img { max-height:52px; width:auto; flex-shrink:0; filter: grayscale(100%) opacity(.75); }
/* Marquee / infinite ticker */
.lg9-marquee-wrap { overflow:hidden; width:100%; position:relative; }
.lg9-marquee-wrap::before, .lg9-marquee-wrap::after { content:""; position:absolute; top:0; bottom:0; width:80px; z-index:2; pointer-events:none; }
.lg9-marquee-wrap::before { left:0; background:linear-gradient(90deg,#fff 0%,transparent 100%); }
.lg9-marquee-wrap::after  { right:0; background:linear-gradient(270deg,#fff 0%,transparent 100%); }
.lg9-marquee-track { display:flex; width:max-content; animation: lg9Marquee 38s linear infinite; will-change: transform; }
.lg9-marquee-track:hover { animation-play-state: paused; }
.lg9-marquee-inner { display:flex; align-items:center; gap:52px; padding:0 26px; }
.lg9-marquee-inner img { max-height:50px; width:auto; flex-shrink:0; filter:grayscale(100%) opacity(.68); transition:filter .2s ease; }
.lg9-marquee-inner img:hover { filter:grayscale(0%) opacity(1); }
.lg9-logo-mark { background:#fff; border:1px solid #dbe6ef; border-radius:12px; padding:8px 10px; box-shadow:0 6px 18px rgba(13,27,42,.08); }
@keyframes lg9Marquee { from { transform:translateX(0); } to { transform:translateX(-50%); } }
.lg9-hero { position: relative; min-height: 84vh; display:flex; align-items:center; background:#09131f; }
.lg9-hero-bg { position:absolute; inset:0; }
.lg9-hero-bg img { width:100%; height:100%; object-fit:cover; opacity:.4; }
.lg9-hero-overlay { position:absolute; inset:0; background: linear-gradient(112deg, rgba(3,14,28,.975) 0%, rgba(5,29,58,.93) 50%, rgba(0,82,171,.74) 100%); }
.lg9-hero-inner { position:relative; z-index:2; width:100%; padding: 88px 0 76px; }
.lg9-hero-inner h1, .lg9-hero-inner h2, .lg9-hero-inner h3, .lg9-hero-inner h4, .lg9-hero-inner .lg9-title, .lg9-hero-inner .lg9-title-md { color:#fff; }
.lg9-hero-inner p { color: rgba(255,255,255,.9) !important; }
.lg9-hero-inner .lg9-stat-panel p { color: #1c2e43 !important; }
.lg9-stat-panel { background: rgba(255,255,255,.94); border:1px solid rgba(255,255,255,.4); border-radius: 24px; padding: 20px; backdrop-filter: blur(10px); }
.lg9-stat-big { font-size: clamp(2.6rem, 6vw, 4.8rem); line-height: 1; font-weight: 900; color: var(--lg-ink); }
.lg9-section { padding: clamp(60px, 7vw, 82px) 0; }
.lg9-section-soft { padding: clamp(60px, 7vw, 82px) 0; background: var(--lg-bg); }
.lg9-callout { position: relative; overflow: hidden; }
.lg9-callout::after { content:""; position:absolute; inset:auto -10% -40% auto; width:240px; height:240px; border-radius:999px; background:radial-gradient(circle, rgba(138,216,255,.18) 0%, rgba(138,216,255,0) 70%); pointer-events:none; }
.lg9-step { display:flex; gap: 14px; align-items:flex-start; }
.lg9-step-num { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg,var(--lg-blue),var(--lg-cyan)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; flex-shrink:0; transition: transform .2s ease; }
.lg9-step:hover .lg9-step-num { transform: scale(1.08); }
.lg9-pill { display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border-radius:999px; background:#e8f4ff; color: var(--lg-blue); font-size:12px; font-weight:800; }
.lg9-pill::before { content: ""; width:8px; height:8px; border-radius:999px; background: linear-gradient(135deg,var(--lg-blue),var(--lg-cyan)); }
.lg9-form-note { font-size: 13px; color: #3f5873; margin-top: 12px; }
.lg9-card blockquote { border-left: 3px solid rgba(0,168,221,.4); padding-left: 16px; }
.lg9-video-grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 22px; }
.lg9-video-card { background:#fff; border:1px solid var(--lg-line); border-radius:18px; overflow:hidden; box-shadow:0 16px 40px rgba(13,27,42,.08); }
.lg9-frame { aspect-ratio: 16 / 9; background:#09131f; }
.lg9-frame iframe { width:100%; height:100%; border:0; display:block; }
.lg9-video-hero { display:grid; gap: 18px; }
@media (min-width: 901px) { .lg9-video-hero { grid-template-columns: 1.4fr 1fr; align-items: start; } }
.lg9-video-feature { background: linear-gradient(145deg, #0a1727 0%, #112f4f 55%, #0d4f95 100%); border-radius: 24px; padding: 12px; box-shadow: 0 26px 60px rgba(9, 27, 45, .22); }
.lg9-video-feature .lg9-frame { border-radius: 16px; overflow: hidden; aspect-ratio: 16 / 8.7; }
.lg9-video-hero-copy { background: #ffffff; color: var(--lg-ink); border-radius: 20px; padding: 24px; border: 1px solid #deebf6; box-shadow: 0 14px 36px rgba(9, 27, 45, .08); }
.lg9-video-hero-copy .lg9-copy { color: var(--lg-ink-soft); }
.lg9-video-meta { display:grid; gap: 12px; margin-top: 18px; }
.lg9-video-meta-card { padding: 14px 16px; border-radius: 16px; background: #f4f9ff; border: 1px solid #dbe9f7; }
.lg9-video-meta-card strong { display:block; margin-bottom: 4px; color: #14314b; }
.lg9-video-library-head { display:flex; justify-content:space-between; align-items:end; gap:18px; flex-wrap:wrap; margin-bottom: 22px; }
.lg9-video-card-body { padding:18px 18px 20px; }
.lg9-video-card-body strong { display:block; margin-bottom:6px; }
.lg9-gallery { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:18px; }
.lg9-gallery .lg9-media { aspect-ratio: 16 / 12; }
.lg9-gallery--mosaic .lg9-media:nth-child(3n+1) { aspect-ratio: 16 / 10; }
.lg9-gallery--mosaic .lg9-media:nth-child(3n+2) { aspect-ratio: 4 / 5; }
.lg9-gallery--mosaic .lg9-media:nth-child(3n+3) { aspect-ratio: 16 / 12; }
.lg9-gallery-band { display:grid; gap: 18px; }
.lg9-gallery-lead { display:grid; grid-template-columns: 1.35fr .9fr; gap: 18px; align-items: stretch; }
.lg9-gallery-lead .lg9-media:first-child { aspect-ratio: 16 / 10; }
.lg9-gallery-lead .lg9-media:last-child { aspect-ratio: 4 / 5; }
.lg9-gallery-caption { display:grid; gap: 8px; margin-bottom: 16px; }
.lg9-gallery-note { color: #516779; font-size: 14px; line-height: 1.65; }
.lg9-gallery-chip-row { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
.lg9-gallery-chip { display:inline-flex; align-items:center; padding:8px 12px; border-radius:999px; background:#eaf3fb; color:#22415b; font-size:12px; font-weight:800; letter-spacing:.2px; }
.lg9-topbar { background: linear-gradient(90deg, #041224 0%, #0a315f 60%, #0f4a86 100%); color: rgba(255,255,255,.96); font-size: 12px; letter-spacing: .3px; border-bottom: 1px solid rgba(255,255,255,.12); }
.lg9-topbar-inner { max-width:1280px; margin:0 auto; padding:8px 24px; display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; }
.wp-site-blocks > header.wp-block-template-part { position: sticky; top:0; z-index: 990; background: rgba(4,20,40,.9); border-bottom:1px solid rgba(170,205,238,.3); backdrop-filter: blur(13px); }
.wp-site-blocks > header .lg9-head { max-width:1280px; margin:0 auto; padding:12px 24px; display:flex; align-items:center; justify-content:space-between; gap:24px; }
.wp-site-blocks > header .lg9-head img { width: 236px; height:auto; image-rendering:-webkit-optimize-contrast; filter: drop-shadow(0 2px 6px rgba(0,0,0,.35)); }
.wp-site-blocks > header .lg9-head-nav { flex:1 1 auto; display:flex; justify-content:flex-end; align-items:center; gap:18px; }
.wp-site-blocks > header .wp-block-navigation { margin:0; }
.wp-site-blocks > header .wp-block-navigation-item__content { padding:8px 10px; border-radius:8px; font-size:14px !important; font-weight:700 !important; color:var(--lg-ink) !important; transition: all .2s ease; }
.wp-site-blocks > header .wp-block-navigation-item__content:hover { background:#edf6ff; color:var(--lg-blue) !important; }
.wp-site-blocks > header .wp-block-navigation-submenu__toggle { color:var(--lg-ink) !important; }
.wp-site-blocks > footer .lg9-footer { background: radial-gradient(130% 160% at 0% 0%, #133b62 0%, #081624 52%, #050e1a 100%); color:rgba(255,255,255,.82); padding: 44px 24px 20px; }
.wp-site-blocks > footer .lg9-footer .wp-block-columns { max-width:1280px; margin:0 auto; gap:32px; }
.wp-site-blocks > footer .lg9-footer .wp-block-column { min-width:0; padding:0 !important; }
.wp-site-blocks > footer .lg9-footer h3 { color:#fff; font-size:14px; text-transform:uppercase; letter-spacing:1.3px; margin-bottom:12px; }
.wp-site-blocks > footer .lg9-footer ul { list-style:none; padding:0; margin:0; }
.wp-site-blocks > footer .lg9-footer li, .wp-site-blocks > footer .lg9-footer p { margin:0; font-size:14px; line-height:1.65; }
.wp-site-blocks > footer .lg9-footer a { color:rgba(255,255,255,.88); }
.wp-site-blocks > footer .lg9-footer a:hover { color:#9ddfff; }
.wp-site-blocks > footer .lg9-footer-base { max-width:1280px; margin:16px auto 0; padding-top:12px; border-top:1px solid rgba(255,255,255,.12); font-size:12px; color:rgba(255,255,255,.58); display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.lg9-footer-chip-wrap { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
.lg9-footer-chip { padding:7px 10px; border-radius:999px; border:1px solid rgba(255,255,255,.2); color:rgba(255,255,255,.88); font-size:12px; }
.lg9-footer-cta { display:inline-flex; align-items:center; justify-content:center; margin-top:14px; padding:10px 14px; border-radius:10px; background:linear-gradient(135deg,#0f4f92,#0f76c2); color:#fff !important; font-weight:800; font-size:13px; }
.lg9-hero-trust { display:flex; flex-wrap:wrap; gap:8px 22px; margin-top:20px; }
.lg9-hero-trust-item { font-size:12.5px; font-weight:700; color:rgba(255,255,255,.72); display:flex; align-items:center; gap:6px; letter-spacing:.1px; }
.lg9-hero-trust-item::before { content:"✓"; color:#4fe8a0; font-size:14px; font-weight:900; }
.lg9-prefooter { background:linear-gradient(135deg,#0e2138 0%,#0d3d6b 55%,#0055a5 100%); padding:52px 24px; }
.lg9-prefooter-inner { max-width:1280px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; gap:28px; flex-wrap:wrap; }
.lg9-prefooter-copy { flex:1 1 380px; }
.lg9 .lg9-section, .lg9 .lg9-section-soft { animation: lg9FadeUp .75s ease both; }
.lg9 .lg9-section:nth-of-type(2), .lg9 .lg9-section-soft:nth-of-type(2) { animation-delay: .08s; }
.lg9 .lg9-section:nth-of-type(3), .lg9 .lg9-section-soft:nth-of-type(3) { animation-delay: .14s; }
.lg9 .lg9-section:nth-of-type(4), .lg9 .lg9-section-soft:nth-of-type(4) { animation-delay: .2s; }
.lg9-grid-3 > .lg9-card { animation: lg9FadeUp .65s ease both; }
.lg9-grid-3 > .lg9-card:nth-child(2) { animation-delay: .09s; }
.lg9-grid-3 > .lg9-card:nth-child(3) { animation-delay: .16s; }
.lg9-hero-bg img { animation: lg9HeroZoom 8s ease-out both; }
@keyframes lg9FadeUp {
    from { opacity:0; transform: translateY(16px); }
    to { opacity:1; transform: translateY(0); }
}
@keyframes lg9HeroZoom {
    from { transform: scale(1.08); }
    to { transform: scale(1); }
}
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

/* Investor pages redesign shell (presentation only, source copy preserved) */
.page-id-5668 .wp-block-post-content,
.page-id-5651 .wp-block-post-content,
.page-id-5686 .wp-block-post-content,
.page-id-5716 .wp-block-post-content {
    max-width: none !important;
    margin: 0 !important;
    padding: 0 !important;
    background: #edf4fb;
}

.ir-shell {
    background: radial-gradient(130% 120% at 100% 0%, #edf6ff 0%, #f8fbff 46%, #ecf3fb 100%);
    color: var(--lg-ink);
}

.ir-shell .ir-hero {
    position: relative;
    min-height: 360px;
    display: flex;
    align-items: flex-end;
    padding: 74px 0 102px;
    overflow: hidden;
    background: #0c1d30;
}

.ir-shell .ir-hero-bg,
.ir-shell .ir-hero-bg img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.ir-shell .ir-hero-bg img {
    object-fit: cover;
    opacity: .36;
}

.ir-shell .ir-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(118deg, rgba(5,16,28,.94) 0%, rgba(9,34,60,.86) 56%, rgba(0,85,165,.56) 100%);
}

.ir-shell .ir-hero-inner {
    position: relative;
    z-index: 2;
}

.ir-shell .ir-hero h1 {
    color: #fff;
    margin: 10px 0 12px;
    font-size: clamp(2rem, 4.8vw, 3.5rem);
    line-height: 1.03;
    letter-spacing: -1px;
}

.ir-shell .ir-hero p {
    margin: 0;
    max-width: 820px;
    color: rgba(255,255,255,.86);
    line-height: 1.78;
    font-size: 1.02rem;
}

.ir-shell .ir-content-wrap {
    max-width: 1220px;
    margin: 0 auto;
    padding: 0 24px 84px;
}

.ir-shell .ir-content-panel {
    position: relative;
    z-index: 1;
    margin-top: -64px;
    background: #fff;
    border: 1px solid #dbe7f2;
    border-radius: 24px;
    box-shadow: 0 24px 56px rgba(7, 27, 47, .12);
    padding: clamp(22px, 2.6vw, 34px);
}

.ir-shell .ir-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid #e4edf6;
}

.ir-shell .ir-toolbar-links {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.ir-shell .ir-toolbar-links a {
    display: inline-flex;
    align-items: center;
    padding: 7px 12px;
    border-radius: 999px;
    background: #eaf3fd;
    color: #1e456d;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .2px;
}

.ir-shell .ir-toolbar-note {
    color: #4a637d;
    font-size: 12px;
    line-height: 1.5;
}

.ir-shell .ir-source-content h1,
.ir-shell .ir-source-content h2,
.ir-shell .ir-source-content h3,
.ir-shell .ir-source-content h4 {
    color: #0c2238;
    letter-spacing: -.35px;
    line-height: 1.15;
}

.ir-shell .ir-source-content p,
.ir-shell .ir-source-content li {
    color: #29445f;
    line-height: 1.8;
    font-size: 1rem;
}

/* Financials + Press: normalize card text rhythm inside Elementor stacks */
.page-id-5686 .ir-shell .ir-source-content .elementor-widget-wrap,
.page-id-5716 .ir-shell .ir-source-content .elementor-widget-wrap {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.page-id-5686 .ir-shell .ir-source-content .elementor-widget-text-editor p,
.page-id-5716 .ir-shell .ir-source-content .elementor-widget-text-editor p,
.page-id-5686 .ir-shell .ir-source-content .uagb-ifb-desc,
.page-id-5716 .ir-shell .ir-source-content .uagb-ifb-desc {
    margin: 0 0 12px;
    line-height: 1.72;
}

.page-id-5686 .ir-shell .ir-source-content .elementor-widget-text-editor p:last-child,
.page-id-5716 .ir-shell .ir-source-content .elementor-widget-text-editor p:last-child,
.page-id-5686 .ir-shell .ir-source-content .uagb-ifb-desc:last-child,
.page-id-5716 .ir-shell .ir-source-content .uagb-ifb-desc:last-child {
    margin-bottom: 0;
}

.page-id-5686 .ir-shell .ir-source-content .elementor-widget-container,
.page-id-5716 .ir-shell .ir-source-content .elementor-widget-container {
    overflow-wrap: anywhere;
}

.ir-shell .ir-source-content a {
    color: #0a4e94;
    font-weight: 700;
}

.ir-shell .ir-source-content table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #dce8f3;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
}

.ir-shell .ir-source-content th,
.ir-shell .ir-source-content td {
    border-bottom: 1px solid #e6eef6;
    padding: 11px 13px;
    text-align: left;
}

.ir-shell .ir-source-content .elementor-section {
    margin-bottom: 20px;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid #e1ebf4;
    box-shadow: 0 14px 36px rgba(8, 28, 48, .08);
}

.ir-shell .ir-source-content .elementor-background-overlay {
    opacity: .9 !important;
}

.ir-shell .ir-source-content .elementor-widget-icon-list {
    background: #f5faff;
    border: 1px solid #deebf8;
    border-radius: 13px;
    padding: 14px 15px;
}

.ir-shell .ir-source-content .elementor-widget-button .elementor-button {
    border-radius: 11px !important;
    padding: 11px 17px !important;
    font-weight: 800 !important;
    box-shadow: 0 10px 24px rgba(0, 75, 147, .2);
}

.ir-shell .ir-source-content .elementor-widget-image img,
.ir-shell .ir-source-content img {
    border-radius: 14px;
    box-shadow: 0 12px 30px rgba(8, 28, 48, .12);
}

.ir-shell .ir-source-content .elementor-top-section:first-of-type {
    margin-top: 0 !important;
}
@media (max-width: 1024px) {
  .lg9-logo-strip { grid-template-columns: repeat(3, minmax(0,1fr)); }
}
@media (prefers-reduced-motion: reduce) {
    .lg9 * { animation: none !important; transition: none !important; }
}
@media (max-width: 900px) {
  .lg9-grid-2, .lg9-grid-3, .lg9-video-grid, .lg9-gallery { grid-template-columns:1fr; }
  .lg9-hero-inner .lg9-grid-2 { grid-template-columns:1fr; }
    .lg9-gallery-lead { grid-template-columns: 1fr; }
        .lg9-video-feature .lg9-frame { aspect-ratio: 16 / 9.8; }
    .lg9-hero { min-height: auto; }
    .lg9-hero-inner { padding: 78px 0 64px; }
    .lg9-logo-strip { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
    .ir-shell .ir-hero { min-height: 300px; padding: 64px 0 92px; }
    .ir-shell .ir-content-panel { margin-top: -52px; border-radius: 20px; }
}
@media (max-width: 780px) {
  .lg9-shell, .lg9-narrow, .wp-site-blocks > header .lg9-head { padding-left:16px; padding-right:16px; }
  .wp-site-blocks > header .lg9-head { flex-wrap:wrap; }
    .wp-site-blocks > header .lg9-head img { width: 190px; }
    .lg9-topbar-inner { padding-left:16px; padding-right:16px; }
    .lg9-section, .lg9-section-soft { padding: 52px 0; }
    .lg9-title { font-size: clamp(2rem, 11vw, 3.15rem); letter-spacing: -1.2px; }
    .lg9-title-md { font-size: clamp(1.75rem, 8vw, 2.5rem); }
    .lg9-copy { font-size: 1rem; line-height: 1.74; }
    .lg9-btns { gap: 12px; }
    .lg9-btn-primary, .lg9-btn-secondary { width: 100%; justify-content: center; padding: 14px 18px; }
    .lg9-card { border-radius: 18px; }
    .lg9-media { border-radius: 18px; }
    .lg9-video-hero-copy { padding: 18px; }
    .lg9-video-library-head { margin-bottom: 16px; }
    .lg9-video-card-body { padding: 15px 15px 16px; }
    .lg9-gallery-chip-row { gap: 8px; }
    .lg9-gallery-chip { padding: 7px 10px; font-size: 11px; }
    .lg9-stat-panel { padding: 18px; }
    .lg9-prefooter { padding: 36px 16px; }
    .lg9-prefooter-inner { flex-direction:column; text-align:center; gap:20px; }
    .lg9-prefooter-inner .lg9-btn-primary { width:100%; justify-content:center; }
}
</style>
"""

HEADER_TEMPLATE = (
    "<!-- wp:html -->" + GLOBAL_STYLE + "<!-- /wp:html -->"
    + "<!-- wp:html --><div class=\"lg9-topbar\"><div class=\"lg9-topbar-inner\"><span>Premium Smart Cabana Product for Hotels, Resorts, and Waterparks</span><span><a href=\"mailto:info@poolsafeinc.com\" style=\"color:#b8e5ff;\">info@poolsafeinc.com</a></span></div></div><div class=\"lg9-head\"><div><a href=\"" + ROOT + "/\"><img src=\"" + IMG["logo"] + "\" alt=\"LounGenie\"></a></div><div class=\"lg9-head-nav\"><!-- /wp:html -->"
    + "<!-- wp:navigation {\"ref\":4,\"overlayMenu\":\"mobile\",\"layout\":{\"type\":\"flex\",\"justifyContent\":\"right\",\"orientation\":\"horizontal\"}} /-->"
    + "<!-- wp:html --><a href=\"" + ROOT + "/index.php/contact-loungenie/\" class=\"lg9-btn-primary\" style=\"padding:11px 16px;font-size:14px;\">Request Demo</a></div></div><!-- /wp:html -->"
)

FOOTER_TEMPLATE = """<!-- wp:group {"align":"full","className":"lg9-prefooter","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull lg9-prefooter"><!-- wp:group {"className":"lg9-prefooter-inner","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"wrap"}} -->
<div class="wp-block-group lg9-prefooter-inner"><!-- wp:group {"className":"lg9-prefooter-copy","layout":{"type":"constrained"}} -->
<div class="wp-block-group lg9-prefooter-copy"><!-- wp:paragraph {"className":"lg9-kicker","style":{"color":{"text":"#9ddfff"},"spacing":{"margin":{"top":"0","bottom":"8px"}}}} -->
<p class="lg9-kicker has-text-color" style="color:#9ddfff;margin-top:0;margin-bottom:8px">Get Started</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2,"style":{"color":{"text":"#ffffff"},"spacing":{"margin":{"top":"0","bottom":"10px"}},"typography":{"lineHeight":"1.12"}},"fontFamily":"space-grotesk"} -->
<h2 class="wp-block-heading has-space-grotesk-font-family has-text-color" style="color:#ffffff;margin-top:0;margin-bottom:10px;line-height:1.12">Ready to see LounGenie at your property?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"rgba(255,255,255,0.76)"},"spacing":{"margin":{"top":"0","bottom":"0"}},"typography":{"fontSize":"15px","lineHeight":"1.75"}}} -->
<p class="has-text-color" style="color:rgba(255,255,255,0.76);margin-top:0;margin-bottom:0;font-size:15px;line-height:1.75">We'll map the Product to your layout, team, and revenue opportunity in one focused conversation.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"lg9-btn-primary"} -->
<div class="wp-block-button lg9-btn-primary"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/index.php/contact-loungenie/">Request a Demo</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"lg9-footer","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull lg9-footer"><!-- wp:columns {"isStackedOnMobile":true} -->
<div class="wp-block-columns is-layout-flex wp-block-columns-is-layout-flex"><!-- wp:column {"width":"44%"} -->
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:44%"><!-- wp:image {"sizeSlug":"full","linkDestination":"custom","width":"180px"} -->
<figure class="wp-block-image size-full is-resized"><a href="[[ROOT]]/"><img src="[[LOGO]]" alt="LounGenie" style="width:180px"/></a></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>LounGenie is the premium smart cabana Product for hotels, resorts, and waterparks. Ordering, storage, charging, and comfort in one poolside unit.</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><li>$0 Upfront</li><li>Revenue Share</li><li>Built in Canada</li></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Explore</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li><a href="[[ROOT]]/">Home</a></li><li><a href="[[ROOT]]/index.php/poolside-amenity-unit/">Features</a></li><li><a href="[[ROOT]]/index.php/loungenie-videos/">Videos</a></li><li><a href="[[ROOT]]/index.php/cabana-installation-photos/">Gallery</a></li><li><a href="[[ROOT]]/index.php/hospitality-innovation/">About</a></li><li><a href="[[ROOT]]/index.php/contact-loungenie/">Contact</a></li></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Investor + Contact</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li><a href="[[ROOT]]/index.php/investors/">Investor Relations</a></li><li><a href="[[ROOT]]/index.php/board/">Board</a></li><li><a href="[[ROOT]]/index.php/financials/">Financials</a></li><li><a href="[[ROOT]]/index.php/press/">Press</a></li></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href=\"https://loungenie.com/wp-content/uploads/2025/12/Pool-Safe-Notice-of-Meeting-Combined-with-MIC.pdf\" target=\"_blank\" rel=\"noopener noreferrer\">Notice of Meeting &amp; Management Info Circular (PDF)</a></p>
<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":3} -->
<p><a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a><br><a href="tel:+14166302444">+1 (416) 630-2444</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="https://www.instagram.com/poolsafeinc/" target="_blank" rel="noopener noreferrer">Instagram</a> | <a href="https://ca.linkedin.com/company/poolsafeinc" target="_blank" rel="noopener noreferrer">LinkedIn</a> | <a href="https://youtube.com/@poolsafeinc?si=r5Qb8P7rphTE83Ms" target="_blank" rel="noopener noreferrer">YouTube</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"className":"lg9-footer-base","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group lg9-footer-base is-content-justification-space-between is-layout-flex wp-block-group-is-layout-flex"><!-- wp:paragraph -->
<p>&copy; 2026 LounGenie / Pool Safe Inc. All rights reserved.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>906 Magnetic Drive, North York, ON M3J 2C4, Canada</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->"""
FOOTER_TEMPLATE = FOOTER_TEMPLATE.replace("[[ROOT]]", ROOT).replace("[[LOGO]]", IMG["logo"])

PAGE_WIDE_TEMPLATE = """<!-- wp:template-part {\"slug\":\"header\",\"area\":\"header\",\"tagName\":\"header\",\"theme\":\"twentytwentyfour\"} /-->
<!-- wp:group {\"tagName\":\"main\",\"align\":\"full\",\"layout\":{\"type\":\"default\"}} -->
<main class=\"wp-block-group alignfull\">
  <!-- wp:post-content {\"align\":\"full\",\"layout\":{\"type\":\"default\"}} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {\"slug\":\"footer\",\"area\":\"footer\",\"tagName\":\"footer\",\"theme\":\"twentytwentyfour\"} /-->"""

NAVIGATION_RAW = """<!-- wp:navigation-link {\"label\":\"Home\",\"type\":\"page\",\"id\":4701,\"url\":\"/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"Features\",\"type\":\"page\",\"id\":2989,\"url\":\"/poolside-amenity-unit/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"Gallery\",\"type\":\"page\",\"id\":5223,\"url\":\"/cabana-installation-photos/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"Videos\",\"type\":\"page\",\"id\":5285,\"url\":\"/loungenie-videos/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"About\",\"type\":\"page\",\"id\":4862,\"url\":\"/hospitality-innovation/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"Contact\",\"type\":\"page\",\"id\":5139,\"url\":\"/contact-loungenie/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-submenu {\"label\":\"Investors\",\"url\":\"/investors/\",\"kind\":\"custom\",\"isTopLevelItem\":true} -->
<ul class=\"wp-block-navigation-submenu\"><!-- wp:navigation-link {\"label\":\"Investor Relations\",\"type\":\"page\",\"id\":5668,\"url\":\"/investors/\",\"kind\":\"post-type\",\"isTopLevelLink\":false} /-->
<!-- wp:navigation-link {\"label\":\"Board\",\"type\":\"page\",\"id\":5651,\"url\":\"/board/\",\"kind\":\"post-type\",\"isTopLevelLink\":false} /-->
<!-- wp:navigation-link {\"label\":\"Financials\",\"type\":\"page\",\"id\":5686,\"url\":\"/financials/\",\"kind\":\"post-type\",\"isTopLevelLink\":false} /-->
<!-- wp:navigation-link {\"label\":\"Press\",\"type\":\"page\",\"id\":5716,\"url\":\"/press/\",\"kind\":\"post-type\",\"isTopLevelLink\":false} /--></ul>
<!-- /wp:navigation-submenu -->"""

HOME = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-hero"><div class="lg9-hero-bg"><img src="{IMG['hero']}" alt="LounGenie at a premium resort"></div><div class="lg9-hero-overlay"></div><div class="lg9-hero-inner"><div class="lg9-shell"><div class="lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker">Smart Cabana Revenue Product</p><h1 class="lg9-title" style="color:#fff;margin:8px 0 18px;max-width:760px;">Transform premium seating into a stronger guest experience and a higher-yield revenue zone.</h1><p class="lg9-hero-lead" style="color:rgba(255,255,255,.92);margin:0 0 28px;">LounGenie combines ordering, secure storage, charging, and comfort into one modern hospitality Product, helping operators capture more demand without adding friction to the guest experience.</p><div class="lg9-btns"><a class="lg9-btn-primary" href="{ROOT}/index.php/contact-loungenie/">Schedule a Demo</a><a class="lg9-btn-secondary" href="{ROOT}/index.php/poolside-amenity-unit/">Explore Features</a></div><div class="lg9-hero-trust"><span class="lg9-hero-trust-item">$0 Upfront</span><span class="lg9-hero-trust-item">Revenue Share Model</span><span class="lg9-hero-trust-item">Built in Canada</span></div></div><div><div class="lg9-stat-panel"><div class="lg9-media" style="aspect-ratio:4/3;margin-bottom:16px;"><img src="{IMG['hero2']}" alt="LounGenie installed in a premium cabana"></div><div class="lg9-stat-big">Up to 30%</div><p style="margin:8px 0 0;color:#243447;font-weight:700;line-height:1.6;">Increase in poolside food and beverage revenue potential.</p></div></div></div></div></div></section><section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div style="text-align:center;max-width:860px;margin:0 auto 26px;"><p class="lg9-kicker">Trusted By Hospitality Leaders</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Designed to belong in premium hospitality environments.</h2></div><div class="lg9-logo-strip"><img src="{IMG['logo']}" alt="LounGenie"><img src="{IMG['ritz']}" alt="Ritz Carlton"><img src="{IMG['marriott']}" alt="Marriott"><img src="{IMG['partner1']}" alt="Hilton"><img src="{IMG['partner2']}" alt="Westin"><img src="{IMG['niagara']}" alt="Niagara Falls"></div></div></section><section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker">The Core Problem</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Premium seating should do more than look expensive.</h2><p class="lg9-copy">Guests leave to charge phones, delay orders when service feels slow, and shorten their stay when valuables feel exposed. Those friction points suppress both satisfaction and spend.</p><div style="display:grid;gap:14px;margin-top:22px;"><div class="lg9-card" style="padding:18px 20px;"><strong style="display:block;margin-bottom:6px;">Guests leave the deck</strong><span class="lg9-copy" style="font-size:15px;">Charging and storage friction reduces dwell time and spend.</span></div><div class="lg9-card" style="padding:18px 20px;"><strong style="display:block;margin-bottom:6px;">Ordering stalls</strong><span class="lg9-copy" style="font-size:15px;">When ordering is inconvenient, conversion drops and checks shrink.</span></div><div class="lg9-card" style="padding:18px 20px;"><strong style="display:block;margin-bottom:6px;">High-value seats underperform</strong><span class="lg9-copy" style="font-size:15px;">Your best real estate should be your strongest revenue zone.</span></div></div></div><div class="lg9-media" style="aspect-ratio:16/12;"><img src="{IMG['hero3']}" alt="Daybed area with LounGenie"></div></div></div></section><section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div style="max-width:760px;margin:0 auto 28px;text-align:center;"><p class="lg9-kicker">How It Works</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">One Product. Four guest benefits that support stronger conversion.</h2></div><div class="lg9-grid-3"><div class="lg9-card" style="padding:26px;"><p class="lg9-kicker" style="margin:0 0 8px;">Order</p><h3 style="font-size:1.35rem;margin:0 0 10px;">Capture intent at the seat.</h3><p class="lg9-copy">QR ordering and service interaction reduce time between guest intent and completed purchase.</p></div><div class="lg9-card" style="padding:26px;"><p class="lg9-kicker" style="margin:0 0 8px;">Stash</p><h3 style="font-size:1.35rem;margin:0 0 10px;">Protect valuables poolside.</h3><p class="lg9-copy">Secure storage increases comfort and helps guests remain in high-spend areas.</p></div><div class="lg9-card" style="padding:26px;"><p class="lg9-kicker" style="margin:0 0 8px;">Charge</p><h3 style="font-size:1.35rem;margin:0 0 10px;">Keep guests powered and present.</h3><p class="lg9-copy">When devices stay charged, guests stay longer and interact more.</p></div></div><div class="lg9-card lg9-callout" style="padding:26px;margin-top:22px;"><div class="lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker" style="margin:0 0 8px;">Chill</p><h3 style="font-size:1.7rem;margin:0 0 10px;">Make every premium seat feel fully considered.</h3><p class="lg9-copy">Comfort features complete the experience and increase perceived value of every premium seat.</p></div><div class="lg9-media" style="aspect-ratio:16/10;"><img src="{IMG['grove2']}" alt="Premium poolside setup"></div></div></div></div></section><section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-grid-2" style="align-items:center;"><div class="lg9-media" style="aspect-ratio:16/11;"><img src="{IMG['hero4']}" alt="Guest journey with LounGenie"></div><div><p class="lg9-kicker">Guest Journey</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">What changes once a guest sits down?</h2><div style="display:grid;gap:18px;margin-top:18px;"><div class="lg9-step"><div class="lg9-step-num">1</div><div><strong style="display:block;margin-bottom:6px;">Secure belongings</strong><span class="lg9-copy" style="font-size:15px;">Valuables go into the waterproof safe.</span></div></div><div class="lg9-step"><div class="lg9-step-num">2</div><div><strong style="display:block;margin-bottom:6px;">Stay charged</strong><span class="lg9-copy" style="font-size:15px;">Phones and devices remain powered at the seat.</span></div></div><div class="lg9-step"><div class="lg9-step-num">3</div><div><strong style="display:block;margin-bottom:6px;">Order with less friction</strong><span class="lg9-copy" style="font-size:15px;">Food and beverage intent converts faster when action is immediate.</span></div></div><div class="lg9-step"><div class="lg9-step-num">4</div><div><strong style="display:block;margin-bottom:6px;">Stay and spend longer</strong><span class="lg9-copy" style="font-size:15px;">A better seat experience creates more on-deck time and more revenue opportunities.</span></div></div></div></div></div></div></section><section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div style="display:flex;justify-content:space-between;gap:18px;align-items:end;flex-wrap:wrap;margin-bottom:24px;"><div><p class="lg9-kicker" style="margin:0 0 8px;">Installations</p><h2 class="lg9-title-md" style="margin:0;">Real deployment imagery from active properties.</h2></div><a href="{ROOT}/index.php/cabana-installation-photos/" style="color:var(--lg-blue);font-weight:800;">View gallery</a></div><div class="lg9-gallery"><div class="lg9-media"><img src="{IMG['lifestyle1']}" alt="Poolside lifestyle scene"></div><div class="lg9-media"><img src="{IMG['lifestyle2']}" alt="Guest seating with LounGenie"></div><div class="lg9-media"><img src="{IMG['lifestyle3']}" alt="Cabana revenue zone"></div></div></div></section><section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-card lg9-callout" style="padding:34px 32px;background:linear-gradient(135deg,#0d1b2a,#123559 55%,#0055a5);border:none;"><div class="lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker" style="color:#8ad8ff;margin:0 0 10px;">Commercial Model</p><h2 class="lg9-title-md" style="color:#fff;margin:0 0 14px;">We handle shipping, installation, training, and servicing. You keep the upside.</h2><p style="color:rgba(255,255,255,.84);line-height:1.85;margin:0;">The rollout is built for fast operational adoption, premium presentation, and durable revenue impact without upfront capital friction.</p></div><div style="text-align:right;"><a class="lg9-btn-primary" href="{ROOT}/index.php/contact-loungenie/">Talk to LounGenie</a></div></div></div></div></section></div><!-- /wp:html -->"""

HOME_EXTRA = ""  # Content is in home.html template
_DEPRECATED_HOME_EXTRA = f"""
<section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div style="max-width:860px;margin:0 auto 24px;text-align:center;"><p class="lg9-kicker">One System. Every Venue Type.</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Built for hotels, resorts, waterparks, cruise lines, surf parks, country clubs, and municipal aquatic centers.</h2><p class="lg9-copy">From 10 cabanas to 500 premium seating areas, the modular Product scales while keeping the guest experience consistent and premium.</p></div><div class="lg9-grid-3"><div class="lg9-card" style="padding:24px;"><h3 style="margin:0 0 8px;font-size:1.2rem;">Hotels + Resorts</h3><p class="lg9-copy">Support premium daybeds, cabanas, and reserved lounge zones with less service friction.</p></div><div class="lg9-card" style="padding:24px;"><h3 style="margin:0 0 8px;font-size:1.2rem;">Waterparks + Attractions</h3><p class="lg9-copy">Handle high guest volume with better ordering flow and stronger seat-level convenience.</p></div><div class="lg9-card" style="padding:24px;"><h3 style="margin:0 0 8px;font-size:1.2rem;">Cruise + Specialty Venues</h3><p class="lg9-copy">Weather-rated units and modular deployment simplify premium outdoor operations in diverse environments.</p></div></div></div></section>
<section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-grid-2" style="align-items:start;"><div><p class="lg9-kicker">Why Properties Make More Money</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Two behavioral shifts drive stronger revenue performance.</h2><div class="lg9-card" style="padding:22px;margin-bottom:14px;"><h3 style="margin:0 0 8px;font-size:1.25rem;">Guests stay longer.</h3><p class="lg9-copy">When valuables are secure and devices stay charged, guests stop leaving the deck and ordering windows increase.</p></div><div class="lg9-card" style="padding:22px;"><h3 style="margin:0 0 8px;font-size:1.25rem;">Ordering becomes instant.</h3><p class="lg9-copy">Seat-level ordering and service interaction remove delay between purchase intent and completed order.</p></div></div><div class="lg9-card" style="padding:24px;"><p class="lg9-kicker" style="margin:0 0 10px;">Partner Feedback</p><blockquote style="margin:0;font-size:1.06rem;line-height:1.8;color:#1c2e43;">\"The LounGenie has enhanced our guest experience with secure storage, charging ports, and the F&B call button. Our guests love the convenience.\"</blockquote><p style="margin:12px 0 0;color:#4a6079;font-weight:700;">Raymond Weissert, General Manager - The Grove Waterpark and Resort</p><hr style="border:none;border-top:1px solid #dbe6ef;margin:16px 0;"><blockquote style="margin:0;font-size:1.06rem;line-height:1.8;color:#1c2e43;">\"The addition of the LounGenie to our cabanas took the experience to the next level.\"</blockquote><p style="margin:12px 0 0;color:#4a6079;font-weight:700;">Kamiya Woodard, Director of Guest Experience - Orlando World Center Marriott</p></div></div></div></section>
<section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div style="max-width:860px;margin:0 auto 24px;text-align:center;"><p class="lg9-kicker">Comparison</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Standard cabana vs. LounGenie-equipped cabana.</h2></div><div class="lg9-card" style="padding:0;overflow:hidden;"><table style="width:100%;border-collapse:collapse;"><thead><tr style="background:#eff6fd;"><th style="padding:14px;border-bottom:1px solid #dbe6ef;text-align:left;">Category</th><th style="padding:14px;border-bottom:1px solid #dbe6ef;text-align:left;">Standard</th><th style="padding:14px;border-bottom:1px solid #dbe6ef;text-align:left;">With LounGenie</th></tr></thead><tbody><tr><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Valuables</td><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Guest leaves early or worries</td><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Waterproof safe at seat</td></tr><tr><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Charging</td><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Guest leaves to recharge</td><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Solar-powered USB at seat</td></tr><tr><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Ordering</td><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">Flag server or walk to bar</td><td style="padding:12px 14px;border-bottom:1px solid #e7eef5;">F&B call button and/or QR workflow</td></tr><tr><td style="padding:12px 14px;">Commercial Model</td><td style="padding:12px 14px;">Passive furniture</td><td style="padding:12px 14px;">Revenue-focused Product, $0 upfront model</td></tr></tbody></table></div></div></section>
<section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-grid-2" style="align-items:start;"><div><p class="lg9-kicker">FAQ</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Frequently asked questions from operators.</h2><div class="lg9-card" style="padding:20px;margin-bottom:12px;"><strong>Do smart cabana units require electrical wiring?</strong><p class="lg9-copy" style="margin:8px 0 0;">No. LounGenie uses solar-powered charging and is designed for fast deployment without trenching and permits.</p></div><div class="lg9-card" style="padding:20px;margin-bottom:12px;"><strong>How do guests place orders?</strong><p class="lg9-copy" style="margin:8px 0 0;">Depending on configuration, guests can use QR ordering and/or a service communication button for staff response.</p></div><div class="lg9-card" style="padding:20px;"><strong>Who handles shipping, installation, training, and servicing?</strong><p class="lg9-copy" style="margin:8px 0 0;">Pool Safe handles shipping, on-site installation, team training, and ongoing servicing as part of the operating model.</p></div></div><div class="lg9-card" style="padding:30px;background:linear-gradient(135deg,#0e2138,#0f365a 55%,#0055a5);color:#fff;border:none;"><p class="lg9-kicker" style="color:#91ddff;margin:0 0 10px;">Ready To Start</p><h3 style="font-size:2rem;margin:0 0 12px;line-height:1.1;">Turn your pool deck into a profit center.</h3><p style="margin:0 0 18px;line-height:1.85;color:rgba(255,255,255,.82);">We assess your layout, handle shipping and installation, train your team, and help you capture incremental guest spend from day one.</p><div class="lg9-btns"><a class="lg9-btn-primary" href="{ROOT}/index.php/contact-loungenie/">Get Your Revenue Plan</a><a class="lg9-btn-secondary" href="{ROOT}/index.php/poolside-amenity-unit/">See Full Feature Set</a></div></div></div></div></section>
"""

HOME = HOME.replace("</div><!-- /wp:html -->", HOME_EXTRA + "</div><!-- /wp:html -->")

HOME_SCHEMA = f"""<!-- wp:html --><script type="application/ld+json">{{
    "@context": "https://schema.org",
    "@graph": [
        {{
            "@type": "Organization",
            "name": "LounGenie by Pool Safe Inc.",
            "url": "{ROOT}/",
            "logo": "{IMG['logo']}",
            "contactPoint": [{{
                "@type": "ContactPoint",
                "telephone": "+1-416-630-2444",
                "contactType": "sales",
                "email": "info@poolsafeinc.com",
                "areaServed": ["CA", "US"],
                "availableLanguage": ["en"]
            }}],
            "sameAs": [
                "https://www.instagram.com/poolsafeinc/",
                "https://ca.linkedin.com/company/poolsafeinc",
                "https://youtube.com/@poolsafeinc?si=r5Qb8P7rphTE83Ms"
            ]
        }},
        {{
            "@type": "Product",
            "name": "LounGenie",
            "brand": "Pool Safe Inc.",
            "description": "A premium smart cabana product with QR ordering, waterproof storage, solar USB charging, and a removable ice bucket for hotels, resorts, and waterparks.",
            "image": ["{IMG['hero']}", "{IMG['hero2']}", "{IMG['hero3']}"],
            "offers": {{
                "@type": "Offer",
                "availability": "https://schema.org/InStock",
                "url": "{ROOT}/index.php/contact-loungenie/"
            }}
        }}
    ]
}}</script><!-- /wp:html -->"""

HOME_TRUST_STRIP = f"""<div class="lg9-marquee-wrap" aria-label="Selected property partners"><div class="lg9-marquee-track"><div class="lg9-marquee-inner"><span class="lg9-logo-mark"><img src="{IMG['ritz']}" alt="Ritz-Carlton"></span><span class="lg9-logo-mark"><img src="{IMG['marriott']}" alt="Marriott"></span><span class="lg9-logo-mark"><img src="{IMG['partner1']}" alt="Hilton"></span><span class="lg9-logo-mark"><img src="{IMG['partner2']}" alt="Westin"></span><span class="lg9-logo-mark"><img src="{IMG['splash']}" alt="Splash Kingdom"></span><span class="lg9-logo-mark"><img src="{IMG['palace']}" alt="Palace Entertainment"></span><span class="lg9-logo-mark"><img src="{IMG['carnival']}" alt="Carnival Cruise"></span><span class="lg9-logo-mark"><img src="{IMG['cowabunga']}" alt="Cowabunga"></span><span class="lg9-logo-mark"><img src="{IMG['niagara']}" alt="Niagara Falls"></span><span class="lg9-logo-mark"><img src="{IMG['holiday']}" alt="Holiday Inn"></span></div><div class="lg9-marquee-inner" aria-hidden="true"><span class="lg9-logo-mark"><img src="{IMG['ritz']}" alt="Ritz-Carlton"></span><span class="lg9-logo-mark"><img src="{IMG['marriott']}" alt="Marriott"></span><span class="lg9-logo-mark"><img src="{IMG['partner1']}" alt="Hilton"></span><span class="lg9-logo-mark"><img src="{IMG['partner2']}" alt="Westin"></span><span class="lg9-logo-mark"><img src="{IMG['splash']}" alt="Splash Kingdom"></span><span class="lg9-logo-mark"><img src="{IMG['palace']}" alt="Palace Entertainment"></span><span class="lg9-logo-mark"><img src="{IMG['carnival']}" alt="Carnival Cruise"></span><span class="lg9-logo-mark"><img src="{IMG['cowabunga']}" alt="Cowabunga"></span><span class="lg9-logo-mark"><img src="{IMG['niagara']}" alt="Niagara Falls"></span><span class="lg9-logo-mark"><img src="{IMG['holiday']}" alt="Holiday Inn"></span></div></div></div><p class="lg9-copy" style="text-align:center;font-size:15px;margin:20px auto 0;max-width:820px;">Selected property partners across resort, hotel, waterpark, and premium outdoor hospitality environments.</p>"""

HOME_DEPLOYMENT_CONTEXT = """<div class="lg9-gallery-chip-row" style="margin-top:18px;"><span class="lg9-gallery-chip">Cabana interior setup</span><span class="lg9-gallery-chip">Pool deck placement</span><span class="lg9-gallery-chip">Resort seating zone</span><span class="lg9-gallery-chip">Daybed deployment</span></div><p class="lg9-gallery-note" style="margin-top:14px;">These scenes show where LounGenie lives in practice: inside cabanas, beside daybeds, and throughout premium pool deck seating where guest dwell time and food-and-beverage conversion matter most.</p>"""


def add_img_delivery_attrs(content):
        image_index = 0

        def repl(match):
                nonlocal image_index
                src = match.group(1)
                tag = match.group(0)
                lowered = src.lower()

                if "decoding=" not in tag:
                        tag = tag.replace("<img ", '<img decoding="async" ', 1)

                if image_index == 0:
                        if "fetchpriority=" not in tag:
                                tag = tag.replace("<img ", '<img fetchpriority="high" ', 1)
                        if "loading=" not in tag:
                                tag = tag.replace("<img ", '<img loading="eager" ', 1)
                        sizes = "100vw"
                        width_hint = "1600w"
                else:
                        if "loading=" not in tag:
                                tag = tag.replace("<img ", '<img loading="lazy" ', 1)
                        if any(token in lowered for token in ["logo", "ritz", "marriott", "hilton", "westin", "splash", "palace", "carnival", "cowabunga", "niagara", "holiday"]):
                                sizes = "(max-width: 780px) 32vw, 160px"
                                width_hint = "320w"
                        else:
                                sizes = "(max-width: 780px) 92vw, (max-width: 1200px) 46vw, 31vw"
                                width_hint = "1280w"

                if "srcset=" not in tag:
                        tag = tag.replace(' src="', f' srcset="{src} {width_hint}" sizes="{sizes}" src="', 1)

                image_index += 1
                return tag

        return re.sub(r'<img\b[^>]*\bsrc="([^"]+)"[^>]*>', repl, content)


def enhance_home_markup(content):
        content = HOME_SCHEMA + content
        content = content.replace(
                f'<div class="lg9-logo-strip"><img src="{IMG["logo"]}" alt="LounGenie"><img src="{IMG["ritz"]}" alt="Ritz Carlton"><img src="{IMG["marriott"]}" alt="Marriott"><img src="{IMG["partner1"]}" alt="Hilton"><img src="{IMG["partner2"]}" alt="Westin"><img src="{IMG["niagara"]}" alt="Niagara Falls"></div>',
                HOME_TRUST_STRIP,
                1,
        )
        content = content.replace(
                '</div></section><section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-card lg9-callout"',
                HOME_DEPLOYMENT_CONTEXT + '</div></section><section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-card lg9-callout"',
                1,
        )
        return add_img_delivery_attrs(content)


HOME = enhance_home_markup(HOME)

FEATURES = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-section-soft" style="padding-top:74px;padding-bottom:58px;"><div class="lg9-narrow" style="text-align:center;"><p class="lg9-kicker">Product Features</p><h1 class="lg9-title-md" style="margin:10px 0 14px;">Every feature is designed to remove friction and lift poolside performance.</h1><p class="lg9-copy">LounGenie works as one integrated Product: each capability improves the guest experience while supporting better commercial outcomes for the property.</p></div></section><section class="lg9-section" style="padding-top:0;background:#fff;"><div class="lg9-shell" style="display:flex;flex-direction:column;gap:24px;"><div class="lg9-card" style="padding:22px;"><div class="lg9-grid-2" style="align-items:center;"><div class="lg9-media" style="aspect-ratio:16/11;"><img src="{IMG['hero2']}" alt="Order feature"></div><div><span class="lg9-pill">ORDER</span><h2 class="lg9-title-md" style="font-size:2rem;margin:12px 0;">Ordering becomes immediate and intuitive.</h2><p class="lg9-copy">Instead of forcing guests to flag staff or leave the chair, LounGenie helps properties reduce the gap between “I want something” and “I placed the order.”</p><ul style="color:#455468;line-height:1.9;"><li>Supports faster conversion from intent to order</li><li>Reduces friction around premium seating</li><li>Feels consistent with a modern guest journey</li></ul></div></div></div><div class="lg9-card" style="padding:22px;"><div class="lg9-grid-2" style="align-items:center;"><div><span class="lg9-pill">STASH</span><h2 class="lg9-title-md" style="font-size:2rem;margin:12px 0;">Security helps guests stay longer and relax faster.</h2><p class="lg9-copy">Secure storage removes one of the biggest hidden reasons guests interrupt their own poolside experience.</p><ul style="color:#455468;line-height:1.9;"><li>Encourages longer stay duration</li><li>Improves peace of mind</li><li>Supports a more premium environment</li></ul></div><div class="lg9-media" style="aspect-ratio:16/11;"><img src="{IMG['hero']}" alt="Stash feature"></div></div></div><div class="lg9-card lg9-callout" style="padding:22px;"><div class="lg9-grid-2" style="align-items:center;"><div class="lg9-media" style="aspect-ratio:16/11;"><img src="{IMG['grove2']}" alt="Charge and chill"></div><div><span class="lg9-pill">CHARGE + CHILL</span><h2 class="lg9-title-md" style="font-size:2rem;margin:12px 0;">The experience feels premium because the essentials are handled.</h2><p class="lg9-copy">Power and comfort matter. When those needs are solved at the seat, the guest experience feels smoother, more premium, and more complete.</p><ul style="color:#455468;line-height:1.9;"><li>Reduces departures caused by charging needs</li><li>Supports all-day comfort</li><li>Improves premium-seat value perception</li></ul></div></div></div></div></section><section class="lg9-section-soft"><div class="lg9-shell"><div style="max-width:760px;margin:0 auto 24px;text-align:center;"><p class="lg9-kicker">Configurations</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Three ways to match the Product to the property.</h2></div><div class="lg9-grid-3"><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">Classic</p><h3 style="margin:0 0 10px;font-size:1.35rem;">Essential comfort and security</h3><p class="lg9-copy">A practical entry point for properties upgrading core poolside amenities.</p></div><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">F&amp;B Communication</p><h3 style="margin:0 0 10px;font-size:1.35rem;">Support faster staff response</h3><p class="lg9-copy">Adds a stronger service interaction model for properties focused on poolside response efficiency.</p></div><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">2.0</p><h3 style="margin:0 0 10px;font-size:1.35rem;">Maximum revenue impact</h3><p class="lg9-copy">The most complete configuration for operators looking to modernize ordering and guest convenience at the highest level.</p></div></div></div></section></div><!-- /wp:html -->"""

ABOUT = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-section" style="background:linear-gradient(135deg,#09131f,#123559 55%,#0055a5);color:#fff;"><div class="lg9-shell lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker" style="color:#8ad8ff;">About</p><h1 class="lg9-title" style="margin:10px 0 16px;color:#fff;">Built around how premium guests actually behave poolside.</h1><p class="lg9-hero-lead" style="color:rgba(255,255,255,.84);max-width:680px;">LounGenie helps hospitality operators upgrade the guest experience with a Product that feels modern, fits premium environments, and supports stronger revenue performance.</p></div><div class="lg9-media" style="aspect-ratio:16/11;"><img src="{IMG['hero3']}" alt="Modern daybed environment"></div></div></section><section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-grid-3"><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">Model</p><h3 style="margin:0 0 10px;font-size:1.3rem;">Designed for operator practicality</h3><p class="lg9-copy">The deployment model reduces adoption friction while keeping the Product standard high.</p></div><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">Experience</p><h3 style="margin:0 0 10px;font-size:1.3rem;">Premium by design</h3><p class="lg9-copy">The visual and functional system is built to feel at home in resorts, hotels, and upscale cabana environments.</p></div><div class="lg9-card" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">Results</p><h3 style="margin:0 0 10px;font-size:1.3rem;">Grounded in real operator outcomes</h3><p class="lg9-copy">The Product is positioned around measured results and practical guest behavior, not inflated claims.</p></div></div></div></section></div><!-- /wp:html -->"""
ABOUT_EXTRA = f"""
<section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div class="lg9-grid-2" style="align-items:center;margin-bottom:24px;"><div><p class="lg9-kicker">Deployment Vision</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">A hospitality product that has to look premium before it can perform premium.</h2><p class="lg9-copy">LounGenie was built for visible placement inside cabanas, daybeds, and reserved seating. That means materials, finishes, and proportions matter just as much as functionality. Operators need a unit that supports service without looking bolted on.</p><p class="lg9-copy">The result is a system that fits resort environments, handles heavy usage, and gives guests the sense that the seat itself was intentionally designed for higher-value experiences.</p></div><div class="lg9-gallery"><div class="lg9-media"><img src="{IMG['hero4']}" alt="Poolside premium seating detail"></div><div class="lg9-media"><img src="{IMG['lifestyle1']}" alt="Premium resort seating with LounGenie"></div><div class="lg9-media"><img src="{IMG['lifestyle2']}" alt="High-end outdoor hospitality installation"></div></div></div></div><div class="lg9-card lg9-callout" style="padding:26px;"><div class="lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker" style="margin:0 0 8px;">Built by PoolSafe</p><h3 style="margin:0 0 10px;font-size:1.8rem;">Zero-upfront deployment only works if support is real.</h3><p class="lg9-copy">PoolSafe handles installation, maintenance, and ongoing service because operators should not absorb operational complexity just to modernize their premium seating offer.</p></div><div class="lg9-media" style="aspect-ratio:16/10;"><img src="{IMG['grove2']}" alt="Resort-ready LounGenie deployment"></div></div></div></div></section>
"""
ABOUT = ABOUT.replace("</div><!-- /wp:html -->", ABOUT_EXTRA + "</div><!-- /wp:html -->")

CONTACT = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-hero" style="min-height:52vh;"><div class="lg9-hero-bg"><img src="{IMG['contact']}" alt="Contact LounGenie" fetchpriority="high"></div><div class="lg9-hero-overlay"></div><div class="lg9-hero-inner"><div class="lg9-shell"><p class="lg9-kicker">Contact LounGenie</p><h1 class="lg9-title-md" style="color:#fff;margin:10px 0 14px;">Find out what your property can earn.</h1><p style="color:rgba(255,255,255,.88);max-width:760px;line-height:1.82;font-size:1.04rem;">The smart cabana system for waterparks, resorts, hotels, and cruise lines. We will build your free revenue projection — personally, based on your property.</p><div class="lg9-hero-trust"><span class="lg9-hero-trust-item">Zero Upfront Cost</span><span class="lg9-hero-trust-item">PoolSafe Handles Installation</span><span class="lg9-hero-trust-item">IAAPA Brass Ring Award Recipient</span></div></div></div></section><section class="lg9-section" style="background:#fff;"><div class="lg9-shell lg9-grid-2" style="align-items:start;"><div><p class="lg9-kicker">How It Works — 4 Steps</p><h2 class="lg9-title-md" style="font-size:2rem;margin:10px 0 22px;">Simple. Personal. Fast.</h2><div style="display:grid;gap:16px;"><div class="lg9-step"><div class="lg9-step-num">1</div><div><strong style="display:block;">Tell Us About Your Property</strong><p class="lg9-copy" style="margin:6px 0 0;font-size:15px;">Share what you operate — waterpark, resort, hotel, or cruise line — and how many cabanas, daybeds, or premium seating zones you have.</p></div></div><div class="lg9-step"><div class="lg9-step-num">2</div><div><strong style="display:block;">Receive Your Free Revenue Projection</strong><p class="lg9-copy" style="margin:6px 0 0;font-size:15px;">Our team personally reviews every submission and builds a projection specific to your property layout and seating capacity. Most conversations run 20 to 30 minutes.</p></div></div><div class="lg9-step"><div class="lg9-step-num">3</div><div><strong style="display:block;">Confirm Your Rollout Plan</strong><p class="lg9-copy" style="margin:6px 0 0;font-size:15px;">We walk through placement, unit options, and program structure together.</p></div></div><div class="lg9-step"><div class="lg9-step-num">4</div><div><strong style="display:block;">We Install. You Launch.</strong><p class="lg9-copy" style="margin:6px 0 0;font-size:15px;">No upfront capital. No permits. No wiring. Our team handles full on-site installation and your guests start experiencing Day 1.</p></div></div></div><div class="lg9-card" style="padding:20px;margin-top:24px;"><h3 style="margin:0 0 10px;font-size:1.1rem;">Why Contact Before Peak Season</h3><p class="lg9-copy">Peak season installation windows fill up. The properties that move first launch on time, fully branded, and ready to capture food and beverage demand from day one. Units can be customized to match your property — any color, your logo, and branded advertising panels available.</p></div><div class="lg9-card" style="padding:20px;margin-top:14px;"><h3 style="margin:0 0 10px;font-size:1.1rem;">Contact Details</h3><p class="lg9-copy">PoolSafe Inc.<br>906 Magnetic Drive, North York, ON M3J 2C4, Canada</p><p class="lg9-copy" style="margin-top:8px;"><a href="tel:+14166302444" style="color:var(--lg-blue);font-weight:700;">416-630-2444</a> &nbsp;&middot;&nbsp; <a href="mailto:info@poolsafeinc.com" style="color:var(--lg-blue);font-weight:700;">info@poolsafeinc.com</a></p></div></div><div><div class="lg9-card lg9-callout" style="padding:28px;"><p class="lg9-kicker" style="margin:0 0 8px;">Request Demo</p><h2 style="margin:0 0 14px;font-size:1.8rem;">Start the conversation.</h2><p class="lg9-copy" style="margin-bottom:20px;">Complete the form and our team will respond within one business day with a tailored revenue projection for your property.</p><div id="lg9-hubspot-form"></div><script>(function(){{var s=document.createElement('script');s.charset='utf-8';s.src='//js.hsforms.net/forms/embed/v2.js';s.onload=function(){{hbspt.forms.create({{region:'na1',portalId:'21854204',formId:'60812333-d602-4875-bd0a-9c2b043c7d95',target:'#lg9-hubspot-form'}});}};document.head.appendChild(s);}})();</script><p class="lg9-form-note">If the form does not load, email <a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a>.</p></div></div></div></section></div><!-- /wp:html -->"""
CONTACT_EXTRA = f"""
<section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker">What We Review With You</p><h2 class="lg9-title-md" style="margin:10px 0 14px;">Every conversation is built around placement, pacing, and revenue fit.</h2><div style="display:grid;gap:14px;margin-top:18px;"><div class="lg9-card" style="padding:18px 20px;"><strong style="display:block;margin-bottom:6px;">Seat mix and density</strong><span class="lg9-copy" style="font-size:15px;">How many cabanas, daybeds, clamshells, or premium lounge positions can support the product.</span></div><div class="lg9-card" style="padding:18px 20px;"><strong style="display:block;margin-bottom:6px;">Service model</strong><span class="lg9-copy" style="font-size:15px;">Whether Classic, Service+, or 2.0 makes the most operational sense for your team.</span></div><div class="lg9-card" style="padding:18px 20px;"><strong style="display:block;margin-bottom:6px;">Branding and rollout timing</strong><span class="lg9-copy" style="font-size:15px;">Color match, logo treatment, and installation timing before your busiest season.</span></div></div></div><div class="lg9-gallery"><div class="lg9-media"><img src="{IMG['contact']}" alt="Contact page deployment image"></div><div class="lg9-media"><img src="{IMG['lifestyle2']}" alt="Premium seating deployment example"></div><div class="lg9-media"><img src="{IMG['park1']}" alt="Cabana-level guest amenity detail"></div></div></div></div></section>
"""
CONTACT = CONTACT.replace("</div><!-- /wp:html -->", CONTACT_EXTRA + "</div><!-- /wp:html -->")

VIDEOS = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-section" style="background:linear-gradient(135deg,#09131f,#123559 55%,#0055a5);color:#fff;"><div class="lg9-narrow" style="text-align:center;"><p class="lg9-kicker" style="color:#8ad8ff;">Videos</p><h1 class="lg9-title-md" style="margin:10px 0 14px;color:#fff;">Product demos and real-world installations.</h1><p style="color:rgba(255,255,255,.8);line-height:1.8;font-size:1.04rem;">This page now uses real video iframes rather than broken linked embeds.</p></div></section><section class="lg9-section-soft" style="padding-top:40px;"><div class="lg9-shell"><div class="lg9-video-grid"><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/EZ2CfBU30Ho" title="LounGenie Overview" allowfullscreen></iframe></div><div style="padding:18px 18px 20px;"><strong style="display:block;margin-bottom:6px;">LounGenie Overview</strong><span class="lg9-copy" style="font-size:15px;">A short look at the Product and how it appears poolside.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/M48NYM06JgY" title="Marriott Demo" allowfullscreen></iframe></div><div style="padding:18px 18px 20px;"><strong style="display:block;margin-bottom:6px;">Orlando World Center Marriott</strong><span class="lg9-copy" style="font-size:15px;">Installation footage and a guest-experience context.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/PhV1JVo9POI" title="The Grove Demo" allowfullscreen></iframe></div><div style="padding:18px 18px 20px;"><strong style="display:block;margin-bottom:6px;">The Grove Resort</strong><span class="lg9-copy" style="font-size:15px;">A resort example showing how the product fits premium environments.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/3Rjba7pWs_I" title="Waterpark Demo" allowfullscreen></iframe></div><div style="padding:18px 18px 20px;"><strong style="display:block;margin-bottom:6px;">Water Park Installation</strong><span class="lg9-copy" style="font-size:15px;">A more energetic deployment context with high guest traffic.</span></div></div></div></div></section></div><!-- /wp:html -->"""
VIDEOS = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-section" style="background:linear-gradient(135deg,#09131f,#123559 55%,#0055a5);color:#fff;"><div class="lg9-narrow" style="text-align:center;"><p class="lg9-kicker" style="color:#8ad8ff;">Videos</p><h1 class="lg9-title-md" style="margin:10px 0 14px;color:#fff;">See the LounGenie smart cabana system in action.</h1><p style="color:rgba(255,255,255,.82);line-height:1.82;font-size:1.04rem;">Watch the full set of LounGenie videos from resort, waterpark, and premium seating environments. This page restores the complete library and leads with a stronger featured-video presentation.</p></div></section><section class="lg9-section-soft" style="padding-top:34px;"><div class="lg9-shell" style="display:grid;gap:26px;"><div class="lg9-video-hero"><div class="lg9-video-card" style="overflow:hidden;"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/EZ2CfBU30Ho" title="LounGenie ORDER STASH CHARGE CHILL" allowfullscreen></iframe></div></div><div class="lg9-video-hero-copy"><p class="lg9-kicker" style="color:#8ad8ff;margin:0 0 8px;">Featured Video</p><h2 class="lg9-title-md" style="color:#fff;margin:0 0 12px;font-size:2.2rem;">LounGenie: ORDER, STASH, CHARGE, CHILL.</h2><p class="lg9-copy" style="margin:0;">See how the flagship LounGenie 2.0 brings together QR ordering, a waterproof safe, solar USB charging ports, and a removable ice bucket in one commercial-grade unit built for premium outdoor hospitality environments.</p><div class="lg9-video-meta"><div class="lg9-video-meta-card"><strong>Why this is featured</strong><span class="lg9-copy" style="font-size:15px;">It captures the clearest overall story of the Product in one video: guest convenience, premium presentation, and revenue-supporting functionality.</span></div><div class="lg9-video-meta-card"><strong>What operators see</strong><span class="lg9-copy" style="font-size:15px;">Ordering flow, secure storage, charging, and seat-level hospitality design working together in a live deployment context.</span></div></div><div class="lg9-btns" style="margin-top:18px;"><a class="lg9-btn-primary" href="{ROOT}/index.php/contact-loungenie/">Request Your Revenue Projection</a><a class="lg9-btn-secondary" href="{ROOT}/index.php/poolside-amenity-unit/">Explore Features</a></div></div></div><div class="lg9-video-library-head"><div><p class="lg9-kicker" style="margin:0 0 8px;">Video Library</p><h2 class="lg9-title-md" style="margin:0;">Every demo restored in one place.</h2></div><p class="lg9-copy" style="max-width:620px;margin:0;">The library is organized to show product function first, then real deployment contexts, then overview content for teams evaluating fit across different seating environments.</p></div><div class="lg9-video-grid"><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/bdVikQssTFc" title="Smarter guest ordering demo" allowfullscreen></iframe></div><div class="lg9-video-card-body"><strong>Smarter Guest Ordering</strong><span class="lg9-copy" style="font-size:15px;">See how QR ordering supports faster conversion from poolside intent to completed food and beverage orders.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/Pmvd2P8e1ew" title="Built for every setting demo" allowfullscreen></iframe></div><div class="lg9-video-card-body"><strong>Built for Every Setting</strong><span class="lg9-copy" style="font-size:15px;">A broader look at how the Product adapts to cabanas, daybeds, clamshells, and premium seating zones.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/rPOsl_9R8dk" title="Resort deployment story" allowfullscreen></iframe></div><div class="lg9-video-card-body"><strong>Resort Deployment Story</strong><span class="lg9-copy" style="font-size:15px;">A live hospitality setting focused on comfort, convenience, and premium cabana presentation.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/M48NYM06JgY" title="Premium resort guest experience demo" allowfullscreen></iframe></div><div class="lg9-video-card-body"><strong>Premium Resort Guest Experience</strong><span class="lg9-copy" style="font-size:15px;">Watch ORDER, STASH, CHARGE, and CHILL in a fully built-out resort environment.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/PhV1JVo9POI" title="Resort and waterpark operations demo" allowfullscreen></iframe></div><div class="lg9-video-card-body"><strong>Resort and Waterpark Operations</strong><span class="lg9-copy" style="font-size:15px;">A deployment example showing stronger guest convenience and better food and beverage performance.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/3Rjba7pWs_I" title="High traffic waterpark installation demo" allowfullscreen></iframe></div><div class="lg9-video-card-body"><strong>High-Traffic Waterpark Installation</strong><span class="lg9-copy" style="font-size:15px;">An energetic installation context where fast service and premium seating value both matter.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/lgYicLSTAXs" title="Executive walkthrough demo" allowfullscreen></iframe></div><div class="lg9-video-card-body"><strong>Executive Walkthrough</strong><span class="lg9-copy" style="font-size:15px;">A direct product walkthrough covering core features, customization, and the zero-upfront commercial model.</span></div></div><div class="lg9-video-card"><div class="lg9-frame"><iframe src="https://www.youtube.com/embed/66kd_Z1EldA" title="Product overview animation" allowfullscreen></iframe></div><div class="lg9-video-card-body"><strong>Product Overview Animation</strong><span class="lg9-copy" style="font-size:15px;">A concise animated explanation of the Product, including safe, charging, ice bucket, and 2.0 ordering context.</span></div></div></div><div class="lg9-card lg9-callout" style="padding:28px;background:linear-gradient(135deg,#0d1b2a,#123559 55%,#0055a5);border:none;"><div class="lg9-grid-2" style="align-items:center;"><div><p class="lg9-kicker" style="color:#8ad8ff;margin:0 0 10px;">Why watch first</p><h2 class="lg9-title-md" style="color:#fff;margin:0 0 12px;">Video shortens the evaluation cycle.</h2><p style="color:rgba(255,255,255,.84);line-height:1.82;margin:0;">These demos help operators compare deployment styles, understand guest flow, and evaluate fit before a live revenue discussion. They are the fastest way to see how the Product behaves in real premium seating environments.</p></div><div style="text-align:right;"><a class="lg9-btn-primary" href="{ROOT}/index.php/contact-loungenie/">Talk to LounGenie</a></div></div></div></div></section></div><!-- /wp:html -->"""
VIDEOS = VIDEOS.replace("Resort Deployment Story", "Active Deployment Story")
VIDEOS = VIDEOS.replace("Premium Resort Guest Experience", "Premium Guest Experience")
VIDEOS = VIDEOS.replace("Resort and Waterpark Operations", "Multi-Venue Operations")


def optimize_videos_markup(content):
    embed_index = 0

    def repl(match):
        nonlocal embed_index
        video_id = match.group(1)
        video_title = match.group(2)
        embed_index += 1

        if embed_index == 1:
            return f'<iframe loading="eager" src="https://www.youtube.com/embed/{video_id}" title="{video_title}" allowfullscreen></iframe>'

        thumb = f"https://i.ytimg.com/vi/{video_id}/hqdefault.jpg"
        watch = f"https://www.youtube.com/watch?v={video_id}"
        return (
            f'<a class="lg-video-thumb" href="{watch}" target="_blank" rel="noopener noreferrer" '
            f'aria-label="Watch {video_title}">'
            f'<img loading="lazy" decoding="async" src="{thumb}" '
            f'srcset="{thumb} 480w" sizes="(max-width: 780px) 92vw, 31vw" '
            f'alt="{video_title} thumbnail" />'
            f'<span class="lg-video-play" aria-hidden="true">&#9658;</span></a>'
        )

    return re.sub(
        r'<iframe src="https://www\.youtube\.com/embed/([A-Za-z0-9_-]+)" title="([^"]+)" allowfullscreen></iframe>',
        repl,
        content,
    )


VIDEOS = optimize_videos_markup(VIDEOS)

GALLERY = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9"><section class="lg9-section" style="padding-bottom:36px;background:#fff;"><div class="lg9-narrow" style="text-align:center;"><p class="lg9-kicker">Gallery</p><h1 class="lg9-title-md" style="margin:10px 0 14px;">Installation imagery organized by venue style, not by property name.</h1><p class="lg9-copy">This page now mirrors the clearer visual separation from the main site, but groups the work by resort settings, waterpark deployments, and close-up amenity details instead of listing company names.</p><div class="lg9-gallery-chip-row" style="justify-content:center;"><span class="lg9-gallery-chip">Resort Cabanas</span><span class="lg9-gallery-chip">Waterpark Deployment</span><span class="lg9-gallery-chip">Amenity Details</span></div></div></section><section class="lg9-section-soft" style="padding-top:18px;"><div class="lg9-shell" style="display:grid;gap:28px;"><div class="lg9-card" style="padding:24px;"><div class="lg9-gallery-caption"><p class="lg9-kicker" style="margin:0;">Resort Cabanas</p><h2 class="lg9-title-md" style="margin:0;font-size:2rem;">Premium seating environments and daybed-style installations.</h2><p class="lg9-gallery-note">A cleaner look at hospitality-led deployments where the Product blends into high-end seating zones without calling out the property itself.</p></div><div class="lg9-gallery-band"><div class="lg9-gallery-lead"><div class="lg9-media"><img src="{IMG['hero']}" alt="Oceanfront resort cabana installation"></div><div class="lg9-media"><img src="{IMG['hero2']}" alt="Private cabana with in-seat amenity unit"></div></div><div class="lg9-gallery"><div class="lg9-media"><img src="{IMG['hero4']}" alt="Resort daybed zone with installed unit"></div><div class="lg9-media"><img src="{IMG['hero3']}" alt="Premium seating area with LounGenie"></div><div class="lg9-media"><img src="{IMG['grove']}" alt="Resort cabana row"></div><div class="lg9-media"><img src="{IMG['grove2']}" alt="Comfort-focused resort seating setup"></div><div class="lg9-media"><img src="{IMG['lifestyle1']}" alt="Poolside premium guest scene"></div><div class="lg9-media"><img src="{IMG['lifestyle2']}" alt="Reserved seating environment"></div></div></div></div><div class="lg9-card" style="padding:24px;"><div class="lg9-gallery-caption"><p class="lg9-kicker" style="margin:0;">Waterpark Deployment</p><h2 class="lg9-title-md" style="margin:0;font-size:2rem;">High-volume outdoor installs organized as one visual family.</h2><p class="lg9-gallery-note">These images capture broader deployment patterns across attraction and waterpark environments without identifying the operators by name.</p></div><div class="lg9-gallery-band"><div class="lg9-gallery-lead"><div class="lg9-media"><img src="{IMG['gallery_water1']}" alt="Large-format water attraction cabana installation"></div><div class="lg9-media"><img src="{IMG['gallery_water6']}" alt="Waterpark cabana with amenity station"></div></div><div class="lg9-gallery"><div class="lg9-media"><img src="{IMG['gallery_water2']}" alt="Private rental cabana deployment"></div><div class="lg9-media"><img src="{IMG['gallery_water3']}" alt="Sunlit cabana row with LounGenie"></div><div class="lg9-media"><img src="{IMG['gallery_water4']}" alt="Attraction venue installation"></div><div class="lg9-media"><img src="{IMG['gallery_water5']}" alt="Water attraction premium seating zone"></div><div class="lg9-media"><img src="{IMG['park1']}" alt="Cabana seating with guest charging access"></div><div class="lg9-media"><img src="{IMG['park3']}" alt="Waterproof safe inside a cabana"></div></div></div></div><div class="lg9-card" style="padding:24px;"><div class="lg9-gallery-caption"><p class="lg9-kicker" style="margin:0;">Amenity Details</p><h2 class="lg9-title-md" style="margin:0;font-size:2rem;">Closer views of storage, charging, and in-cabana presentation.</h2><p class="lg9-gallery-note">This section focuses on the hardware and guest-use moments, so operators can see how the Product reads up close inside the seat environment.</p></div><div class="lg9-gallery"><div class="lg9-media"><img src="{IMG['gallery_detail1']}" alt="Amenity unit interior with removable bucket"></div><div class="lg9-media"><img src="{IMG['gallery_detail2']}" alt="Close-up of installed cabana unit"></div><div class="lg9-media"><img src="{IMG['gallery_detail3']}" alt="Detailed view of waterproof safe and amenity components"></div><div class="lg9-media"><img src="{IMG['park2']}" alt="Ice bucket and secure storage detail"></div><div class="lg9-media"><img src="{IMG['park4']}" alt="Cabana interior product detail"></div><div class="lg9-media"><img src="{IMG['gallery_water7']}" alt="Interior charging and secure storage setup"></div></div></div></div></section></div><!-- /wp:html -->"""
GALLERY = GALLERY.replace("class=\"lg9-gallery\"", "class=\"lg9-gallery lg9-gallery--mosaic\"")
GALLERY = GALLERY.replace("This page now mirrors the clearer visual separation from the main site, but groups the work by resort settings, waterpark deployments, and close-up amenity details instead of listing company names.", "This page now uses a tighter editorial layout with visual grouping by venue style and product moment, while avoiding property or company naming.")

INVESTORS = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9 ir-rich"><section class="lg9-hero" style="min-height:56vh;"><div class="lg9-hero-bg"><img src="{IMG['hero']}" alt="Investor relations hero" fetchpriority="high"></div><div class="lg9-hero-overlay" style="background:linear-gradient(112deg, rgba(5,12,24,.92) 0%, rgba(9,30,52,.86) 52%, rgba(0,75,147,.55) 100%);"></div><div class="lg9-hero-inner"><div class="lg9-shell"><p class="lg9-kicker">Investor Relations</p><h1 class="lg9-title-md" style="color:#fff;margin:10px 0 12px;max-width:860px;">Pool Safe Inc. (TSX-V: POOL)</h1><p style="color:rgba(255,255,255,.84);max-width:840px;line-height:1.8;">Corporate overview, listing details, investor contacts, and governance resources.</p><div class="lg9-btns" style="margin-top:16px;"><a class="lg9-btn-primary" href="{ROOT}/index.php/financials/">View Financials</a><a class="lg9-btn-secondary" href="{ROOT}/index.php/press/">View Press Releases</a></div></div></div></section><div class="lg9-divider"></div><section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div class="lg9-grid-2"><div class="lg9-card" style="padding:24px;"><h3 style="margin:0 0 10px;">Corporate Address</h3><p class="lg9-copy">906 Magnetic Drive, North York, ON M3J 2C4, Canada</p><h3 style="margin:16px 0 10px;">Listing</h3><p class="lg9-copy">TSX Venture Exchange<br>Symbol: POOL</p></div><div class="lg9-card" style="padding:24px;"><h3 style="margin:0 0 10px;">Advisors</h3><p class="lg9-copy">Auditors: Horizon Assurance LLP<br>Lawyers: Garfinkle Biderman LLP<br>Transfer Agent: TSX Trust Company, 200 University Ave., Suite 300, Toronto, ON M5H 4H1</p><p><a href="http://www.tsxtrust.com/" target="_blank" rel="noopener noreferrer">www.tsxtrust.com</a></p></div></div></div></section><section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-card" style="padding:26px;"><h3 style="margin:0 0 10px;">Compliance Reports</h3><ul style="margin:0;padding-left:18px;line-height:1.9;"><li><a href="https://21854204.fs1.hubspotusercontent-na1.net/hubfs/21854204/PSI%20Disclosure%20%26%20Confidentiality%20Policy%20-03-31-2024.pdf" target="_blank" rel="noopener noreferrer">Pool Safe Inc. Disclosure and Confidentiality Policy</a></li><li><a href="{ROOT}/wp-content/uploads/2026/02/2025-Report-on-Fighting-Against-Forced-Labour-Pool-Safe-Inc.pdf" target="_blank" rel="noopener noreferrer">Fighting Against Forced Labour and Child Labour Report</a></li></ul><h3 style="margin:20px 0 10px;">Investor Contact</h3><p class="lg9-copy">Email: <a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a><br>Phone: <a href="tel:+14166302444">+1 (416) 630-2444</a><br>Public filings: <a href="http://www.sedar.com/" target="_blank" rel="noopener noreferrer">www.sedar.com</a></p></div></div></section></div><!-- /wp:html -->"""

BOARD = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9 ir-rich"><section class="lg9-hero" style="min-height:50vh;"><div class="lg9-hero-bg"><img src="{IMG['boardhero']}" alt="Board governance" fetchpriority="high"></div><div class="lg9-hero-overlay" style="background:linear-gradient(112deg, rgba(6,13,25,.93) 0%, rgba(8,29,49,.86) 52%, rgba(0,75,147,.52) 100%);"></div><div class="lg9-hero-inner"><div class="lg9-shell"><p class="lg9-kicker">Governance</p><h1 class="lg9-title-md" style="color:#fff;margin:10px 0 12px;">Board of Directors</h1><p style="color:rgba(255,255,255,.9);max-width:780px;line-height:1.8;">Restored board roster and governance references.</p></div></div></section><section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div class="lg9-card" style="padding:26px;"><h3 style="margin:0 0 12px;">Meet the LounGenie Leadership Team</h3><div class="lg9-grid-3"><div class="lg9-card" style="padding:18px;"><h4 style="margin:0 0 6px;">David Berger</h4><p class="lg9-copy" style="margin:0;">CEO</p></div><div class="lg9-card" style="padding:18px;"><h4 style="margin:0 0 6px;">Steven Glaser</h4><p class="lg9-copy" style="margin:0;">COO, CFO &amp; Director</p></div><div class="lg9-card" style="padding:18px;"><h4 style="margin:0 0 6px;">Steven Mintz</h4><p class="lg9-copy" style="margin:0;">Director</p></div><div class="lg9-card" style="padding:18px;"><h4 style="margin:0 0 6px;">Gillian Deacon</h4><p class="lg9-copy" style="margin:0;">Marketing Executive</p></div><div class="lg9-card" style="padding:18px;"><h4 style="margin:0 0 6px;">Robert Pratt</h4><p class="lg9-copy" style="margin:0;">Director</p></div></div><p class="lg9-copy" style="margin:16px 0 0;">Source page: <a href="https://www.loungenie.com/board/" target="_blank" rel="noopener noreferrer">www.loungenie.com/board/</a></p></div></div></section><section class="lg9-section-soft"><div class="lg9-shell"><div class="lg9-card" style="padding:26px;"><h3 style="margin:0 0 12px;">Board and Governance Inquiries</h3><p class="lg9-copy" style="margin:0;">Email: <a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a><br>Phone: <a href="tel:+14166302444">+1 (416) 630-2444</a></p></div></div></section></div><!-- /wp:html -->"""

FINANCIALS = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9 ir-rich"><section class="lg9-hero" style="min-height:50vh;"><div class="lg9-hero-bg"><img src="{IMG['financehero']}" alt="Financial reporting" fetchpriority="high"></div><div class="lg9-hero-overlay" style="background:linear-gradient(112deg, rgba(5,12,24,.93) 0%, rgba(8,30,50,.86) 52%, rgba(0,75,147,.52) 100%);"></div><div class="lg9-hero-inner"><div class="lg9-shell"><p class="lg9-kicker">Reporting</p><h1 class="lg9-title-md" style="color:#fff;margin:10px 0 12px;">Financial Information</h1><p style="color:rgba(255,255,255,.9);max-width:780px;line-height:1.8;">Restored key filings and direct archive links.</p></div></div></section><section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div class="lg9-card" style="padding:26px;"><h3 style="margin:0 0 12px;">Core Filing Links</h3><ul style="margin:0;padding-left:20px;line-height:1.9;"><li><a href="https://www.loungenie.com/wp-content/uploads/2026/03/Pool-Safe-Notice-of-Meeting-Combined-with-MIC.pdf" target="_blank" rel="noopener noreferrer">Notice of Meeting &amp; Management Info Circular</a></li><li><a href="https://www.loungenie.com/wp-content/uploads/2026/03/Pool-Safe-Form-of-Proxy_Common-Shares-Final.pdf" target="_blank" rel="noopener noreferrer">Form of Proxy</a></li><li><a href="https://loungenie.com/wp-content/uploads/2025/12/Pool-Safe-Financials-Q3-2025-Sedar-1.pdf" target="_blank" rel="noopener noreferrer">Financials - September 30, 2025</a></li><li><a href="https://loungenie.com/wp-content/uploads/2025/12/Pool-Safe-MDA-Q3-2025-Sedar-1.pdf" target="_blank" rel="noopener noreferrer">MD&amp;A - September 30, 2025</a></li><li><a href="https://loungenie.com/wp-content/uploads/2025/10/Pool-Safe-Financials-Q2-2025-Sedar.pdf" target="_blank" rel="noopener noreferrer">Financials - June 30, 2025</a></li><li><a href="https://loungenie.com/wp-content/uploads/2025/10/Pool-Safe-MDA-Q1-2025-Sedar.pdf" target="_blank" rel="noopener noreferrer">MD&amp;A - March 31, 2025</a></li></ul><p class="lg9-copy" style="margin:14px 0 0;">Full historical filing archive: <a href="https://www.loungenie.com/financials/" target="_blank" rel="noopener noreferrer">www.loungenie.com/financials/</a></p><p class="lg9-copy" style="margin:8px 0 0;">SEDAR+: <a href="https://www.sedarplus.ca/" target="_blank" rel="noopener noreferrer">www.sedarplus.ca</a></p></div></div></section></div><!-- /wp:html -->"""
FINANCIALS = FINANCIALS.replace("<p class=\"lg9-kicker\">Reporting</p>", "<p class=\"lg9-kicker\">the LounGenie</p>")
FINANCIALS = FINANCIALS.replace("Financial Information", "the LounGenie Financials")
FINANCIALS = FINANCIALS.replace("Restored key filings and direct archive links.", "Official filings, reporting documents, and archive pathways for investors.")

PRESS = f"""<!-- wp:html -->{GLOBAL_STYLE}<div class="lg9 ir-rich"><section class="lg9-hero" style="min-height:50vh;"><div class="lg9-hero-bg"><img src="{IMG['presshero']}" alt="Press and media" fetchpriority="high"></div><div class="lg9-hero-overlay" style="background:linear-gradient(112deg, rgba(5,12,24,.93) 0%, rgba(8,30,50,.86) 52%, rgba(0,75,147,.52) 100%);"></div><div class="lg9-hero-inner"><div class="lg9-shell"><p class="lg9-kicker">Press</p><h1 class="lg9-title-md" style="color:#fff;margin:10px 0 12px;">LounGenie News and Press Releases</h1><p style="color:rgba(255,255,255,.9);max-width:780px;line-height:1.8;">Restored release links and archive access.</p></div></div></section><section class="lg9-section" style="background:#fff;"><div class="lg9-shell"><div class="lg9-card" style="padding:26px;"><h3 style="margin:0 0 12px;">Recent Releases</h3><ul style="margin:0;padding-left:20px;line-height:1.9;"><li><a href="https://www.loungenie.com/wp-content/uploads/2026/03/David-Deacon-Joins-PSI-Board-Final-03-02-2026.pdf" target="_blank" rel="noopener noreferrer">POOL SAFE ADDS INDUSTRIALIST DAVID DEACON AS EXECUTIVE CHAIRMAN</a></li><li><a href="https://loungenie.com/wp-content/uploads/2026/02/PSI-Announces-Closing-of-Sr.-Secured-Deb-Extensions-Final-12-31-2025.pdf" target="_blank" rel="noopener noreferrer">POOL SAFE ANNOUNCES DEBENTURE AND BONUS WARRANTS EXTENSIONS</a></li><li><a href="https://loungenie.com/wp-content/uploads/2025/12/Pool-Safe-Credit-Amendment-PP-News-Release-Sedar-06-02-2025.pdf" target="_blank" rel="noopener noreferrer">POOL SAFE ANNOUNCING AMENDMENT TO LINE OF CREDIT AND ISSUANCE OF BONUS WARRANTS</a></li><li><a href="https://loungenie.com/wp-content/uploads/2025/12/PSI-Repays-Promissory-Note-Debentures-05-23-2025.pdf" target="_blank" rel="noopener noreferrer">POOL SAFE ANNOUNCES REPAYMENT OF PROMISSORY NOTE AND RETIREMENT OF NON-CONVERTIBLE DEBENTURES</a></li><li><a href="https://loungenie.com/wp-content/uploads/2025/12/PSI-Announces-Short-Term-Promissory-Note-Final-03-19-2025.pdf" target="_blank" rel="noopener noreferrer">POOL SAFE ANNOUNCES ISSUANCE OF SHORT-TERM PROMISSORY NOTE</a></li><li><a href="https://loungenie.com/wp-content/uploads/2025/12/PSI-Announces-Auditors-Appointment-Final-02-25-2025.pdf" target="_blank" rel="noopener noreferrer">POOL SAFE ANNOUNCES APPOINTMENT OF SUCCESSOR AUDITOR</a></li><li><a href="https://loungenie.com/wp-content/uploads/2025/12/PSI-Announces-Retirement-of-Debentures_Warrant-Exercise-12-30-2024-3.pdf" target="_blank" rel="noopener noreferrer">POOL SAFE ANNOUNCES RETIREMENT OF NON-CONVERTIBLE DEBENTURES</a></li><li><a href="https://loungenie.com/wp-content/uploads/2025/12/PSI-Announces-Debenture-Warrant-Extension-Final-12-19-2024-1.pdf" target="_blank" rel="noopener noreferrer">POOL SAFE ANNOUNCES DEBENTURE AND BONUS WARRANTS EXPIRY EXTENSION</a></li><li><a href="https://loungenie.com/wp-content/uploads/2025/12/PSI-Announces-Master-Service-Agmt-final-03-05-2024-2.pdf" target="_blank" rel="noopener noreferrer">POOL SAFE ANNOUNCES SIGNING OF MASTER SERVICE AGREEMENT</a></li><li><a href="https://loungenie.com/wp-content/uploads/2025/12/PSI-Named-Approved-Supplier-with-Avendra-Final-07-26-2023.pdf" target="_blank" rel="noopener noreferrer">POOL SAFE AND AVENDRA PARTNER TO SUPPLY LOUNGENIE UNITS</a></li></ul><p class="lg9-copy" style="margin:14px 0 0;">Complete press archive (all releases): <a href="https://www.loungenie.com/press/" target="_blank" rel="noopener noreferrer">www.loungenie.com/press/</a></p></div></div></section></div><!-- /wp:html -->"""

# Keep the richer inline v12 sales-page layouts rather than overriding them with the simpler template files.

PAGE_SEO = {
    4701: {"title": "LounGenie | Smart Poolside Revenue & Guest Experience Platform", "excerpt": "LounGenie helps hotels, resorts, and waterparks increase poolside revenue with QR ordering, secure storage, charging, and premium guest seating amenities.", "content": HOME},
    2989: {"title": "LounGenie Features | ORDER, STASH, CHARGE, CHILL", "excerpt": "Explore LounGenie features including QR ordering, waterproof storage, solar charging, and removable cooling built for premium outdoor hospitality seating.", "content": FEATURES},
    4862: {"title": "About LounGenie | Hospitality Technology for Premium Seating", "excerpt": "Learn how LounGenie combines guest convenience and operator revenue performance in one commercial-grade hospitality product.", "content": ABOUT},
    5139: {"title": "Contact LounGenie | Request a Poolside Revenue Demo", "excerpt": "Contact LounGenie to request a demo, discuss deployment timing, and review projected revenue impact for your property.", "content": CONTACT},
    5285: {"title": "LounGenie Videos | Product Demos, Resort & Waterpark Installations", "excerpt": "Watch LounGenie videos and real installation footage across resort and waterpark environments to evaluate product fit and guest experience impact.", "content": VIDEOS},
    5223: {"title": "LounGenie Gallery | Cabana, Daybed & Waterpark Installation Photos", "excerpt": "Browse LounGenie installation photos across cabanas, daybeds, and waterpark environments with detailed views of real premium seating deployments.", "content": GALLERY},
    5668: {"title": "LounGenie Investor Relations | Pool Safe Inc. (TSX-V: POOL)", "excerpt": "Access LounGenie and Pool Safe investor relations information, listing details, governance links, and investor contact resources.", "content": INVESTORS},
    5651: {"title": "LounGenie Board of Directors | Pool Safe Inc. Governance", "excerpt": "Review Pool Safe and LounGenie board leadership, governance context, and investor inquiry pathways.", "content": BOARD},
    5686: {"title": "LounGenie Financials | Pool Safe Investor Filings", "excerpt": "Access official LounGenie and Pool Safe financial filings, MD&A links, and investor reporting resources.", "content": FINANCIALS},
    5716: {"title": "LounGenie Press Releases | Pool Safe News & Announcements", "excerpt": "Read official LounGenie and Pool Safe press releases, partnership announcements, and investor-facing company updates.", "content": PRESS},
}

PAGE_SEO = {page_id: payload for page_id, payload in PAGE_SEO.items() if page_id in CORE_PAGE_IDS}

SOURCE_SYNC_PAGES = {
    5668: "investors",
    5651: "board",
    5686: "financials",
    5716: "press",
}

def get_source_sync_pages(include_investor_pages: bool):
    return SOURCE_SYNC_PAGES if include_investor_pages else {}

INVESTOR_TEMPLATE_META = {
    5668: {
        "template": "investors-shell.html",
        "kicker": "Investor Relations",
        "title": "Pool Safe Inc. (TSX-V: POOL)",
        "subtitle": "Corporate profile, filings, governance, and investor resources in one modern investor experience.",
        "hero_img": IMG["hero"],
    },
    5651: {
        "template": "board-shell.html",
        "kicker": "Governance",
        "title": "Board of Directors",
        "subtitle": "Leadership, governance structure, and board resources for current and prospective investors.",
        "hero_img": IMG["boardhero"],
    },
    5686: {
        "template": "financials-shell.html",
        "kicker": "Financial Reporting",
        "title": "Financials",
        "subtitle": "Official filings, financial statements, and reporting references with direct access links.",
        "hero_img": IMG["financehero"],
    },
    5716: {
        "template": "press-shell.html",
        "kicker": "Press Releases",
        "title": "Press and News",
        "subtitle": "Company announcements, release history, and media updates for investor visibility.",
        "hero_img": IMG["presshero"],
    },
}

INVESTOR_REQUIRED_BLOCK = """<!-- wp:group {\"className\":\"ir-required-block\",\"layout\":{\"type\":\"constrained\"}} -->
<div class=\"wp-block-group ir-required-block\">\n<!-- wp:heading {\"level\":2} -->
<h2 class=\"wp-block-heading\">Corporate Snapshot</h2>
<!-- /wp:heading -->\n\n<!-- wp:columns -->
<div class=\"wp-block-columns\"><!-- wp:column -->
<div class=\"wp-block-column\"><!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">CORPORATE ADDRESS</h3>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p>906 Magnetic Drive<br>North York, ON, M3J 2C4<br>Canada</p>
<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">LISTING</h3>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p>TSX Venture Exchange</p>
<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">SYMBOL</h3>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p>POOL</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->\n\n<!-- wp:column -->
<div class=\"wp-block-column\"><!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">AUDITORS</h3>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p>Horizon Assurance LLP.</p>
<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">LAWYERS</h3>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p>Garfinkle Biderman LLP</p>
<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">TRANSFER AGENT</h3>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p>TSX TRUST COMPANY<br>200 University Ave., Suite 300<br>Toronto, ON, M5H 4H1<br><a href=\"https://www.tsxtrust.com\" target=\"_blank\" rel=\"noopener noreferrer\">www.tsxtrust.com</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">Compliance and Governance</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Pool Safe Inc. Compliance Report</li><li>Pool Safe Inc. Disclosure and Confidentiality Policy</li><li>Pool Safe Inc. Fighting Against Forced Labour, Child Labour Report</li></ul>
<!-- /wp:list -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">INVESTOR RELATIONS CONTACT</h3>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p>Email: <a href=\"mailto:info@poolsafeinc.com\">info@poolsafeinc.com</a><br>Phone: <a href=\"tel:+14166302444\">1+ (416) 630-2444</a></p>
<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->
<p>View Pool Safe's public filings on SEDAR at <a href=\"https://www.sedar.com\" target=\"_blank\" rel=\"noopener noreferrer\">www.sedar.com</a>.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->\n\n"""

BOARD_REQUIRED_BLOCK = """<!-- wp:group {\"className\":\"ir-required-block ir-board-required\",\"layout\":{\"type\":\"constrained\"}} -->
<div class=\"wp-block-group ir-required-block ir-board-required\">\n<!-- wp:heading {\"level\":2} -->
<h2 class=\"wp-block-heading\">Board of Directors</h2>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p>This leadership team content is required and should never be removed from the Board page.</p>
<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">David Berger</h3>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p><strong>CEO</strong></p>
<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->
<p>Mr. David Berger is Executive Chairman and CEO, Pool Safe Inc. Mr. Berger was formerly the Director of Operations of Kiddie Ride Entertainment Limited, a company he founded to create fun and exciting amusement rides for children, located in shopping malls across southern Ontario. Prior to Kiddie Ride, Mr. Berger held the position of Managing Director at Jodami Enterprises Limited, an Engineering company that provided plumbing and electrical supplies across the Greater Toronto Area.</p>
<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">Steven Glaser</h3>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p><strong>COO, CFO &amp; Director</strong></p>
<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->
<p>Mr. Steven Glaser is Chief Operating Officer, Chief Financial Officer and Director, Pool Safe Inc. Mr. Glaser is a financial service executive with a diverse background in corporate finance, communications and governance for private and public companies. He spent the last eight years working in the corporate finance and investment banking arena focused on assisting late stage private and early stage public companies with strategic planning and capital raising. Prior to that, Mr. Glaser spent seven years as Vice President Corporate Affairs of Azure Dynamics Corporation. He was responsible for the company's corporate governance, its domestic and international stock exchange listings, as well as the build-out of the company's Investor Relations division. Mr. Glaser holds a Bachelor of Administrative Studies degree as well as an M.B.A. in finance.</p>
<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">Steven Mintz</h3>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p><strong>Director</strong></p>
<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->
<p>Mr. Steven Mintz is Director, Pool Safe Inc. Mr. Mintz is a graduate from the University of Toronto and obtained his C.A. designation in June of 1992. Between 1992 and 1997, Mr. Mintz was employed by a boutique bankruptcy and insolvency firm. He obtained his Trustee in Bankruptcy license in 1995. Since January 1997 he has been a self-employed financial consultant, serving private individuals and companies as well as public companies in a variety of industries, including mining, oil and gas, real estate and investment strategies. He is currently a director of 22 Capital Corp, Everton Resources Inc., Mooncor Oil and Gas Corp. and Portage Biotech Inc.</p>
<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">Gillian Deacon</h3>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p><strong>Marketing Executive</strong></p>
<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->
<p>Ms. Deacon brings over 15 years of integrated marketing experience across brand, experiential, partnership and content marketing. Ms. Deacon is currently the Vice President of Partnership Marketing for the Arizona Cardinals Football Club overseeing the day-to-day operations and direct supervision to the corporate partnership activation and service staff and all related functions. Prior to joining the Cardinals, Ms. Deacon was located in New York City as the Vice President, Solutions and Operations at Oak View Group (Nov 2020 - April 2025), the largest developer of sports and entertainment facilities in the world, with over $5 billion committed spend on new arena developments in various prime global locales. Leading to OVG, Ms. Deacon drove the initial formation and further growth, and development of the Wasserman Experience division in Canada, an industry leading global sports, entertainment and lifestyle company working with some of the world's most iconic brands, properties and talent.</p>
<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">Robert Pratt</h3>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p><strong>Director</strong></p>
<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->
<p>Mr. Pratt is currently the President of R. Pratt Consulting Ltd. From 2018-2024 he was President and Chief Operating Officer at Sandman Hotel Group and Sutton Place Hotels, a Canadian hotel chain. From October 2015 until July 2018, Mr. Pratt was the President of One Lodging Management, responsible for the day-to-day operations of 119 properties.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->\n\n"""

FINANCIALS_REQUIRED_BLOCK = """<!-- wp:group {\"className\":\"ir-required-block ir-financials-required\",\"layout\":{\"type\":\"constrained\"}} -->
<div class=\"wp-block-group ir-required-block ir-financials-required\">\n<!-- wp:heading {\"level\":2} -->
<h2 class=\"wp-block-heading\">Required Filing Index</h2>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p>The filing list below is required and must always remain visible on this page.</p>
<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">2026 Special Meeting of Shareholders</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Pool Safe AGM Material</li><li>Notice of Meeting &amp; Management Info Circular</li><li>Form of Proxy</li></ul>
<!-- /wp:list -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">2025 Financial Reports</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Q3 2025 - Financials - September 30, 2025; MD&amp;A - September 30, 2025</li><li>Q2 2025 - Financials - June 30, 2025; MD&amp;A - June 30, 2025</li><li>Q1 2025 - Financials - March 31, 2025; MD&amp;A - March 31, 2025</li></ul>
<!-- /wp:list -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">2025 Annual General Meeting</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Pool Safe AGM Material</li><li>Notice of Meeting</li><li>Management Info Circular</li><li>Form of Proxy</li><li>Request Form</li></ul>
<!-- /wp:list -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">2024 Financial Reports</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Q4 YE 2024 - Financials - December 31, 2024; MD&amp;A - December 31, 2024</li><li>Q3 2024 - Financials - September 30, 2024; MD&amp;A - September 30, 2024</li><li>Q2 2024 - Financials - June 30, 2024; MD&amp;A - June 30, 2024</li><li>Q1 2024 - Financials - March 31, 2024; MD&amp;A - March 31, 2024</li></ul>
<!-- /wp:list -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">2023 Financial Reports</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Q4 YE 2023 - Financials - December 31, 2023; MD&amp;A - December 31, 2023</li><li>Q3 2023 - Financials - September 30, 2023; MD&amp;A - September 30, 2023</li><li>Q2 2023 - Financials - June 30, 2023; MD&amp;A - June 30, 2023</li><li>Q1 2023 - Financials - March 31, 2023; MD&amp;A - March 31, 2023</li></ul>
<!-- /wp:list -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">2022 Financial Reports</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Q4 YE 2022 - Financials - December 31, 2022; MD&amp;A - December 31, 2022</li><li>Q3 2022 - Financials - September 30, 2022; MD&amp;A - September 30, 2022</li><li>Q2 2022 - Financials - June 30, 2022; MD&amp;A - June 30, 2022</li><li>Q1 2022 - Financials - March 31, 2022; MD&amp;A - March 31, 2022</li></ul>
<!-- /wp:list -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">2021 Financial Reports</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Q4 YE 2021 - Financials - December 31, 2021; MD&amp;A - December 31, 2021</li><li>Q3 2021 - Financials - September 30, 2021; MD&amp;A - September 30, 2021</li><li>Q2 2021 - Financials - June 30, 2021; MD&amp;A - June 30, 2021</li><li>Q1 2021 - Financials - March 31, 2021; MD&amp;A - March 31, 2021</li></ul>
<!-- /wp:list -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">2020 Financial Reports</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Q4 YE 2020 - Financials - December 31, 2020; MD&amp;A - December 31, 2020</li><li>Q3 2020 - Financials - September 30, 2020; MD&amp;A - September 30, 2020</li><li>Q2 2020 - Financials - June 30, 2020; MD&amp;A - June 30, 2020</li><li>Q1 2020 - Financials - March 31, 2020; MD&amp;A - March 31, 2020</li></ul>
<!-- /wp:list -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">2019 Financial Reports</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Q4 YE 2019 - Financials - December 31, 2019; MD&amp;A - December 31, 2019</li><li>Q3 2019 - Financials - September 30, 2019; MD&amp;A - September 30, 2019</li><li>Q2 2019 - Financials - June 30, 2019; MD&amp;A - June 30, 2019</li><li>Q1 2019 - Financials - March 31, 2019; MD&amp;A - March 31, 2019</li></ul>
<!-- /wp:list -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">2018 Financial Reports</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Q4 YE 2018 - Financials - December 31, 2018; MD&amp;A - December 31, 2018</li><li>Q3 2018 - Financials - September 30, 2018; MD&amp;A - September 30, 2018</li><li>Q2 2018 - Financials - June 30, 2018; MD&amp;A - June 30, 2018</li><li>Q1 2018 - Financials - March 31, 2018; MD&amp;A - March 31, 2018</li></ul>
<!-- /wp:list -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">2017 Financial Reports</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Q4 YE 2017 - Financials - December 31, 2017; MD&amp;A - December 31, 2017</li><li>Q3 2017 - Financials - September 30, 2017; MD&amp;A - September 30, 2017</li><li>Q2 2017 - Financials - June 30, 2017; MD&amp;A - June 30, 2017</li><li>Q1 2017 - Financials - March 31, 2017; MD&amp;A - March 31, 2017</li></ul>
<!-- /wp:list -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">2016 Financial Reports</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>Q4 YE 2016 - Financials - December 31, 2016; MD&amp;A - December 31, 2016</li><li>Q3 2016 - Financials - September 30, 2016</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->\n\n"""

PRESS_REQUIRED_BLOCK = """<!-- wp:group {\"className\":\"ir-required-block ir-press-required\",\"layout\":{\"type\":\"constrained\"}} -->
<div class=\"wp-block-group ir-required-block ir-press-required\">\n<!-- wp:heading {\"level\":2} -->
<h2 class=\"wp-block-heading\">LounGenie News &amp; Press Releases</h2>
<!-- /wp:heading -->\n\n<!-- wp:paragraph -->
<p>Stay informed on LounGenie's newest partnerships, product launches, and corporate milestones.</p>
<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":3} -->
<h3 class=\"wp-block-heading\">Required Press Archive</h3>
<!-- /wp:heading -->\n\n<!-- wp:list -->
<ul class=\"wp-block-list\"><li>POOL SAFE ADDS INDUSTRIALIST DAVID DEACON AS EXECUTIVE CHAIRMAN</li><li>POOL SAFE ANNOUNCES DEBENTURE AND BONUS WARRANTS EXTENSIONS</li><li>POOL SAFE ANNOUNCING AMENDMENT TO LINE OF CREDIT AND ISSUANCE OF BONUS WARRANTS</li><li>POOL SAFE ANNOUNCES REPAYMENT OF PROMISSORY NOTE AND RETIREMENT OF NON-CONVERTIBLE DEBENTURES</li><li>POOL SAFE ANNOUNCES ISSUANCE OF SHORT-TERM PROMISSORY NOTE</li><li>POOL SAFE ANNOUNCES APPOINTMENT OF SUCCESSOR AUDITOR</li><li>POOL SAFE ANNOUNCES RETIREMENT OF $790,000 OF NON-CONVERTIBLE DEBENTURES</li><li>POOL SAFE ANNOUNCES DEBENTURE AND BONUS WARRANTS EXPIRY EXTENSION</li><li>POOL SAFE ANNOUNCES APPOINTMENT OF SUCCESSOR AUDITOR</li><li>POOL SAFE ANNOUNCES SIGNING OF MASTER SERVICE AGREEMENT</li><li>POOL SAFE EXTENSION OF THE SR. SECURED DEBENTURE AND BONUS WARRANTS.</li><li>POOL SAFE AND AVENDRA PARTNER TO SUPPLY LOUNGENIE UNITS</li><li>POOL SAFE CLOSES FINAL TRANCHE OF $1.14 MILLION NON-CONVERTIBLE DEBENTURE FINANCING</li><li>POOL SAFE ANNOUNCES UPSIZE ON NON-BROKERED DEBENTURE FINANCING</li><li>POOL SAFE CLOSES FIRST TRANCHE OF $1 MILLION CONVERTIBLE DEBENTURE FINANCING</li><li>POOL SAFE ANNOUNCES NON-BROKERED DEBENTURE FINANCING</li><li>POOL SAFE INC. ANNOUNCES RESULTS OF ANNUAL GENERAL AND SPECIAL MEETING OF SHAREHOLDERS</li><li>POOL SAFE INC. UNVEILS NEW NAME, LOGO AND WEBSITE FOR LOUNGENIE, A REBRAND OF ITS AWARD-WINNING POOLSAFE PRODUCT</li><li>POOL SAFE ANNOUNCES CLOSING OF DEBT CONVERSION, UPDATE ON DEBENTURE AND BONUS WARRANTS EXPIRY EXTENSION AND RSU GRANT</li><li>POOL SAFE CLOSES FINAL TRANCHE OF PREVIOUSLY ANNOUNCED NON-CONVERTIBLE DEBENTURE FINANCING</li><li>POOL SAFE CLOSES FIRST TRANCHE OF $1.5 MILLION CONVERTIBLE DEBENTURE FINANCING</li><li>POOL SAFE GAINS HOSPITALITY VETERAN AS ITS NEW EXECUTIVE CHAIRMAN AND PROVIDES CORPORATE UPDATE</li><li>POOL SAFE CONCLUDES SIGNIFICANTLY INCREASED LINE OF CREDIT AT A LOWER INTEREST RATE AND EXTENDED TERM IN ADDITION TO A NON-BROKERED DEBENTURE FINANCING</li><li>POOL SAFE ANNOUNCES CHANGES TO ITS BOARD OF DIRECTORS</li><li>POOL SAFE ANNOUNCES FINANCING AND CONVERSION OF ALL CONVERTIBLE DEBENTURES AND PROMISSORY NOTE</li><li>POOL SAFE ANNOUNCES PROPOSED AMENDMENTS TO CREDIT AGREEMENT, CONVERTIBLE DEBENTURES AND PROMISSORY NOTE</li><li>POOL SAFE PARTNERS WITH THE RAVINE WATERPARK IN CALIFORNIA</li><li>POOLSAFES NOW AVAILABLE AT NORWEGIAN CRUISE LINE HOLDINGS LTD. RESORT DESTINATION HARVEST CAYE, BELIZE</li><li>MAUI JACK'S WATERPARK PURCHASES POOLSAFE UNITS</li><li>POOL SAFE ENTERS THE FLORIDA MARKET WITH THREE REVENUE SHARE PARTNERSHIPS</li><li>POOL SAFE ANNOUNCES TRANSFORMATIVE REVOLVING CREDIT FACILITY</li><li>POOL SAFE PARTNERS WITH GLOBAL RESORT AND CASINO OPERATOR FOR LAS VEGAS PROPERTY</li><li>POOL SAFE SIGNS FIRST TWO CONTRACTS IN DUBAI</li><li>MARRIOTT GAYLORD TEXAN RESORT &amp; CONVENTION CENTER PARTNERS WITH POOL SAFE FOR THEIR VIP CABANAS</li><li>POOL SAFE ANNOUNCE INITIAL CLOSING OF PRIVATE PLACEMENT</li><li>POOL SAFE ANNOUNCES CHANGES TO ITS BOARD OF DIRECTORS</li><li>POOL SAFE ANNOUNCES FINAL CLOSE OF CONVERTIBLE DEBENTURE FINANCING</li><li>POOLSAFE PARTNERS WITH COMMERCIAL FUNDING GROUP TO PIVOT THE BUSINESS MODEL TO RECURRING REVENUE</li><li>POOL SAFE ANNOUNCES CONVERTIBLE DEBENTURE FINANCING</li><li>POOL SAFE GRANTS STOCK OPTIONS</li><li>POOL SAFE ANNOUNCES CHANGES TO ITS BOARD OF DIRECTORS</li><li>POOL SAFE AND PARTNER ALAWWAL PROPERTIES OPEN DUBAI OFFICE</li><li>POOL SAFE ANNOUNCES INITIAL CLOSING OF CONVERTIBLE DEBENTURE FINANCING</li><li>COWABUNGA BAY WATERPARK PARTNERS WITH POOL SAFE FOR ALL THEIR PRIVATE AND VIP CABANAS</li><li>POOL SAFE ANNOUNCES CONVERTIBLE DEBENTURE FINANCING</li><li>POOL SAFE INC. AND INFORMED MARKETING LLC SIGN U.S. DISTRIBUTION AGREEMENT</li><li>PSI &amp; ALAWWAL SIGN EXCLUSIVE DISTRIBUTION AGREEMENT FOR MENA.</li><li>PSI RETAINS FRONTIER (PR)</li><li>PSI GRANTS OPTIONS (PR)</li><li>PSI ENGAGES MACKIE (PR)</li><li>PSI POUNDER COMPLETE QT (PR)</li><li>PSI MENA DISTRIBUTION AGMT (PR)</li><li>PSI MNGMT &amp; BOD CHANGES(PR)</li><li>PSI POUNDER UPDATE ON QT (PR)</li></ul>
<!-- /wp:list -->\n\n<!-- wp:paragraph -->
<p><strong>Contact</strong><br>906 Magnetic Drive,<br>North York, ON<br>M3J 2C4<br>Canada<br>Phone: 416-630-2444<br>Email: Info@poolsafeinc.com</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->\n\n"""


def build_investor_shell(page_id, source_content):
    meta = INVESTOR_TEMPLATE_META.get(page_id)
    if not meta:
        return source_content

    fallback = """<!-- wp:html -->[[GLOBAL_STYLE]]<div class="lg9 ir-shell"><section class="ir-hero"><div class="ir-hero-bg"><img src="[[IR_HERO_IMAGE]]" alt="[[IR_TITLE]]" fetchpriority="high"></div><div class="ir-hero-overlay"></div><div class="ir-hero-inner"><div class="lg9-shell"><p class="lg9-kicker">[[IR_KICKER]]</p><h1>[[IR_TITLE]]</h1><p>[[IR_SUBTITLE]]</p></div></div></section><section class="ir-content-wrap"><div class="ir-content-panel"><div class="ir-toolbar"><div class="ir-toolbar-links"><a href="[[ROOT]]/index.php/investors/">Investor Relations</a><a href="[[ROOT]]/index.php/board/">Board</a><a href="[[ROOT]]/index.php/financials/">Financials</a><a href="[[ROOT]]/index.php/press/">Press</a></div><span class="ir-toolbar-note">Page shell is editable via page-templates files. Source investor content remains intact.</span></div><div class="ir-source-content">[[IR_CONTENT]]</div></div></section></div><!-- /wp:html -->"""

    tokens = {
        "GLOBAL_STYLE": GLOBAL_STYLE,
        "ROOT": ROOT,
        "IR_KICKER": meta["kicker"],
        "IR_TITLE": meta["title"],
        "IR_SUBTITLE": meta["subtitle"],
        "IR_HERO_IMAGE": meta["hero_img"],
        "IR_REQUIRED_BLOCK": INVESTOR_REQUIRED_BLOCK if page_id == 5668 else (BOARD_REQUIRED_BLOCK if page_id == 5651 else (FINANCIALS_REQUIRED_BLOCK if page_id == 5686 else (PRESS_REQUIRED_BLOCK if page_id == 5716 else ""))),
        "IR_CONTENT": source_content,
    }
    return render_token_template(meta["template"], fallback, tokens)

QUALITY_CHECKS = {
    "home": {
        "url": f"{ROOT}/",
        "must_contain": [
            "Transform premium seating into a stronger guest experience and a higher-yield revenue zone.",
            "Revenue Share Model",
            "$0 Upfront",
        ],
    },
    "features": {
        "url": f"{ROOT}/index.php/poolside-amenity-unit/",
        "must_contain": ["ORDER", "STASH", "CHARGE", "CHILL", "Every feature is designed to remove friction and lift poolside performance."],
    },
    "about": {
        "url": f"{ROOT}/index.php/hospitality-innovation/",
        "must_contain": ["Built around how premium guests actually behave poolside.", "Built by PoolSafe", "Waterpark"],
    },
    "contact": {
        "url": f"{ROOT}/index.php/contact-loungenie/",
        "must_contain": ["hbspt.forms.create", "Find out what your property can earn"],
    },
    "videos": {
        "url": f"{ROOT}/index.php/loungenie-videos/",
        "must_contain": ["youtube.com/embed/EZ2CfBU30Ho"],
    },
    "gallery": {
        "url": f"{ROOT}/index.php/cabana-installation-photos/",
        "must_contain": ["Installation imagery organized by venue style, not by property name.", "Waterpark", "Closer"],
    },
    "investors": {
        "url": f"{ROOT}/index.php/investors/",
        "must_contain": [
            "906 Magnetic Drive",
            "TSX Venture Exchange",
            "POOL",
            "Horizon Assurance LLP.",
            "Garfinkle Biderman LLP",
            "TSX TRUST COMPANY",
            "200 University Ave., Suite 300",
            "Disclosure and Confidentiality Policy",
            "Fighting Against Forced Labour, Child Labour Report",
            "info@poolsafeinc.com",
            "1+ (416) 630-2444",
            "www.sedar.com",
        ],
    },
    "board": {
        "url": f"{ROOT}/index.php/board/",
        "must_contain": [
            "Board of Directors",
            "David Berger",
            "Steven Glaser",
            "Steven Mintz",
            "Gillian Deacon",
            "Robert Pratt",
            "COO, CFO &amp; Director",
            "President of R. Pratt Consulting Ltd",
        ],
    },
    "financials": {
        "url": f"{ROOT}/index.php/financials/",
        "must_contain": [
            "Required Filing Index",
            "2026 Special Meeting of Shareholders",
            "Notice of Meeting &amp; Management Info Circular",
            "Form of Proxy",
            "2025 Annual General Meeting",
            "Q4 YE 2024",
            "Q3 2023 &#8211; Financials &#8211; September 30, 2023; MD&amp;A &#8211; September 30, 2023",
            "Q1 2021 &#8211; Financials &#8211; March 31, 2021; MD&amp;A &#8211; March 31, 2021",
            "Q4 YE 2019 &#8211; Financials &#8211; December 31, 2019; MD&amp;A &#8211; December 31, 2019",
            "Q3 2016 &#8211; Financials &#8211; September 30, 2016",
            "Pool-Safe-Notice-of-Meeting-Combined-with-MIC.pdf",
        ],
    },
    "press": {
        "url": f"{ROOT}/index.php/press/",
        "must_contain": [
            "LounGenie News &amp; Press Releases",
            "Stay informed on LounGenie&#8217;s newest partnerships, product launches, and corporate milestones.",
            "POOL SAFE ADDS INDUSTRIALIST DAVID DEACON AS EXECUTIVE CHAIRMAN",
            "POOL SAFE ANNOUNCES DEBENTURE AND BONUS WARRANTS EXTENSIONS",
            "POOL SAFE ANNOUNCING AMENDMENT TO LINE OF CREDIT AND ISSUANCE OF BONUS WARRANTS",
            "POOL SAFE ANNOUNCES APPOINTMENT OF SUCCESSOR AUDITOR",
            "POOL SAFE AND AVENDRA PARTNER TO SUPPLY LOUNGENIE UNITS",
            "POOL SAFE SIGNS FIRST TWO CONTRACTS IN DUBAI",
            "PSI POUNDER UPDATE ON QT (PR)",
            "906 Magnetic Drive",
            "Info@poolsafeinc.com",
        ],
    },
}


def post_json(url, payload):
    data = json.dumps(payload).encode()
    req = urllib.request.Request(url, method="POST", data=data, headers={**HEADERS, "Content-Length": str(len(data))})
    with urllib.request.urlopen(req, timeout=90) as r:
        return json.loads(r.read())


def get_json(url):
    req = urllib.request.Request(url, headers=HEADERS)
    with urllib.request.urlopen(req, timeout=90) as r:
        return json.loads(r.read())


def get_public_json(url):
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
    with urllib.request.urlopen(req, timeout=90) as r:
        return json.loads(r.read())


def fetch_source_page(slug):
    # Pull editable raw body to avoid re-wrapping rendered shell markup.
    url = f"{SOURCE_BASE}/pages?slug={slug}&context=edit&_fields=title,excerpt,content"
    req = urllib.request.Request(url, headers=HEADERS)
    with urllib.request.urlopen(req, timeout=90) as r:
        rows = json.loads(r.read())
    if not rows:
        raise ValueError(f"Source page not found for slug: {slug}")
    row = rows[0]
    raw_content = row.get("content", {}).get("raw") or row.get("content", {}).get("rendered", "")
    return {
        "title": row["title"]["rendered"],
        "excerpt": row["excerpt"]["rendered"],
        "content": extract_ir_editable_content(raw_content),
    }


def backup_current_pages():
    BACKUP_DIR.mkdir(exist_ok=True)
    timestamp = datetime.now(timezone.utc).strftime("%Y%m%d-%H%M%S")
    backup_path = BACKUP_DIR / f"professional-redesign-v12-live-backup-{timestamp}.json"
    snapshot = []
    for page_id in sorted(PAGE_SEO):
        snapshot.append(
            get_public_json(
                f"{PAGES}/{page_id}?_fields=id,slug,status,link,modified,title,excerpt,content,template"
            )
        )
    backup_path.write_text(json.dumps(snapshot, indent=2), encoding="utf-8")
    return backup_path


def update_header():
    return post_json(f"{PARTS}/twentytwentyfour//header", {"content": HEADER_TEMPLATE, "status": "publish"})


def update_footer():
    return post_json(f"{PARTS}/twentytwentyfour//footer", {"content": FOOTER_TEMPLATE, "status": "publish"})


def update_nav():
    return post_json(f"{NAVS}/4", {"content": NAVIGATION_RAW, "status": "publish"})


def update_template():
    return post_json(f"{TEMPLATES}/twentytwentyfour//page-wide", {"content": PAGE_WIDE_TEMPLATE, "status": "publish"})


def ensure_external_links_new_tab(html):
    return re.sub(
        r'<a\s+(?![^>]*\btarget=)([^>]*\bhref="https?://(?!(?:www\.)?loungenie\.com[/"])[^"]*"[^>]*)>',
        r'<a \1 target="_blank" rel="noopener noreferrer">',
        html,
        flags=re.IGNORECASE,
    )


def normalize_investor_links(html):
    # Fix known malformed concatenated URL from source content while preserving full source fidelity.
    return html.replace(
        "https://loungenie.com/wp-content/uploads/2025/12/Pool-Safe-Arranges-Debenture-Financing.pdfhttps://loungenie.com/wp-content/uploads/2025/12/Pool-Safe-Arranges-Debenture-Financing.pdf",
        "https://loungenie.com/wp-content/uploads/2025/12/Pool-Safe-Arranges-Debenture-Financing.pdf",
    )


def normalize_legacy_urls(html):
    normalized = (
        html.replace(
            "https://loungenie.com/Loungenie%E2%84%A2/wp-content/uploads/",
            "https://www.loungenie.com/wp-content/uploads/",
        )
        .replace(
            "https://loungenie.com/wp-content/uploads/",
            "https://www.loungenie.com/wp-content/uploads/",
        )
    )
    # Repair known dead legacy Elementor background asset.
    return normalized.replace(
        "https://www.loungenie.com/wp-content/uploads/2026/03/services9.jpg",
        IMG["hero2"],
    )


def update_page(page_id, payload):
    content = ensure_external_links_new_tab(payload["content"])
    content = normalize_investor_links(content)
    content = normalize_legacy_urls(content)
    content = add_img_delivery_attrs(content)
    return post_json(
        f"{PAGES}/{page_id}",
        {"title": payload["title"], "excerpt": payload["excerpt"], "content": content, "status": "publish", "template": "page-wide"},
    )


def update_seo_meta(page_id, payload):
    meta_payload = {
        "rank_math_title": payload["title"],
        "rank_math_description": payload["excerpt"],
        "rank_math_robots": ["index", "follow"],
        "_yoast_wpseo_title": payload["title"],
        "_yoast_wpseo_metadesc": payload["excerpt"],
    }
    return post_json(f"{PAGES}/{page_id}", {"meta": meta_payload})


def fetch_html(url):
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
    with urllib.request.urlopen(req, timeout=90) as r:
        return r.read().decode("utf-8", errors="ignore")


def run_quality_audit():
    print("\n[3/3] Quality audit across all pages...")
    failures = 0
    for label, cfg in QUALITY_CHECKS.items():
        try:
            html = fetch_html(cfg["url"])
            missing = [token for token in cfg["must_contain"] if token not in html]
            h1_count = len(re.findall(r"<h1[^>]*>", html, flags=re.IGNORECASE))
            ir_shell_count = len(re.findall(r'class="[^\"]*ir-shell[^\"]*"', html, flags=re.IGNORECASE))
            if missing:
                print(f"  [FAIL] {label}: missing {len(missing)} marker(s) -> {missing[:3]}")
                failures += 1
            elif h1_count != 1:
                print(f"  [FAIL] {label}: expected 1 h1, found {h1_count}")
                failures += 1
            elif label in {"investors", "board", "financials", "press"} and ir_shell_count != 1:
                print(f"  [FAIL] {label}: expected 1 ir-shell, found {ir_shell_count}")
                failures += 1
            else:
                print(f"  [OK] {label}: quality markers verified")
        except Exception as e:
            print(f"  [FAIL] {label}: audit failed: {e}")
            failures += 1
    return failures == 0


parser = argparse.ArgumentParser(description="Apply the LounGenie staging redesign.")
parser.add_argument(
    "--include-investor-pages",
    action="store_true",
    help="Also sync investor pages (investors, board, financials, press). Disabled by default for content safety.",
)
parser.add_argument(
    "--preflight-only",
    action="store_true",
    help="Run strict page-by-page audit only (no publishing). Exits non-zero on failures.",
)
args = parser.parse_args()

if args.preflight_only:
    print("=" * 74)
    print("LounGenie v12+ | Preflight Audit Only")
    print("=" * 74)
    ok = run_quality_audit()
    if ok:
        print("\nPreflight passed. Safe to publish.")
        raise SystemExit(0)
    print("\nPreflight failed. Fix issues before publishing.")
    raise SystemExit(1)

ACTIVE_SOURCE_SYNC_PAGES = get_source_sync_pages(args.include_investor_pages)

print("=" * 74)
print("LounGenie v12+ | Premium Conversion + Investor Content Rebuild")
print("=" * 74)

print("\n[0/3] Backing up current target pages...")
try:
    backup_path = backup_current_pages()
    print(f"  ✓ backup saved to {backup_path}")
except Exception as e:
    print(f"  ✗ backup failed: {e}")
    raise

print("\n[1/3] Updating header, footer, nav, and full-width template...")
for label, fn in [("header", update_header), ("footer", update_footer), ("navigation", update_nav), ("page-wide template", update_template)]:
    try:
        fn()
        print(f"  ✓ {label}")
    except Exception as e:
        print(f"  ✗ {label}: {e}")

print("\n[2/3] Rebuilding sales pages...")
if ACTIVE_SOURCE_SYNC_PAGES:
    print("  Syncing investor pages from source for exact authored wording...")
else:
    print("  Investor page sync disabled for content safety.")
synced_investor_ids = set()
for page_id, slug in ACTIVE_SOURCE_SYNC_PAGES.items():
    try:
        source = fetch_source_page(slug)
        if slug == "investors":
            # Prevent reintroducing legacy recursive wrappers from historical page revisions.
            source["content"] = ""
        PAGE_SEO[page_id] = {
            "title": source["title"],
            "excerpt": source["excerpt"],
            "content": build_investor_shell(page_id, source["content"]),
        }
        synced_investor_ids.add(page_id)
        print(f"  ✓ synced full source: {slug}")
    except Exception as e:
        print(f"  ✗ sync failed for {slug}, skipping update to avoid overwriting authored content: {e}")

for page_id, payload in PAGE_SEO.items():
    if page_id in ACTIVE_SOURCE_SYNC_PAGES and page_id not in synced_investor_ids:
        print(f"  - skipped {page_id} to protect authored investor content")
        continue
    try:
        result = update_page(page_id, payload)
        try:
            update_seo_meta(page_id, payload)
            seo_status = " + seo"
        except Exception as meta_error:
            seo_status = f" + seo failed ({meta_error})"
        print(f"  ✓ {page_id} {result.get('link','')}{seo_status}")
    except Exception as e:
        print(f"  ✗ {page_id}: {e}")

audit_ok = run_quality_audit()

print("\nDone.")
print("- Removed giant template-level featured images.")
print("- Replaced broken linked video blocks with real iframe embeds.")
print("- Rebuilt sales pages with stronger modern layout, typography, and media control.")
print("- Kept investor navigation and investor section structure in place.")
if not audit_ok:
    raise SystemExit(1)


