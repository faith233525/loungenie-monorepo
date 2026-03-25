#!/usr/bin/env python3
import json
p='artifacts/image_audit.json'
obj=json.load(open(p))
files_with_images=obj.get('files_with_images',0)
missing=0
total_images=0
for f in obj.get('report',[]):
    for im in f.get('images',[]):
        total_images+=1
        if not im.get('has_alt'):
            missing+=1
print(f'files_with_images={files_with_images}\ntotal_images={total_images}\nmissing_alt={missing}')
