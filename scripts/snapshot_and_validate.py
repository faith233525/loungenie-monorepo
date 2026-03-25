#!/usr/bin/env python3
import os
import sys
import json
import argparse
import re
from pathlib import Path

try:
    import requests
    from requests.auth import HTTPBasicAuth
except Exception as e:
    print('requests library is required. Install with: pip install requests')
    raise

BASE = 'https://www.loungenie.com/staging'

parser = argparse.ArgumentParser(description='Snapshot a WP page and run basic validations')
parser.add_argument('--page', required=True, type=int)
parser.add_argument('--out', required=True)
args = parser.parse_args()

WP_AUTH = os.environ.get('WP_AUTH')
if not WP_AUTH:
    print('WP_AUTH environment variable not set. Aborting.')
    sys.exit(2)

# Prepare auth for requests
use_basic_header = False
auth = None
if ':' in WP_AUTH:
    # assume username:password
    user, pwd = WP_AUTH.split(':', 1)
    auth = HTTPBasicAuth(user, pwd)
else:
    # assume pre-encoded base64 (possibly with spaces) -> strip spaces and send as Basic header
    token = WP_AUTH.replace(' ', '')
    use_basic_header = True

page_id = args.page
out_path = Path(args.out)
out_path.parent.mkdir(parents=True, exist_ok=True)

url = f"{BASE}/wp-json/wp/v2/pages/{page_id}?_embed=1"
headers = {'User-Agent': 'snapshot-validator/1.0'}
if use_basic_header:
    headers['Authorization'] = 'Basic ' + token

print(f'Fetching page {page_id} from {url}')
resp = requests.get(url, headers=headers, auth=auth, timeout=30)
if resp.status_code != 200:
    print('Failed to fetch page:', resp.status_code, resp.text[:200])
    sys.exit(3)

page_json = resp.json()
with open(out_path, 'w', encoding='utf-8') as f:
    json.dump(page_json, f, ensure_ascii=False, indent=2)
print('Saved snapshot to', out_path)

# Run validations
report = {
    'page_id': page_id,
    'snapshot_file': str(out_path),
    'issues': [],
    'checks': {}
}

content = page_json.get('content', {}).get('rendered', '')

# Check for inline <style> and <script>
style_count = len(re.findall(r'<style[\s>]', content, flags=re.I))
script_count = len(re.findall(r'<script[\s>]', content, flags=re.I))
img_count = len(re.findall(r'<img\s', content, flags=re.I))

report['checks']['inline_style_count'] = style_count
report['checks']['inline_script_count'] = script_count
report['checks']['raw_img_count_in_content'] = img_count

if style_count > 0:
    report['issues'].append(f'Found {style_count} inline <style> blocks in page content')
if script_count > 0:
    report['issues'].append(f'Found {script_count} inline <script> blocks in page content')
if img_count > 0:
    report['issues'].append(f'Found {img_count} raw <img> tags in page content')

# If a generated payload exists, run some checks
payload_path = Path(f'backups/{page_id}_gutenberg_payload.json')
payload_checks = {}
if payload_path.exists():
    payload_text = payload_path.read_text(encoding='utf-8')
    gallery_count = payload_text.count('<!-- wp:gallery')
    ids_count = payload_text.count('"ids"')
    payload_checks['gallery_count'] = gallery_count
    payload_checks['ids_occurrences'] = ids_count
    if gallery_count > 0 and ids_count == 0:
        report['issues'].append('Found gallery blocks in payload but no "ids" occurrences')
else:
    payload_checks['found'] = False

report['checks']['payload'] = payload_checks

# Check refined mapping for duplicate IDs
mapping_path = Path(f'backups/{page_id}_gutenberg_payload_mapping_refined.json')
mapping_checks = {}
if mapping_path.exists():
    mapping = json.loads(mapping_path.read_text(encoding='utf-8'))
    # mapping expected to be list of {src, id}
    id_to_srcs = {}
    for item in mapping if isinstance(mapping, list) else mapping.get('mappings', []):
        src = item.get('src') if isinstance(item, dict) else None
        aid = item.get('id') if isinstance(item, dict) else None
        if aid is not None:
            id_to_srcs.setdefault(str(aid), []).append(src)
    duplicates = {k: v for k, v in id_to_srcs.items() if len(v) > 1}
    mapping_checks['unique_mapped_ids'] = len(id_to_srcs)
    mapping_checks['duplicate_id_cases'] = {k: len(v) for k, v in duplicates.items()}
    if duplicates:
        report['issues'].append(f'Found {len(duplicates)} attachment IDs mapped from multiple source URLs')
else:
    mapping_checks['found'] = False

report['checks']['mapping'] = mapping_checks

# Save report
report_path = Path(f'backups/{page_id}_snapshot_validation_report.json')
with open(report_path, 'w', encoding='utf-8') as f:
    json.dump(report, f, ensure_ascii=False, indent=2)

print('Validation report written to', report_path)
print('\nSummary:')
print(' - inline style blocks:', style_count)
print(' - inline script blocks:', script_count)
print(' - raw <img> tags in content:', img_count)
if mapping_checks.get('duplicate_id_cases'):
    print(' - duplicate attachment id mappings:', mapping_checks['duplicate_id_cases'])

if report['issues']:
    print('\nISSUES FOUND:')
    for it in report['issues']:
        print(' -', it)
    sys.exit(4)

print('\nNo blocking issues found.')
sys.exit(0)
