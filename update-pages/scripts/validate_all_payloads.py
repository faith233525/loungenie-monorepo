#!/usr/bin/env python3
import json
import re
from pathlib import Path

CONTENT_DIR = Path('content')
BACKUPS = Path('backups')
REPORT_OUT = BACKUPS / 'validation_consolidated_report.json'

page_files = sorted(CONTENT_DIR.glob('*.json'))
if not page_files:
    print('No content/*.json files found; nothing to validate.')
    raise SystemExit(2)

report = {'pages': [], 'summary': {}}

def parse_block_attrs(attr_text):
    try:
        return json.loads(attr_text)
    except Exception:
        return None

# regex to find block openings like <!-- wp:gallery {...} -->
block_open_re = re.compile(r'<!--\s*wp:([a-zA-Z0-9/_-]+)(\s*(\{.*?\})\s*)?-->', re.S)
wp_image_re = re.compile(r'<!--\s*wp:image\s*(\{.*?\})\s*-->', re.S)
wp_gallery_re = re.compile(r'<!--\s*wp:gallery\s*(\{.*?\})\s*-->', re.S)
raw_img_re = re.compile(r'<img\s', re.I)

all_pages_ok = True
for pf in page_files:
    data = json.loads(pf.read_text(encoding='utf-8'))
    pid = data.get('page_id') or data.get('page') or pf.stem
    content = data.get('content') or ''
    page_report = {'page_file': str(pf), 'page_id': pid, 'issues': [], 'checks': {}}

    # 1) raw <img> check
    raw_img_count = len(raw_img_re.findall(content))
    page_report['checks']['raw_img_count'] = raw_img_count
    if raw_img_count > 0:
        page_report['issues'].append(f'Found {raw_img_count} raw <img> tags in generated content')

    # 2) check for wp:image blocks and numeric ids
    image_blocks = wp_image_re.findall(content)
    image_count = len(image_blocks)
    page_report['checks']['wp_image_blocks'] = image_count
    non_numeric_image_ids = []
    for m in image_blocks:
        # m is the captured attrs JSON string
        attrs = parse_block_attrs(m)
        if attrs and isinstance(attrs.get('id'), int):
            continue
        else:
            non_numeric_image_ids.append(attrs.get('id') if attrs else None)
    if non_numeric_image_ids:
        page_report['issues'].append(f'Found {len(non_numeric_image_ids)} wp:image blocks with missing/non-numeric id values')
        page_report['checks']['non_numeric_image_ids_sample'] = non_numeric_image_ids[:5]

    # 3) check galleries
    galleries = wp_gallery_re.findall(content)
    page_report['checks']['gallery_blocks'] = len(galleries)
    galleries_missing_ids = 0
    galleries_non_numeric = 0
    galleries_ids_samples = []
    for m in galleries:
        attrs = parse_block_attrs(m)
        if not attrs or 'ids' not in attrs:
            galleries_missing_ids += 1
            galleries_ids_samples.append(None)
            continue
        ids = attrs.get('ids')
        if not isinstance(ids, list) or not ids:
            galleries_missing_ids += 1
            galleries_ids_samples.append(ids)
            continue
        # ensure numeric
        non_numeric = [x for x in ids if not isinstance(x, int)]
        if non_numeric:
            galleries_non_numeric += 1
            galleries_ids_samples.append(ids[:6])
    if galleries_missing_ids:
        page_report['issues'].append(f'Found {galleries_missing_ids} gallery blocks missing ids[]')
    if galleries_non_numeric:
        page_report['issues'].append(f'Found {galleries_non_numeric} gallery blocks with non-numeric ids')
    if galleries_ids_samples:
        page_report['checks']['galleries_ids_samples'] = galleries_ids_samples[:6]

    # 4) check for Gutenberg block comments presence
    has_blocks = '<!-- wp:' in content
    page_report['checks']['has_block_comments'] = has_blocks
    if not has_blocks:
        page_report['issues'].append('Generated content contains no Gutenberg block comments')

    # 5) cross-check ids against mapping file for the page if exists
    mapping_path = BACKUPS / f"{pid}_gutenberg_payload_mapping_refined.json"
    mapped_ids = set()
    if mapping_path.exists():
        try:
            mdata = json.loads(mapping_path.read_text(encoding='utf-8'))
            mapped = mdata.get('mapped') or {}
            # mapped may be dict url->id
            if isinstance(mapped, dict):
                for v in mapped.values():
                    if isinstance(v, int):
                        mapped_ids.add(v)
            # or list of dicts
            elif isinstance(mapped, list):
                for it in mapped:
                    if isinstance(it, dict):
                        aid = it.get('id')
                        if isinstance(aid, int):
                            mapped_ids.add(aid)
        except Exception:
            pass
    # Collect ids used in content
    used_ids = set()
    # wp:image ids
    for m in image_blocks:
        attrs = parse_block_attrs(m[0])
        if attrs and isinstance(attrs.get('id'), int):
            used_ids.add(attrs.get('id'))
    # gallery ids
    for m in galleries:
        attrs = parse_block_attrs(m[0])
        if attrs and isinstance(attrs.get('ids'), list):
            for x in attrs.get('ids'):
                if isinstance(x, int):
                    used_ids.add(x)
    page_report['checks']['used_attachment_ids_count'] = len(used_ids)
    # find any used id not present in mapped_ids (if mapping exists)
    if mapped_ids:
        missing_from_mapping = sorted([x for x in used_ids if x not in mapped_ids])
        if missing_from_mapping:
            page_report['issues'].append(f'Found {len(missing_from_mapping)} used attachment IDs not present in refined mapping')
            page_report['checks']['used_ids_missing_in_mapping_sample'] = missing_from_mapping[:8]
    # finalize
    page_report['ok'] = not page_report['issues']
    if not page_report['ok']:
        all_pages_ok = False
    report['pages'].append(page_report)

# summary
report['summary']['total_pages'] = len(report['pages'])
report['summary']['pages_ok'] = sum(1 for p in report['pages'] if p['ok'])
report['summary']['pages_with_issues'] = report['summary']['total_pages'] - report['summary']['pages_ok']

REPORT_OUT.write_text(json.dumps(report, indent=2), encoding='utf-8')
print('Wrote consolidated report to', REPORT_OUT)
print(report['summary'])
if not all_pages_ok:
    raise SystemExit(3)
