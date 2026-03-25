import base64
import json
import urllib.error
import urllib.request

AUTH = base64.b64encode(b"copilot:7NiL OZ17 ApP3 tIgF 6zlT ug7u").decode()
BASE = "https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2"
HEADERS = {"Authorization": f"Basic {AUTH}", "Content-Type": "application/json", "User-Agent": "Mozilla/5.0"}

PAGES = BASE + "/pages"
TEMPLATES = BASE + "/templates"

PAGE_WIDE_TEMPLATE = """<!-- wp:template-part {\"slug\":\"header\",\"area\":\"header\",\"tagName\":\"header\",\"theme\":\"twentytwentyfour\"} /-->
<!-- wp:group {\"tagName\":\"main\",\"align\":\"full\",\"layout\":{\"type\":\"default\"}} -->
<main class=\"wp-block-group alignfull\">
  <!-- wp:post-featured-image {\"align\":\"full\"} /-->
  <!-- wp:post-content {\"align\":\"full\",\"layout\":{\"type\":\"default\"}} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {\"slug\":\"footer\",\"area\":\"footer\",\"tagName\":\"footer\",\"theme\":\"twentytwentyfour\"} /-->"""

SEO = {
    4701: {
        "title": "LounGenie | Smart Poolside Revenue Platform",
        "excerpt": "LounGenie helps hotels, resorts, and water parks grow poolside food and beverage revenue with QR ordering, secure storage, charging, and premium guest amenities.",
        "featured_media": 8382,
    },
    2989: {
        "title": "LounGenie Features | ORDER, STASH, CHARGE, CHILL",
        "excerpt": "Explore the LounGenie platform features that improve guest convenience and support stronger poolside food and beverage performance.",
        "featured_media": 8380,
    },
    4862: {
        "title": "About LounGenie | Hospitality Revenue Innovation",
        "excerpt": "Learn how LounGenie combines hospitality-focused product design with a zero-capital deployment model for hotels and resorts.",
        "featured_media": 8380,
    },
    5139: {
        "title": "Contact LounGenie | Schedule a Poolside Revenue Demo",
        "excerpt": "Contact LounGenie to discuss your property, request a demonstration, and explore how the platform can fit your poolside operation.",
        "featured_media": 9073,
    },
    5285: {
        "title": "LounGenie Videos | Product and Installation Demos",
        "excerpt": "Watch LounGenie videos featuring real installations, product walkthroughs, and resort poolside use cases.",
        "featured_media": 8382,
    },
    5223: {
        "title": "LounGenie Gallery | Cabana Installation Photos",
        "excerpt": "View installation photos showing how LounGenie fits hotels, resorts, and water parks across premium poolside environments.",
        "featured_media": 8378,
    },
    5668: {
        "title": "Investor Relations | Pool Safe Inc. (TSX-V: POOL)",
        "excerpt": "Investor relations information for Pool Safe Inc., including listing details, compliance resources, transfer agent information, and investor contacts.",
        "featured_media": 2778,
    },
    5651: {
        "title": "Board | Pool Safe Inc. Leadership",
        "excerpt": "Meet the leadership team and board behind Pool Safe Inc. and the LounGenie hospitality platform.",
        "featured_media": 2778,
    },
    5686: {
        "title": "Financials | Pool Safe Inc. Reports and Filings",
        "excerpt": "Access Pool Safe Inc. quarterly results, annual filings, meeting materials, and shareholder financial resources.",
        "featured_media": 2778,
    },
    5716: {
        "title": "Press | Pool Safe Inc. News and Releases",
        "excerpt": "Read recent press releases, announcements, and corporate updates from Pool Safe Inc. and LounGenie.",
        "featured_media": 2778,
    },
}


def post_json(url, payload):
    data = json.dumps(payload).encode()
    req = urllib.request.Request(url, method="POST", data=data, headers={**HEADERS, "Content-Length": str(len(data))})
    with urllib.request.urlopen(req, timeout=60) as r:
        return json.loads(r.read())


def update_template():
    try:
        post_json(f"{TEMPLATES}/twentytwentyfour//page-wide", {"content": PAGE_WIDE_TEMPLATE, "status": "publish"})
        return True, "page-wide template updated"
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}: {e.read().decode()[:220]}"
    except Exception as e:
        return False, str(e)


def update_page(page_id, title, excerpt, featured_media):
    try:
        result = post_json(
            f"{PAGES}/{page_id}",
            {
                "title": title,
                "excerpt": excerpt,
                "featured_media": featured_media,
                "status": "publish",
            },
        )
        return True, result.get("link", "")
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}: {e.read().decode()[:220]}"
    except Exception as e:
        return False, str(e)


print("=" * 70)
print("LounGenie v7 | Page Template Fix + SEO Excerpts")
print("=" * 70)

print("\n[1/2] Fixing the page-wide template layout...")
ok_t, msg_t = update_template()
print("  ", "✓" if ok_t else "✗", msg_t)

print("\n[2/2] Updating titles, excerpts, and featured images...")
for page_id, data in SEO.items():
    ok, out = update_page(page_id, data["title"], data["excerpt"], data["featured_media"])
    print(f"   {'✓' if ok else '✗'} {page_id} {out}")

print("\nDone.")
print("- Removed the built-in page-wide title/content column split.")
print("- Added cleaner SEO excerpts and featured-media signals across sales and investor pages.")
