"""
LounGenie redesign v5
- Gutenberg-native block pages (editable)
- Header and nav modernization
- Page title suppression for redesigned pages
"""

import base64
import json
import urllib.error
import urllib.request

AUTH = base64.b64encode(b"copilot:7NiL OZ17 ApP3 tIgF 6zlT ug7u").decode()
BASE = "https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2"
HEADERS = {"Authorization": f"Basic {AUTH}", "Content-Type": "application/json"}

PAGES = BASE + "/pages"
NAVS = BASE + "/navigation"
PARTS = BASE + "/template-parts"

ROOT = "https://loungenie.com/Loungenie%E2%84%A2"
UP = ROOT + "/wp-content/uploads/2026/03/"
UP25 = ROOT + "/wp-content/uploads/2025/10/"

IMG = {
    "hero": UP + "hero9-bg-1.jpg",
    "hil1": UP + "Hilton-Wakoloa-Village-2018-10-Aloha-Falls-Cabana-1-scaled.jpg",
    "hil2": UP + "Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-2-scaled.jpg",
    "hil3": UP + "Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-3-scaled.jpg",
    "hil4": UP + "Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-4-scaled.jpg",
    "grove1": UP + "The-Grove-1.jpg",
    "grove5": UP + "The-Grove-5.jpg",
    "grove7": UP + "The-Grove-7-scaled.jpg",
    "sea": UP + "Sea-World-San-Diego.jpg",
    "cowa1": UP + "38f4fc95-7925-4625-b0e8-5ba78771c037.jpg",
    "cowa2": UP + "a5ea38b9-4578-4356-a118-f168caa0ec90.jpg",
    "cowa3": UP + "IMG_3233-scaled-1.jpg",
    "cowa4": UP + "IMG_3235-scaled-1.jpg",
    "contact": UP + "3-VOR-cabana-e1773774348955.jpg",
    "logo": UP25 + "cropped-cropped-LounGenie-Logo.png",
}

PAGES_META = [
    (4701, "LounGenie | Poolside Revenue Platform", "home", "page-wide"),
    (2989, "Features | LounGenie", "features", "page-wide"),
    (4862, "About | LounGenie", "about", "page-wide"),
    (5139, "Contact | LounGenie", "contact", "page-wide"),
    (5285, "Videos | LounGenie", "videos", "page-wide"),
    (5223, "Gallery | LounGenie", "gallery", "page-wide"),
]

GLOBAL_STYLE = """
<!-- wp:html -->
<style>
:root {
  --lg-navy: #0f2137;
  --lg-blue: #0055a5;
  --lg-muted: #5f6b7a;
}
.page-id-4701 .wp-block-post-title,
.page-id-2989 .wp-block-post-title,
.page-id-4862 .wp-block-post-title,
.page-id-5139 .wp-block-post-title,
.page-id-5285 .wp-block-post-title,
.page-id-5223 .wp-block-post-title,
.page-id-4701 .entry-title,
.page-id-2989 .entry-title,
.page-id-4862 .entry-title,
.page-id-5139 .entry-title,
.page-id-5285 .entry-title,
.page-id-5223 .entry-title { display: none !important; }

.wp-site-blocks > header.wp-block-template-part {
  position: sticky;
  top: 0;
  z-index: 950;
  background: rgba(255,255,255,.94);
  border-bottom: 1px solid #e6edf4;
  backdrop-filter: blur(8px);
}
.wp-site-blocks > header .wp-block-navigation-item__content {
  font-size: 14px !important;
  font-weight: 700 !important;
  color: var(--lg-navy) !important;
  border-radius: 8px;
  padding: 8px 10px;
}
.wp-site-blocks > header .wp-block-navigation-item__content:hover {
  background: #eef6ff;
  color: var(--lg-blue) !important;
}

.lg-wrap { max-width: 1260px; margin: 0 auto; padding: 0 24px; }
.lg-narrow { max-width: 860px; margin: 0 auto; padding: 0 24px; }
.lg-muted { color: var(--lg-muted); }

@media (max-width: 780px) {
  .lg-wrap, .lg-narrow { padding: 0 16px; }
}
</style>
<!-- /wp:html -->
"""

