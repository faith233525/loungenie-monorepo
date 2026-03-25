#!/usr/bin/env python3
import json, re, sys
from pathlib import Path

BACKUPS = Path('backups')
CONTENT = Path('content')
WP_BASE = 'https://www.loungenie.com/staging'

def load_media_list():
    # try to read last fetched media mapping from backups/media_list.json
    ml = BACKUPS / 'media_list.json'
    if ml.exists():
        return json.loads(ml.read_text(encoding='utf-8'))
    # fallback: try mapping files to build a list
    medias = []
    for f in BACKUPS.glob('*_gutenberg_payload_mapping_refined.json'):
        try:
            d=json.loads(f.read_text(encoding='utf-8'))
            m=d.get('mapped') or {}
            if isinstance(m,dict):
                for url,idv in m.items():
                    medias.append({'source_url':url,'id':idv})
        except Exception:
            continue
    return medias

def build_lookup(media_list):
    url_map = {}
    name_map = {}
    for it in media_list:
        url = it.get('source_url') or it.get('source') or it.get('url') or it.get('src')
        mid = it.get('id') or it.get('attachment_id')
        if not url or not mid:
            continue
        norm = url.split('?')[0]
        url_map[norm]=int(mid)
        b = norm.split('/')[-1]
        name_map[b]=int(mid)
        naked = re.sub(r'-(?:\d+x\d+|scaled|large|medium|thumbnail)(?=\.)','',b,flags=re.I)
        name_map[naked]=int(mid)
        name_map[b.lower()] = int(mid)
        name_map[naked.lower()] = int(mid)
    return url_map, name_map

def extract_filenames_from_payload(payload_text):
    # find gallery ids arrays with strings and image blocks with src in attrs
    filenames = set()
    # image srcs in img tags
    for s in re.findall(r'<img[^>]+src=["\']([^"\']+)["\']', payload_text, flags=re.I):
        filenames.add(s.split('?')[0])
    # wp:gallery attrs ids that are strings
    for m in re.finditer(r'<!--\s*wp:gallery\s*(\{.*?\})\s*-->', payload_text, flags=re.S):
        try:
            attrs = json.loads(m.group(1))
            ids = attrs.get('ids') or []
            for it in ids:
                if isinstance(it, str): filenames.add(it.split('?')[0])
        except Exception:
            continue
    # wp:image blocks with src attr
    for m in re.finditer(r'<!--\s*wp:image\s*(\{.*?\})\s*-->', payload_text, flags=re.S):
        try:
            attrs = json.loads(m.group(1))
            src = attrs.get('src')
            if isinstance(src,str): filenames.add(src.split('?')[0])
        except Exception:
            continue
    # normalize to basenames where possible
    normalized = set()
    for f in filenames:
        if '/' in f:
            normalized.add(f.split('/')[-1])
        else:
            normalized.add(f)
    return normalized

def aggressive_match(names, url_map, name_map):
    resolved = {}
    for n in names:
        # try exact url
        if n in url_map:
            resolved[n]=url_map[n]
            continue
        # try basename exact
        if n in name_map:
            resolved[n]=name_map[n]
            continue
        # try without extension
        base_no_ext = re.sub(r'\.[a-zA-Z0-9]+$','',n)
        if base_no_ext in name_map:
            resolved[n]=name_map[base_no_ext]
            continue
        # partial substring match: find any key containing base_no_ext
        for key,mid in name_map.items():
            if base_no_ext.lower() in key.lower():
                resolved[n]=mid
                break
        if n in resolved:
            continue
        # case-insensitive exact
        for key,mid in name_map.items():
            if key.lower()==n.lower():
                resolved[n]=mid
                break
    return resolved

