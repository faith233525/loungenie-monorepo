import urllib.request

pages = [
    "https://loungenie.com/Loungenie%e2%84%a2/",
    "https://loungenie.com/Loungenie%e2%84%a2/index.php/poolside-amenity-unit/",
    "https://loungenie.com/Loungenie%e2%84%a2/index.php/contact-loungenie/",
]
for url in pages:
    html = urllib.request.urlopen(urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0", "Accept-Encoding": "identity"}), timeout=30).read().decode("utf-8", "replace")
    print("\n===", url)
    print("has lgx-shell:", "lgx-shell" in html)
    print("has lgx-card:", "lgx-card" in html)
    print("has modern hero phrase:", "modern poolside experience" in html.lower() or "modern hospitality" in html.lower() or "modern smart" in html.lower())
    print("has old split columns shell:", '<div class="wp-block-columns alignwide"' in html and 'wp-block-post-title' in html)
