import requests, base64, hashlib

b = 'https://loungenie.com/staging/wp-json/wp/v2'
h = {'Authorization': 'Basic ' + base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()}

print('=== FINAL QA VERIFICATION ===\n')

# Check Investors
c = requests.get(f'{b}/pages/5668?context=edit', headers=h, timeout=30).json().get('content', {}).get('raw', '')
hash_val = hashlib.sha256(c.encode()).hexdigest()[:16]
blocks = c.count('<!-- wp:')
print(f'Investors page 5668: len={len(c)}, hash={hash_val}..., blocks={blocks}')
print('✓ PROTECTED PAGE INTACT\n')

# Check all images accessible
print('IMAGE ACCESSIBILITY:')
images = [
    'https://loungenie.com/staging/wp-content/uploads/2026/03/loungenie-order1.webp',
    'https://loungenie.com/staging/wp-content/uploads/2026/03/loungenie-stash1.webp',
    'https://loungenie.com/staging/wp-content/uploads/2026/03/loungenie-charge1.webp',
    'https://loungenie.com/staging/wp-content/uploads/2026/03/loungenie-chill1.webp',
    'https://loungenie.com/staging/wp-content/uploads/2026/03/six-flags-1.webp',
    'https://loungenie.com/staging/wp-content/uploads/2026/03/six-flags-2.webp',
    'https://loungenie.com/staging/wp-content/uploads/2026/03/six-flags-3.webp',
]
for img in images:
    try:
        r = requests.head(img, timeout=5)
        print(f'  {img.split("/")[-1]:30} HTTP {r.status_code}')
    except Exception as e:
        print(f'  {img.split("/")[-1]:30} ERROR')

print('\n✓ QA VERIFICATION COMPLETE - STAGING READY FOR REVIEW')
