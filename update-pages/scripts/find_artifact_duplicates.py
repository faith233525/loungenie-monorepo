import os
import json
from collections import defaultdict

ROOT = os.path.join(os.path.dirname(__file__), '..')
ARTIFACTS = os.path.join(ROOT, 'artifacts')

id_map = defaultdict(list)
slug_map = defaultdict(list)
title_map = defaultdict(list)
files_scanned = 0

for dirpath, dirnames, filenames in os.walk(ARTIFACTS):
    for fname in filenames:
        if not fname.lower().endswith('.json'):
            continue
        fpath = os.path.join(dirpath, fname)
        try:
            with open(fpath, 'r', encoding='utf-8') as fh:
                data = json.load(fh)
        except Exception as e:
            print(f"ERROR reading {fpath}: {e}")
            continue
        files_scanned += 1
        # id
        _id = None
        if isinstance(data, dict):
            _id = data.get('id')
            slug = data.get('slug')
            title_raw = None
            title = data.get('title')
            if isinstance(title, dict):
                title_raw = title.get('raw') or title.get('rendered')
            elif isinstance(title, str):
                title_raw = title
            # normalize
            if _id is not None:
                id_map[str(_id)].append(os.path.relpath(fpath, ROOT))
            if slug:
                slug_map[str(slug)].append(os.path.relpath(fpath, ROOT))
            if title_raw:
                title_map[str(title_raw).strip()].append(os.path.relpath(fpath, ROOT))

# load navigation payload if present
nav_path = os.path.join(ARTIFACTS, 'navigation_payload.json')
nav_ids = []
if os.path.exists(nav_path):
    try:
        with open(nav_path, 'r', encoding='utf-8') as fh:
            nav = json.load(fh)
            # try to extract ids from blocks or entries
            def collect_ids(obj):
                            import re
                            ids = []
                            if isinstance(obj, dict):
                                for k,v in obj.items():
                                    if k in ('id','pageId') and isinstance(v,(int,str)):
                                        ids.append(str(v))
                                    else:
                                        ids.extend(collect_ids(v))
                            elif isinstance(obj, list):
                                for item in obj:
                                    ids.extend(collect_ids(item))
                            elif isinstance(obj, str):
                                # also extract numeric id references embedded in strings (e.g., block comments)
                                for m in re.findall(r'"id":\s*(\d+)', obj):
                                    ids.append(str(m))
                            return ids
            nav_ids = collect_ids(nav)
    except Exception as e:
        print(f"ERROR reading navigation payload: {e}")

print(f"Files scanned: {files_scanned}")

def report_map(name, m):
    dup = {k:v for k,v in m.items() if len(v) > 1}
    print('\n' + name + ' duplicates: ' + str(len(dup)))
    for k, v in sorted(dup.items(), key=lambda x: (-len(x[1]), x[0])):
        print(f"- {k} ({len(v)} occurrences)")
        for p in v:
            print(f"    {p}")

report_map('ID', id_map)
report_map('SLUG', slug_map)
report_map('TITLE', title_map)

# check nav ids not found in artifacts
artifact_ids = set(id_map.keys())
missing_in_artifacts = [i for i in nav_ids if i not in artifact_ids]
extra_artifact_ids = [i for i in artifact_ids if i not in nav_ids]

print('\nNavigation referenced IDs: ' + str(len(nav_ids)))
if nav_ids:
    unique_nav_ids = sorted(set(nav_ids), key=int)
    print('Unique navigation IDs: ' + str(len(unique_nav_ids)))

print('\nNavigation IDs missing from artifacts: ' + str(len(missing_in_artifacts)))
for i in missing_in_artifacts[:200]:
    print(' - ' + i)

print('\nArtifact IDs not referenced by navigation: ' + str(len(extra_artifact_ids)))
for i in list(extra_artifact_ids)[:200]:
    print(' - ' + i)

# summary suggestions
print('\nSummary:')
print(f' - Scanned {files_scanned} artifact JSON files')
print(f' - Duplicate IDs: {len([k for k,v in id_map.items() if len(v)>1])}')
print(f' - Duplicate slugs: {len([k for k,v in slug_map.items() if len(v)>1])}')
print(f' - Duplicate titles: {len([k for k,v in title_map.items() if len(v)>1])}')
print(' - If duplicates exist, inspect listed files to reconcile or update navigation_payload.json')
