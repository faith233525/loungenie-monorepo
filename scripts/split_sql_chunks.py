#!/usr/bin/env python3
"""Split a large SQL dump into smaller chunk files (default 100MB).

Usage:
  python scripts/split_sql_chunks.py /path/to/input.sql --outdir artifacts/database/chunks --size-mb 100

Note: This script attempts to avoid breaking mid-line by extending each chunk to the next newline.
For more robust SQL-aware splitting, use a specialized tool or import on the server directly.
"""
import argparse
import os

parser = argparse.ArgumentParser(description='Split a large SQL dump into fixed-size chunks')
parser.add_argument('input_file', help='Path to input SQL file')
parser.add_argument('--outdir', default='artifacts/database/chunks/', help='Output directory for chunks')
parser.add_argument('--size-mb', type=int, default=100, help='Chunk size in megabytes (default 100)')
args = parser.parse_args()

input_file = args.input_file
outdir = args.outdir
chunk_size = args.size_mb * 1024 * 1024

os.makedirs(outdir, exist_ok=True)

with open(input_file, 'rb') as f:
    chunk_num = 1
    while True:
        data = f.read(chunk_size)
        if not data:
            break
        # Read until the end of the current line to reduce risk of breaking SQL statements
        extra = f.readline()
        chunk_path = os.path.join(outdir, f'chunk_{chunk_num:03d}.sql')
        with open(chunk_path, 'wb') as chunk_f:
            chunk_f.write(data)
            if extra:
                chunk_f.write(extra)
        print(f'Created chunk {chunk_num:03d}: {chunk_path}')
        chunk_num += 1

print('Splitting complete.')
