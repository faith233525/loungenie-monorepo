#!/usr/bin/env python3
import requests
import re

pages = [
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

for name, url in pages:
    h = requests.get(url, timeout=30, headers={'User-Agent': 'Mozilla/5.0'}).text
    eager = len(re.findall(r'loading="eager"', h, re.I))
    lazy = len(re.findall(r'loading="lazy"', h, re.I))
    imgs = len(re.findall(r'<img ', h, re.I))
    print(name, 'img', imgs, 'eager', eager, 'lazy', lazy)

g = requests.get('https://www.loungenie.com/cabana-installation-photos/', timeout=30, headers={'User-Agent': 'Mozilla/5.0'}).text
for k in ['Sea-World-San-Diego.jpg', 'Sea-World-San-Diego-Edited.webp', 'Sea-World-San-Diego-1.jpg']:
    print(k, 'count', g.count(k))

for p in [('investors','https://www.loungenie.com/investors/'),('board','https://www.loungenie.com/board/'),('financials','https://www.loungenie.com/financials/'),('press','https://www.loungenie.com/press/')]:
    h = requests.get(p[1], timeout=30, headers={'User-Agent':'Mozilla/5.0'}).text
    print(p[0], 'remainingPatch', 'YES' if 'lg-aa-plus-remaining' in h else 'NO')
