#!/usr/bin/env python3
import argparse
import base64
import hashlib
import json
import re
import urllib.request
from pathlib import Path

DEFAULT_BASE_API = "https://loungenie.com/staging/wp-json/wp/v2"
DEFAULT_USER = "Copilot"
DEFAULT_APP_PASSWORD = "U7GM Z9qE QOq6 MQva IzcQ 6PU2"

PAGES = {
    "investors": 5668,
    "board": 5651,
    "financials": 5686,
    "press": 5716,
}


def build_headers(user: str, app_password: str) -> dict[str, str]:
    token = base64.b64encode(f"{user}:{app_password}".encode("utf-8")).decode("ascii")
    return {
        "Authorization": f"Basic {token}",
        "User-Agent": "GitHub-Copilot/check-investor-pages",
    }


def request_json(url: str, headers: dict[str, str]) -> dict:
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req, timeout=30) as response:
        return json.loads(response.read().decode("utf-8"))


def clean_backup_body(text: str) -> str:
    parts = text.split("\n\n", 1)
    return parts[1] if len(parts) == 2 else text


def sha256_text(content: str) -> str:
    return hashlib.sha256(content.encode("utf-8")).hexdigest()


def main() -> int:
    parser = argparse.ArgumentParser(description="Verify staging investor pages against protected backups.")
    parser.add_argument("--base-api", default=DEFAULT_BASE_API, help="WordPress REST API base URL")
    parser.add_argument("--user", default=DEFAULT_USER, help="WordPress username")
    parser.add_argument("--app-password", default=DEFAULT_APP_PASSWORD, help="WordPress application password")
    parser.add_argument("--backup-dir", default=".", help="Directory containing *_protected_backup.html files")
    args = parser.parse_args()

    headers = build_headers(args.user, args.app_password)
    backup_dir = Path(args.backup_dir).resolve()

    print("=== INVESTOR PAGE CONTENT VERIFICATION ===\n")
    print(f"Base API: {args.base_api}\n")

    failures: list[str] = []

    for name, page_id in PAGES.items():
        backup_path = backup_dir / f"{name}_protected_backup.html"
        print(f"Checking {name} (ID {page_id})...")

        if not backup_path.exists():
            print(f"  missing backup: {backup_path}")
            failures.append(name)
            print()
            continue

        page = request_json(f"{args.base_api}/pages/{page_id}?context=edit", headers)
        current_raw = page.get("content", {}).get("raw", "")
        current_hash = sha256_text(current_raw)
        modified = page.get("modified", "N/A")
        title = page.get("title", {}).get("rendered", name.title())

        backup_raw = clean_backup_body(backup_path.read_text(encoding="utf-8"))
        backup_hash = sha256_text(backup_raw)
        matches = current_hash == backup_hash

        print(f"  title: {title}")
        print(f"  modified: {modified}")
        print(f"  backup sha256:  {backup_hash}")
        print(f"  current sha256: {current_hash}")
        print(f"  matches backup: {matches}")

        if not matches:
            failures.append(name)

        print()

    if failures:
        print("MISMATCHES DETECTED:")
        for name in failures:
            print(f"  - {name}")
        print("\nUse: python restore_investor_pages.py [page-name] --dry-run")
        return 1

    print("All protected investor pages match their backups.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
