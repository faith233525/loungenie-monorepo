"""Scan repository for duplicate files and write a report to stdout.

The script walks the repository from the directory it is run from,
hashes every regular file, groups files that share an identical hash,
and prints a human-readable report.  Files inside .git/ and
node_modules/ trees are excluded automatically.
"""

import hashlib
import os
import sys
from collections import defaultdict


EXCLUDE_DIRS = {".git", "node_modules", "__pycache__", ".tox", "venv", ".venv"}


def file_hash(path: str, block_size: int = 65536) -> str:
    """Return the SHA-256 hex digest of *path*."""
    h = hashlib.sha256()
    with open(path, "rb") as fh:
        while True:
            block = fh.read(block_size)
            if not block:
                break
            h.update(block)
    return h.hexdigest()


def find_duplicates(root: str) -> "dict[str, list[str]]":
    """Walk *root* and return a dict mapping hash -> [list of paths]."""
    hashes: dict = defaultdict(list)
    for dirpath, dirnames, filenames in os.walk(root):
        # Prune excluded directories in-place
        dirnames[:] = [d for d in dirnames if d not in EXCLUDE_DIRS]
        for filename in filenames:
            filepath = os.path.join(dirpath, filename)
            try:
                digest = file_hash(filepath)
                hashes[digest].append(os.path.relpath(filepath, root))
            except (OSError, PermissionError):
                pass
    # Keep only groups with more than one file
    return {h: paths for h, paths in hashes.items() if len(paths) > 1}


def main() -> None:
    root = os.getcwd()
    duplicates = find_duplicates(root)

    if not duplicates:
        print("No duplicate files found.")
        return

    print(f"Found {len(duplicates)} group(s) of duplicate files:\n")
    for idx, (digest, paths) in enumerate(sorted(duplicates.items()), start=1):
        print(f"Group {idx} (SHA-256: {digest[:12]}...):")
        for p in sorted(paths):
            print(f"  {p}")
        print()


if __name__ == "__main__":
    main()