HEADER_TEMPLATE = (
    "<!-- wp:group -->"
    "<div class=\"wp-block-group\" style=\"padding-top:12px;padding-bottom:12px\">"
    "<!-- wp:group -->"
    "<div class=\"wp-block-group alignwide\" style=\"display:flex;justify-content:space-between;align-items:center;gap:16px\">"
    "<!-- wp:image -->"
    f"<figure class=\"wp-block-image size-full\"><a href=\"{ROOT}/\"><img src=\"{IMG['logo']}\" alt=\"LounGenie\" style=\"width:260px;height:auto\"/></a></figure>"
    "<!-- /wp:image -->"
    "<!-- wp:navigation {\"ref\":4} /-->"
    "</div>"
    "<!-- /wp:group -->"
    "</div>"
    "<!-- /wp:group -->"
)

NAVIGATION_RAW = """<!-- wp:navigation-link {\"label\":\"Home\",\"type\":\"page\",\"id\":4701,\"url\":\"/Loungenie%E2%84%A2/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"Features\",\"type\":\"page\",\"id\":2989,\"url\":\"/Loungenie%E2%84%A2/index.php/poolside-amenity-unit/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"Gallery\",\"type\":\"page\",\"id\":5223,\"url\":\"/Loungenie%E2%84%A2/index.php/cabana-installation-photos/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"Videos\",\"type\":\"page\",\"id\":5285,\"url\":\"/Loungenie%E2%84%A2/index.php/loungenie-videos/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"About\",\"type\":\"page\",\"id\":4862,\"url\":\"/Loungenie%E2%84%A2/index.php/hospitality-innovation/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->
<!-- wp:navigation-link {\"label\":\"Contact\",\"type\":\"page\",\"id\":5139,\"url\":\"/Loungenie%E2%84%A2/index.php/contact-loungenie/\",\"kind\":\"post-type\",\"isTopLevelLink\":true} /-->"""


def home_blocks():
    return (
        GLOBAL_STYLE
        + "<!-- wp:cover -->"
        + f"<div class=\"wp-block-cover alignfull\" style=\"min-height:74vh\"><span aria-hidden=\"true\" class=\"wp-block-cover__background has-background-dim-70 has-background-dim\"></span><img class=\"wp-block-cover__image-background\" alt=\"Poolside hero\" src=\"{IMG['hero']}\" data-object-fit=\"cover\"/><div class=\"wp-block-cover__inner-container\"><div class=\"lg-wrap\"><p style=\"text-transform:uppercase;letter-spacing:2px;color:#fff;font-size:11px;font-weight:700\">IAAPA Brass Ring Award Winner</p><h1 style=\"color:#fff;font-size:clamp(2.2rem,6vw,4.6rem);line-height:1.05;margin:0 0 16px;font-weight:800\">Increase Poolside Food and Beverage Revenue by Up to 30%</h1><p style=\"color:#fff;font-size:1.1rem;line-height:1.7;max-width:860px\">LounGenie delivers a premium poolside platform with in-seat ordering, secure storage, wireless charging, and guest comfort in one commercial system.</p><div class=\"wp-block-buttons\"><div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\" href=\"{ROOT}/index.php/contact-loungenie/\">Schedule a Demo</a></div><div class=\"wp-block-button is-style-outline\"><a class=\"wp-block-button__link wp-element-button\" href=\"{ROOT}/index.php/poolside-amenity-unit/\">Explore Features</a></div></div></div></div></div>"
        + "<!-- /wp:cover -->"
        + "<!-- wp:group --><div class=\"wp-block-group alignfull\" style=\"padding-top:72px;padding-bottom:72px;background:#f7fafc\"><div class=\"lg-wrap\"><div class=\"wp-block-columns\"><div class=\"wp-block-column\"><p style=\"text-transform:uppercase;letter-spacing:1.7px;color:#0055a5;font-size:11px;font-weight:700\">Why it works</p><h2 style=\"font-size:clamp(1.8rem,4vw,3rem);margin:0 0 12px;font-weight:800\">Longer stays. Better experience. Higher order volume.</h2><ul><li>In-seat QR ordering removes friction.</li><li>Secure storage reduces early exits.</li><li>Wireless charging keeps guests poolside.</li><li>Premium setup improves dwell time.</li></ul></div><div class=\"wp-block-column\">"
        + f"<figure class=\"wp-block-image size-large\"><img src=\"{IMG['hil4']}\" alt=\"Hilton installation\" style=\"border-radius:16px\"/></figure>"
        + "</div></div></div></div><!-- /wp:group -->"
        + "<!-- wp:group --><div class=\"wp-block-group alignfull\" style=\"padding-top:70px;padding-bottom:70px;background:#0f2137\"><div class=\"lg-narrow\"><h2 style=\"color:#fff;text-align:center;font-size:clamp(2.5rem,8vw,5rem);margin:0 0 6px;font-weight:900\">Up to 30%</h2><p style=\"color:#fff;text-align:center;font-size:1.12rem\">increase in poolside food and beverage revenue.</p></div></div><!-- /wp:group -->"
        + "<!-- wp:group --><div class=\"wp-block-group alignfull\" style=\"padding-top:64px;padding-bottom:64px\"><div class=\"lg-wrap\"><h3 style=\"text-align:center;margin-bottom:24px\">Real Installations</h3>"
        + f"<figure class=\"wp-block-gallery has-nested-images columns-3 is-cropped\"><figure class=\"wp-block-image\"><img src=\"{IMG['hil1']}\" alt=\"Hilton Aloha Falls\"/></figure><figure class=\"wp-block-image\"><img src=\"{IMG['grove7']}\" alt=\"The Grove\"/></figure><figure class=\"wp-block-image\"><img src=\"{IMG['sea']}\" alt=\"SeaWorld\"/></figure></figure>"
        + "</div></div><!-- /wp:group -->"
    )


