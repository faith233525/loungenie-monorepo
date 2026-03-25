#!/usr/bin/env python3
"""Stream-edit a large SQL dump to align table prefixes safely.
Usage: align_sql_prefix.py <input.sql> <output.sql> --target-prefix wp7p_ [--apply]
If --apply is omitted the script will only detect and report the found prefix and counts.
"""
import argparse
import re
import sys

parser = argparse.ArgumentParser(description='Align table prefixes in SQL dump')
parser.add_argument('input', help='Input SQL dump path')
parser.add_argument('output', help='Output SQL path (only written with --apply)')
parser.add_argument('--target-prefix', required=True, help='Desired table prefix, e.g. wp7p_')
parser.add_argument('--apply', action='store_true', help='Write the output file with replacements')
args = parser.parse_args()

create_re = re.compile(r'CREATE TABLE `([^`]+)`', re.IGNORECASE)
backtick_prefix_re = None

def detect_prefix(p):
    with open(p, 'r', encoding='utf-8', errors='ignore') as fh:
        for ln in fh:
            m = create_re.search(ln)
            if m:
                tbl = m.group(1)
                if '_' in tbl:
                    # prefix is everything up to and including the first underscore
                    idx = tbl.find('_')
                    return tbl[:idx+1]
    return None

inpath = args.input
outpath = args.output

detected = detect_prefix(inpath)
if not detected:
    print('No CREATE TABLE prefix found in', inpath)
    sys.exit(1)

print('Detected prefix:', detected)
print('Target prefix:', args.target_prefix)
if detected == args.target_prefix:
    print('No change required; prefixes already match.')
    # still allow to copy file if --apply and desired
    if args.apply:
        print('Copying file to', outpath)
        with open(inpath,'rb') as r, open(outpath,'wb') as w:
            while True:
                chunk = r.read(1<<20)
                if not chunk:
                    break
                w.write(chunk)
        print('Copy complete.')
    sys.exit(0)

# prepare a pattern to find occurrences like `oldprefix
old = detected
new = args.target_prefix
pattern = ('`' + old)
count = 0
print('Counting occurrences to replace (streaming)...')
with open(inpath, 'r', encoding='utf-8', errors='ignore') as fh:
    for ln in fh:
        count += ln.count(pattern)
print('Found', count, 'occurrences of the prefix in backticked table identifiers.')

if not args.apply:
    print('\nDry run complete. To apply changes re-run with --apply')
    sys.exit(0)

print('Applying replacement and writing to', outpath)
repl = pattern.replace(old, new)  # yields '`' + new
with open(inpath, 'r', encoding='utf-8', errors='ignore') as r, open(outpath, 'w', encoding='utf-8', errors='ignore') as w:
    for ln in r:
        if pattern in ln:
            ln = ln.replace(pattern, '`' + new)
        w.write(ln)
print('Write complete.')
print('Done.')
