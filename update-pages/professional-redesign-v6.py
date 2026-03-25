import base64
import json
import urllib.error
import urllib.request

AUTH = base64.b64encode(b"copilot:7NiL OZ17 ApP3 tIgF 6zlT ug7u").decode()
BASE = "https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2"
HEADERS = {"Authorization": f"Basic {AUTH}", "Content-Type": "application/json", "User-Agent": "Mozilla/5.0"}

PAGES = BASE + "/pages"
NAVS = BASE + "/navigation"
PARTS = BASE + "/template-parts"

ROOT = "https://loungenie.com/Loungenie%E2%84%A2"
UP25 = ROOT + "/wp-content/uploads/2025/10/"
LOGO = UP25 + "cropped-cropped-LounGenie-Logo.png"

INVESTOR_PAGES = [
    (5668, "Investors", "Investor Relations", "Corporate overview, listings, contacts, and compliance resources."),
    (5651, "Board", "Leadership & Board", "The people guiding Pool Safe and the LounGenie platform forward."),
    (5686, "Financials", "Financial Reports", "Quarterly results, annual filings, and shareholder materials."),
    (5716, "Press", "Press Releases", "Corporate announcements, milestones, and public market updates."),
]

HIDE_TITLES_STYLE = """
<style>
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
.page-id-5716 .entry-title { display:none !important; }

.wp-site-blocks > header.wp-block-template-part {
  position: sticky;
  top: 0;
  z-index: 980;
  background: rgba(255,255,255,.95);
  border-bottom: 1px solid #e7edf4;
  backdrop-filter: blur(10px);
}
.wp-site-blocks > header .lg-head-shell {
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 12px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}
.wp-site-blocks > header .lg-head-nav {
  flex: 1 1 auto;
  display: flex;
  justify-content: flex-end;
}
.wp-site-blocks > header .wp-block-navigation {
  margin: 0;
}
.wp-site-blocks > header .wp-block-navigation-item__content {
  font-size: 14px !important;
  font-weight: 700 !important;
  color: #0f2137 !important;
  border-radius: 8px;
  padding: 8px 10px;
}
.wp-site-blocks > header .wp-block-navigation-item__content:hover {
  background: #eef6ff;
  color: #0055a5 !important;
}
.wp-site-blocks > header .wp-block-navigation-submenu__toggle {
  color: #0f2137 !important;
}
.wp-site-blocks > header img { image-rendering: -webkit-optimize-contrast; }

.ir-wrap { max-width: 1180px; margin: 0 auto; padding: 0 24px; }
.ir-narrow { max-width: 900px; margin: 0 auto; padding: 0 24px; }
.ir-hero {
  background: linear-gradient(135deg, #0f2137 0%, #163456 48%, #0055a5 100%);
  color: white;
  padding: 72px 0 68px;
}
.ir-kicker {
  text-transform: uppercase;
  letter-spacing: 1.9px;
  font-size: 11px;
  font-weight: 700;
  opacity: .82;
}
.ir-tabs {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
  margin-top: 26px;
}
.ir-tabs a {
  display: inline-flex;
  align-items: center;
  padding: 10px 16px;
  border-radius: 999px;
  border: 1px solid rgba(255,255,255,.28);
  color: white;
  text-decoration: none;
  font-size: 13px;
  font-weight: 700;
}
.ir-tabs a:hover,
.ir-tabs a[aria-current="page"] {
  background: white;
  color: #0f2137;
}
.ir-surface {
  background: #ffffff;
  border: 1px solid #e6edf4;
  border-radius: 20px;
  box-shadow: 0 18px 48px rgba(15,33,55,.08);
  padding: 34px 30px;
  margin-top: -28px;
  position: relative;
}
.ir-rich h1, .ir-rich h2, .ir-rich h3, .ir-rich h4, .ir-rich h5, .ir-rich h6 {
  color: #0f2137;
  line-height: 1.2;
  margin-top: 0;
}
.ir-rich h1 { font-size: clamp(2rem, 4vw, 3rem); margin-bottom: 14px; }
.ir-rich h2 { font-size: clamp(1.45rem, 3vw, 2rem); margin-top: 34px; margin-bottom: 12px; }
.ir-rich h3 { font-size: 1.05rem; letter-spacing: .2px; margin-top: 24px; margin-bottom: 8px; }
.ir-rich h5 { font-size: 1rem; margin-bottom: 6px; }
.ir-rich h6 { font-size: 1.2rem; margin-top: 14px; margin-bottom: 4px; }
.ir-rich p, .ir-rich li {
  color: #455468;
  line-height: 1.8;
  font-size: 16px;
}
.ir-rich a { color: #0055a5; font-weight: 700; }
.ir-rich img {
  border-radius: 16px;
  box-shadow: 0 12px 32px rgba(15,33,55,.10);
  margin-top: 10px;
  margin-bottom: 14px;
}
.page-id-5651 .ir-rich img {
  width: min(280px, 100%);
  height: auto;
}
.page-id-5651 .ir-rich > a,
.page-id-5651 .ir-rich > img {
  display: inline-block;
}
.page-id-5686 .ir-rich p a,
.page-id-5716 .ir-rich > a,
.page-id-5668 .ir-rich p a {
  word-break: break-word;
}

.site-footer-shell {
  background: #09182b;
  color: rgba(255,255,255,.76);
  padding: 56px 24px 28px;
}
.site-footer-shell a {
  color: rgba(255,255,255,.88);
  text-decoration: none;
}
.site-footer-shell a:hover { color: white; }
.site-footer-grid {
  max-width: 1280px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1.3fr 1fr 1fr 1fr;
  gap: 28px;
}
.site-footer-grid h3 {
  color: white;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 1.4px;
  margin-bottom: 12px;
}
.site-footer-grid p,
.site-footer-grid li {
  font-size: 14px;
  line-height: 1.8;
  margin: 0;
}
.site-footer-grid ul {
  list-style: none;
  padding: 0;
  margin: 0;
}
.site-footer-base {
  max-width: 1280px;
  margin: 26px auto 0;
  padding-top: 18px;
  border-top: 1px solid rgba(255,255,255,.12);
  font-size: 13px;
  color: rgba(255,255,255,.56);
}
@media (max-width: 900px) {
  .site-footer-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 700px) {
  .site-footer-grid { grid-template-columns: 1fr; }
  .wp-site-blocks > header .lg-head-shell,
  .ir-wrap,
  .ir-narrow { padding-left: 16px; padding-right: 16px; }
}
</style>
"""

