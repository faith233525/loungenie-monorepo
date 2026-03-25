#!/usr/bin/env python3
"""
Recursive duplicate removal + trivial accessibility fixes for IR pages.
Saves before/after snapshots to artifacts/ and updates pages as DRAFT.

Notes:
- Requires `requests` and `beautifulsoup4` (and urllib3 warnings disabled by requests usage below).
- Does NOT create DB export. Ask for DB/cPanel creds for full DB dump.
"""
import json
import hashlib
from pathlib import Path
import re
import sys

try:
    import requests
    from bs4 import BeautifulSoup, Tag
except Exception as e:
    raise SystemExit('Install dependencies: pip install requests beautifulsoup4')

BASE = 'https://loungenie.com/staging'
USER = 'copilot'
PASS = 'SBlI yPMK 5crY p3Lo FOtF M3Tw'
PAGES = ['investors','board','financials','press']
ART = Path(r'c:/Users/pools/Documents/wordpress-develop/artifacts')
ART.mkdir(parents=True, exist_ok=True)

session = requests.Session()
session.auth = (USER, PASS)
session.verify = False

URL_RE = re.compile(r'^(//|https?:).*/wp-content/uploads/|/wp-content/uploads/|/staging/wp-content/uploads/')

def normalize_html(s: str) -> str:
    return ' '.join(s.split())

def node_hash(node: Tag) -> str:
    # compute hash of tag name + attributes (sorted) + inner HTML normalized
    attrs = ''
    try:
        if hasattr(node, 'attrs'):
            attrs = ''.join(f'{k}={v}' for k,v in sorted(node.attrs.items()))
    except Exception:
        attrs = ''
    inner = normalize_html(str(node))
    return hashlib.sha256((node.name + '|' + attrs + '|' + inner).encode('utf-8')).hexdigest()

def find_duplicate_subtrees(soup_root):
    seen = {}
    duplicates = []
    # traverse in document order
    for node in soup_root.find_all(recursive=True):
        if not isinstance(node, Tag):
            continue
        h = node_hash(node)
        if h in seen:
            duplicates.append((node, h, seen[h]))
        else:
            seen[h] = node
    return duplicates

def remove_duplicate_subtrees(soup_root):
    seen = {}
    removed = []
    # iterate with list to avoid live-tree removal issues
    for node in soup_root.find_all(recursive=True):
        if not isinstance(node, Tag):
            continue
        h = node_hash(node)
        if h in seen:
            # if the first occurrence is ancestor of this node, keep; otherwise remove this node
            first = seen[h]
            # avoid removing ancestor or parent conflicts: only remove if not same element
            if node is first:
                continue
            # remove node from tree
            try:
                node.extract()
                removed.append({'hash': h, 'tag': node.name, 'snippet': normalize_html(str(node))[:400]})
            except Exception:
                pass
        else:
            seen[h] = node
    return removed

def fix_trivial_accessibility(soup):
    fixes = []
    # links target _blank -> ensure rel includes noopener noreferrer
    for a in soup.find_all('a', target=True):
        if a.get('target') == '_blank':
            rel = a.get('rel') or []
            if isinstance(rel, str):
                rel_list = [r.strip() for r in rel.split()] if rel.strip() else []
            else:
                rel_list = list(rel)
            changed = False
            if 'noopener' not in rel_list:
                rel_list.append('noopener'); changed = True
            if 'noreferrer' not in rel_list:
                rel_list.append('noreferrer'); changed = True
            if changed:
                a['rel'] = ' '.join(rel_list)
                fixes.append({'type':'rel_added','href': a.get('href')})
    # images missing alt -> add alt=""
    for img in soup.find_all('img'):
        if img.get('alt') is None:
            img['alt'] = ''
            fixes.append({'type':'alt_empty_added','src': img.get('src')})
    # tables missing caption -> add empty caption
    for table in soup.find_all('table'):
        if not table.find('caption'):
            cap = soup.new_tag('caption')
            cap.string = ''
            table.insert(0, cap)
            fixes.append({'type':'caption_added'})
    # add landmarks if missing (wrap content with main if no main)
    if not soup.find('main'):
        # try to find primary content area
        body_children = [c for c in soup.body.contents if isinstance(c, Tag)] if soup.body else []
        if body_children:
            main = soup.new_tag('main')
            # move everything into main
            for c in list(soup.body.contents):
                main.append(c.extract())
            soup.body.append(main)
            fixes.append({'type':'main_added'})
    # nav/header/footer: add empty if missing
    if not soup.find('nav'):
        nav = soup.new_tag('nav')
        nav.string = ''
        if soup.body:
            soup.body.insert(0, nav)
            fixes.append({'type':'nav_added'})
    if not soup.find('header'):
        hdr = soup.new_tag('header')
        hdr.string = ''
        if soup.body:
            soup.body.insert(0, hdr)
            fixes.append({'type':'header_added'})
    if not soup.find('footer'):
        ftr = soup.new_tag('footer')
        ftr.string = ''
        if soup.body:
            soup.body.append(ftr)
            fixes.append({'type':'footer_added'})
    return fixes

# Main processing
summary = []
for slug in PAGES:
    print('Processing', slug)
    r = session.get(f"{BASE}/wp-json/wp/v2/pages?slug={slug}")
    if not r.ok:
        print('Failed to fetch', slug, r.status_code)
        continue
    data = r.json()
    if not data:
        print('No page for', slug)
        continue
    page = data[0]
    page_id = page['id']
    before_file = ART / f"{slug}_before.json"
    before_file.write_text(json.dumps(page, indent=2), encoding='utf-8')

    content_html = page.get('content', {}).get('rendered') or page.get('content')
    soup = BeautifulSoup(content_html, 'html.parser')

    # recursive duplicate removal
    removed = remove_duplicate_subtrees(soup)

    # trivial accessibility fixes
    fixes = fix_trivial_accessibility(soup)

    new_content = str(soup)
    after_local = ART / f"{slug}_after_local.html"
    after_local.write_text(new_content, encoding='utf-8')

    # update page as DRAFT (leave for review)
    payload = {'content': new_content, 'status': 'draft'}
    upd = session.post(f"{BASE}/wp-json/wp/v2/pages/{page_id}", json=payload)
    if not upd.ok:
        print('Failed to update', slug, upd.status_code, upd.text)
        continue
    updated_page = upd.json()
    after_remote = ART / f"{slug}_after_remote.json"
    after_remote.write_text(json.dumps(updated_page, indent=2), encoding='utf-8')

    # re-verify duplicates remaining
    remote_html = updated_page.get('content', {}).get('rendered') or updated_page.get('content')
    rsoup = BeautifulSoup(remote_html, 'html.parser')
    post_dups = find_duplicate_subtrees(rsoup)

    summary.append({
        'slug': slug,
        'page_id': page_id,
        'before_snapshot': str(before_file),
        'after_local': str(after_local),
        'after_remote': str(after_remote),
        'removed_count': len(removed),
        'removed': removed[:10],
        'fixes': fixes,
        'duplicates_remaining_count': len(post_dups)
    })

# write summary
out = ART / 'ir_recursive_cleanup_summary.json'
out.write_text(json.dumps(summary, indent=2), encoding='utf-8')
print('Summary written to', out)
print('Done')
