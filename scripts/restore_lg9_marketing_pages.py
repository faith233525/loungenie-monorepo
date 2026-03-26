#!/usr/bin/env python3
"""Restore LG9 marketing page layouts on a target WordPress site via REST.

This script takes the block-based LG9 layouts from the professional redesign
backup JSON and applies them to matching pages on the target site by slug.

Environment variables:
  WP_URL      - Base URL of the WordPress site, e.g. https://loungenie.com/stage
  WP_USER     - REST user (typically same as WP_REST_USER)
  WP_APP_PASS - Application password for the REST user (or WP_PASS as fallback)

Optional:
  LG9_BACKUP_PATH - Override path to the JSON backup file. Defaults to
                    backups/professional-redesign-v12-live-backup-20260321-175613.json

Usage (staging example):
  $env:WP_URL = 'https://loungenie.com/stage'
  $env:WP_USER = $env:WP_REST_USER
  $env:WP_APP_PASS = $env:WP_REST_PASS
  venv\Scripts\python.exe scripts/restore_lg9_marketing_pages.py

Pages restored (by slug):
  - poolside-amenity-unit      (Features)
  - hospitality-innovation     (About)
  - contact-loungenie          (Contact)
  - cabana-installation-photos (Gallery)
  - loungenie-videos           (Videos)

This performs a full content replace for these pages on the target site.
"""

import json
import os
import sys
from typing import Dict, Any, List

try:
    import requests  # type: ignore[import]
except Exception as exc:  # pragma: no cover
    sys.stderr.write("[restore_lg9_marketing_pages] Missing 'requests' library: %s\n" % exc)
    sys.stderr.write("Install into your venv with: pip install requests\n")
    sys.exit(1)


DEFAULT_BACKUP_PATH = os.path.join(
    os.path.dirname(os.path.dirname(__file__)),
    "backups",
    "professional-redesign-v12-live-backup-20260321-175613.json",
)

TARGET_SLUGS: List[str] = [
    "home",
    "poolside-amenity-unit",
    "hospitality-innovation",
    "contact-loungenie",
    "cabana-installation-photos",
    "loungenie-videos",
]


def get_auth() -> tuple[str, str]:
    user = os.environ.get("WP_USER")
    pw = os.environ.get("WP_APP_PASS") or os.environ.get("WP_PASS")
    if not (user and pw):
        raise RuntimeError("Set WP_USER and WP_APP_PASS (or WP_PASS) in environment")
    return user, pw


def load_backup(path: str) -> Dict[str, str]:
    """Load backup JSON and return mapping slug -> content.rendered HTML."""
    if not os.path.isfile(path):
        raise FileNotFoundError(f"Backup JSON not found at {path}")

    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)

    by_slug: Dict[str, str] = {}
    if not isinstance(data, list):
        raise ValueError("Backup JSON is not a list; unexpected structure")

    for entry in data:
        try:
            slug = entry.get("slug")
            rendered = entry.get("content", {}).get("rendered", "")
        except AttributeError:
            continue
        if slug and rendered:
            by_slug[slug] = rendered

    return by_slug


def restore_page(session: requests.Session, base: str, slug: str, html: str) -> None:
    """Find a page by slug on the target site and replace its content."""
    list_url = f"{base}/wp-json/wp/v2/pages?slug={slug}"
    resp = session.get(list_url, headers={"Accept": "application/json"}, timeout=30)
    try:
        resp.raise_for_status()
    except Exception as exc:  # pragma: no cover
        sys.stderr.write(f"[restore:{slug}] Failed to query pages: {exc}\n")
        sys.stderr.write(resp.text + "\n")
        return

    pages: List[Any] = resp.json() or []
    if not pages:
        sys.stderr.write(f"[restore:{slug}] No page found with this slug on target site; skipping.\n")
        return

    page = pages[0]
    page_id = page.get("id")
    if not page_id:
        sys.stderr.write(f"[restore:{slug}] Page result missing 'id'; skipping.\n")
        return

    update_url = f"{base}/wp-json/wp/v2/pages/{page_id}"
    payload = {"content": html}
    resp = session.post(
        update_url,
        headers={"Accept": "application/json", "Content-Type": "application/json"},
        data=json.dumps(payload),
        timeout=60,
    )
    try:
        resp.raise_for_status()
    except Exception as exc:  # pragma: no cover
        sys.stderr.write(f"[restore:{slug}] Failed to update page {page_id}: {exc}\n")
        sys.stderr.write(resp.text + "\n")
        return

    updated = resp.json()
    status = updated.get("status")
    rendered_len = len(updated.get("content", {}).get("rendered", ""))
    print(f"[restore:{slug}] Updated page {page_id} (status={status}, rendered_len={rendered_len}).")


def main() -> None:
    base = os.environ.get("WP_URL")
    if not base:
        sys.stderr.write("WP_URL not set; set to e.g. https://loungenie.com/stage\n")
        sys.exit(1)
    base = base.rstrip("/")

    backup_path = os.environ.get("LG9_BACKUP_PATH", DEFAULT_BACKUP_PATH)

    try:
        auth = get_auth()
    except RuntimeError as e:
        sys.stderr.write(str(e) + "\n")
        sys.exit(1)

    try:
        backup_by_slug = load_backup(backup_path)
    except Exception as exc:
        sys.stderr.write(f"Failed to load backup JSON from {backup_path}: {exc}\n")
        sys.exit(1)

    # Optional: limit restore to a single slug for safer one-page-at-a-time updates,
    # especially when targeting the live site.
    only_slug = os.environ.get("LG9_ONLY_SLUG")

    session = requests.Session()
    session.auth = auth

    print(f"Restoring LG9 marketing layouts to {base} using backup {backup_path}...")

    for slug in TARGET_SLUGS:
        if only_slug and slug != only_slug:
            continue
        html = backup_by_slug.get(slug)
        if not html:
            sys.stderr.write(f"[restore:{slug}] No backup content found for this slug; skipping.\n")
            continue
        restore_page(session, base, slug, html)

    print("Done. Review the staging site pages to confirm layouts.")


if __name__ == "__main__":  # pragma: no cover
    main()