HEADER_TEMPLATE = (
    "<!-- wp:html -->"
    + HIDE_TITLES_STYLE
    + "<!-- /wp:html -->"
    + "<!-- wp:html --><div class=\"lg-head-shell\"><div class=\"lg-head-brand\">"
    + f"<a href=\"{ROOT}/\"><img src=\"{LOGO}\" alt=\"LounGenie\" style=\"width:260px;height:auto;display:block\"></a>"
    + "</div><div class=\"lg-head-nav\"><!-- /wp:html -->"
    + "<!-- wp:navigation {\"ref\":4,\"overlayMenu\":\"mobile\",\"layout\":{\"type\":\"flex\",\"justifyContent\":\"right\",\"orientation\":\"horizontal\"}} /-->"
    + "<!-- wp:html --></div></div><!-- /wp:html -->"
)

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

FOOTER_TEMPLATE = f"""<!-- wp:html -->
<footer class="site-footer-shell">
  <div class="site-footer-grid">
    <div>
      <a href="{ROOT}/"><img src="{LOGO}" alt="LounGenie" style="width:200px;height:auto;display:block;margin-bottom:14px"></a>
      <p>LounGenie is a poolside revenue platform for hotels, resorts, and water parks, combining guest convenience with operational efficiency.</p>
    </div>
    <div>
      <h3>Explore</h3>
      <ul>
        <li><a href="{ROOT}/">Home</a></li>
        <li><a href="{ROOT}/index.php/poolside-amenity-unit/">Features</a></li>
        <li><a href="{ROOT}/index.php/cabana-installation-photos/">Gallery</a></li>
        <li><a href="{ROOT}/index.php/loungenie-videos/">Videos</a></li>
        <li><a href="{ROOT}/index.php/hospitality-innovation/">About</a></li>
        <li><a href="{ROOT}/index.php/contact-loungenie/">Contact</a></li>
      </ul>
    </div>
    <div>
      <h3>Investors</h3>
      <ul>
        <li><a href="{ROOT}/index.php/investors/">Investor Relations</a></li>
        <li><a href="{ROOT}/index.php/board/">Board</a></li>
        <li><a href="{ROOT}/index.php/financials/">Financials</a></li>
        <li><a href="{ROOT}/index.php/press/">Press</a></li>
      </ul>
    </div>
    <div>
      <h3>Contact</h3>
      <ul>
        <li><a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a></li>
        <li><a href="https://www.sedarplus.ca/" target="_blank" rel="noopener">SEDAR+</a></li>
        <li><a href="https://www.tsxtrust.com/" target="_blank" rel="noopener">TSX Trust</a></li>
      </ul>
    </div>
  </div>
  <div class="site-footer-base">&copy; 2026 LounGenie / Pool Safe. All rights reserved.</div>
</footer>
<!-- /wp:html -->"""


