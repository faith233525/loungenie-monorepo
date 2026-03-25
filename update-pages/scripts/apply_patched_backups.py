#!/usr/bin/env python3
import json
from pathlib import Path

CONTENT = Path('content')
BACKUPS = Path('backups')

for cf in sorted(CONTENT.glob('*.json')):
    data = json.loads(cf.read_text(encoding='utf-8'))
    pid = data.get('page_id') or data.get('page') or cf.stem
    candidates = [
        BACKUPS / f'{pid}_gutenberg_payload_patched_aggressive.json',
        BACKUPS / f'{pid}_gutenberg_payload_patched.json',
        BACKUPS / f'{pid}_gutenberg_payload.json',
    ]
    applied = None
    for c in candidates:
        if c.exists():
            try:
                bp = json.loads(c.read_text(encoding='utf-8'))
                content = bp.get('content') or bp.get('raw') or bp.get('page_content') or bp.get('html')
                if content:
                    data['content'] = content
                    cf.write_text(json.dumps(data, indent=2), encoding='utf-8')
                    applied = str(c)
                    break
            except Exception:
                continue
    print(f'Applied {applied} -> {cf}' if applied else f'No payload applied for {cf}')

print('Done.')