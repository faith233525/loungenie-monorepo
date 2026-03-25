#!/usr/bin/env python3
"""Probe common plugin paths on staging to detect presence of block/slider plugins."""
import requests
from pathlib import Path

STAGING = 'https://loungenie.com/staging'
CHECKS = [
    'wp-content/plugins/kadence-blocks/kadence-blocks.php',
    'wp-content/plugins/kadence-blocks/build/index.js',
    'wp-content/plugins/kadence-blocks/assets/js/frontend.min.js',
    'wp-content/plugins/kadence-blocks/includes/blocks/advanced-gallery.php',
    'wp-content/plugins/elementor/assets/js/frontend.min.js',
    'wp-content/plugins/smart-slider-3/public/assets/js/frontend.min.js',
    'wp-content/plugins/metaslider/assets/js/mu-plugins.min.js',
    'wp-content/plugins/revslider/public/assets/js/jquery.themepunch.revolution.min.js',
    'wp-content/plugins/woocommerce/woocommerce.php',
]

OUT = Path('logs/plugins_check.txt')
OUT.parent.mkdir(exist_ok=True)

def main():
    results = []
    for p in CHECKS:
        url = f'{STAGING}/{p}'
        try:
            r = requests.get(url, timeout=10)
            results.append((url, r.status_code))
        except Exception as e:
            results.append((url, str(e)))

    with OUT.open('w', encoding='utf-8') as f:
        for url, status in results:
            f.write(f'{status}\t{url}\n')

    print('WROTE', OUT)

if __name__ == '__main__':
    main()
