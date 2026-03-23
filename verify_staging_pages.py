import requests

pages = [
    ('home', 'https://loungenie.com/staging/'),
    ('features', 'https://loungenie.com/staging/poolside-amenity-unit/'),
    ('about', 'https://loungenie.com/staging/hospitality-innovation/'),
    ('contact', 'https://loungenie.com/staging/contact-loungenie/'),
    ('videos', 'https://loungenie.com/staging/loungenie-videos/'),
    ('gallery', 'https://loungenie.com/staging/cabana-installation-photos/'),
    ('investors', 'https://loungenie.com/staging/investors/'),
    ('board', 'https://loungenie.com/staging/board/'),
    ('financials', 'https://loungenie.com/staging/financials/'),
    ('press', 'https://loungenie.com/staging/press/'),
]

for name, url in pages:
    html = requests.get(url + '?v=stagecheck1', timeout=40).text
    print(name, len(html), 'cover', 'wp-block-cover' in html, 'footer', 'lg9-footer' in html)
