import urllib.request, re

url = "https://loungenie.com/Loungenie%e2%84%a2/"
req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0", "Accept-Encoding": "identity"})
with urllib.request.urlopen(req, timeout=30) as r:
    html = r.read().decode("utf-8", "replace")

body = re.search(r'body[^>]*class="([^"]+)"', html)
print("body class:", body.group(1)[:220] if body else "NOT FOUND")
print()
print("Has .lg div:           ", '<div class="lg">' in html)
print("page-template-page-wide:", "page-template-page-wide" in html)
print("has-global-padding:     ", "has-global-padding" in html)
print("entry-content .lg CSS:  ", "entry-content .lg" in html)
print("Inter font:             ", "fonts.googleapis.com" in html)
print()
idx = html.find('<div class="lg">')
if idx >= 0:
    print("Around .lg:", html[max(0,idx-80):idx+220])
else:
    print("*** .lg div NOT FOUND ***")
    idx2 = html.find("<!-- wp:html -->")
    print("wp:html block present:", idx2 >= 0)
    idx3 = html.find("wp-block-post-content")
    if idx3 >= 0:
        print("post-content area:", html[idx3:idx3+400])
