#!/usr/bin/env python3
import json
from pathlib import Path

CONTENT = Path('content')
for cf in sorted(CONTENT.glob('*.json')):
    data = json.loads(cf.read_text(encoding='utf-8'))
    content = data.get('content')
    if isinstance(content, dict):
        # prefer 'rendered' key
        new = content.get('rendered') or content.get('raw') or ''
        if new:
            data['content'] = new
            cf.write_text(json.dumps(data, indent=2), encoding='utf-8')
            print('Normalized content for', cf)
    else:
        # nothing to do
        pass
print('Done')