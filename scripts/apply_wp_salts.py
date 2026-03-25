#!/usr/bin/env python3
"""Fetch WordPress salts and insert them into a wp-config file.
Usage: python scripts/apply_wp_salts.py path/to/wp-config-staging.php
Backs up the original file to the same directory with .bak timestamp.
"""
import sys
import urllib.request
import time
from pathlib import Path

if len(sys.argv) < 2:
    print('Usage: apply_wp_salts.py <wp-config.php>')
    sys.exit(2)

cfg_path = Path(sys.argv[1])
if not cfg_path.exists():
    print('File not found:', cfg_path)
    sys.exit(3)

url = 'https://api.wordpress.org/secret-key/1.1/salt/'
print('Fetching salts from', url)
resp = urllib.request.urlopen(url)
salts = resp.read().decode('utf-8')

orig = cfg_path.read_text(encoding='utf-8')
# find the block markers
start_marker = '/**#@+'
end_marker = '/**#@-*/'
si = orig.find(start_marker)
if si == -1:
    print('Start marker not found; aborting to avoid corrupting file')
    sys.exit(4)
# find end marker after start
ei = orig.find(end_marker, si)
if ei == -1:
    print('End marker not found; aborting to avoid corrupting file')
    sys.exit(5)
# include end_marker length
ei_end = ei + len(end_marker)
# build new content: keep up to start_marker line, then insert salts, then keep remainder after end_marker
prefix = orig[:si]
# ensure salts end with newline
if not salts.endswith('\n'):
    salts = salts + '\n'
# suffix is content after end_marker line
suffix = orig[ei_end:]
new_content = prefix + start_marker + '\n' + salts + '\n' + end_marker + suffix
# backup original
bak_path = cfg_path.with_name(cfg_path.name + '.bak.' + time.strftime('%Y%m%d%H%M%S'))
cfg_path.rename(bak_path)
print('Original backed up to', bak_path)
# write new file
cfg_path.write_text(new_content, encoding='utf-8')
print('Updated', cfg_path)
