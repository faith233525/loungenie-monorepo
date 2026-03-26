#!/usr/bin/env python3
"""Build `dist/lg-block-patterns.zip` from the `wp-content/plugins/lg-block-patterns` folder."""
import os
import sys
import zipfile

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
PLUGIN_DIR = os.path.join(ROOT, 'wp-content', 'plugins', 'lg-block-patterns')
DIST_DIR = os.path.join(ROOT, 'dist')
OUT_ZIP = os.path.join(DIST_DIR, 'lg-block-patterns.zip')

if not os.path.isdir(PLUGIN_DIR):
    print('Plugin directory not found:', PLUGIN_DIR)
    sys.exit(1)

os.makedirs(DIST_DIR, exist_ok=True)

with zipfile.ZipFile(OUT_ZIP, 'w', zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk(PLUGIN_DIR):
        for f in files:
            full = os.path.join(root, f)
            arcname = os.path.relpath(full, PLUGIN_DIR)
            zf.write(full, arcname)

print('Wrote', OUT_ZIP)
