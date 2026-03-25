from pathlib import Path
import re

script_path = Path(r"c:\Users\pools\Documents\wordpress-develop\professional-redesign-v12.py")
photos = Path(r"c:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos")
text = script_path.read_text(encoding='utf-8', errors='ignore')
img_block = re.search(r"IMG\s*=\s*\{(.*?)\n\}", text, re.S)
entries = []
if img_block:
    for line in img_block.group(1).splitlines():
        m = re.search(r'"([a-zA-Z0-9_]+)"\s*:\s*(.+?),\s*$', line.strip())
        if m:
            k,v = m.group(1), m.group(2)
            entries.append((k,v))

# build file index once
all_files = [p for p in photos.rglob('*') if p.is_file()]
name_index = {p.name.lower(): p for p in all_files}

def extract_name(v):
    # pull quoted string literal tail filename if present
    if '"' in v:
        parts = re.findall(r'"([^"]+)"', v)
        if parts:
            s = parts[-1]
            return Path(s).name
    return None

print('TOKENS:', len(entries))
for k,v in entries:
    n = extract_name(v)
    if not n:
        print(f"{k:14} | (no filename)")
        continue
    exact = name_index.get(n.lower())
    if exact:
        print(f"{k:14} | {n:55} | FOUND | {exact}")
        continue
    stem = Path(n).stem.lower().replace('-', ' ').replace('_',' ')
    cand = None
    for p in all_files:
        ps = p.stem.lower().replace('-', ' ').replace('_',' ')
        if stem and (stem in ps or ps in stem):
            cand = p
            break
    if cand:
        print(f"{k:14} | {n:55} | CLOSE | {cand.name}")
    else:
        print(f"{k:14} | {n:55} | MISSING")
