#!/usr/bin/env python3
import json, re, argparse
from pathlib import Path

BACKUPS = Path('backups')
CONTENT = Path('content')
MEDIA = BACKUPS / 'media_lookup.json'


def load_media():
    if not MEDIA.exists():
        return {}
    return json.loads(MEDIA.read_text(encoding='utf-8'))


media = load_media()


def resolve(key):
    if key in media:
        return int(media[key])
    b = key.split('/')[-1]
    if b in media:
        return int(media[b])
    naked = re.sub(r'-(?:\d+x\d+|scaled|large|medium|thumbnail)(?=\.)', '', b, flags=re.I)
    if naked in media:
        return int(media[naked])
    if key.lower() in media:
        return int(media[key.lower()])
    if b.lower() in media:
        return int(media[b.lower()])
    return None


GALLERY_RE = re.compile(r'<!--\s*wp:gallery\s*(\{.*?\})\s*-->', re.DOTALL)


def patch_text(txt):
    new_txt = txt
    patched_local = 0
    for m in reversed(list(GALLERY_RE.finditer(txt))):
        js = m.group(1)
        try:
            obj = json.loads(js)
        except Exception:
            continue
        ids = obj.get('ids')
        if not ids or all(isinstance(x, int) for x in ids):
            continue
        new_ids = []
        changed = False
        for item in ids:
            if isinstance(item, int):
                new_ids.append(item)
                continue
            mapped = resolve(item)
            if mapped:
                new_ids.append(mapped)
                changed = True
            else:
                candidate = item.split('?')[0].split('/')[-1]
                mapped = resolve(candidate)
                if mapped:
                    new_ids.append(mapped)
                    changed = True
                else:
                    new_ids.append(item)
        if changed:
            obj['ids'] = new_ids
            new_js = json.dumps(obj, separators=(',', ':'), ensure_ascii=False)
            start, end = m.span(1)
            new_txt = new_txt[:start] + new_js + new_txt[end:]
            patched_local += 1
    return new_txt, patched_local


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--page', help='Page ID (numeric) to limit patching')
    args = ap.parse_args()

    targets = []
    if args.page:
        # consider backups and content
        pid = str(args.page)
        for p in BACKUPS.glob(f'{pid}*_gutenberg_payload*.json'):
            targets.append(p)
        cfile = CONTENT / f'{pid}.json'
        if cfile.exists():
            targets.append(cfile)
    else:
        targets = sorted(BACKUPS.glob('*_gutenberg_payload*.json')) + sorted(CONTENT.glob('*.json'))

    total = 0
    for f in targets:
        try:
            txt = f.read_text(encoding='utf-8')
        except Exception:
            continue
        new_txt, patched = patch_text(txt)
        if patched:
            # write patched version alongside existing file
            if f.parent == CONTENT:
                f.write_text(new_txt, encoding='utf-8')
                print('Updated content file', f)
            else:
                out = f.with_name(f.stem + '_patched_ids.json')
                out.write_text(new_txt, encoding='utf-8')
                print('Wrote', out)
            total += patched

    print('Patched galleries in', total, 'occurrences')


if __name__ == '__main__':
    main()

