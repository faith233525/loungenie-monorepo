"""Slice the investors rendered HTML to understand nested structure."""
import urllib.request

url = 'https://www.loungenie.com/loungenie/investors/'
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
with urllib.request.urlopen(req, timeout=20) as r:
    html = r.read().decode('utf-8', errors='replace')

# Show slice around the UAGB block
print('=== HTML around UAGB block (positions 44000-56000) ===')
print(html[44000:56000])
