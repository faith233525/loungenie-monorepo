#!/usr/bin/env python3
"""Inject LG9 shell CSS into the staging front page via REST.

This ONLY targets the staging site when WP_URL is set to the staging URL
(e.g. https://loungenie.com/stage). It prepends a single HTML block containing
an inline <style> tag with the LG9 header/footer and marquee styles, built from
assets/css/lg9-site.css and assets/css/lg9-responsive.css in this repo.

Idempotent: if a <style> tag with id="lg9-shell-inline" is already present in
content.raw, it does nothing.

Environment variables:
  WP_URL      - Base URL of staging site, e.g. https://loungenie.com/stage
  WP_USER     - REST username for staging
  WP_APP_PASS - REST application password (or WP_PASS) for staging
"""

import json
import os
import sys
from typing import Tuple

try:
    import requests  # type: ignore[import]
except Exception as exc:  # pragma: no cover
    sys.stderr.write(f"[apply_lg9_shell_css_staging] Missing 'requests': {exc}\n")
    sys.stderr.write("Install with: pip install requests\n")
    sys.exit(1)


ROOT = os.path.dirname(os.path.dirname(__file__))


def get_auth() -> Tuple[str, str]:
    user = os.environ.get("WP_USER")
    pw = os.environ.get("WP_APP_PASS") or os.environ.get("WP_PASS")
    if not (user and pw):
        raise RuntimeError("Set WP_USER and WP_APP_PASS (or WP_PASS) for staging.")
    return user, pw


def build_css() -> str:
    css_paths = [
        os.path.join(ROOT, "assets", "css", "lg9-site.css"),
        os.path.join(ROOT, "assets", "css", "lg9-responsive.css"),
    ]
    parts = []
    for path in css_paths:
        if not os.path.isfile(path):
            raise FileNotFoundError(f"CSS file not found: {path}")
        with open(path, "r", encoding="utf-8") as f:
            parts.append(f.read())
    return "\n\n".join(parts)


def main() -> None:
    base = os.environ.get("WP_URL")
    if not base:
        sys.stderr.write("WP_URL not set; set to https://loungenie.com/stage for staging.\n")
        sys.exit(1)
    base = base.rstrip("/")

    # Safety: refuse to run if not clearly targeting staging path
    if "loungenie.com/stage" not in base:
        sys.stderr.write(f"Refusing to run: WP_URL '{base}' does not look like staging (/stage).\n")
        sys.exit(1)

    try:
        auth = get_auth()
    except RuntimeError as e:
        sys.stderr.write(str(e) + "\n")
        sys.exit(1)

    try:
        css = build_css()
    except Exception as exc:
        sys.stderr.write(f"Failed to build LG9 CSS from assets: {exc}\n")
        sys.exit(1)

    session = requests.Session()
    session.auth = auth

    # Discover static front page ID
    settings_url = f"{base}/wp-json/wp/v2/settings"
    resp = session.get(settings_url, timeout=30)
    try:
        resp.raise_for_status()
    except Exception as exc:  # pragma: no cover
        sys.stderr.write(f"Failed to fetch settings from {settings_url}: {exc}\n")
        sys.stderr.write(resp.text + "\n")
        sys.exit(1)

    settings = resp.json()
    front_id = settings.get("page_on_front")
    if not front_id:
        sys.stderr.write("No page_on_front set; nothing to update.\n")
        sys.exit(1)

    page_url = f"{base}/wp-json/wp/v2/pages/{front_id}?context=edit"
    resp = session.get(page_url, timeout=30)
    try:
        resp.raise_for_status()
    except Exception as exc:  # pragma: no cover
        sys.stderr.write(f"Failed to fetch front page {front_id}: {exc}\n")
        sys.stderr.write(resp.text + "\n")
        sys.exit(1)

    page = resp.json()
    raw = page.get("content", {}).get("raw", "") or ""

    if "lg9-shell-inline" in raw:
        print(f"Staging front page {front_id} already has lg9-shell-inline CSS; no change.")
        return

    style_block = (
        "<!-- wp:html -->\n"
        "<style id=\"lg9-shell-inline\">\n" + css + "\n" "</style>\n"
        "<!-- /wp:html -->\n"
    )

    new_content = style_block + raw

    update_url = f"{base}/wp-json/wp/v2/pages/{front_id}"
    payload = {"content": new_content}
    resp = session.post(
        update_url,
        headers={"Accept": "application/json", "Content-Type": "application/json"},
        data=json.dumps(payload),
        timeout=60,
    )
    try:
        resp.raise_for_status()
    except Exception as exc:  # pragma: no cover
        sys.stderr.write(f"Failed to update staging front page {front_id}: {exc}\n")
        sys.stderr.write(resp.text + "\n")
        sys.exit(1)

    updated = resp.json()
    status = updated.get("status")
    rendered_len = len(updated.get("content", {}).get("rendered", ""))
    print(
        f"Updated staging front page {front_id} with lg9-shell-inline CSS block "
        f"(status={status}, rendered_len={rendered_len})."
    )


if __name__ == "__main__":  # pragma: no cover
    main()
