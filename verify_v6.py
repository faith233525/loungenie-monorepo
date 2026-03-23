import re
import urllib.request

ROOT = "https://loungenie.com/Loungenie%e2%84%a2"
PAGES = [
    ROOT + "/",
    ROOT + "/index.php/investors/",
    ROOT + "/index.php/board/",
    ROOT + "/index.php/financials/",
    ROOT + "/index.php/press/",
]

for url in PAGES:
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0", "Accept-Encoding": "identity"})
    html = urllib.request.urlopen(req, timeout=30).read().decode("utf-8", "replace")
    body = re.search(r'<body[^>]*class="([^"]+)"', html)
    print("\n===", url)
    print("body:", (body.group(1) if body else "N/A")[:200])
    print("has Investors nav label:", ">Investors<" in html)
    print("has footer investor link:", "index.php/investors/" in html and "index.php/financials/" in html and "index.php/press/" in html)
    if "index.php/investors/" in url or "index.php/board/" in url or "index.php/financials/" in url or "index.php/press/" in url:
        print("page-wide template:", "page-template-page-wide" in html)
        print("custom investor hero:", "ir-hero" in html)
        print("original wording still present sample:", any(x in html for x in ["TSX Venture Exchange", "Board of Directors", "2025 Financial Reports", "Read More"]))

home = urllib.request.urlopen(urllib.request.Request(ROOT + "/", headers={"User-Agent": "Mozilla/5.0"}), timeout=30).read().decode("utf-8", "replace")
header = re.search(r"<header[\s\S]*?</header>", home)
footer = re.search(r"<footer[\s\S]*?</footer>", home)
print("\nHEADER SNIP\n")
print((header.group(0) if header else "NO HEADER")[:2200])
print("\nFOOTER SNIP\n")
print((footer.group(0) if footer else "NO FOOTER")[:2200])