def features_blocks():
    return (
        GLOBAL_STYLE
        + "<!-- wp:group --><div class=\"wp-block-group alignfull\" style=\"padding-top:66px;padding-bottom:58px;background:#f7fafc\"><div class=\"lg-narrow\"><p style=\"text-transform:uppercase;letter-spacing:1.7px;color:#0055a5;font-size:11px;font-weight:700;text-align:center\">Platform Features</p><h1 style=\"text-align:center;font-size:clamp(2rem,5vw,3.5rem);font-weight:800\">ORDER. STASH. CHARGE. CHILL.</h1><p class=\"lg-muted\" style=\"text-align:center\">A modern poolside system that converts guest convenience into measurable revenue growth.</p></div></div><!-- /wp:group -->"
        + "<!-- wp:group --><div class=\"wp-block-group alignfull\" style=\"padding-top:46px;padding-bottom:70px\"><div class=\"lg-wrap\">"
        + f"<div class=\"wp-block-columns\"><div class=\"wp-block-column\"><figure class=\"wp-block-image\"><img src=\"{IMG['hil2']}\" alt=\"Order feature\" style=\"border-radius:14px\"/></figure></div><div class=\"wp-block-column\"><h2>ORDER</h2><p>Guests order directly from their seat via QR code.</p><ul><li>Higher order capture</li><li>Lower service friction</li><li>Better peak-hour flow</li></ul></div></div>"
        + f"<div class=\"wp-block-columns\" style=\"margin-top:28px\"><div class=\"wp-block-column\"><h2>STASH</h2><p>Secure storage keeps valuables protected and guests relaxed.</p><ul><li>Fewer early departures</li><li>Longer dwell time</li><li>Improved premium experience</li></ul></div><div class=\"wp-block-column\"><figure class=\"wp-block-image\"><img src=\"{IMG['hil4']}\" alt=\"Stash feature\" style=\"border-radius:14px\"/></figure></div></div>"
        + f"<div class=\"wp-block-columns\" style=\"margin-top:28px\"><div class=\"wp-block-column\"><figure class=\"wp-block-image\"><img src=\"{IMG['grove5']}\" alt=\"Charge and chill\" style=\"border-radius:14px\"/></figure></div><div class=\"wp-block-column\"><h2>CHARGE + CHILL</h2><p>Wireless charging and comfort amenities keep guests engaged poolside all day.</p><ul><li>Reduced battery-driven churn</li><li>Stronger repeat-order potential</li><li>Premium guest perception</li></ul></div></div>"
        + "</div></div><!-- /wp:group -->"
    )


def about_blocks():
    return (
        GLOBAL_STYLE
        + "<!-- wp:group --><div class=\"wp-block-group alignfull\" style=\"padding-top:66px;padding-bottom:66px;background:#0f2137\"><div class=\"lg-wrap\"><p style=\"text-transform:uppercase;letter-spacing:1.7px;color:#fff;font-size:11px;font-weight:700\">About LounGenie</p><h1 style=\"color:#fff;margin:0 0 12px\">Turning Pool Decks Into Revenue Centers</h1><p style=\"color:#fff\">We deploy guest-first poolside technology for hotels and resorts with zero fake stats and practical operational fit.</p></div></div><!-- /wp:group -->"
        + f"<!-- wp:group --><div class=\"wp-block-group alignfull\" style=\"padding-top:58px;padding-bottom:70px\"><div class=\"lg-wrap\"><div class=\"wp-block-columns\"><div class=\"wp-block-column\"><figure class=\"wp-block-image\"><img src=\"{IMG['grove7']}\" alt=\"Grove installation\" style=\"border-radius:14px\"/></figure></div><div class=\"wp-block-column\"><h2>What Defines Our Approach</h2><ul><li>Zero-capital deployment model</li><li>Simple integration with existing workflows</li><li>Focus on guest convenience and safety</li><li>Measured performance reporting</li></ul></div></div></div></div><!-- /wp:group -->"
    )


