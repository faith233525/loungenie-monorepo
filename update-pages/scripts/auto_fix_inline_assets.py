#!/usr/bin/env python3
import argparse, re, json
from pathlib import Path

CONTENT = Path('content')

STYLE_RE = re.compile(r'<style[^>]*>.*?</style>', re.DOTALL | re.IGNORECASE)
SCRIPT_RE = re.compile(r'<script[^>]*>.*?</script>', re.DOTALL | re.IGNORECASE)


def process_file(path, move_to_html=False):
    j = json.loads(path.read_text(encoding='utf-8'))
    content = j.get('content') or ''
    if not isinstance(content, str):
        return False, 0, 0

    styles = STYLE_RE.findall(content)
    scripts = SCRIPT_RE.findall(content)
    removed = 0

    if not styles and not scripts:
        return False, 0, 0

    if move_to_html:
        # Move removed blocks into a Custom HTML block at end
        collected = '\n'.join(styles + scripts)
        if collected:
            html_block = '<!-- wp:html -->\n' + collected + '\n<!-- /wp:html -->'
            content = content + '\n' + html_block

    # Remove inline blocks
    content = STYLE_RE.sub('', content)
    content = SCRIPT_RE.sub('', content)
    j['content'] = content
    path.write_text(json.dumps(j, indent=2), encoding='utf-8')
    removed = len(styles) + len(scripts)
    return True, len(styles), len(scripts)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--pages', nargs='+', help='Page IDs to fix (filenames in content/)')
    ap.add_argument('--move-to-html', action='store_true', help='Move removed CSS/JS into a Custom HTML block instead of deleting')
    args = ap.parse_args()

    targets = []
    if args.pages:
        for p in args.pages:
            f = CONTENT / f'{p}.json'
            if f.exists():
                targets.append(f)
    else:
        targets = sorted(CONTENT.glob('*.json'))

    total_files = 0
    total_styles = 0
    total_scripts = 0
    for f in targets:
        changed, s_count, j_count = process_file(f, move_to_html=args.move_to_html)
        if changed:
            print(f'Patched {f}: removed {s_count} <style>, {j_count} <script>')
            total_files += 1
            total_styles += s_count
            total_scripts += j_count

    print('Done. Files changed:', total_files, 'styles removed:', total_styles, 'scripts removed:', total_scripts)


if __name__ == '__main__':
    main()
