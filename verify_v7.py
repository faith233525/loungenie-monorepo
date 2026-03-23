import re
import urllib.request

ROOT = "https://loungenie.com/Loungenie%e2%84%a2"
for url in [ROOT + "/", ROOT + "/index.php/investors/"]:
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0", "Accept-Encoding": "identity"})
    html = urllib.request.urlopen(req, timeout=30).read().decode("utf-8", "replace")
    print("\n===", url)
    print("has old columns title shell:", '<div class="wp-block-columns alignwide"' in html and 'wp-block-post-title' in html)
    print("main snippet:\n", html[html.find('<main'):html.find('</main>')+7][:3200])
    title = re.search(r'<title>(.*?)</title>', html, re.I | re.S)
    desc = re.search(r'<meta name="description" content="([^"]+)"', html, re.I)
    og = re.search(r'<meta property="og:image" content="([^"]+)"', html, re.I)
    print("title:", title.group(1)[:120] if title else 'N/A')
    print("desc:", desc.group(1)[:180] if desc else 'N/A')
    print("og:image:", og.group(1) if og else 'N/A')
