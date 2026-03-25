#!/usr/bin/env python3
"""
Update multiple WordPress pages via REST API using Gutenberg/Kadence blocks.

Usage:
  python scripts/update_pages.py --content-dir content --media media_lookup.json

Environment variables (recommended via CI secrets):
  WP_USERNAME
  WP_APP_PASSWORD
  WP_SITE_URL

Features:
  - Reads JSON page definitions from a content directory
  - Resolves media filenames to attachment IDs using media_lookup.json
  - Backs up existing page content to backups/
  - Sends PATCH requests to update page content (Gutenberg block markup)
  - Saves API responses to outputs/
  - Continues on errors unless --stop-on-error is set
"""

import argparse
import json
import logging
import os
import sys
import time
from glob import glob
from pathlib import Path

import requests


LOG = logging.getLogger(__name__)


def load_media_map(path):
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)


def build_block_markup(block, media_map):
    """Recursively build Gutenberg block markup from a block dict.

    Expected block format (simple):
      {
        "block": "kadence/rowlayout",
        "attrs": { ... },
        "innerHTML": "...optional raw HTML...",
        "innerBlocks": [ ... ]
      }

    If an attribute named "ids" contains filenames (strings), this will map
    them to numeric attachment IDs using media_map.
    """
    name = block.get('block')
    if not name:
        return block.get('html', '') or block.get('innerHTML', '') or ''

    attrs = block.get('attrs', {}) or {}

    # Resolve media filenames in common attributes (e.g., ids list)
    for k, v in list(attrs.items()):
        if isinstance(v, list) and v:
            # if list contains strings that appear to be filenames, map them
            if any(isinstance(x, str) for x in v):
                new_list = []
                for item in v:
                    if isinstance(item, int):
                        new_list.append(item)
                    elif isinstance(item, str):
                        mapped = media_map.get(item)
                        if mapped is not None:
                            new_list.append(mapped)
                        else:
                            LOG.warning('Missing media mapping for %s (attr %s)', item, k)
                    else:
                        new_list.append(item)
                attrs[k] = new_list

    # Serialize attrs compactly
    try:
        attrs_json = json.dumps(attrs, separators=(',', ':'), ensure_ascii=False)
    except Exception:
        attrs_json = '{}'

    opening = f'<!-- wp:{name} {attrs_json} -->'

    inner = block.get('innerHTML', '') or block.get('html', '') or ''

    # innerBlocks
    inner_blocks = block.get('innerBlocks') or []
    if inner_blocks:
        parts = [build_block_markup(b, media_map) for b in inner_blocks]
        inner += ''.join(parts)

    closing = f'<!-- /wp:{name} -->'

    return opening + inner + closing


def build_page_markup(page_json, media_map):
    # page_json may contain a top-level 'blocks' array or a 'content' raw string
    if 'content' in page_json and isinstance(page_json['content'], str):
        # assume raw Gutenberg markup already provided
        return page_json['content']

    blocks = page_json.get('blocks') or []
    parts = []
    for b in blocks:
        parts.append(build_block_markup(b, media_map))
    return '\n'.join(parts)


def backup_page(page_id, wp_site, auth, out_dir):
    url = f"{wp_site.rstrip('/')}/wp-json/wp/v2/pages/{page_id}"
    # auth can be a tuple (user,pass) or None; if headers provided, use them
    headers = None
    if isinstance(auth, dict) and auth.get('Authorization'):
        headers = {'Authorization': auth.get('Authorization')}
        r = requests.get(url, headers=headers, timeout=30)
    else:
        r = requests.get(url, auth=auth, timeout=30)
    timestamp = int(time.time())
    out_dir = Path(out_dir)
    out_dir.mkdir(parents=True, exist_ok=True)
    out_file = out_dir / f"{page_id}_backup_{timestamp}.json"
    with out_file.open('w', encoding='utf-8') as f:
        f.write(r.text)
    LOG.info('Saved backup to %s', out_file)
    return r.status_code, out_file


