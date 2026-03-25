import requests
from bs4 import BeautifulSoup as BS

urls = [
    ('home', 'https://www.loungenie.com/'),
    ('features', 'https://www.loungenie.com/poolside-amenity-unit/'),
    ('about', 'https://www.loungenie.com/hospitality-innovation/'),
    ('contact', 'https://www.loungenie.com/contact-loungenie/'),
    ('videos', 'https://www.loungenie.com/loungenie-videos/'),
    ('gallery', 'https://www.loungenie.com/cabana-installation-photos/'),
    ('investors', 'https://www.loungenie.com/investors/'),
    ('board', 'https://www.loungenie.com/board/'),
    ('financials', 'https://www.loungenie.com/financials/'),
    ('press', 'https://www.loungenie.com/press/'),
]

for label, url in urls:
    html = requests.get(url, timeout=30).text
    soup = BS(html, 'html.parser')
    h1 = soup.find('h1')
    title = soup.title.string if soup.title else ''
    desc = ''
    for tag in soup.find_all('meta'):
        if tag.get('name', '').lower() == 'description':
            desc = tag.get('content', '')
    print(label)
    print('H1:', h1.text.strip() if h1 else 'NONE')
    print('Title:', title.strip())
    print('Desc:', desc[:120] + '...' if desc else 'NONE')
    print('-' * 40)
