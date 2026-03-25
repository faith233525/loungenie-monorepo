import re
import urllib.request

url = "https://loungenie.com/Loungenie%e2%84%a2/"
req = urllib.request.Request(
    url,
    headers={"User-Agent": "Mozilla/5.0", "Accept-Encoding": "identity"},
)
html = urllib.request.urlopen(req, timeout=30).read().decode("utf-8", "replace")

markers = [
    "elementor-frontend-css",
    "post-3824.css",
    "hfe-style",
    "elementor",
    "elementor-kit",
    "ehf-template",
]

print("ASSET CHECK")
for marker in markers:
    print(f"{marker}: {marker in html}")

body = re.search(r'<body[^>]*class="([^"]+)"', html)
print("\nBODY:")
print((body.group(1) if body else "N/A")[:300])

print("\nNAV CHECK")
print("short nav labels:", all(x in html for x in [">Home<", ">Features<", ">Gallery<", ">Videos<", ">About<", ">Contact<"]))
print("site title block present in HTML:", "wp-block-site-title" in html)
