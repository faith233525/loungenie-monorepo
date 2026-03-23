import requests

pages = [
    ('home', 'https://loungenie.com/staging/'),
    ('features', 'https://loungenie.com/staging/poolside-amenity-unit/'),
    ('about', 'https://loungenie.com/staging/hospitality-innovation/'),
    ('contact', 'https://loungenie.com/staging/contact-loungenie/'),
    ('videos', 'https://loungenie.com/staging/loungenie-videos/'),
    ('gallery', 'https://loungenie.com/staging/cabana-installation-photos/'),
]

for name, url in pages:
    html = requests.get(url + '?v=hero_fix2', timeout=30).text
    print(
        name,
        'cover=', 'wp-block-cover' in html,
        'focal=', 'data-object-position="50% 50%"' in html,
        'hero-class=', 'lg9-page-hero' in html,
    )
