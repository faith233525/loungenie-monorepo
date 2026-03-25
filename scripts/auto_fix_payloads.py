#!/usr/bin/env python3
import json
import re
from pathlib import Path

CONTENT_DIR = Path('content')
BACKUPS = Path('backups')

content_files = sorted(CONTENT_DIR.glob('*.json'))
if not content_files:
    print('No content/*.json files found; nothing to fix.')
    raise SystemExit(2)

# regexes
block_open_re = re.compile(r'<!--\s*wp:([a-zA-Z0-9/_-]+)(\s*(\{.*?\})\s*)?-->', re.S)
wp_image_re = re.compile(r'<!--\s*wp:image\s*(\{.*?\})\s*-->', re.S)
wp_gallery_re = re.compile(r'<!--\s*wp:gallery\s*(\{.*?\})\s*-->', re.S)

def load_mapping(pid):
    path = BACKUPS / f"{pid}_gutenberg_payload_mapping_refined.json"
    if not path.exists():
        path = BACKUPS / f"{pid}_gutenberg_payload_mapping.json"
        if not path.exists():
            return {}
    try:
        data = json.loads(path.read_text(encoding='utf-8'))
    except Exception:
        return {}
    # mapping may be {'mapped': {url:id}} or direct dict
    mapped = {}
    if isinstance(data, dict):
        if 'mapped' in data and isinstance(data['mapped'], dict):
            mapped = data['mapped']
        else:
            # maybe top-level url->id
            candidates = {k:v for k,v in data.items() if isinstance(v, int)}
            if candidates:
                mapped = candidates
    elif isinstance(data, list):
        for it in data:
            if isinstance(it, dict):
                url = it.get('src') or it.get('url')
                aid = it.get('id')
                if url and aid:
                    mapped[url] = aid
    # normalize keys (strip query)
    norm = {}
    for k,v in mapped.items():
        norm[k.split('?')[0]] = v
        # also add basename and naked basename
        b = k.split('/')[-1]
        norm[b] = v
        naked = re.sub(r'-(?:\d+x\d+|scaled|large|medium|thumbnail)(?=\.)', '', b, flags=re.I)
        norm[naked] = v
    return norm

changed_any = False
for cf in content_files:
    data = json.loads(cf.read_text(encoding='utf-8'))
    pid = data.get('page_id') or data.get('page') or cf.stem
    content = data.get('content') or ''
    backup_payload = BACKUPS / f"{pid}_gutenberg_payload.json"
    mapping = load_mapping(pid)
    updated = False
    updated_flag = {'v': False}

    # If content has no block comments but backup payload exists, replace content from backup
    if '<!-- wp:' not in content and backup_payload.exists():
        try:
            bp = json.loads(backup_payload.read_text(encoding='utf-8'))
            new_content = bp.get('content') or ''
            if new_content:
                data['content'] = new_content
                cf.write_text(json.dumps(data, indent=2), encoding='utf-8')
                print(f'Populated blocks for {pid} from {backup_payload}')
                updated = True
        except Exception as e:
            print('Failed to load backup payload for', pid, e)

    # For content that has block comments, try to patch wp:image and wp:gallery attrs
    if '<!-- wp:' in data.get('content',''):
        cont = data['content']
        # patch image blocks
        def repl_image(m):
            attrs_text = m.group(1)
            try:
                attrs = json.loads(attrs_text)
            except Exception:
                return m.group(0)
            if not isinstance(attrs.get('id'), int):
                # try to find src in attrs
                src = attrs.get('src')
                if not src:
                    # sometimes inner HTML has img with src; skip
                    return m.group(0)
                key = src.split('?')[0]
                aid = mapping.get(key) or mapping.get(src) or mapping.get(src.split('/')[-1])
                if aid:
                    attrs['id'] = int(aid)
                    updated_flag['v'] = True
            return '<!-- wp:image ' + json.dumps(attrs, separators=(',',':'), ensure_ascii=False) + ' -->'
        cont2 = wp_image_re.sub(repl_image, cont)

        # patch gallery blocks where ids may be URL strings
        def repl_gallery(m):
            attrs_text = m.group(1)
            try:
                attrs = json.loads(attrs_text)
            except Exception:
                return m.group(0)
            ids = attrs.get('ids')
            if ids and isinstance(ids, list):
                new_ids = []
                changed = False
                for it in ids:
                    if isinstance(it, int):
                        new_ids.append(it)
                    elif isinstance(it, str):
                        key = it.split('?')[0]
                        aid = mapping.get(key) or mapping.get(it) or mapping.get(it.split('/')[-1])
                        if aid:
                            new_ids.append(int(aid))
                            changed = True
                        else:
                            # try normalized basename
                            b = it.split('/')[-1]
                            naked = re.sub(r'-(?:\d+x\d+|scaled|large|medium|thumbnail)(?=\.)', '', b, flags=re.I)
                            aid = mapping.get(b) or mapping.get(naked)
                            if aid:
                                new_ids.append(int(aid))
                                changed = True
                            else:
                                new_ids.append(it)
                if changed:
                    attrs['ids'] = new_ids
                    updated_flag['v'] = True
            return '<!-- wp:gallery ' + json.dumps(attrs, separators=(',',':'), ensure_ascii=False) + ' -->'
        cont3 = wp_gallery_re.sub(repl_gallery, cont2)

        if cont3 != cont:
            data['content'] = cont3
            cf.write_text(json.dumps(data, indent=2), encoding='utf-8')
            print(f'Patched image/gallery attrs for {pid} in {cf}')
            updated_flag['v'] = True

        # also update backup payload if present
        if updated_flag['v'] and backup_payload.exists():
            try:
                bp = json.loads(backup_payload.read_text(encoding='utf-8'))
                bp['content'] = data['content']
                backup_payload.write_text(json.dumps(bp, indent=2), encoding='utf-8')
                print(f'Updated backup payload {backup_payload}')
            except Exception as e:
                print('Failed to update backup payload for', pid, e)

    if updated_flag['v']:
        changed_any = True

print('Auto-fix complete. Changes made:' , changed_any)
