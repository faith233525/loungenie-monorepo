#!/usr/bin/env python3
import requests

h = requests.get('https://www.loungenie.com/cabana-installation-photos/', timeout=30, headers={'User-Agent': 'Mozilla/5.0'}).text
keys = [
    'Cowabunga-Bay-VIP-Pool-scaled.jpg',
    'Margaritaville-Grand-Turk-3.jpg',
    'Marriott-Gaylord-Texan-1-scaled.jpg',
    'Westin-Keirland-Arizona.jpg',
    'westin-kierland-resort.jpeg',
    'Westin-Las-Vegas-HiRes-scaled.jpg',
    'Yas-Waterworld.jpg',
    'Yas-Waterworld-March-2020.jpg',
    'Waldorf-Landscape-scaled.jpg',
    'CHIC-Hotel-Punta-Cana.jpg',
]
print('new_images_expected', len(keys))
found = 0
for k in keys:
    ok = k in h
    print(k, 'FOUND' if ok else 'MISSING')
    if ok:
        found += 1
print('new_images_found', found)
