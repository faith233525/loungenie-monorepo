#!/usr/bin/env python3
"""Check media URLs referenced in backup HTML for HTTP availability."""
import re
import requests
from pathlib import Path

BACKUPS = [
    Path('backups/4701_raw.html'),
    Path('backups/2989_raw.html'),
]
OUT = Path('logs/media_check_summary.txt')
OUT.parent.mkdir(exist_ok=True)

URL_RE = re.compile(r'https?://[^"\'\)\s>]+')

def find_urls(text):
    return set(m.group(0) for m in URL_RE.finditer(text))

def check_url(url):
    try:
        r = requests.head(url, allow_redirects=True, timeout=15)
        return r.status_code, r.headers.get('Content-Type','')
    except Exception as e:
        return None, str(e)

def main():
    results = []
    for path in BACKUPS:
        if not path.exists():
            results.append((str(path), 'MISSING', []))
            continue
        text = path.read_text(encoding='utf-8', errors='ignore')
        urls = sorted(u for u in find_urls(text) if '/wp-content/uploads/' in u or 'logo' in u.lower())
        checks = []
        for u in urls:
            status, ctype = check_url(u)
            checks.append((u, status, ctype))
        results.append((str(path), 'OK', checks))

    with OUT.open('w', encoding='utf-8') as f:
        for path, state, checks in results:
            f.write(f'FILE: {path} STATUS: {state}\n')
            if state != 'OK':
                continue
            for u, status, ctype in checks:
                f.write(f'{status}\t{ctype}\t{u}\n')
            f.write('\n')

    print('WROTE', OUT)


if __name__ == '__main__':
    main()
