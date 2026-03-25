#!/usr/bin/env python3
"""Scan artifacts JSON for image URLs and detect missing alt text.
Writes report to artifacts/image_audit.json
"""
import os, json, re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ART = ROOT / 'artifacts'

img_pattern = re.compile(r'https?://[^\s\"\']+\.(?:png|jpg|jpeg|webp|gif)', re.I)

reports = []
files_scanned = 0

for p in ART.rglob('*.json'):
    files_scanned += 1
    try:
        data = json.loads(p.read_text(encoding='utf-8'))
    except Exception as e:
        continue
    text = json.dumps(data)
    urls = set(img_pattern.findall(text))
    if not urls:
        continue
    # Heuristic: look for 'alt' near the url in the JSON text
    entries = []
    for u in urls:
        idx = text.find(u)
        start = max(0, idx-200)
        segment = text[start:idx+len(u)+200]
        has_alt = '"alt"' in segment or 'alt=' in segment
        entries.append({'url': u, 'has_alt': has_alt})
    reports.append({'file': str(p.relative_to(ROOT)), 'images': entries})

out = {
    'files_scanned': files_scanned,
    'files_with_images': len(reports),
    'report': reports
}

outpath = ART / 'image_audit.json'
outpath.write_text(json.dumps(out, indent=2), encoding='utf-8')
print('Wrote', outpath)
