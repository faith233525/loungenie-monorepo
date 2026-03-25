#!/usr/bin/env python3
"""
Convert image URLs in a page backup to WordPress attachment IDs and produce
an updated Gutenberg block payload for a page.

Usage:
  - Ensure your Python environment has `requests` installed.
  - Set `WP_AUTH` environment variable (Basic auth header value) and optionally
    `WP_BASE` (defaults to https://loungenie.com/staging).
  - Run: `python scripts/convert_images_to_attachment_ids.py --backup backups/professional-redesign-v12-live-backup-20260321-160731.json --page 5223`

Output:
  - writes `backups/5223_gutenberg_payload.json` containing {"content": "<blocks>..."}

Note: this script does NOT POST to the site.
"""
import os
import sys
import json
import re
import argparse
from urllib.parse import urlparse

try:
    import requests
except Exception:
    print('This script requires the requests package. Install with: pip install requests')
    sys.exit(1)


def load_backup(path):
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)


def find_page_obj(backups, page_id):
    for obj in backups:
        if isinstance(obj, dict) and obj.get('id') == page_id:
            return obj
    return None


def fetch_all_media(wp_base, wp_auth):
    headers = {}
    if wp_auth:
        headers['Authorization'] = 'Basic ' + wp_auth

    media = []
    per_page = 100
    page = 1
    while True:
        url = f"{wp_base.rstrip('/')}/wp-json/wp/v2/media?per_page={per_page}&page={page}"
        r = requests.get(url, headers=headers, timeout=30)
        if r.status_code != 200:
            raise RuntimeError(f'Failed to fetch media page {page}: {r.status_code} {r.text[:200]}')
        batch = r.json()
        if not batch:
            break
        media.extend(batch)
        if len(batch) < per_page:
            break
        page += 1
    return media


def build_url_to_id_map(media_list):
    # Build multiple lookup maps to enable fuzzy matching:
    # - exact_map: full URL (no query)
    # - basename_map: file basename -> id
    # - naked_basename_map: basename without WP size suffix (e.g., image-1024x768.jpg -> image.jpg) -> id
    exact_map = {}
    basename_map = {}
    naked_basename_map = {}

    def naked_name(name):
        # remove size suffixes like -300x200, -1024x768 and common suffixes like -scaled
        # preserve extension
        m = re.match(r'^(?P<base>.+?)(?:-(?:\d+x\d+|scaled|large|medium|thumbnail))?(?P<ext>\.[^.]+)$', name, re.I)
        if m:
            return m.group('base') + m.group('ext')
        # fallback: return original
        return name

    for m in media_list:
        mid = m.get('id')
        # prefer source_url or guid->rendered
        source_urls = set()
        if isinstance(m.get('source_url'), str):
            source_urls.add(m['source_url'].split('?')[0])
        if m.get('guid') and isinstance(m['guid'].get('rendered'), str):
            source_urls.add(m['guid']['rendered'].split('?')[0])

        # include any registered size URLs from media_details
        sizes = m.get('media_details', {}).get('sizes') or {}
        for sval in sizes.values():
            if isinstance(sval.get('source_url'), str):
                source_urls.add(sval['source_url'].split('?')[0])

        for su in source_urls:
            exact_map[su] = mid
            # basename (file name)
            try:
                b = os.path.basename(urlparse(su).path)
            except Exception:
                b = su.split('/')[-1]
            if b:
                basename_map[b] = mid
                naked = naked_name(b)
                naked_basename_map[naked] = mid

    # Return all maps bundled
    return {
        'exact': exact_map,
        'basename': basename_map,
        'naked': naked_basename_map,
    }


def extract_galleries(html):
    # find <figure ... class="wp-block-gallery" ...>...</figure>
    gallery_re = re.compile(r'(<figure[^>]*class=["\'][^"\']*wp-block-gallery[^"\']*["\'][^>]*>.*?</figure>)', re.S | re.I)
    return gallery_re.findall(html)


def extract_img_srcs(fragment):
    img_re = re.compile(r'<img[^>]+src=["\']([^"\']+)["\']', re.I)
    return img_re.findall(fragment)


