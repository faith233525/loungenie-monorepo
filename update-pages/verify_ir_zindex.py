import requests, re

pages = [
    ('investors', 'https://www.loungenie.com/investors/?v=zfix1'),
    ('board',     'https://www.loungenie.com/board/?v=zfix1'),
    ('financials','https://www.loungenie.com/financials/?v=zfix1'),
    ('press',     'https://www.loungenie.com/press/?v=zfix1'),
]

for name, url in pages:
    html = requests.get(url, timeout=30).text
    has_zindex = 'position: relative;\n    z-index: 1;\n    margin-top: -64px' in html
    has_fin = 'Latest Financials' in html
    has_press = 'Latest Press Release' in html
    print(f'{name}: panel_zindex={has_zindex}  fin_btn={has_fin}  press_btn={has_press}')
