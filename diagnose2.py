import urllib.request, base64
AUTH = base64.b64encode(b"copilot:7NiL OZ17 ApP3 tIgF 6zlT ug7u").decode()
req = urllib.request.Request(
    "https://loungenie.com/Loungenie%E2%84%A2/",
    headers={"Authorization": "Basic " + AUTH, "User-Agent": "Mozilla/5.0"}
)
r = urllib.request.urlopen(req, timeout=20)
html = r.read().decode("utf-8", errors="ignore")
r.close()

# Print the hero section
i4 = html.find('class="lg"')
print("=== FIRST 3000 chars of .lg content ===")
print(html[i4:i4+3000])

# What Astra adds to entry-content specifically
print("\n=== Entry content wrapper search ===")
for tag in ['class="entry-content"', 'class="ast-article-single"', 'class="site-content"']:
    idx = html.find(tag)
    if idx >= 0:
        print(f"\nFOUND [{tag}] at {idx}:")
        print(html[idx:idx+200])

# Check nav menu items
print("\n=== NAV MENU ===")
nav_idx = html.find('class="main-navigation"')
if nav_idx < 0:
    nav_idx = html.find('class="ast-flex main-header-menu')
print(html[nav_idx:nav_idx+1500] if nav_idx >= 0 else "NAV NOT FOUND")
