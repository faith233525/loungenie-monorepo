#!/usr/bin/env python3
import json
from pathlib import Path
ART = Path(r'c:/Users/pools/Documents/wordpress-develop/artifacts')
summary = {'pages_published': [], 'contrast_updates': [], 'media_urls_count': 0, 'uploads_backup': 'not_found_or_failed'}

def load(fn):
    p = ART / fn
    if p.exists():
        return json.loads(p.read_text())
    return None

publish = load('ir_publish_summary.json')
contrast = load('ir_contrast_patch_summary.json')
export = load('ir_site_export_summary.json')
kadence = load('apply_kadence_tweaks_summary.json')

if publish and 'published' in publish:
    summary['pages_published'] = publish['published']
if contrast:
    summary['contrast_updates'] = contrast.get('updated', [])
if export:
    summary['media_urls_count'] = len(export.get('media_urls', []))
# detect uploads backup dir
if (ART / 'uploads_backup').exists():
    summary['uploads_backup'] = 'exists'
else:
    summary['uploads_backup'] = 'not-done-or-path-not-found'

# include paths to key artifacts
summary['artifacts'] = {
    'site_export_summary': str(ART / 'ir_site_export_summary.json'),
    'contrast_patch_summary': str(ART / 'ir_contrast_patch_summary.json'),
    'publish_summary': str(ART / 'ir_publish_summary.json'),
    'kadence_tweaks': str(ART / 'apply_kadence_tweaks_summary.json')
}

out = ART / 'ir_final_workflow_summary.json'
out.write_text(json.dumps(summary, indent=2))
print('Wrote', out)
