import base64
import json
import re
import urllib.request

AUTH = base64.b64encode(b"copilot:7NiL OZ17 ApP3 tIgF 6zlT ug7u").decode()
HEADERS = {"Authorization": f"Basic {AUTH}", "User-Agent": "Mozilla/5.0"}
BASE = "https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2/pages"

for pid in [5668, 5651, 5686, 5716]:
    req = urllib.request.Request(f"{BASE}/{pid}?context=edit", headers=HEADERS)
    data = json.loads(urllib.request.urlopen(req, timeout=30).read())
    print("\nPAGE", pid, data["title"]["rendered"], "template", data.get("template"))
    raw = data["content"]["raw"]
    print(raw[:5000])

url = "https://loungenie.com/Loungenie%E2%84%A2/index.php/investors/"
req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0", "Accept-Encoding": "identity"})
html = urllib.request.urlopen(req, timeout=30).read().decode("utf-8", "replace")
main = re.search(r"<main[\s\S]*?</main>", html)
print("\nRENDERED MAIN\n")
print((main.group(0) if main else "NO MAIN")[:5000])
