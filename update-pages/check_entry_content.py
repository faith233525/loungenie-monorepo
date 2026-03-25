"""Check what's at position 9000-10200 and 360000-364000 in investors HTML."""
import urllib.request

url = 'https://www.loungenie.com/loungenie/investors/'
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
with urllib.request.urlopen(req, timeout=20) as r:
    html = r.read().decode('utf-8', errors='replace')

print('=== Around entry-content (9800-10500) ===')
print(html[9800:10500])

print('\n=== Around lg9 div at 363000-364000 ===')
print(html[362800:364100])

# Count how many times entry-content appears
count = html.count('entry-content')
print(f'\nentry-content appears {count} times')

# Find positions of all entry-content occurrences
idx = 0
for i in range(count):
    idx = html.find('entry-content', idx)
    context = html[max(0,idx-50):idx+80]
    print(f'  pos={idx}: {context}')
    idx += 1
