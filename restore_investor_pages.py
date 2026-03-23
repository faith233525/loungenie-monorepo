#!/usr/bin/env python3
import argparse
import base64
import hashlib
import json
import re
import urllib.error
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
        "Content-Type": "application/json",
        "User-Agent": "GitHub-Copilot/restore-investor-pages",
    }


def request_json(url: str, headers: dict[str, str], method: str = "GET", data: dict | None = None) -> dict:
    payload = None if data is None else json.dumps(data).encode("utf-8")
    req = urllib.request.Request(url, headers=headers, method=method, data=payload)
    with urllib.request.urlopen(req, timeout=30) as response:
        return json.loads(response.read().decode("utf-8"))


def clean_backup_body(text: str) -> str:
    parts = text.split("\n\n", 1)
    return parts[1] if len(parts) == 2 else text


def sha256_text(content: str) -> str:
    return hashlib.sha256(content.encode("utf-8")).hexdigest()


def main() -> int:
    parser = argparse.ArgumentParser(description="Restore protected investor pages from local backup HTML.")
    parser.add_argument("pages", nargs="*", choices=sorted(PAGES), help="Pages to restore. Default: all protected pages")
    parser.add_argument("--base-api", default=DEFAULT_BASE_API, help="WordPress REST API base URL")
    parser.add_argument("--user", default=DEFAULT_USER, help="WordPress username")
    parser.add_argument("--app-password", default=DEFAULT_APP_PASSWORD, help="WordPress application password")
    parser.add_argument("--backup-dir", default=".", help="Directory containing *_protected_backup.html files")
    parser.add_argument("--dry-run", action="store_true", help="Validate backups and show planned updates without writing")
    args = parser.parse_args()

    headers = build_headers(args.user, args.app_password)
    backup_dir = Path(args.backup_dir).resolve()
    pages = args.pages or list(PAGES.keys())

    print("=== RESTORE PROTECTED INVESTOR PAGES ===\n")
    print(f"Base API: {args.base_api}")
    print(f"Mode: {'dry-run' if args.dry_run else 'apply'}\n")

    failures: list[str] = []

    for name in pages:
        page_id = PAGES[name]
        backup_path = backup_dir / f"{name}_protected_backup.html"
        print(f"{name.upper()} (ID {page_id})")

        if not backup_path.exists():
            print(f"  missing backup: {backup_path}")
            failures.append(name)
            print()
            continue

        backup_raw = clean_backup_body(backup_path.read_text(encoding="utf-8"))
        backup_hash = sha256_text(backup_raw)
        current = request_json(f"{args.base_api}/pages/{page_id}?context=edit", headers)
        current_raw = current.get("content", {}).get("raw", "")
        current_hash = sha256_text(current_raw)
        status = current.get("status", "publish")

        print(f"  backup sha256:  {backup_hash}")
        print(f"  current sha256: {current_hash}")
        print(f"  current status: {status}")

        if current_hash == backup_hash:
            print("  already matches backup")
            print()
            continue

        if args.dry_run:
            print("  would restore content from backup")
            print()
            continue

        try:
            updated = request_json(
                f"{args.base_api}/pages/{page_id}",
                headers,
                method="POST",
                data={"content": backup_raw, "status": status},
            )
        except urllib.error.HTTPError as exc:
            body = exc.read().decode("utf-8", errors="replace")
            print(f"  restore failed: HTTP {exc.code}")
            print(f"  response: {body[:400]}")
            failures.append(name)
            print()
            continue

        updated_raw = updated.get("content", {}).get("raw", backup_raw)
        updated_hash = sha256_text(updated_raw)
        print(f"  restored sha256: {updated_hash}")
        print(f"  matches backup: {updated_hash == backup_hash}")
        if updated_hash != backup_hash:
            failures.append(name)
        print()

    if failures:
        print("RESTORE ISSUES:")
        for name in failures:
            print(f"  - {name}")
        return 1

    print("Requested investor pages are aligned with their backups.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