def post_json(url, payload):
    data = json.dumps(payload).encode()
    req = urllib.request.Request(
        url,
        method="POST",
        data=data,
        headers={**HEADERS, "Content-Length": str(len(data))},
    )
    with urllib.request.urlopen(req, timeout=60) as r:
        return json.loads(r.read())


def get_page_raw(page_id):
    req = urllib.request.Request(f"{PAGES}/{page_id}?context=edit", headers=HEADERS)
    with urllib.request.urlopen(req, timeout=30) as r:
        data = json.loads(r.read())
    return data["content"]["raw"], data["title"]["rendered"]


def investor_tabs(current_slug):
    items = [
        ("investors", "Investor Relations", f"{ROOT}/index.php/investors/"),
        ("board", "Board", f"{ROOT}/index.php/board/"),
        ("financials", "Financials", f"{ROOT}/index.php/financials/"),
        ("press", "Press", f"{ROOT}/index.php/press/"),
    ]
    html = ['<div class="ir-tabs">']
    for slug, label, url in items:
        current = ' aria-current="page"' if slug == current_slug else ''
        html.append(f'<a href="{url}"{current}>{label}</a>')
    html.append('</div>')
    return "".join(html)


def build_investor_page(slug, hero_title, hero_subtitle, raw_html):
    tabs = investor_tabs(slug)
    return (
        "<!-- wp:html -->"
        + HIDE_TITLES_STYLE
        + "<!-- /wp:html -->"
        + "<!-- wp:html -->"
        + f'<section class="ir-hero"><div class="ir-wrap"><p class="ir-kicker">Investor Information</p><h1 style="font-size:clamp(2.2rem,5vw,4rem);line-height:1.05;margin:8px 0 14px;font-weight:800;">{hero_title}</h1><p style="max-width:760px;font-size:1.05rem;line-height:1.75;margin:0;opacity:.86;">{hero_subtitle}</p>{tabs}</div></section>'
        + f'<section class="ir-wrap"><div class="ir-surface"><div class="ir-rich">{raw_html}</div></div></section>'
        + "<!-- /wp:html -->"
    )


def update_navigation():
    try:
        post_json(f"{NAVS}/4", {"content": NAVIGATION_RAW, "status": "publish"})
        return True, "navigation updated"
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}: {e.read().decode()[:220]}"
    except Exception as e:
        return False, str(e)


def update_header():
    try:
        post_json(f"{PARTS}/twentytwentyfour//header", {"content": HEADER_TEMPLATE, "status": "publish"})
        return True, "header updated"
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}: {e.read().decode()[:220]}"
    except Exception as e:
        return False, str(e)


def update_footer():
    try:
        post_json(f"{PARTS}/twentytwentyfour//footer", {"content": FOOTER_TEMPLATE, "status": "publish"})
        return True, "footer updated"
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}: {e.read().decode()[:220]}"
    except Exception as e:
        return False, str(e)


def update_page(page_id, title, content):
    try:
        result = post_json(
            f"{PAGES}/{page_id}",
            {
                "title": title,
                "content": content,
                "status": "publish",
                "template": "page-wide",
            },
        )
        return True, result.get("link", "")
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}: {e.read().decode()[:220]}"
    except Exception as e:
        return False, str(e)


print("=" * 72)
print("LounGenie v6 | Investor Restore + Footer + Structure Pass")
print("=" * 72)

print("\n[1/4] Updating header navigation with investor section...")
ok_n, msg_n = update_navigation()
ok_h, msg_h = update_header()
print("  ", "✓" if ok_n else "✗", msg_n)
print("  ", "✓" if ok_h else "✗", msg_h)

print("\n[2/4] Updating footer with product and investor links...")
ok_f, msg_f = update_footer()
print("  ", "✓" if ok_f else "✗", msg_f)

print("\n[3/4] Rebuilding investor pages without changing body wording...")
for page_id, slug_label, hero_title, hero_subtitle in INVESTOR_PAGES:
    raw_html, title = get_page_raw(page_id)
    slug = slug_label.lower()
    content = build_investor_page(slug, hero_title, hero_subtitle, raw_html)
    ok, out = update_page(page_id, title, content)
    print(f"   {'✓' if ok else '✗'} {slug_label:10s} {out}")

print("\n[4/4] Complete.")
print("- Investor pages restored in header via Investors submenu.")
print("- Footer now includes product navigation, investor links, and contact details.")
print("- Investor page wording preserved, with improved layout, spacing, and page-wide template.")
