#!/usr/bin/env python3
import json
import re
import argparse
from pathlib import Path

parser = argparse.ArgumentParser(description='Patch gallery ids into Gutenberg payload using refined mapping')
parser.add_argument('--page', type=int, help='Page ID (used to build default paths)')
parser.add_argument('--payload', help='Path to the payload file (overrides page)')
parser.add_argument('--mapping', help='Path to the refined mapping file (overrides page)')
parser.add_argument('--out', help='Output path for patched payload (overrides default)')
args = parser.parse_args()

page_id = args.page or 5223
payload_path = Path(args.payload) if args.payload else Path(f'backups/{page_id}_gutenberg_payload.json')
mapping_path = Path(args.mapping) if args.mapping else Path(f'backups/{page_id}_gutenberg_payload_mapping_refined.json')
out_path = Path(args.out) if args.out else Path(f'backups/{page_id}_gutenberg_payload_patched.json')

if not payload_path.exists():
    print('Payload not found:', payload_path)
    raise SystemExit(2)
if not mapping_path.exists():
    print('Refined mapping not found:', mapping_path)
    raise SystemExit(2)

payload_text = payload_path.read_text(encoding='utf-8')
mapping_data = json.loads(mapping_path.read_text(encoding='utf-8'))

# Normalize mapping into url->id and basename->id
url_to_id = {}
basename_to_id = {}

mappings = []
if isinstance(mapping_data, list):
    mappings = mapping_data
elif isinstance(mapping_data, dict):
    # try common keys
    if 'mappings' in mapping_data and isinstance(mapping_data['mappings'], list):
        mappings = mapping_data['mappings']
    elif 'mapped' in mapping_data and isinstance(mapping_data['mapped'], list):
        mappings = mapping_data['mapped']
    else:
        # try discover list-like values
        for v in mapping_data.values():
            if isinstance(v, list):
                mappings = v
                break

if isinstance(mapping_data, dict) and 'mapped' in mapping_data and isinstance(mapping_data['mapped'], dict):
    # mapping is a dict of url->id
    for url, aid in mapping_data['mapped'].items():
        try:
            aid_i = int(aid)
        except Exception:
            continue
        norm = url.split('?')[0]
        url_to_id[norm] = aid_i
        b = norm.split('/')[-1]
        b_naked = re.sub(r"(-\d+x\d+)(?=\.[a-zA-Z0-9]+$)", '', b)
        basename_to_id[b] = aid_i
        basename_to_id[b_naked] = aid_i
else:
    for item in mappings:
        if not isinstance(item, dict):
            continue
        # attempt to find url and id fields
        url = item.get('src') or item.get('url') or item.get('source') or item.get('orig')
        aid = item.get('id') or item.get('attachment_id') or item.get('aid')
        if url and aid is not None:
            norm = url.split('?')[0]
            url_to_id[norm] = int(aid)
            # basename without size suffix
            b = norm.split('/')[-1]
            b_naked = re.sub(r"(-\d+x\d+)(?=\.[a-zA-Z0-9]+$)", '', b)
            basename_to_id[b] = int(aid)
            basename_to_id[b_naked] = int(aid)

print(f'Loaded {len(url_to_id)} exact URL mappings and {len(basename_to_id)} basename mappings')

# Find gallery blocks and inject ids
pattern = re.compile(r'<!-- wp:gallery(\s*(\{.*?\})\s*)?-->(?P<body>.*?)<!-- /wp:gallery -->', re.S)

changed = 0
skipped = 0

def replace_gallery(m):
    global changed, skipped
    existing_json = m.group(2)
    body = m.group('body')
    # find all img src in body
    srcs = re.findall(r'<img[^>]+src=["\']([^"\']+)["\']', body, flags=re.I)
    ids = []
    for s in srcs:
        s_norm = s.split('?')[0]
        if s_norm in url_to_id:
            ids.append(url_to_id[s_norm])
            continue
        b = s_norm.split('/')[-1]
        b_naked = re.sub(r"(-\d+x\d+)(?=\.[a-zA-Z0-9]+$)", '', b)
        if b in basename_to_id:
            ids.append(basename_to_id[b])
            continue
        if b_naked in basename_to_id:
            ids.append(basename_to_id[b_naked])
            continue
    # parse existing attrs json if present
    attrs = {}
    if existing_json:
        try:
            attrs = json.loads(existing_json)
        except Exception:
            attrs = {}
    # If ids already present, check whether they're numeric; if so, skip.
    if 'ids' in attrs and attrs['ids']:
        existing = attrs['ids']
        # if all numeric, skip
        if all(isinstance(x, int) for x in existing):
            skipped += 1
            return m.group(0)
        # attempt to map existing string ids (filenames or urls) to numeric ids
        mapped_existing = []
        for e in existing:
            if isinstance(e, int):
                mapped_existing.append(e)
                continue
            s_norm = str(e).split('?')[0]
            if s_norm in url_to_id:
                mapped_existing.append(url_to_id[s_norm])
                continue
            b = s_norm.split('/')[-1]
            b_naked = re.sub(r"(-\d+x\d+)(?=\.[a-zA-Z0-9]+$)", '', b)
            if b in basename_to_id:
                mapped_existing.append(basename_to_id[b])
                continue
            if b_naked in basename_to_id:
                mapped_existing.append(basename_to_id[b_naked])
                continue
        if mapped_existing:
            attrs['ids'] = mapped_existing
            changed += 1
            new_open = '<!-- wp:gallery ' + json.dumps(attrs, separators=(',',':')) + '-->'
            return new_open + body + '<!-- /wp:gallery -->'
        # fallthrough to try building ids from image srcs below
    if not ids:
        skipped += 1
        return m.group(0)
    attrs['ids'] = ids
    new_open = '<!-- wp:gallery ' + json.dumps(attrs, separators=(',',':')) + '-->'
    changed += 1
    return new_open + body + '<!-- /wp:gallery -->'

patched_text = pattern.sub(replace_gallery, payload_text)

out_path.write_text(patched_text, encoding='utf-8')
print(f'Patched payload written to {out_path}\nChanged galleries: {changed}, Skipped: {skipped}')

# Summary of ids counts
if changed == 0:
    print('No galleries were updated. Inspect mapping and payload manually.')
    raise SystemExit(3)

print('Done.')
