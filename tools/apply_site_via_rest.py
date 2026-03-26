import base64
import json
from pathlib import Path
import requests

BASE = (Path.cwd() and __import__("os").environ.get("STAGING_WP_URL","")).rstrip("/")
USER = __import__("os").environ.get("STAGING_WP_USER","")
APP  = __import__("os").environ.get("STAGING_WP_APP_PASSWORD","")

if not BASE or not USER or not APP:
    raise SystemExit("Missing STAGING_WP_URL / STAGING_WP_USER / STAGING_WP_APP_PASSWORD")

auth = base64.b64encode(f"{USER}:{APP}".encode()).decode()
HEADERS = {"Authorization": f"Basic {auth}"}

pages_dir = Path("content/pages")
payloads = sorted(pages_dir.glob("*.html"))

def find_page(slug: str):
    r = requests.get(f"{BASE}/wp-json/wp/v2/pages", params={"slug": slug, "per_page": 1}, headers=HEADERS, timeout=30)
    r.raise_for_status()
    data = r.json()
    return data[0] if data else None

def update_page(page_id: int, html: str, title: str):
    body = {"content": html, "title": title, "status": "publish"}
    r = requests.post(f"{BASE}/wp-json/wp/v2/pages/{page_id}", headers={**HEADERS, "Content-Type":"application/json"}, data=json.dumps(body), timeout=30)
    r.raise_for_status()
    return r.json()

for f in payloads:
    slug = f.stem.lower()
    html = f.read_text(encoding="utf-8")
    page = find_page(slug)
    if not page:
        print(f"SKIP {slug}: page not found")
        continue
    updated = update_page(page["id"], html, page.get("title",{}).get("rendered", slug.title()))
    print(f"UPDATED {slug} -> id {updated['id']}")
