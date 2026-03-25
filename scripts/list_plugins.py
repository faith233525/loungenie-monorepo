from pathlib import Path
import re

PLUGINS_DIR = Path('artifacts/wp-content-extracted/wp-content/plugins')

def read_plugin_header(plugin_folder: Path):
    # find first PHP file in root of plugin folder
    for f in plugin_folder.iterdir():
        if f.is_file() and f.suffix == '.php':
            txt = f.read_text(encoding='utf-8', errors='ignore')
            m = re.search(r'Plugin Name:\s*(.+)', txt)
            if m:
                return m.group(1).strip()
    return None

def main():
    out = []
    if not PLUGINS_DIR.exists():
        print(f'Plugins dir not found: {PLUGINS_DIR}')
        return
    for d in sorted(PLUGINS_DIR.iterdir()):
        if d.is_dir():
            header = read_plugin_header(d) or ''
            size = sum(f.stat().st_size for f in d.rglob('*') if f.is_file())
            out.append(f'{d.name}\t{header}\t{size}')

    Path('artifacts/plugins_list.txt').write_text('\n'.join(out), encoding='utf-8')
    print('Wrote artifacts/plugins_list.txt')

if __name__ == '__main__':
    main()
