import urllib.request, base64
AUTH = base64.b64encode(b"copilot:7NiL OZ17 ApP3 tIgF 6zlT ug7u").decode()
req = urllib.request.Request(
    "https://loungenie.com/Loungenie%E2%84%A2/",
    headers={"Authorization": "Basic " + AUTH, "User-Agent": "Mozilla/5.0"}
)
r = urllib.request.urlopen(req, timeout=20)
html = r.read().decode("utf-8", errors="ignore")
r.close()

i = html.find('class="lg"')
print("LG found at:", i)
print("200 chars before LG:", html[max(0,i-200):i])
lg = html[i:i+60000]
print("\n=== .lg first 1500 chars ===")
print(repr(lg[:1500]))
print("\n<br> count in lg:", lg.count("<br"))
hero = lg.find('class="hero"')
print("\nHERO:", lg[hero:hero+600])
logos = lg.find("logo-strip")
print("\nLOGO STRIP:", lg[logos:logos+400])
grid2 = lg.find('class="grid-2"')
print("\nGRID-2:", lg[grid2:grid2+400])
