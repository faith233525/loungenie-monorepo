import requests, re

pages = [
    ('board',      'https://www.loungenie.com/board/?v=btnfix3'),
    ('financials', 'https://www.loungenie.com/financials/?v=btnfix3'),
    ('press',      'https://www.loungenie.com/press/?v=btnfix3'),
]

checks = [
    ('.ir-actions .wp-block-button__link { display:inline-flex', 'css_fixed'),
    ('ir-action-btn {', 'old_css_gone'),
    ('ir-toolbar-note', 'toolbar_note_gone'),
    ('Latest Financials', 'fin_btn_present'),
    ('Latest Press Release', 'press_btn_present'),
    ('wp-block-buttons ir-actions', 'buttons_block_present'),
]

for name, url in pages:
    html = requests.get(url, timeout=30).text
    print(f'\n=== {name} ===')
    for needle, label in checks:
        found = needle in html
        if label in ('old_css_gone', 'toolbar_note_gone'):
            print(f'  {label}: {not found}')   # these should be absent
        else:
            print(f'  {label}: {found}')
