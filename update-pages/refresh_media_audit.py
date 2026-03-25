import base64
import csv
import json
import urllib.request
import urllib.error

AUTH = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
HEADERS = {
    "Authorization": f"Basic {AUTH}",
    "User-Agent": "Mozilla/5.0",
}
BASE = "https://www.loungenie.com/wp-json/wp/v2/media"

rows = []
page = 1
while True:
    url = f"{BASE}?per_page=100&page={page}&_fields=id,slug,title,alt_text,source_url,mime_type,media_details"
    req = urllib.request.Request(url, headers=HEADERS)
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            data = json.loads(resp.read())
    except urllib.error.HTTPError as e:
        if e.code == 400:
            break
        raise
    if not data:
        break

    for it in data:
        md = it.get("media_details") or {}
        rows.append({
            "id": it.get("id", ""),
            "slug": it.get("slug", ""),
            "title": (it.get("title") or {}).get("rendered", ""),
            "alt_text": it.get("alt_text", ""),
            "source_url": it.get("source_url", ""),
            "mime_type": it.get("mime_type", ""),
            "file": md.get("file", ""),
            "filesize": md.get("filesize", 0) or 0,
            "width": md.get("width", 0) or 0,
            "height": md.get("height", 0) or 0,
        })
    if len(data) < 100:
        break
    page += 1

with open("media_audit.csv", "w", newline="", encoding="utf-8") as f:
    w = csv.DictWriter(f, fieldnames=["id","slug","title","alt_text","source_url","mime_type","file","filesize","width","height"])
    w.writeheader()
    w.writerows(rows)

print(f"TOTAL={len(rows)}")
