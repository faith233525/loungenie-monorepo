import base64
import json
from pathlib import Path
import requests
import sys
import os
import time

# Get environment variables - try each naming convention
wp_url = os.environ.get('WP_SITE_URL') or os.environ.get('STAGING_WP_URL') or os.environ.get('WP_URL') or ''
wp_user = os.environ.get('WP_REST_USER') or os.environ.get('STAGING_WP_USER') or os.environ.get('WP_REST_USERNAME') or os.environ.get('WP_USER') or ''
wp_pass = os.environ.get('WP_REST_PASS') or os.environ.get('STAGING_WP_APP_PASSWORD') or os.environ.get('WP_REST_PASSWORD') or os.environ.get('WP_APP_PASS') or ''

# Debug output
sys.stderr.write(f"DEBUG: WP_SITE_URL={wp_url if wp_url else '(not set)'}\n")
sys.stderr.write(f"DEBUG: WP_REST_USER={wp_user if wp_user else '(not set)'}\n")
sys.stderr.write(f"DEBUG: WP_REST_PASS={'****' if wp_pass else '(not set)'}\n")
sys.stderr.flush()

BASE = wp_url.rstrip("/") if wp_url else ""
USER = wp_user
APP = wp_pass

if not BASE or not USER or not APP:
    print("\n❌ MISSING WORDPRESS CREDENTIALS")
    print(f"  WP_SITE_URL: {BASE or '(not set)'}")
    print(f"  WP_REST_USER: {USER or '(not set)'}")
    print(f"  WP_REST_PASS: {'****' if APP else '(not set)'}\n")
    print("Set these GitHub Actions secrets and re-run the workflow.")
    sys.exit(1)

auth = base64.b64encode(f"{USER}:{APP}".encode()).decode()
HEADERS = {"Authorization": f"Basic {auth}"}

pages_dir = Path("content/pages")
payloads = sorted(pages_dir.glob("*.html"))

def find_page(slug: str):
    for attempt in range(3):
        try:
            r = requests.get(f"{BASE}/wp-json/wp/v2/pages", params={"slug": slug, "per_page": 1}, headers=HEADERS, timeout=60)
            r.raise_for_status()
            data = r.json()
            return data[0] if data else None
        except requests.exceptions.Timeout:
            if attempt < 2:
                print(f"  (timeout on {slug}, retrying...)")
                time.sleep(2)
            else:
                raise

def update_page(page_id: int, html: str, title: str):
    for attempt in range(3):
        try:
            body = {"content": html, "title": title, "status": "publish"}
            r = requests.post(f"{BASE}/wp-json/wp/v2/pages/{page_id}", headers={**HEADERS, "Content-Type":"application/json"}, data=json.dumps(body), timeout=60)
            r.raise_for_status()
            return r.json()
        except requests.exceptions.Timeout:
            if attempt < 2:
                print(f"  (timeout on page {page_id}, retrying...)")
                time.sleep(2)
            else:
                raise

for f in payloads:
    slug = f.stem.lower()
    html = f.read_text(encoding="utf-8")
    page = find_page(slug)
    if not page:
        print(f"SKIP {slug}: page not found")
        continue
    updated = update_page(page["id"], html, page.get("title",{}).get("rendered", slug.title()))
    print(f"UPDATED {slug} -> id {updated['id']}")
    time.sleep(1)  # Delay between requests to avoid API overload
