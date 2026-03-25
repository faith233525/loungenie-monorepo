#!/usr/bin/env python3
import json
import re
from pathlib import Path

page_id = 5223
payload_path = Path(f'backups/{page_id}_gutenberg_payload.json')
mapping_path = Path(f'backups/{page_id}_gutenberg_payload_mapping_refined.json')
report_path = Path(f'backups/{page_id}_gallery_check_report.json')

if not payload_path.exists():
    print('Payload not found:', payload_path)
    raise SystemExit(2)
if not mapping_path.exists():
    print('Mapping not found:', mapping_path)
    raise SystemExit(2)

payload_json = json.loads(payload_path.read_text(encoding='utf-8'))
payload = payload_json.get('content', '')
mapping = json.loads(mapping_path.read_text(encoding='utf-8'))

# Build id->urls mapping
id_to_urls = {}
if isinstance(mapping, dict):
    # mapping may have a 'mapped' dict of url->id
    if 'mapped' in mapping and isinstance(mapping['mapped'], dict):
        for url, aid in mapping['mapped'].items():
            id_to_urls.setdefault(str(aid), []).append(url)
    elif 'mapped' in mapping and isinstance(mapping['mapped'], list):
        for item in mapping['mapped']:
            if isinstance(item, dict):
                url = item.get('src') or item.get('url')
                aid = item.get('id')
                if url and aid is not None:
                    id_to_urls.setdefault(str(aid), []).append(url)
    else:
        # try top-level dict of url->id
        # detect if values are integers
        is_url_map = all(isinstance(v, int) for v in mapping.values())
        if is_url_map:
            for url, aid in mapping.items():
                id_to_urls.setdefault(str(aid), []).append(url)
        else:
            # try 'mapped' merged earlier
            for k, v in mapping.items():
                if isinstance(v, dict):
                    for url, aid in v.items():
                        id_to_urls.setdefault(str(aid), []).append(url)
else:
    # mapping is a list
    for item in mapping:
        if isinstance(item, dict):
            url = item.get('src') or item.get('url')
            aid = item.get('id')
            if url and aid is not None:
                id_to_urls.setdefault(str(aid), []).append(url)

print(f'Built id->urls map with {len(id_to_urls)} ids')

pattern = re.compile(r'<!-- wp:gallery\s*(\{.*?\})\s*-->(?P<body>.*?)<!-- /wp:gallery -->', re.S)

report = {'page': page_id, 'galleries': [], 'summary': {}}

for i, m in enumerate(pattern.finditer(payload), start=1):
    attrs_json = m.group(1)
    body = m.group('body')
    try:
        attrs = json.loads(attrs_json)
    except Exception:
        attrs = {}
    # include raw attrs for debugging
    raw_attrs = attrs_json if attrs_json is not None else ''
    ids = attrs.get('ids', [])
    # find img srcs in body
    srcs = re.findall(r'<img[^>]+src=["\']([^"\']+)["\']', body, flags=re.I)
    missing_ids = []
    unknown_ids = []
    for aid in ids:
        if str(aid) not in id_to_urls:
            unknown_ids.append(aid)
    if not ids:
        missing_ids.append('no ids')
    report['galleries'].append({'index': i, 'ids': ids, 'img_srcs_sample': srcs[:6], 'unknown_ids': unknown_ids, 'missing_ids': missing_ids, 'raw_attrs': raw_attrs})

# summary
total = len(report['galleries'])
with_ids = sum(1 for g in report['galleries'] if g['ids'])
missing = sum(1 for g in report['galleries'] if not g['ids'])
unknown = sum(1 for g in report['galleries'] if g['unknown_ids'])

report['summary'] = {'total_galleries': total, 'galleries_with_ids': with_ids, 'galleries_missing_ids': missing, 'galleries_with_unknown_ids': unknown}

report_path.write_text(json.dumps(report, indent=2), encoding='utf-8')
print('Wrote report to', report_path)
print(report['summary'])
