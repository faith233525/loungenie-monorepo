import re
from pathlib import Path

def find_wp_config(search_root='artifacts'):
    p = Path(search_root)
    matches = list(p.rglob('wp-config.php'))
    return matches

def parse_table_prefix(path: Path):
    txt = path.read_text(encoding='utf-8', errors='ignore')
    m = re.search(r"\$table_prefix\s*=\s*['\"]([a-zA-Z0-9_]+)['\"]\s*;", txt)
    return m.group(1) if m else None

def main():
    configs = find_wp_config()
    out_lines = []
    if not configs:
        print('No wp-config.php found under artifacts')
        return
    for c in configs:
        prefix = parse_table_prefix(c)
        out_lines.append(f'{c} -> table_prefix={prefix}')
    out = '\n'.join(out_lines)
    Path('artifacts/table_prefix_report.txt').write_text(out, encoding='utf-8')
    print('Wrote artifacts/table_prefix_report.txt')

if __name__ == '__main__':
    main()
