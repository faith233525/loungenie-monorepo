#!/usr/bin/env python3
import json
from pathlib import Path

BACKUPS = Path('backups')
out = {}

for f in BACKUPS.glob('*_gutenberg_payload_mapping_refined.json'):
    try:
        d = json.loads(f.read_text(encoding='utf-8'))
        mapped = d.get('mapped') or {}
        if isinstance(mapped, dict):
            for k, v in mapped.items():
                # prefer existing mapping; keep first-seen
                if k not in out:
                    try:
                        out[k] = int(v)
                    except Exception:
                        out[k] = v
    except Exception:
        continue

# also include any per-page mapping.json files
for f in BACKUPS.glob('*_gutenberg_payload_mapping.json'):
    try:
        d = json.loads(f.read_text(encoding='utf-8'))
        mapped = d.get('mapped') or {}
        if isinstance(mapped, dict):
            for k, v in mapped.items():
                if k not in out:
                    try:
                        out[k] = int(v)
                    except Exception:
                        out[k] = v
    except Exception:
        continue

dest = BACKUPS / 'media_lookup.json'
dest.write_text(json.dumps(out, indent=2), encoding='utf-8')
print('Wrote', dest, 'entries=', len(out))
