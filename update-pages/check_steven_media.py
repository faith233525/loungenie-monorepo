import urllib.request, json, base64

cred = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
hdrs = {"Authorization": "Basic " + cred}
req = urllib.request.Request(
    "https://www.loungenie.com/wp-json/wp/v2/media?search=steven-glaser&per_page=20",
    headers=hdrs
)
with urllib.request.urlopen(req) as r:
    items = json.loads(r.read())

print(f"Found {len(items)} media items for 'steven-glaser'")
for item in items:
    print(f"\n  ID={item['id']} title={item['title']['rendered']}")
    print(f"  source: {item['source_url']}")
    sizes = item.get("media_details", {}).get("sizes", {})
    for name, s in sizes.items():
        print(f"    [{name}] {s['width']}x{s['height']} -> {s['source_url']}")

# Also try broader search
req2 = urllib.request.Request(
    "https://www.loungenie.com/wp-json/wp/v2/media?search=glaser&per_page=20",
    headers=hdrs
)
with urllib.request.urlopen(req2) as r:
    items2 = json.loads(r.read())
if len(items2) != len(items):
    print(f"\n\nAlso found {len(items2)} for 'glaser':")
    for item in items2:
        print(f"  ID={item['id']} src={item['source_url']}")
