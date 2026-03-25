#!/usr/bin/env python3
"""
Fix missing "id" fields in artifacts/*_update.json by inferring from filename.

Usage:
  python scripts/fix_artifact_ids.py --dry-run
  python scripts/fix_artifact_ids.py

This will scan artifacts for files named like `page_4862_update.json` and
will insert an integer `id` field at the top-level if it's missing.
"""
import json
from pathlib import Path
import re
import argparse


def infer_id_from_name(name: str):
    m = re.search(r"page_(\d+)_update\.json$", name)
    if m:
        return int(m.group(1))
    return None


def process_file(p: Path, dry_run=True):
    text = p.read_text(encoding="utf-8")
    try:
        data = json.loads(text)
    except Exception as e:
        print(f"SKIP (invalid json): {p} -> {e}")
        return False

    if isinstance(data, dict) and "id" in data:
        print(f"OK: {p} already has id={data.get('id')}")
        return True

    inferred = infer_id_from_name(p.name)
    if inferred is None:
        print(f"MISSING id and cannot infer: {p}")
        return False

    print(f"ADDING id={inferred} to {p}")
    if not dry_run:
        data_out = dict(data)
        data_out["id"] = inferred
        p.write_text(json.dumps(data_out, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    return True


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--dry-run", action="store_true", help="Show changes but do not write files")
    parser.add_argument("--dir", default="artifacts", help="Artifacts directory")
    args = parser.parse_args()

    base = Path(args.dir)
    if not base.exists():
        print(f"Artifacts dir not found: {base}")
        return

    files = list(base.glob("*_update.json"))
    if not files:
        print("No *_update.json files found in artifacts/")
        return

    added = 0
    for f in files:
        ok = process_file(f, dry_run=args.dry_run)
        if ok and not args.dry_run:
            added += 1

    if args.dry_run:
        print("Dry run complete. Rerun without --dry-run to apply changes.")
    else:
        print(f"Done. Updated {added} files.")


if __name__ == "__main__":
    main()
