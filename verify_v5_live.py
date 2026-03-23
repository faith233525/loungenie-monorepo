import re
import urllib.request

BASE = "https://loungenie.com/Loungenie%e2%84%a2"
PAGES = [
    BASE + "/",
    BASE + "/index.php/poolside-amenity-unit/",
    BASE + "/index.php/hospitality-innovation/",
    BASE + "/index.php/contact-loungenie/",
    BASE + "/index.php/loungenie-videos/",
    BASE + "/index.php/cabana-installation-photos/",
]

for url in PAGES:
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0", "Accept-Encoding": "identity"})
    html = urllib.request.urlopen(req, timeout=30).read().decode("utf-8", "replace")

    title = re.search(r"<title>(.*?)</title>", html, re.I | re.S)
    body = re.search(r'<body[^>]*class="([^"]+)"', html)

    print("\n===", url)
    print("title:", (title.group(1).strip() if title else "N/A")[:110])
    print("body classes:", (body.group(1) if body else "N/A")[:180])
    print("autogen page title marker present:", "wp-block-post-title" in html)
    print("converted blocks marker (wp-block-group):", "wp-block-group" in html)
    print("legacy elementor css:", "elementor-frontend-css" in html)

home = urllib.request.urlopen(urllib.request.Request(BASE + "/", headers={"User-Agent": "Mozilla/5.0"}), timeout=30).read().decode("utf-8", "replace")
print("\n=== HEADER CHECK (home) ===")
print("logo uses high-res custom file:", "cropped-cropped-LounGenie-Logo.png" in home)
print("short nav labels:", all(x in home for x in [">Home<", ">Features<", ">Gallery<", ">Videos<", ">About<", ">Contact<"]))
