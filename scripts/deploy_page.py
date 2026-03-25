#!/usr/bin/env python3
"""
Deploy a single page artifact to a WordPress site via REST API.

Usage:
  python scripts/deploy_page.py artifacts/page_4862_update.json

Environment expected:
  WP_URL - base site URL (e.g. https://loungenie.com/staging)
  WP_USER - username for basic auth (or left empty if using WP_AUTH)
  WP_PASS - application password for WP_USER (or left empty)
  WP_AUTH - optional single env var of the form 'user:app-password' (preferred)

The script will perform a PUT to /wp-json/wp/v2/pages/<id> if `id` present,
otherwise it will POST to create a new page.
"""
import sys
import json
import os
from urllib.parse import urljoin
import sys

try:
    import requests
except Exception:
    print("requests library required. Install with: pip install requests")
    raise


def get_auth():
    wp_auth = os.environ.get("WP_AUTH")
    if wp_auth:
        if ":" in wp_auth:
            user, pw = wp_auth.split(":", 1)
            return (user, pw)
    user = os.environ.get("WP_USER")
    pw = os.environ.get("WP_PASS")
    if user and pw:
        return (user, pw)
    raise RuntimeError("No WP credentials found in WP_AUTH or WP_USER+WP_PASS")


def main():
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass
    if len(sys.argv) < 2:
        print("Usage: deploy_page.py <artifact.json>")
        sys.exit(2)
    artifact_path = sys.argv[1]
    wp_url = os.environ.get("WP_URL")
    if not wp_url:
        raise RuntimeError("Set WP_URL environment variable (e.g. https://loungenie.com/staging)")

    with open(artifact_path, "r", encoding="utf-8") as f:
        payload = json.load(f)

    auth = get_auth()
    headers = {"Accept": "application/json"}

    session = requests.Session()
    session.auth = auth

    page_id = payload.get("id")
    if page_id:
        base = wp_url.rstrip('/')
        url = f"{base}/wp-json/wp/v2/pages/{page_id}"
        print(f"PUT {url}")
        r = session.put(url, json=payload, headers=headers)
    else:
        base = wp_url.rstrip('/')
        url = f"{base}/wp-json/wp/v2/pages"
        print(f"POST {url}")
        r = session.post(url, json=payload, headers=headers)

    try:
        r.raise_for_status()
    except Exception:
        print("Request failed:", r.status_code, r.text)
        sys.exit(1)

    out = r.json()
    print(json.dumps(out, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()
