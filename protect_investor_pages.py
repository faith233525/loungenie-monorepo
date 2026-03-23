#!/usr/bin/env python3
import argparse
import base64
import hashlib
import json
import re
import urllib.request
from datetime import datetime, timezone
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
        "Content-Type": "application/json",
        "User-Agent": "GitHub-Copilot/backup-investor-pages",
    }


def request_json(url: str, headers: dict[str, str]) -> dict:
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req, timeout=30) as response:
        return json.loads(response.read().decode("utf-8"))


def clean_backup_body(text: str) -> str:
    parts = text.split("\n\n", 1)
    return parts[1] if len(parts) == 2 else text


def page_hash(content: str) -> str:
    return hashlib.sha256(content.encode("utf-8")).hexdigest()


def main() -> int:
    parser = argparse.ArgumentParser(description="Back up and verify staging investor pages.")
    parser.add_argument("--base-api", default=DEFAULT_BASE_API, help="WordPress REST API base URL")
    parser.add_argument("--user", default=DEFAULT_USER, help="WordPress username")
    parser.add_argument("--app-password", default=DEFAULT_APP_PASSWORD, help="WordPress application password")
    parser.add_argument("--output-dir", default=".", help="Directory for *_protected_backup.html files")
    parser.add_argument("--verify-only", action="store_true", help="Do not write backups; only compare live content to existing backups")
    args = parser.parse_args()

    headers = build_headers(args.user, args.app_password)
    output_dir = Path(args.output_dir).resolve()
    output_dir.mkdir(parents=True, exist_ok=True)
    timestamp = datetime.now(timezone.utc).isoformat()

    print("=== INVESTOR PAGE BACKUP / VERIFICATION ===\n")
    print(f"Base API: {args.base_api}")
    print(f"Mode: {'verify-only' if args.verify_only else 'backup-and-verify'}\n")

    failures: list[str] = []

    for name, page_id in PAGES.items():
        backup_path = output_dir / f"{name}_protected_backup.html"
        page = request_json(f"{args.base_api}/pages/{page_id}?context=edit", headers)
        raw = page.get("content", {}).get("raw", "")
        rendered = page.get("content", {}).get("rendered", "")
        title = page.get("title", {}).get("rendered", name.title())
        live_hash = page_hash(raw)

        print(f"{name.upper()} (ID {page_id})")
        print(f"  title: {title}")
        print(f"  raw length: {len(raw)}")
        print(f"  rendered length: {len(rendered)}")
        print(f"  live sha256: {live_hash}")

        if not args.verify_only:
            backup_text = (
                f"<!-- PROTECTED PAGE BACKUP: {name.upper()} ID {page_id} -->\n"
                f"<!-- Base API: {args.base_api} -->\n"
                f"<!-- Backed Up UTC: {timestamp} -->\n"
                f"<!-- Title: {title} -->\n"
                f"<!-- Content SHA256: {live_hash} -->\n\n"
                f"{raw}"
            )
            backup_path.write_text(backup_text, encoding="utf-8")
            print(f"  wrote backup: {backup_path.name}")

        if backup_path.exists():
            backup_raw = clean_backup_body(backup_path.read_text(encoding="utf-8"))
            backup_hash = page_hash(backup_raw)
            matches = backup_hash == live_hash
            print(f"  backup sha256: {backup_hash}")
            print(f"  matches backup: {matches}")
            if not matches:
                failures.append(name)
        else:
            print("  backup sha256: missing")
            print("  matches backup: False")
            failures.append(name)

        print()

    if failures:
        print("MISMATCHES DETECTED:")
        for name in failures:
            print(f"  - {name}")
        return 1

    print("All protected investor pages match their backups.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
