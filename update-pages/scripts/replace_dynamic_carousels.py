#!/usr/bin/env python3
"""Replace dynamic gallery/carousel blocks with simple static image galleries.

Targets pages: Home (4701), Features (2989), Gallery (5223).
Uses backups/<id>_raw.html to collect image URLs and then updates the page content
through the WP REST API on staging.
"""
import re
import json
import base64
import requests
from pathlib import Path

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
HEADERS = {'Authorization': f'Basic {AUTH}', 'Content-Type': 'application/json'}

TARGETS = [4701, 2989, 5223]
BACKUP_DIR = Path('backups')
LOG = Path('logs/replace_carousels_log.txt')
LOG.parent.mkdir(exist_ok=True)

IMG_RE = re.compile(r'https?://[^"\'\)\s>]+/wp-content/uploads/[^"\'\)\s>]+\.(?:jpg|jpeg|png|gif|webp)')
BLOCK_RE = re.compile(r'<!-- wp:(?:gallery|kadence/advancedgallery)[\s\S]*?/-->', re.IGNORECASE)

def find_image_urls_in_backup(page_id):
    path = BACKUP_DIR / f'{page_id}_raw.html'
    if not path.exists():
        return []
    text = path.read_text(encoding='utf-8', errors='ignore')
    return list(dict.fromkeys(IMG_RE.findall(text)))

def get_page_raw(page_id):
    r = requests.get(f'{BASE}/pages/{page_id}?context=edit', headers=HEADERS, timeout=30)
    r.raise_for_status()
    return r.json().get('content', {}).get('raw', '')

def update_page_raw(page_id, new_content):
    payload = json.dumps({'content': new_content, 'status': 'publish'})
    r = requests.post(f'{BASE}/pages/{page_id}', headers=HEADERS, data=payload, timeout=60)
    return r.status_code, r.text

def build_static_gallery(urls):
    if not urls:
        return ''
    parts = ['<!-- wp:group {"className":"lg9-static-gallery"} -->', '<div class="lg9-static-gallery">']
    for u in urls:
        parts.append(f'<figure class="wp-block-image"><img src="{u}" alt=""/></figure>')
    parts.append('</div>')
    parts.append('<!-- /wp:group -->')
    return '\n'.join(parts)

def replace_block(content, snippet):
    # replace first gallery/advancedgallery block if present
    if BLOCK_RE.search(content):
        return BLOCK_RE.sub(snippet, content, count=1), True
    # fallback: append before end
    return content + '\n' + snippet, False

def main():
    log = []
    for pid in TARGETS:
        urls = find_image_urls_in_backup(pid)
        snippet = build_static_gallery(urls)
        if not snippet:
            log.append(f'{pid}: no images found in backup')
            continue
        try:
            raw = get_page_raw(pid)
        except Exception as e:
            log.append(f'{pid}: failed to fetch page raw: {e}')
            continue
        new_raw, replaced = replace_block(raw, snippet)
        if new_raw == raw:
            log.append(f'{pid}: no change')
            continue
        status, text = update_page_raw(pid, new_raw)
        log.append(f'{pid}: updated HTTP {status} replaced={replaced}')

    LOG.write_text('\n'.join(log), encoding='utf-8')
    print('WROTE', LOG)

if __name__ == '__main__':
    main()
