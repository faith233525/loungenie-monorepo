"""
Generate ALT suggestions and oversized image list from artifacts/audit_all_images.txt
Outputs:
 - artifacts/alt_suggestions.csv (page,image_url,existing_alt,suggested_alt)
 - artifacts/oversized_images.csv (page,image_url,width,height)

Usage: python scripts/generate_alt_and_oversized.py
"""
import re
import os
from pathlib import Path

root = Path(__file__).resolve().parents[1]
artifacts_dir = root / 'artifacts'
input_file = artifacts_dir / 'audit_all_images.txt'
alt_csv = artifacts_dir / 'alt_suggestions.csv'
oversized_csv = artifacts_dir / 'oversized_images.csv'

if not input_file.exists():
    print(f"Missing input: {input_file}")
    raise SystemExit(1)

page = None
entries = []
with input_file.open('r', encoding='utf-8', errors='ignore') as fh:
    for line in fh:
        line = line.rstrip('\n')
        m_page = re.match(r'^PAGE:\s*(.+) \(', line)
        if m_page:
            page = m_page.group(1).strip()
            continue
        # match lines like: 1. https://...\n      alt='...' | w=180 h=94 ...
        m_url = re.match(r'\s*\d+\.\s+(https?://[^\s]+|//[^\s]+|[^\s]+)', line)
        if m_url:
            url = m_url.group(1)
            # read next line for alt and dims
            next_line = fh.readline().rstrip('\n') if fh else ''
            alt = ''
            width = ''
            height = ''
            m_alt = re.search(r"alt='([^']*)'", next_line)
            if m_alt:
                alt = m_alt.group(1)
            m_dims = re.search(r'w=(\d+)(?:\s+h=(\d+))?', next_line)
            if m_dims:
                width = m_dims.group(1)
                height = m_dims.group(2) or ''
            entries.append({'page': page or '', 'url': url, 'alt': alt, 'w': width, 'h': height})

# produce ALT suggestions
import csv

with alt_csv.open('w', encoding='utf-8', newline='') as outf:
    writer = csv.writer(outf)
    writer.writerow(['page','image_url','existing_alt','suggested_alt'])
    for e in entries:
        existing = e['alt'] or ''
        # suggest filename-derived alt if missing or empty
        if not existing.strip():
            # derive from URL filename
            fname = e['url'].split('/')[-1]
            # remove size suffixes and extensions
            fname = re.sub(r'-\d+x\d+(?=\.)', '', fname)
            fname = re.sub(r'\.(webp|png|jpg|jpeg|svg|gif|avif)$', '', fname, flags=re.I)
            suggestion = fname.replace('-', ' ').replace('_', ' ').strip()
            if not suggestion:
                suggestion = 'image'
        else:
            suggestion = existing
        writer.writerow([e['page'], e['url'], existing, suggestion])

# produce oversized list (width>2000 or height>2000)
with oversized_csv.open('w', encoding='utf-8', newline='') as outf:
    writer = csv.writer(outf)
    writer.writerow(['page','image_url','width','height'])
    for e in entries:
        try:
            w = int(e['w']) if e['w'] else 0
        except ValueError:
            w = 0
        try:
            h = int(e['h']) if e['h'] else 0
        except ValueError:
            h = 0
        if w >= 2000 or h >= 2000:
            writer.writerow([e['page'], e['url'], w, h])

print(f"Wrote: {alt_csv}\nWrote: {oversized_csv}")
