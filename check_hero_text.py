import urllib.request, re

pages = [
    ("board",      "https://www.loungenie.com/board/"),
    ("financials", "https://www.loungenie.com/financials/"),
    ("press",      "https://www.loungenie.com/press/"),
    ("investors",  "https://www.loungenie.com/investors/"),
]

for label, url in pages:
    html = urllib.request.urlopen(url, timeout=20).read().decode("utf-8", errors="replace")
    # Find hero div
    hero_match = re.search(r'(fin-hero|board-hero|press-hero|inv-hero|\.hero)', html, re.IGNORECASE)
    # Find all inline style blocks relevant to hero
    hero_divs = re.findall(r'<div[^>]+(?:hero)[^>]*>(.*?)</div>', html, re.IGNORECASE | re.DOTALL)
    print(f"\n=== {label.upper()} ===")
    if hero_divs:
        for d in hero_divs[:2]:
            print(repr(d[:400]))
    else:
        print("  No hero div found in rendered HTML")
    
    # Check if style block is present
    style_blocks = re.findall(r'<style[^>]*>(.*?)</style>', html, re.IGNORECASE | re.DOTALL)
    for s in style_blocks:
        if 'hero' in s.lower() and 'color' in s.lower():
            print(f"  STYLE BLOCK: {s[:600]}")
            break
    else:
        print("  No hero style block found")