def update_mapping_file(pid, resolved_map):
    mp = BACKUPS / f'{pid}_gutenberg_payload_mapping_refined.json'
    if not mp.exists():
        data={'page':pid,'mapped':{}}
    else:
        data=json.loads(mp.read_text(encoding='utf-8'))
    mapped = data.get('mapped') or {}
    if isinstance(mapped, dict):
        for k,v in resolved_map.items():
            # store as basename->id if key is basename
            mapped[k]=v
    else:
        data['mapped']=mapped
        for k,v in resolved_map.items():
            data['mapped'][k]=v
    mp.write_text(json.dumps(data, indent=2), encoding='utf-8')
    return mp

def patch_payload_ids(pid, payload_path, resolved_map):
    # reuse patch_payload_with_ids logic: replace string ids and img srcs
    text = payload_path.read_text(encoding='utf-8')
    # replace gallery string ids
    def repl_gallery(m):
        try:
            attrs=json.loads(m.group(1))
        except Exception:
            return m.group(0)
        ids=attrs.get('ids') or []
        new_ids=[]
        changed=False
        for it in ids:
            if isinstance(it,int): new_ids.append(it); continue
            key = it.split('?')[0]
            b = key.split('/')[-1]
            if key in resolved_map:
                new_ids.append(resolved_map[key]); changed=True
            elif b in resolved_map:
                new_ids.append(resolved_map[b]); changed=True
            else:
                new_ids.append(it)
        if changed:
            attrs['ids']=new_ids
            return '<!-- wp:gallery ' + json.dumps(attrs, separators=(',',':')) + '-->'
        return m.group(0)
    text = re.sub(r'<!--\s*wp:gallery\s*(\{.*?\})\s*-->', repl_gallery, text, flags=re.S)
    # replace wp:image src attrs
    def repl_image(m):
        try:
            attrs=json.loads(m.group(1))
        except Exception:
            return m.group(0)
        src = attrs.get('src')
        if isinstance(src,str):
            key=src.split('?')[0]
            b=key.split('/')[-1]
            aid = resolved_map.get(key) or resolved_map.get(b)
            if aid:
                attrs['id']=int(aid)
        return '<!-- wp:image ' + json.dumps(attrs, separators=(',',':')) + ' -->'
    text = re.sub(r'<!--\s*wp:image\s*(\{.*?\})\s*-->', repl_image, text, flags=re.S)
    out = BACKUPS / f'{pid}_gutenberg_payload_patched_aggressive.json'
    out.write_text(text, encoding='utf-8')
    return out

def run_once():
    media_list = load_media_list()
    url_map, name_map = build_lookup(media_list)
    pages = sorted(CONTENT.glob('*.json'))
    any_changes=False
    report = {'pages':{}}
    for pf in pages:
        data=json.loads(pf.read_text(encoding='utf-8'))
        pid = data.get('page_id') or data.get('page') or pf.stem
        payload_b = BACKUPS / f'{pid}_gutenberg_payload.json'
        if not payload_b.exists():
            report['pages'][str(pf)] = {'status':'no_payload'}
            continue
        payload_text = payload_b.read_text(encoding='utf-8')
        names = extract_filenames_from_payload(payload_text)
        unresolved = [n for n in names if (n not in url_map and n not in name_map)]
        resolved = aggressive_match(unresolved, url_map, name_map)
        if resolved:
            any_changes=True
            mp = update_mapping_file(pid, resolved)
            patched = patch_payload_ids(pid, payload_b, resolved)
            report['pages'][str(pf)] = {'resolved': len(resolved), 'patched_payload': str(patched), 'mapping': str(mp)}
        else:
            report['pages'][str(pf)] = {'resolved': 0, 'unresolved_count': len(unresolved), 'sample_unresolved': list(unresolved)[:8]}
    BACKUPS.joinpath('aggressive_auto_fix_report.json').write_text(json.dumps(report, indent=2), encoding='utf-8')
    return any_changes, report

if __name__=='__main__':
    # run multiple passes until no changes
    max_passes=5
    for i in range(max_passes):
        changed, rpt = run_once()
        print(f'Pass {i+1}: changes={changed}')
        if not changed:
            break
    print('Done. Report written to backups/aggressive_auto_fix_report.json')