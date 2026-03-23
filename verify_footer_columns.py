import requests, re

html = requests.get('https://www.loungenie.com/?v=ftr_col1', timeout=30).text

# Find the footer and check columns structure
idx = html.find('<footer')
if idx != -1:
    footer_html = html[idx:idx+4000]
    print('[Footer HTML]')
    print(footer_html)
    print('\n--- checks ---')
    print('has wp-block-columns:', 'wp-block-columns' in footer_html)
    print('has wp-block-column:', 'wp-block-column' in footer_html)
    print('has 3 columns:', footer_html.count('wp-block-column is-layout-flow') >= 3)
    print('has Explore:', 'Explore' in footer_html)
    print('has Investor + Contact:', 'Investor + Contact' in footer_html)
    print('has logo img:', 'LounGenie-Logo' in footer_html)
    print('has old lg9-footer-grid:', 'lg9-footer-grid' in footer_html)
