#!/usr/bin/env python3
import json, re
from pathlib import Path

CONTENT = Path('content')
BACKUPS = Path('backups')

def load_global_mapping():
    mapped = {}
    for f in BACKUPS.glob('*_gutenberg_payload_mapping_refined.json'):
        try:
            d=json.loads(f.read_text(encoding='utf-8'))
            m=d.get('mapped') or {}
            if isinstance(m,dict):
                for k,v in m.items():
                    mapped[k.split('?')[0]]=v
                    b=k.split('/')[-1]
                    mapped[b]=v
                    naked=re.sub(r'-(?:\d+x\d+|scaled|large|medium|thumbnail)(?=\.)','',b,flags=re.I)
                    mapped[naked]=v
        except Exception:
            continue
    return mapped

mapping = load_global_mapping()

for cf in sorted(CONTENT.glob('*.json')):
    data=json.loads(cf.read_text(encoding='utf-8'))
    content = data.get('content','')
    if isinstance(content,str) and '<!-- wp:' not in content:
        # find all img tags
        imgs = list(re.finditer(r'<img[^>]+src=["\']([^"\']+)["\'][^>]*>', content, flags=re.I))
        new = content
        replaced_any=False
        for m in reversed(imgs):
            src = m.group(1).split('?')[0]
            b = src.split('/')[-1]
            aid = mapping.get(src) or mapping.get(b) or mapping.get(b.lower())
            if aid:
                img_block = f'<!-- wp:image {{"id":{int(aid)},"sizeSlug":"large","linkDestination":"none"}} -->'
                # replace the whole <img ...> with the block comment
                start, end = m.span()
                new = new[:start] + img_block + new[end:]
                replaced_any=True
        if replaced_any:
            # wrap entire HTML in a group block so Gutenberg sees block comments
            wrapped = '<!-- wp:group -->\n' + new + '\n<!-- /wp:group -->'
            data['content']=wrapped
            cf.write_text(json.dumps(data, indent=2), encoding='utf-8')
            print('Converted raw HTML to blocks for', cf)

print('Done')
