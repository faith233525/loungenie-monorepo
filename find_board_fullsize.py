import urllib.request, json, base64

cred = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
hdrs = {"Authorization": "Basic " + cred}
base = "https://www.loungenie.com/wp-json/wp/v2"

searches = [
    ("David-Berger",  "David Berger"),
    ("Steven-Mintz",  "Steven Mintz"),
    ("GD-pic",        "Gillian Deacon"),
    ("Robert-Pratt",  "Robert Pratt"),
]

for keyword, person in searches:
    req = urllib.request.Request(f"{base}/media?search={keyword}&per_page=5", headers=hdrs)
    with urllib.request.urlopen(req) as r:
        items = json.loads(r.read())
    print(f"\n=== {person} ({len(items)} results) ===")
    for item in items:
        print(f"  source: {item['source_url']}")
        sizes = item.get("media_details", {}).get("sizes", {})
        for sname, s in sizes.items():
            print(f"    [{sname:15}] {s['width']:4}x{s['height']:4} -> {s['source_url'].split('/')[-1]}")
