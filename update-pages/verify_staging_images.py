#!/usr/bin/env python3
import re

for name in ['features', 'gallery']:
    c = open(f'staging_raw_{name}.txt', encoding='utf-8').read()
    has_lock = 'lock' in c.lower() or 'stash' in c.lower()
    has_sf   = 'six-flags' in c
    has_old  = 'IMG_323' in c or 'IMG_324' in c
    print(f'{name}: lock={has_lock}  six_flags={has_sf}  old_imgs={has_old}  len={len(c)}')
    srcs = re.findall(r'src="(https?://[^"]+)"', c)
    for s in srcs:
        print(' ', s.split('/')[-1])