def contact_blocks():
    return (
        GLOBAL_STYLE
        + "<!-- wp:group --><div class=\"wp-block-group alignfull\" style=\"padding-top:66px;padding-bottom:70px;background:#f7fafc\"><div class=\"lg-wrap\"><h1>Contact LounGenie</h1><p>Tell us about your property and we will share a practical rollout plan.</p><div class=\"wp-block-columns\"><div class=\"wp-block-column\">"
        + f"<figure class=\"wp-block-image\"><img src=\"{IMG['contact']}\" alt=\"Contact image\" style=\"border-radius:14px\"/></figure><p><strong>Email:</strong> <a href=\"mailto:info@poolsafeinc.com\">info@poolsafeinc.com</a></p>"
        + "</div><div class=\"wp-block-column\"><!-- wp:html --><form action=\"https://formsubmit.co/info@poolsafeinc.com\" method=\"POST\" style=\"background:#fff;border:1px solid #e6edf4;border-radius:14px;padding:20px\"><input type=\"hidden\" name=\"_captcha\" value=\"false\"><input type=\"hidden\" name=\"_subject\" value=\"New Demo Request - LounGenie\"><p><label>Name<br><input name=\"name\" required style=\"width:100%;padding:10px;border:1px solid #d7e0eb;border-radius:8px\"></label></p><p><label>Email<br><input type=\"email\" name=\"email\" required style=\"width:100%;padding:10px;border:1px solid #d7e0eb;border-radius:8px\"></label></p><p><label>Property<br><input name=\"property\" required style=\"width:100%;padding:10px;border:1px solid #d7e0eb;border-radius:8px\"></label></p><p><label>Message<br><textarea name=\"message\" rows=\"4\" style=\"width:100%;padding:10px;border:1px solid #d7e0eb;border-radius:8px\"></textarea></label></p><button type=\"submit\" style=\"background:#0055a5;color:#fff;border:0;padding:12px 18px;border-radius:8px;font-weight:700\">Send Request</button></form><!-- /wp:html --></div></div></div></div><!-- /wp:group -->"
    )


def videos_blocks():
    return (
        GLOBAL_STYLE
        + "<!-- wp:group --><div class=\"wp-block-group alignfull\" style=\"padding-top:66px;padding-bottom:48px\"><div class=\"lg-narrow\"><h1 style=\"text-align:center\">LounGenie Videos</h1><p class=\"lg-muted\" style=\"text-align:center\">Real installations and product walkthroughs.</p></div></div><!-- /wp:group -->"
        + "<!-- wp:group --><div class=\"wp-block-group alignfull\" style=\"padding-bottom:66px\"><div class=\"lg-wrap\"><div class=\"wp-block-columns\"><div class=\"wp-block-column\"><!-- wp:embed --><figure class=\"wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube\"><div class=\"wp-block-embed__wrapper\">https://www.youtube.com/watch?v=EZ2CfBU30Ho</div></figure><!-- /wp:embed --></div><div class=\"wp-block-column\"><!-- wp:embed --><figure class=\"wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube\"><div class=\"wp-block-embed__wrapper\">https://www.youtube.com/watch?v=M48NYM06JgY</div></figure><!-- /wp:embed --></div></div><div class=\"wp-block-columns\" style=\"margin-top:20px\"><div class=\"wp-block-column\"><!-- wp:embed --><figure class=\"wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube\"><div class=\"wp-block-embed__wrapper\">https://www.youtube.com/watch?v=PhV1JVo9POI</div></figure><!-- /wp:embed --></div><div class=\"wp-block-column\"><!-- wp:embed --><figure class=\"wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube\"><div class=\"wp-block-embed__wrapper\">https://www.youtube.com/watch?v=3Rjba7pWs_I</div></figure><!-- /wp:embed --></div></div></div></div><!-- /wp:group -->"
    )


