#!/usr/bin/env python3
import argparse
import base64
import json
import os
import re
import sys
from pathlib import Path

import requests

PAGES = {
    "investors": 5668,
    "board": 5651,
    "financials": 5686,
    "press": 5716,
}


def build_auth_header(username: str, app_password: str) -> dict:
    token = base64.b64encode(f"{username}:{app_password}".encode("utf-8")).decode("ascii")
    return {
        "Authorization": f"Basic {token}",
        "Content-Type": "application/json",
    }


def load_backup_html(page_name: str, backup_dir: Path) -> str:
    path = backup_dir / f"{page_name}_protected_backup.html"
    if not path.exists():
        raise FileNotFoundError(f"Backup not found: {path}")

    raw = path.read_text(encoding="utf-8")
    # Strip optional leading metadata comment block.
    return re.sub(r"^<!--.*?-->\s*", "", raw, flags=re.DOTALL)


def restore_page(base_api: str, headers: dict, page_name: str, page_id: int, content: str, timeout: int) -> tuple[int, str]:
    url = f"{base_api}/pages/{page_id}"
    resp = requests.post(
        url,
        headers=headers,
        json={"content": content, "status": "publish"},
        timeout=timeout,
    )

    if resp.status_code != 200:
        return resp.status_code, resp.text[:400]

    data = resp.json()
    modified = data.get("modified", "unknown")
    rendered_len = len(data.get("content", {}).get("rendered", ""))
    return 200, f"modified={modified} rendered_len={rendered_len}"


def main() -> int:
    parser = argparse.ArgumentParser(description="Restore production investor pages from protected backup HTML files.")
    parser.add_argument(
        "--base-api",
        default="https://www.loungenie.com/wp-json/wp/v2",
        help="WordPress REST API base URL (default: production)",
    )
    parser.add_argument(
        "--backup-dir",
        default=".",
        help="Directory containing *_protected_backup.html files",
    )
    parser.add_argument(
        "--timeout",
        type=int,
        default=60,
        help="HTTP timeout seconds",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Validate auth and backups without publishing changes",
    )
    args = parser.parse_args()

    username = os.environ.get("WP_PROD_USER")
    password = os.environ.get("WP_PROD_APP_PASSWORD")

    if not username or not password:
        print("Missing required environment variables: WP_PROD_USER and WP_PROD_APP_PASSWORD")
        return 2

    backup_dir = Path(args.backup_dir).resolve()
    if not backup_dir.exists():
        print(f"Backup directory does not exist: {backup_dir}")
        return 2

    headers = build_auth_header(username, password)

    # Permission check.
    perm_check = requests.get(f"{args.base_api}/pages/5668?context=edit", headers=headers, timeout=args.timeout)
    if perm_check.status_code != 200:
        print(f"Auth/permission check failed: HTTP {perm_check.status_code}")
        print(perm_check.text[:300])
        return 3

    print("Auth/permission check passed.")

    contents = {}
    for name in PAGES:
        try:
            contents[name] = load_backup_html(name, backup_dir)
            print(f"Loaded backup: {name} ({len(contents[name])} chars)")
        except Exception as exc:
            print(f"Failed loading backup for {name}: {exc}")
            return 4

    if args.dry_run:
        print("Dry-run complete. No pages were modified.")
        return 0

    failures = 0
    for name, page_id in PAGES.items():
        status, detail = restore_page(args.base_api, headers, name, page_id, contents[name], args.timeout)
        if status == 200:
            print(f"OK {name} ({page_id}) {detail}")
        else:
            failures += 1
            print(f"FAIL {name} ({page_id}) HTTP {status}")
            print(detail)

    if failures:
        print(f"Completed with {failures} failed page restore(s).")
        return 5

    print("All investor pages restored successfully.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
