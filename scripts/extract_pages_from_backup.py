#!/usr/bin/env python3
import json
from pathlib import Path

BACKUP = Path('backups/professional-redesign-v12-live-backup-20260321-160731.json')
OUT = Path('backups')
if not BACKUP.exists():
    print('Backup not found:', BACKUP)
    raise SystemExit(1)
data = json.loads(BACKUP.read_text(encoding='utf-8'))
target_ids = [4862,5139]
count=0
for item in data:
    pid = item.get('id')
    if pid in target_ids:
        payload = {'content': item.get('content',''), 'title': item.get('title',{})}
        out = OUT / f'{pid}_gutenberg_payload.json'
        out.write_text(json.dumps(payload, indent=2), encoding='utf-8')
        print('Wrote', out)
        count+=1
print('Done. extracted', count)