#!/usr/bin/env python3
"""
Fetch Investors/Board/Financials/Press pages from staging and create draft copies.
Usage: python scripts/create_ir_drafts.py
"""
import sys
import json
from pathlib import Path

# Credentials (from earlier validated REST auth)
USER = "copilot"
PASS = "SBlI yPMK 5crY p3Lo FOtF M3Tw"
BASE = "https://loungenie.com/staging"
SLUGS = ["investors", "board", "financials", "press"]

try:
    import requests
except Exception:
    requests = None

ART = Path(r"c:/Users/pools/Documents/wordpress-develop/artifacts")
ART.mkdir(parents=True, exist_ok=True)

def fetch_page(slug):
    url = f"{BASE}/wp-json/wp/v2/pages?slug={slug}"
    if requests:
        r = requests.get(url, auth=(USER, PASS), verify=False)
        r.raise_for_status()
        return r.json()
    else:
        # fallback using urllib
        from urllib.request import Request, urlopen
        import base64
        req = Request(url)
        cred = (USER + ":" + PASS).encode('utf-8')
        req.add_header('Authorization', 'Basic ' + base64.b64encode(cred).decode('ascii'))
        resp = urlopen(req)
        return json.load(resp)


def create_draft(title, content, slug):
    url = f"{BASE}/wp-json/wp/v2/pages"
    payload = {"title": title, "content": content, "status": "draft", "slug": f"{slug}-draft"}
    if requests:
        r = requests.post(url, auth=(USER, PASS), json=payload, verify=False)
        r.raise_for_status()
        return r.json()
    else:
        from urllib.request import Request, urlopen
        import base64
        data = json.dumps(payload).encode('utf-8')
        req = Request(url, data=data, method='POST')
        req.add_header('Content-Type', 'application/json')
        cred = (USER + ":" + PASS).encode('utf-8')
        req.add_header('Authorization', 'Basic ' + base64.b64encode(cred).decode('ascii'))
        resp = urlopen(req)
        return json.load(resp)


def main():
    results = []
    for slug in SLUGS:
        print(f"Processing slug: {slug}")
        try:
            resp = fetch_page(slug)
        except Exception as e:
            print(f"  ERROR fetching {slug}: {e}")
            continue
        if not resp:
            print(f"  No page found for slug '{slug}'")
            continue
        page = resp[0]
        title = page.get('title', {}).get('rendered') or page.get('title')
        content = page.get('content', {}).get('rendered') or page.get('content')
        # preserve content verbatim
        try:
            created = create_draft(title, content, slug)
            pid = created.get('id')
            edit_link = f"{BASE}/wp-admin/post.php?post={pid}&action=edit"
            preview_link = f"{BASE}/?p={pid}&preview=true"
            print(f"  Created draft id={pid} edit={edit_link}")
            results.append({"source_slug": slug, "draft_id": pid, "edit_link": edit_link, "preview_link": preview_link})
        except Exception as e:
            print(f"  ERROR creating draft for {slug}: {e}")

    out = ART / 'ir-drafts-created.json'
    out.write_text(json.dumps(results, indent=2))
    print('\nSummary written to:', out)

if __name__ == '__main__':
    main()
