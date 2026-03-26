#!/usr/bin/env python3
"""Inject an LG9 partner logo strip into the front page content via WP REST.

- Uses WP_URL, WP_USER, WP_APP_PASS from the environment.
- Looks up the static front page (page_on_front) and fetches content in context=edit.
- If no lg9-marquee-wrap is present, inserts a partner logo marquee just after the
  first cover block (<!-- /wp:cover -->). If no cover is found, prepends it.
- Safe to run multiple times (idempotent: skips if marker already present).

Intended for staging, e.g.:
  $env:WP_URL = 'https://loungenie.com/stage'
  $env:WP_USER = $env:WP_REST_USER
  $env:WP_APP_PASS = $env:WP_REST_PASS
  venv\Scripts\python.exe scripts/add_partner_logo_strip_home.py
"""
import json
import os
import sys

try:
    import requests  # type: ignore[import]
except Exception as exc:  # pragma: no cover
    sys.stderr.write("[add_partner_logo_strip_home] Missing 'requests' library: %s\n" % exc)
    sys.stderr.write("Install into your venv with: pip install requests\n")
    sys.exit(1)


SNIPPET = """\n<!-- PARTNER LOGO STRIP (LG9) -->\n<section class=\"lg9-section\" style=\"background:#fff;padding:32px 0;\">\n  <div style=\"text-align:center;max-width:860px;margin:0 auto 26px;padding:0 24px;\">\n    <p class=\"lg9-kicker\" style=\"font-size:0.8rem;letter-spacing:0.14em;text-transform:uppercase;color:#50637b;margin:0 0 6px;\">Trusted By Hospitality Leaders</p>\n    <h2 class=\"lg9-title-md\" style=\"margin:10px 0 14px;line-height:1.4;font-size:1.6rem;color:#0b1726;\">Used by top hotels, resorts, cruise lines, and waterparks worldwide.</h2>\n  </div>\n  <div class=\"lg9-marquee-wrap\">\n    <div class=\"lg9-marquee-track\">\n      <div class=\"lg9-marquee-inner\">\n        <span class=\"lg9-logo-mark\"><img src=\"/wp-content/uploads/2026/03/logo-marriott.webp\" alt=\"Hospitality partner logo\" /></span>\n        <span class=\"lg9-logo-mark\"><img src=\"/wp-content/uploads/2026/03/logo-hilton.webp\" alt=\"Hospitality partner logo\" /></span>\n        <span class=\"lg9-logo-mark\"><img src=\"/wp-content/uploads/2026/03/logo-westin.webp\" alt=\"Hospitality partner logo\" /></span>\n        <span class=\"lg9-logo-mark\"><img src=\"/wp-content/uploads/2026/03/logo-ritz.webp\" alt=\"Hospitality partner logo\" /></span>\n        <span class=\"lg9-logo-mark\"><img src=\"/wp-content/uploads/2026/03/logo-sixflags.webp\" alt=\"Hospitality partner logo\" /></span>\n      </div>\n    </div>\n  </div>\n</section>\n"""


def get_auth():
    user = os.environ.get("WP_USER")
    pw = os.environ.get("WP_APP_PASS") or os.environ.get("WP_PASS")
    if not (user and pw):
        raise RuntimeError("Set WP_USER and WP_APP_PASS (or WP_PASS) in environment")
    return user, pw


def main() -> None:
    base = os.environ.get("WP_URL")
    if not base:
        sys.stderr.write("WP_URL not set; set to e.g. https://loungenie.com/stage\n")
        sys.exit(1)
    base = base.rstrip("/")

    try:
        auth = get_auth()
    except RuntimeError as e:
        sys.stderr.write(str(e) + "\n")
        sys.exit(1)

    session = requests.Session()
    session.auth = auth
    headers = {"Accept": "application/json", "Content-Type": "application/json"}

    # 1. Discover static front page id
    settings_url = f"{base}/wp-json/wp/v2/settings"
    resp = session.get(settings_url, headers={"Accept": "application/json"}, timeout=30)
    try:
        resp.raise_for_status()
    except Exception as exc:  # pragma: no cover
        sys.stderr.write(f"Failed to fetch settings from {settings_url}: {exc}\n")
        sys.stderr.write(resp.text + "\n")
        sys.exit(1)

    settings = resp.json()
    front_id = settings.get("page_on_front")
    if not front_id:
        sys.stderr.write("No static front page configured (page_on_front is 0); aborting.\n")
        sys.exit(1)

    # 2. Fetch front page content in edit context to get raw blocks
    page_url = f"{base}/wp-json/wp/v2/pages/{front_id}?context=edit"
    resp = session.get(page_url, headers={"Accept": "application/json"}, timeout=30)
    try:
        resp.raise_for_status()
    except Exception as exc:  # pragma: no cover
        sys.stderr.write(f"Failed to fetch front page {front_id}: {exc}\n")
        sys.stderr.write(resp.text + "\n")
        sys.exit(1)

    page = resp.json()
    raw = page.get("content", {}).get("raw", "") or ""
    if not raw:
        sys.stderr.write("Front page content is empty; nothing to modify.\n")
        sys.exit(1)

    if "lg9-marquee-wrap" in raw:
        print("Partner logo strip already present; no changes made.")
        return

    anchor = "<!-- /wp:cover -->"
    idx = raw.find(anchor)
    if idx != -1:
        insert_at = idx + len(anchor)
        new_raw = raw[:insert_at] + "\n\n" + SNIPPET + "\n" + raw[insert_at:]
        where = "after first cover block"
    else:
        new_raw = SNIPPET + "\n\n" + raw
        where = "at top of content (no cover block marker found)"

    update_payload = {"content": new_raw}
    update_url = f"{base}/wp-json/wp/v2/pages/{front_id}"
    resp = session.post(update_url, headers=headers, data=json.dumps(update_payload), timeout=45)
    try:
        resp.raise_for_status()
    except Exception as exc:  # pragma: no cover
        sys.stderr.write(f"Failed to update front page {front_id}: {exc}\n")
        sys.stderr.write(resp.text + "\n")
        sys.exit(1)

    updated = resp.json()
    print(f"Updated front page {front_id} with LG9 partner logo strip ({where}).")
    print(f"Status: {updated.get('status')}, rendered length: {len(updated.get('content', {}).get('rendered', '') )}")


if __name__ == "__main__":  # pragma: no cover
    main()
