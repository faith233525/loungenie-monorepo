import base64
import json
from pathlib import Path
import requests
import sys

# Try multiple env var names for flexibility
os = __import__("os")

# Debug: show all env vars containing WP
print("DEBUG - Environment variables:")
for key in sorted(os.environ.keys()):
    if 'WP' in key.upper() or 'STAGING' in key.upper():
        val = os.environ.get(key, '')
        masked = val[:10] + '...' if len(val) > 10 else val
        print(f"  {key}: {masked}")

BASE = (os.environ.get("STAGING_WP_URL") or 
        os.environ.get("WP_SITE_URL") or 
        os.environ.get("WP_URL") or "").rstrip("/")

USER = (os.environ.get("STAGING_WP_USER") or 
        os.environ.get("WP_REST_USER") or 
        os.environ.get("WP_REST_USERNAME") or 
        os.environ.get("WP_USER") or "")

APP = (os.environ.get("STAGING_WP_APP_PASSWORD") or 
       os.environ.get("WP_REST_PASSWORD") or 
       os.environ.get("WP_REST_PASS") or 
       os.environ.get("WP_APP_PASS") or "")

if not BASE or not USER or not APP:
    print("\n❌ MISSING WORDPRESS CREDENTIALS")
    print("\nRequired env vars (set one of each group):\n")
    print("  WordPress URL:")
    print("    • STAGING_WP_URL, WP_SITE_URL, or WP_URL")
    print(f"    Current: {BASE or '(not set)'}\n")
    print("  WordPress User:")
    print("    • STAGING_WP_USER, WP_REST_USER, WP_REST_USERNAME, or WP_USER")
    print(f"    Current: {USER or '(not set)'}\n")
    print("  App Password:")
    print("    • STAGING_WP_APP_PASSWORD, WP_REST_PASSWORD, WP_REST_PASS, or WP_APP_PASS")
    print(f"    Current: {'****' if APP else '(not set)'}\n")
    print("To deploy your pages, add these as GitHub Actions secrets and re-run the workflow.")
    sys.exit(1)

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
