#!/usr/bin/env python3
"""Clean and split a large SQL dump into uploadable chunks.
- Replaces target URLs if requested
- Skips large 'bloat' tables
- Writes 100MB chunks by default

Usage:
  python scripts/clean_and_split_sql.py <input.sql> --outdir artifacts/database/staging_chunks --size-mb 100
"""
import argparse
import os
from pathlib import Path

parser = argparse.ArgumentParser(description='Clean and split large SQL dump')
parser.add_argument('input_file', help='Path to input SQL file')
parser.add_argument('--outdir', default='artifacts/database/staging_chunks/', help='Output directory')
parser.add_argument('--size-mb', type=int, default=100, help='Chunk size in MB')
parser.add_argument('--skip-tables', nargs='*', default=['wp7p_wordfence_log','wp7p_litespeed_img_optm','wp7p_wc_admin_notes'], help='Table name substrings to skip')
parser.add_argument('--replace-from', default='https://loungenie.com', help='String to replace')
parser.add_argument('--replace-to', default='https://loungenie.com/staging', help='Replacement string')
args = parser.parse_args()

input_path = Path(args.input_file)
if not input_path.exists():
    raise SystemExit(f'Input file not found: {input_path}')

outdir = Path(args.outdir)
outdir.mkdir(parents=True, exist_ok=True)
chunk_size = args.size_mb * 1024 * 1024

chunk_num = 1
current_chunk_size = 0
out_path = outdir / f'chunk_{chunk_num:03d}.sql'
out_f = open(out_path, 'w', encoding='utf-8')
print('Writing chunks to', outdir)

with open(input_path, 'r', encoding='utf-8', errors='ignore') as f:
    for line in f:
        # Skip lines that reference known bloat tables
        if any(tbl in line for tbl in args.skip_tables):
            continue
        # Replace URLs
        if args.replace_from in line:
            line = line.replace(args.replace_from, args.replace_to)
        out_f.write(line)
        current_chunk_size += len(line.encode('utf-8'))
        if current_chunk_size >= chunk_size:
            out_f.close()
            print(f'Created chunk {chunk_num:03d}: {out_path} ({current_chunk_size} bytes)')
            chunk_num += 1
            current_chunk_size = 0
            out_path = outdir / f'chunk_{chunk_num:03d}.sql'
            out_f = open(out_path, 'w', encoding='utf-8')
# close last
out_f.close()
print('Cleaning and splitting complete. Chunks in:', outdir)
