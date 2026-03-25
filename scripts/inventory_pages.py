#!/usr/bin/env python3
"""Inventory WordPress pages: Gutenberg/Kadence usage and asset health.

Writes: logs/inventory_report.json, logs/inventory_report.csv, logs/inventory_report.txt
"""
import base64
import csv
import json
import os
import re
import requests
from urllib.parse import urljoin

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
# Use WP_AUTH env var. Accept either raw 'user:pass' or a base64 string.
WP_AUTH = os.environ.get('WP_AUTH')
if not WP_AUTH:
    raise SystemExit('Environment variable WP_AUTH not set; set to "user:pass" or base64 string')
if ':' in WP_AUTH:
    AUTH = base64.b64encode(WP_AUTH.encode()).decode()
else:
    AUTH = WP_AUTH
HEADERS = {'Authorization': f'Basic {AUTH}', 'Content-Type': 'application/json'}
STAGING = 'https://loungenie.com/staging'

OUT_DIR = 'logs'
os.makedirs(OUT_DIR, exist_ok=True)

PAGE_BATCH = 100

def fetch_all_pages():
    pages = []
    page = 1
    while True:
        r = requests.get(f'{BASE}/pages?per_page={PAGE_BATCH}&page={page}', headers=HEADERS, timeout=30)
        if r.status_code == 400:
            break
        r.raise_for_status()
        batch = r.json()
        if not batch:
            break
        pages.extend(batch)
        page += 1
    return pages

def get_page_raw(page_id: int) -> str:
    r = requests.get(f'{BASE}/pages/{page_id}?context=edit', headers=HEADERS, timeout=30)
    r.raise_for_status()
    return r.json().get('content', {}).get('raw', '')

RE_UPLOAD = re.compile(r'(https?://[^\s"\'>]+/wp-content/uploads/[^\s"\'>]+)|(/wp-content/uploads/[^\s"\'>]+)')
RE_PLUGIN = re.compile(r'(https?://[^\s"\'>]+/wp-content/plugins/[^\s"\'>]+)|(/wp-content/plugins/[^\s"\'>]+)')

def check_url(url: str) -> int:
    try:
        r = requests.head(url, timeout=15, allow_redirects=True)
        return r.status_code
    except Exception:
        try:
            r = requests.get(url, timeout=15, stream=True)
            return r.status_code
        except Exception:
            return 0

def normalize_url(m: str) -> str:
    if m.startswith('/wp-content'):
        return urljoin(STAGING + '/', m.lstrip('/'))
    return m

def analyze_page(p):
    page_id = p.get('id')
    title = p.get('title', {}).get('rendered', '')
    raw = get_page_raw(page_id)
    uses_gutenberg = bool(re.search(r'<!--\s*wp:', raw))
    uses_kadence = 'kadence' in raw.lower()

    uploads = set()
    for g in RE_UPLOAD.findall(raw):
        candidate = g[0] or g[1]
        uploads.add(normalize_url(candidate))

    plugins = set()
    for g in RE_PLUGIN.findall(raw):
        candidate = g[0] or g[1]
        plugins.add(normalize_url(candidate))

    broken_uploads = []
    for u in sorted(uploads):
        status = check_url(u)
        if status == 0 or status >= 400:
            broken_uploads.append({'url': u, 'status': status})

    broken_plugins = []
    for u in sorted(plugins):
        status = check_url(u)
        if status == 0 or status >= 400:
            broken_plugins.append({'url': u, 'status': status})

    return {
        'id': page_id,
        'title': title,
        'uses_gutenberg': uses_gutenberg,
        'uses_kadence': uses_kadence,
        'uploads_count': len(uploads),
        'plugins_count': len(plugins),
        'broken_uploads': broken_uploads,
        'broken_plugins': broken_plugins,
    }

def main():
    pages = fetch_all_pages()
    report = []
    print(f'Found {len(pages)} pages; analyzing...')
    for p in pages:
        try:
            r = analyze_page(p)
            report.append(r)
            print(f"Page {r['id']} '{r['title']}' - Gutenberg:{r['uses_gutenberg']} Kadence:{r['uses_kadence']} uploads:{r['uploads_count']} plugins:{r['plugins_count']} broken_media:{len(r['broken_uploads'])} broken_plugins:{len(r['broken_plugins'])}")
        except Exception as e:
            print(f'Error analyzing page {p.get("id")}: {e}')

    with open(os.path.join(OUT_DIR, 'inventory_report.json'), 'w', encoding='utf-8') as fh:
        json.dump(report, fh, indent=2)

    with open(os.path.join(OUT_DIR, 'inventory_report.csv'), 'w', encoding='utf-8', newline='') as fh:
        writer = csv.writer(fh)
        writer.writerow(['id', 'title', 'uses_gutenberg', 'uses_kadence', 'uploads_count', 'plugins_count', 'broken_uploads', 'broken_plugins'])
        for r in report:
            writer.writerow([r['id'], r['title'], r['uses_gutenberg'], r['uses_kadence'], r['uploads_count'], r['plugins_count'], len(r['broken_uploads']), len(r['broken_plugins'])])

    with open(os.path.join(OUT_DIR, 'inventory_report.txt'), 'w', encoding='utf-8') as fh:
        for r in report:
            fh.write(f"Page {r['id']} - {r['title']}\n")
            fh.write(f"  Gutenberg: {r['uses_gutenberg']}  Kadence: {r['uses_kadence']}\n")
            fh.write(f"  Upload refs: {r['uploads_count']}  Plugin refs: {r['plugins_count']}\n")
            if r['broken_uploads']:
                fh.write('  Broken uploads:\n')
                for b in r['broken_uploads']:
                    fh.write(f"    {b['status']} {b['url']}\n")
            if r['broken_plugins']:
                fh.write('  Broken plugin assets:\n')
                for b in r['broken_plugins']:
                    fh.write(f"    {b['status']} {b['url']}\n")
            fh.write('\n')

    print('Reports written to logs/inventory_report.{json,csv,txt}')


if __name__ == '__main__':
    main()
