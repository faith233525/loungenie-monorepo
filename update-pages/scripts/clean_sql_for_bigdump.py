#!/usr/bin/env python3
"""Produce a cleaned SQL file suitable for BigDump upload.
- Removes INSERTs/CREATEs referencing configured skip tables
- Replaces production URL with staging URL
- Streams output to avoid memory pressure

Usage:
  python scripts/clean_sql_for_bigdump.py artifacts/pools425_wp872_aligned.sql artifacts/pools425_wp872_cleaned.sql

Note: This is destructive on the output path (overwrites). Run locally.
"""
import sys
from pathlib import Path

if len(sys.argv) != 3:
    print('Usage: clean_sql_for_bigdump.py <input.sql> <output.sql>')
    sys.exit(2)

input_path = Path(sys.argv[1])
output_path = Path(sys.argv[2])

if not input_path.exists():
    print('Input file not found:', input_path)
    sys.exit(3)

# Substrings identifying tables to skip (adjust as needed)
skip_table_substrings = [
    'wp7p_wordfence_log',
    'wp7p_litespeed_img_optm',
    'wp7p_wc_admin_notes',
    'wp7p_wfls_'
]

replace_from = 'https://loungenie.com'
replace_to = 'https://loungenie.com/staging'

print('Reading:', input_path)
print('Writing cleaned SQL to:', output_path)

with input_path.open('r', encoding='utf-8', errors='ignore') as r, output_path.open('w', encoding='utf-8') as w:
    skipped = 0
    written = 0
    for line in r:
        # Skip lines that reference unwanted tables
        if any(sub in line for sub in skip_table_substrings):
            skipped += 1
            continue
        if replace_from in line:
            line = line.replace(replace_from, replace_to)
        w.write(line)
        written += 1

print(f'Done. Lines written: {written}. Lines skipped (approx): {skipped}.')
print('Upload the output file via FTP to /staging and run BigDump in the browser.')