def map_src_to_id(src, url_map):
    p = src.split('?')[0]
    # try exact
    idv = url_map.get('exact', {}).get(p)
    try:
        b = os.path.basename(urlparse(p).path)
    except Exception:
        b = p.split('/')[-1]
    if idv is None:
        idv = url_map.get('basename', {}).get(b)
    if idv is None:
        naked = re.sub(r'-(?:\d+x\d+|scaled|large|medium|thumbnail)(?=\.)', '', b, flags=re.I)
        idv = url_map.get('naked', {}).get(naked)
    if idv is None:
        for u, vid in url_map.get('exact', {}).items():
            if b in u:
                idv = vid
                break
    return idv


def normalize_filename(name):
    # lowercase, strip query, strip extension, remove common size suffixes
    n = name.split('?')[0]
    try:
        b = os.path.basename(urlparse(n).path)
    except Exception:
        b = n.split('/')[-1]
    b = b.lower()
    # remove extension
    b_noext = re.sub(r'\.[a-z0-9]+$', '', b, flags=re.I)
    # strip common WP suffixes like -1024x768, -scaled, -large, -medium, -thumbnail
    b_clean = re.sub(r'-(?:\d+x\d+|scaled|large|medium|thumbnail)$', '', b_noext, flags=re.I)
    return b_clean


def refine_mappings(url_map, mapped, missing):
    # Build reverse index of normalized names -> id
    rev = {}
    for u, mid in url_map.get('exact', {}).items():
        try:
            b = os.path.basename(urlparse(u).path)
        except Exception:
            b = u.split('/')[-1]
        norm = normalize_filename(b)
        rev.setdefault(norm, set()).add(mid)
    for bname, mid in url_map.get('basename', {}).items():
        norm = normalize_filename(bname)
        rev.setdefault(norm, set()).add(mid)
    for naked, mid in url_map.get('naked', {}).items():
        norm = normalize_filename(naked)
        rev.setdefault(norm, set()).add(mid)

    newly_mapped = {}
    still_missing = []

    for s in missing:
        norm_target = normalize_filename(s)
        found = None
        # direct normalized match
        if norm_target in rev:
            found = next(iter(rev[norm_target]))
        else:
            # substring match: any rev key contains norm_target or vice versa
            for k, ids in rev.items():
                if norm_target in k or k in norm_target:
                    found = next(iter(ids))
                    break
        if found:
            newly_mapped[s] = found
        else:
            still_missing.append(s)

    # merge newly mapped into mapped dict
    mapped.update(newly_mapped)
    return mapped, still_missing


def replace_galleries_with_ids(html, url_map):
    def repl(match):
        gallery_html = match.group(1)
        srcs = extract_img_srcs(gallery_html)
        ids = []
        for s in srcs:
            # normalize url (trim query)
            p = s.split('?')[0]
            idv = None
            # try exact url
            idv = url_map.get('exact', {}).get(p)
            if idv is None:
                # try matching by full basename
                try:
                    b = os.path.basename(urlparse(p).path)
                except Exception:
                    b = p.split('/')[-1]
                idv = url_map.get('basename', {}).get(b)
            if idv is None:
                # try naked basename (strip -WIDTHxHEIGHT and common suffixes)
                try:
                    b = os.path.basename(urlparse(p).path)
                except Exception:
                    b = p.split('/')[-1]
                # remove size suffixes
                naked = re.sub(r'-(?:\d+x\d+|scaled|large|medium|thumbnail)(?=\.)', '', b, flags=re.I)
                idv = url_map.get('naked', {}).get(naked)
            if idv is None:
                # partial fallback: match by contains
                for u, vid in url_map.get('exact', {}).items():
                    if u.endswith(b) or b in u:
                        idv = vid
                        break
            if idv:
                ids.append(idv)
        if not ids:
            # keep original if we couldn't map any images
            return gallery_html
        # choose columns based on count (simple heuristic)
        cols = 3 if len(ids) >= 3 else len(ids)
        # Use a proper gallery block comment so Gutenberg recognizes it
        return f'<!-- wp:gallery {{"ids":{ids},"columns":{cols},"linkTo":"none"}} -->\n<!-- /wp:gallery -->'

    gallery_re = re.compile(r'(<figure[^>]*class=["\'][^"\']*wp-block-gallery[^"\']*["\'][^>]*>.*?</figure>)', re.S | re.I)
    new_html = gallery_re.sub(repl, html)
    return new_html


