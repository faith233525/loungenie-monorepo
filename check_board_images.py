import urllib.request, json, base64, re

cred = base64.b64encode(b'admin:i6IM cqLZ vQDC pIRk nKFr g35i').decode()
hdrs = {"Authorization": f"Basic {cred}"}
req = urllib.request.Request("https://www.loungenie.com/wp-json/wp/v2/pages/5651", headers=hdrs)
with urllib.request.urlopen(req) as r:
    page = json.loads(r.read())
content = page["content"]["rendered"]

imgs = re.findall(r"<img[^>]+>", content, re.IGNORECASE)
print(f"Found {len(imgs)} images\n")
for img in imgs:
    src = re.search(r'src=["\']([^"\']+)["\']', img)
    width = re.search(r'width=["\']([^"\']+)["\']', img)
    height = re.search(r'height=["\']([^"\']+)["\']', img)
    style = re.search(r'style=["\']([^"\']+)["\']', img)
    print(f"SRC: {src.group(1) if src else 'none'}")
    print(f"  W={width.group(1) if width else '?'} H={height.group(1) if height else '?'}")
    print(f"  style={style.group(1) if style else 'none'}")
    print()
