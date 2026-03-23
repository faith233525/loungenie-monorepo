import urllib.request

urls = [
    "https://loungenie.com/Loungenie%e2%84%a2/",
    "https://loungenie.com/Loungenie%e2%84%a2/index.php/contact-loungenie/",
    "https://loungenie.com/Loungenie%e2%84%a2/index.php/investors/",
]
for url in urls:
    html = urllib.request.urlopen(urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0", "Accept-Encoding": "identity"}), timeout=30).read().decode("utf-8", "replace")
    print("\n===", url)
    print("has info@poolsafe.com:", "info@poolsafe.com" in html)
    print("has info@poolsafeinc.com:", "info@poolsafeinc.com" in html)