def gallery_blocks():
    return (
        GLOBAL_STYLE
        + "<!-- wp:group --><div class=\"wp-block-group alignfull\" style=\"padding-top:66px;padding-bottom:48px\"><div class=\"lg-narrow\"><h1 style=\"text-align:center\">Installation Gallery</h1><p class=\"lg-muted\" style=\"text-align:center\">Hotels, resorts, and water parks using LounGenie.</p></div></div><!-- /wp:group -->"
        + "<!-- wp:group --><div class=\"wp-block-group alignfull\" style=\"padding-bottom:66px\"><div class=\"lg-wrap\">"
        + f"<figure class=\"wp-block-gallery has-nested-images columns-3 is-cropped\"><figure class=\"wp-block-image\"><img src=\"{IMG['hil1']}\" alt=\"Hilton Aloha\"/></figure><figure class=\"wp-block-image\"><img src=\"{IMG['hil3']}\" alt=\"Hilton daybed\"/></figure><figure class=\"wp-block-image\"><img src=\"{IMG['grove1']}\" alt=\"Grove\"/></figure><figure class=\"wp-block-image\"><img src=\"{IMG['grove5']}\" alt=\"Grove premium\"/></figure><figure class=\"wp-block-image\"><img src=\"{IMG['cowa1']}\" alt=\"Cowabunga 1\"/></figure><figure class=\"wp-block-image\"><img src=\"{IMG['cowa4']}\" alt=\"Cowabunga 2\"/></figure><figure class=\"wp-block-image\"><img src=\"{IMG['sea']}\" alt=\"SeaWorld\"/></figure><figure class=\"wp-block-image\"><img src=\"{IMG['cowa2']}\" alt=\"Cowabunga interior\"/></figure><figure class=\"wp-block-image\"><img src=\"{IMG['cowa3']}\" alt=\"Cowabunga detail\"/></figure></figure>"
        + "</div></div><!-- /wp:group -->"
    )


def get_content(slug):
    return {
        "home": home_blocks,
        "features": features_blocks,
        "about": about_blocks,
        "contact": contact_blocks,
        "videos": videos_blocks,
        "gallery": gallery_blocks,
    }[slug]()


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
        post_json(
            f"{PARTS}/twentytwentyfour//header",
            {"content": HEADER_TEMPLATE, "status": "publish"},
        )
        return True, "header updated"
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}: {e.read().decode()[:220]}"
    except Exception as e:
        return False, str(e)


def update_page(page_id, title, content, template):
    try:
        out = post_json(
            f"{PAGES}/{page_id}",
            {
                "title": title,
                "content": content,
                "status": "publish",
                "template": template,
            },
        )
        return True, out.get("link", "")
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}: {e.read().decode()[:240]}"
    except Exception as e:
        return False, str(e)


def media_ok(url):
    try:
        req = urllib.request.Request(url, method="HEAD")
        with urllib.request.urlopen(req, timeout=20) as r:
            return 200 <= r.status < 400
    except Exception:
        return False


print("=" * 70)
print("LounGenie v5 | Gutenberg Conversion + Header/Logo Cleanup")
print("=" * 70)

print("\n[1/5] Media validation")
missing = [k for k, v in IMG.items() if not media_ok(v)]
if missing:
    print("  Missing keys:", ", ".join(missing))
else:
    print("  All selected media URLs are valid.")

print("\n[2/5] Update nav + header")
ok_n, msg_n = update_navigation()
ok_h, msg_h = update_header()
print("  ", "✓" if ok_n else "✗", msg_n)
print("  ", "✓" if ok_h else "✗", msg_h)

print("\n[3/5] Convert Home + Features to Gutenberg blocks")
for page_id, title, slug, template in PAGES_META[:2]:
    ok, out = update_page(page_id, title, get_content(slug), template)
    print(f"   {'✓' if ok else '✗'} {slug:8s} {out}")

print("\n[4/5] Convert About + Contact + Videos + Gallery")
for page_id, title, slug, template in PAGES_META[2:]:
    ok, out = update_page(page_id, title, get_content(slug), template)
    print(f"   {'✓' if ok else '✗'} {slug:8s} {out}")

print("\n[5/5] Done")
print("- Pages converted to Gutenberg-native block content for easier editing.")
print("- Auto page title display is hidden on redesigned pages.")
print("- Header now uses a high-resolution logo image block and cleaner nav.")