def replace_image_figures_with_blocks(html, url_map):
    # Replace single image figure blocks
    def repl_img(match):
        full = match.group(0)
        src = match.group(1)
        p = src.split('?')[0]
        idv = None
        # try exact
        idv = url_map.get('exact', {}).get(p)
        try:
            b = os.path.basename(urlparse(p).path)
        except Exception:
            b = p.split('/')[-1]
        if idv is None:
            idv = url_map.get('basename', {}).get(b)
        if idv is None:
            naked = re.sub(r'-(?:\d+x\d+|scaled|large|medium|thumbnail)(?=\.)', '', b, flags=re.I)
            idv = url_map.get('naked', {}).get(naked)
        if idv is None:
            for u, vid in url_map.get('exact', {}).items():
                if b in u:
                    idv = vid
                    break
        if idv:
            return f'<!-- wp:image {{"id":{idv},"sizeSlug":"large","linkDestination":"none"}} -->\n<!-- /wp:image -->'
        return full

    img_fig_re = re.compile(r'<figure[^>]*class=["\']wp-block-image[^"\']*["\'][^>]*>.*?<img[^>]+src=["\']([^"\']+)["\'][^>]*>.*?</figure>', re.S | re.I)
    return img_fig_re.sub(repl_img, html)

def extract_and_remove_inline_assets(html, page_id):
    # extract <style> and <script> blocks and write to files, then remove them from html
    styles = re.findall(r'<style[^>]*>.*?</style>', html, flags=re.S | re.I)
    scripts = re.findall(r'<script[^>]*>.*?</script>', html, flags=re.S | re.I)
    if styles:
        out_css = f'backups/{page_id}_extracted_style.css'
        with open(out_css, 'w', encoding='utf-8') as f:
            for s in styles:
                inner = re.sub(r'^<style[^>]*>|</style>$', '', s, flags=re.I)
                f.write(inner + "\n\n")
        print('Wrote extracted CSS to', out_css)
    if scripts:
        out_js = f'backups/{page_id}_extracted_script.js'
        with open(out_js, 'w', encoding='utf-8') as f:
            for s in scripts:
                inner = re.sub(r'^<script[^>]*>|</script>$', '', s, flags=re.I)
                f.write(inner + "\n\n")
        print('Wrote extracted JS to', out_js)
    # remove them from html
    html = re.sub(r'<style[^>]*>.*?</style>', '', html, flags=re.S | re.I)
    html = re.sub(r'<script[^>]*>.*?</script>', '', html, flags=re.S | re.I)
    return html

def convert_gallery_containers(html, url_map, page_id, mapped_map=None):
    # target known gallery container classes used in the payload
    container_re = re.compile(r'(<div[^>]*class=["\'][^"\']*(lg9-gallery|lg9-gallery--mosaic|lg9-gallery-band|lg9-gallery-lead)[^"\']*["\'][^>]*>.*?</div>)', re.S | re.I)
    def repl(match):
        block = match.group(1)
        srcs = extract_img_srcs(block)
        ids = []
        for s in srcs:
            idv = None
            if mapped_map and s in mapped_map:
                idv = mapped_map.get(s)
            if idv is None:
                idv = map_src_to_id(s, url_map)
            if idv:
                ids.append(idv)
        if not ids:
            return block
        cols = 3 if len(ids) >= 3 else len(ids)
        return f'<!-- wp:gallery {{"ids":{ids},"columns":{cols},"linkTo":"none"}} -->\n<!-- /wp:gallery -->'
    return container_re.sub(repl, html)

def convert_remaining_images(html, url_map, page_id, mapped_map=None):
    # Replace remaining standalone <img ...> with wp:image blocks preserving alt
    def repl_img(match):
        whole = match.group(0)
        src = match.group('src')
        alt = match.group('alt') or ''
        idv = None
        if mapped_map and src in mapped_map:
            idv = mapped_map.get(src)
        if idv is None:
            idv = map_src_to_id(src, url_map)
        if idv:
            return f'<!-- wp:image {{"id":{idv},"sizeSlug":"large","linkDestination":"none"}} -->\n<!-- /wp:image -->'
        return whole

    img_re = re.compile(r'<img[^>]+src=["\'](?P<src>[^"\']+)["\'][^>]*?(?:alt=["\'](?P<alt>[^"\']*)["\'])?[^>]*>', re.I)
    new_html = img_re.sub(repl_img, html)
    return new_html
