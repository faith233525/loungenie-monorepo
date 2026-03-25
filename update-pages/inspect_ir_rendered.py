import requests, re

html = requests.get('https://www.loungenie.com/board/?v=inspbtn1', timeout=30).text

# Find ir-hero section and what immediately follows
idx = html.find('ir-hero')
if idx != -1:
    print('[ir-hero region]')
    print(html[max(0, idx-50):idx+2000])

print('\n\n[ir-actions region]')
idx2 = html.find('ir-actions')
while idx2 != -1:
    print(f'--- occurrence at {idx2} ---')
    print(html[max(0, idx2-100):idx2+600])
    print()
    idx2 = html.find('ir-actions', idx2+1)
    if idx2 > 0 and html[max(0,idx2-200):idx2].count('ir-actions') > 3:
        break  # stop after CSS section

print('\n\n[After hero, first 2000 chars in main content]')
idx3 = html.find('ir-content-wrap')
if idx3 != -1:
    print(html[idx3:idx3+2000])