def update_page(page_id, wp_site, auth, content_raw, out_dir):
    url = f"{wp_site.rstrip('/')}/wp-json/wp/v2/pages/{page_id}"
    payload = {'content': {'raw': content_raw}}
    # auth may be a requests-compatible auth tuple or a dict with Authorization header
    if isinstance(auth, dict) and auth.get('Authorization'):
        headers = {'Authorization': auth.get('Authorization')}
        r = requests.patch(url, json=payload, headers=headers, timeout=60)
    else:
        r = requests.patch(url, json=payload, auth=auth, timeout=60)
    timestamp = int(time.time())
    out_dir = Path(out_dir)
    out_dir.mkdir(parents=True, exist_ok=True)
    out_file = out_dir / f"{page_id}_response_{timestamp}.json"
    with out_file.open('w', encoding='utf-8') as f:
        f.write(r.text)
    LOG.info('Saved API response to %s (HTTP %s)', out_file, r.status_code)
    return r.status_code, out_file, r.text


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--content-dir', required=True, help='Directory with page JSON files')
    ap.add_argument('--media', required=True, help='media_lookup.json mapping filenames to IDs')
    ap.add_argument('--site', required=False, help='Override WP site URL (e.g. https://example.com/staging)')
    ap.add_argument('--pages', nargs='+', help='List of page IDs to deploy (only those in content dir)')
    ap.add_argument('--stop-on-error', action='store_true', help='Stop on first page error')
    ap.add_argument('--backup-dir', default='backups', help='Directory to save page backups')
    ap.add_argument('--output-dir', default='outputs', help='Directory to save API responses')
    args = ap.parse_args()

    logging.basicConfig(level=logging.INFO, format='%(levelname)s: %(message)s')

    wp_user = os.getenv('WP_USERNAME')
    wp_pass = os.getenv('WP_APP_PASSWORD')
    wp_site = os.getenv('WP_SITE_URL')
    wp_auth_env = os.getenv('WP_AUTH')

    # Allow --site to override env
    if args.site:
        wp_site = args.site

    # Build media map
    media_map = load_media_map(args.media)

    # Determine auth: prefer explicit username/pass, otherwise accept WP_AUTH as header
    auth = None
    if wp_user and wp_pass:
        auth = (wp_user, wp_pass)
    elif wp_auth_env:
        # accept WP_AUTH as base64 or spaced token; remove spaces
        token = wp_auth_env.replace(' ', '')
        auth = {'Authorization': 'Basic ' + token}
    else:
        LOG.error('Missing WP credentials: set WP_USERNAME & WP_APP_PASSWORD, or WP_AUTH')
        sys.exit(2)

    if not wp_site:
        LOG.error('Missing WP_SITE_URL (set env or pass --site)')
        sys.exit(2)

    content_files = sorted(glob(os.path.join(args.content_dir, '*.json')))
    # If --pages provided, filter the content files to those page IDs or filenames
    if args.pages:
        wanted = set(str(x) for x in args.pages)
        filtered = []
        for p in content_files:
            name = os.path.splitext(os.path.basename(p))[0]
            if name in wanted:
                filtered.append(p)
        content_files = filtered
    if not content_files:
        LOG.error('No JSON files found in %s', args.content_dir)
        sys.exit(2)

    for path in content_files:
        LOG.info('Processing %s', path)
        with open(path, 'r', encoding='utf-8') as f:
            page_json = json.load(f)

        page_id = page_json.get('page_id')
        if not page_id:
            LOG.error('No page_id in %s; skipping', path)
            if args.stop_on_error:
                sys.exit(1)
            continue

        # Build the Gutenberg markup
        try:
            markup = build_page_markup(page_json, media_map)
        except Exception as e:
            LOG.exception('Failed to build markup for %s: %s', path, e)
            if args.stop_on_error:
                sys.exit(1)
            else:
                continue

        # Basic validation: ensure markup includes at least one wp block comment
        if '<!-- wp:' not in markup:
            LOG.warning('Generated markup for page %s does not contain any block comments', page_id)

        # Backup existing page
        try:
            backup_page(page_id, wp_site, auth, args.backup_dir)
        except Exception:
            LOG.exception('Backup failed for page %s', page_id)
            if args.stop_on_error:
                sys.exit(1)

        # Update page
        try:
            status, out_file, resp_text = update_page(page_id, wp_site, auth, markup, args.output_dir)
            if status >= 400:
                LOG.error('Update failed for page %s (HTTP %s). See %s', page_id, status, out_file)
                if args.stop_on_error:
                    sys.exit(1)
        except Exception:
            LOG.exception('Update request failed for page %s', page_id)
            if args.stop_on_error:
                sys.exit(1)

    LOG.info('Processing complete')


if __name__ == '__main__':
    main()