def main():
    p = argparse.ArgumentParser()
    p.add_argument('--backup', required=True)
    p.add_argument('--page', type=int, required=True)
    p.add_argument('--wp-base', default=os.environ.get('WP_BASE', 'https://loungenie.com/staging'))
    p.add_argument('--out', default='backups/5223_gutenberg_payload.json')
    args = p.parse_args()

    wp_auth = os.environ.get('WP_AUTH')
    if not wp_auth:
        print('WP_AUTH not found in environment. Set WP_AUTH to a Base64 (user:pass) value or provide plugin token.')
        # proceed but warn — we may not be able to fetch media

    backups = load_backup(args.backup)
    page_obj = find_page_obj(backups, args.page)
    if not page_obj:
        print(f'Page id {args.page} not found in {args.backup}')
        sys.exit(2)

    content = page_obj.get('content', {}).get('rendered', '')
    if not content:
        print('No content.rendered found for the page.')
        sys.exit(3)

    print('Fetching media list from', args.wp_base)
    media_list = []
    try:
        media_list = fetch_all_media(args.wp_base, wp_auth)
    except Exception as e:
        print('Warning: could not fetch media list:', e)

    url_map = build_url_to_id_map(media_list)
    exact_count = len(url_map.get('exact', {})) if isinstance(url_map, dict) else 0
    print(f'Fetched {exact_count} media items')

    # Build mapping log: all image srcs from original content -> attachment id (or null)
    all_srcs = set(extract_img_srcs(content))
    mapped = {}
    missing = []
    for s in sorted(all_srcs):
        idv = map_src_to_id(s, url_map)
        if idv:
            mapped[s] = idv
        else:
            missing.append(s)

    mapping_out = args.out.replace('.json', '_mapping.json')
    mapping_payload = {
        'page': args.page,
        'mapped_count': len(mapped),
        'missing_count': len(missing),
        'mapped': mapped,
        'missing': missing,
    }
    with open(mapping_out, 'w', encoding='utf-8') as mf:
        json.dump(mapping_payload, mf, indent=2, ensure_ascii=False)
    print('Wrote mapping log to', mapping_out)

    # Attempt to refine mappings for any missing images using looser matching
    if missing:
        print('Attempting refined matching for', len(missing), 'missing images')
        refined_mapped = dict(mapped)  # copy
        refined_mapped, still_missing = refine_mappings(url_map, refined_mapped, missing)
        refined_out = args.out.replace('.json', '_mapping_refined.json')
        refined_payload = {
            'page': args.page,
            'mapped_count': len(refined_mapped),
            'missing_count': len(still_missing),
            'mapped': refined_mapped,
            'missing': still_missing,
            'notes': 'Refined pass used normalized filename and substring matching.'
        }
        with open(refined_out, 'w', encoding='utf-8') as rf:
            json.dump(refined_payload, rf, indent=2, ensure_ascii=False)
        print('Wrote refined mapping log to', refined_out)
        # overwrite main mapping with refined summary for convenience
        with open(mapping_out, 'w', encoding='utf-8') as mf:
            json.dump(refined_payload, mf, indent=2, ensure_ascii=False)
        mapped = refined_mapped
        missing = still_missing

    # Strip inline CSS/JS from the content and save them separately
    new = extract_and_remove_inline_assets(content, args.page)
    # Convert known gallery container divs (lg9 classes) to Gutenberg gallery blocks
    new = convert_gallery_containers(new, url_map, args.page, mapped)
    # Run existing converters for <figure class="wp-block-gallery"> and figure image blocks
    new = replace_galleries_with_ids(new, url_map)
    new = replace_image_figures_with_blocks(new, url_map)
    # Convert remaining standalone <img> tags to wp:image blocks
    new = convert_remaining_images(new, url_map, args.page, mapped)

    payload = { 'content': new }
    with open(args.out, 'w', encoding='utf-8') as f:
        json.dump(payload, f, indent=2, ensure_ascii=False)

    print('Wrote updated payload to', args.out)


if __name__ == '__main__':
    main()
