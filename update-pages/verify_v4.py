import urllib.request
import re

url = "https://loungenie.com/Loungenie%e2%84%a2/"
req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0", "Accept-Encoding": "identity"})
html = urllib.request.urlopen(req, timeout=30).read().decode("utf-8", "replace")

print("page-wide:", "page-template-page-wide" in html)
print("has .lg:", '<div class="lg">' in html)
print("short nav labels:", all(x in html for x in [">Home<", ">Features<", ">Gallery<", ">Videos<", ">About<", ">Contact<"]))
print("old long home label present:", "Home | LounGenie Poolside Revenue Platform" in html)
print("emoji entities present sample:", ("&#x1f3c6;" in html) or ("&#x1f4c5;" in html))
print("header sticky CSS present:", ".wp-site-blocks > header.wp-block-template-part" in html)

m = re.search(r"<header[\s\S]*?</header>", html)
print("\nheader snippet:\n")
print(m.group(0)[:2200] if m else "NO HEADER")
