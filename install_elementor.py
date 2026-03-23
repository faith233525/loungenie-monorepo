import requests, base64, json

SITE = 'https://loungenie.com/staging'
BASE = f'{SITE}/wp-json/wp/v2'
AUTH = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
H = {'Authorization': f'Basic {AUTH}', 'Content-Type': 'application/json'}

# Activate Elementor
print('=== Activating Elementor ===')
r = requests.put(f'{BASE}/plugins/elementor/elementor', headers=H, json={"status": "active"}, timeout=30)
print('Status:', r.status_code)
j = r.json()
if r.status_code == 200:
    print(f"  {j.get('name')} => status={j.get('status')}")
else:
    print(j.get('message', r.text[:300]))

# Install Elementor Addons / Starter Templates (Astra)
print()
print('=== Installing Starter Templates (Astra) ===')
r2 = requests.post(f'{BASE}/plugins', headers=H, json={"slug": "astra-sites", "status": "active"}, timeout=90)
print('Status:', r2.status_code)
j2 = r2.json()
if r2.status_code in (200, 201):
    print(f"  {j2.get('name')} => status={j2.get('status')}")
else:
    print(j2.get('message', str(j2)[:300]))

# List all plugins after
print()
print('=== Final plugin list ===')
r3 = requests.get(f'{BASE}/plugins', headers=H, timeout=30)
for p in r3.json():
    print(f"  [{p.get('status','?'):8s}] {p.get('name','?')}")
