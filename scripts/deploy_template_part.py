#!/usr/bin/env python3
"""
Deploy a template-part (header/footer) artifact to WordPress via REST API.

Usage:
  python scripts/deploy_template_part.py artifacts/header_payload.json

Environment expected:
  WP_URL - base site URL (e.g. https://loungenie.com/staging)
  WP_AUTH - 'user:app-password' or WP_USER/WP_PASS pair

This will POST a new template-part if no `id` present, or PUT to update if `id` exists.
"""
import sys
import json
import os
import argparse
from urllib.parse import urljoin

try:
    import requests
except Exception:
    print("requests library required. Install with: pip install requests")
    raise


def get_auth(override_auth=None):
    if override_auth:
        if ":" in override_auth:
            user, pw = override_auth.split(":", 1)
            return (user, pw)
        raise RuntimeError("--auth must be in the form user:app-password")
    wp_auth = os.environ.get("WP_AUTH")
    if wp_auth and ":" in wp_auth:
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
    parser = argparse.ArgumentParser(description="Deploy a template-part artifact to WordPress via REST API.")
    parser.add_argument("artifact", help="path to artifact json file")
    parser.add_argument("--url", help="WP base URL (e.g. https://loungenie.com/staging)")
    parser.add_argument("--auth", help="Override auth as user:app-password")
    args = parser.parse_args()
    artifact_path = args.artifact
    wp_url = args.url or os.environ.get("WP_URL")
    if not wp_url:
        raise RuntimeError("Set WP_URL environment variable or pass --url (e.g. https://loungenie.com/staging)")

    with open(artifact_path, "r", encoding="utf-8") as f:
        payload = json.load(f)

    auth = get_auth(override_auth=args.auth)
    headers = {"Accept": "application/json"}

    session = requests.Session()
    session.auth = auth

    # WP template-part endpoint (try singular, fallback to plural)
    base = wp_url.rstrip('/')
    part_id = payload.get('id')
    if part_id:
        url = f"{base}/wp-json/wp/v2/template-part/{part_id}"
        print(f"PUT {url}")
        r = session.put(url, json=payload, headers=headers)
        if r.status_code == 404:
            url = f"{base}/wp-json/wp/v2/template-parts/{part_id}"
            print(f"PUT fallback {url}")
            r = session.put(url, json=payload, headers=headers)
    else:
        url = f"{base}/wp-json/wp/v2/template-part"
        print(f"POST {url}")
        r = session.post(url, json=payload, headers=headers)
        if r.status_code == 404:
            url = f"{base}/wp-json/wp/v2/template-parts"
            print(f"POST fallback {url}")
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
