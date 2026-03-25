"""
Generate a WP import CSV from artifacts/alt_suggestions.csv.
Outputs: artifacts/alt_wp_import.csv with columns:
  page,image_url,existing_alt,suggested_alt,filename

Usage:
  python scripts/generate_wp_alt_payload.py
"""
import csv
from pathlib import Path

root = Path(__file__).resolve().parents[1]
artifacts = root / 'artifacts'
input_csv = artifacts / 'alt_suggestions.csv'
output_csv = artifacts / 'alt_wp_import.csv'

if not input_csv.exists():
    print(f"Missing input: {input_csv}")
    raise SystemExit(1)

with input_csv.open('r', encoding='utf-8', newline='') as inf, output_csv.open('w', encoding='utf-8', newline='') as outf:
    reader = csv.DictReader(inf)
    writer = csv.writer(outf)
    writer.writerow(['page','image_url','existing_alt','suggested_alt','filename'])
    for r in reader:
        url = r.get('image_url','').strip()
        suggested = r.get('suggested_alt','').strip()
        existing = r.get('existing_alt','').strip()
        page = r.get('page','').strip()
        filename = url.split('/')[-1] if url else ''
        writer.writerow([page, url, existing, suggested, filename])

print(f"Wrote: {output_csv}")
