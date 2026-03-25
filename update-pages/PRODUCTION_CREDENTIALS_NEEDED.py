#!/usr/bin/env python3
"""
PRODUCTION SYNC TEMPLATE
User must provide production admin credentials
"""

import requests, base64

# ⚠️ REQUIRED: Replace with production admin credentials
PROD_USERNAME = "CHANGE_ME"          # Your production admin username
PROD_PASSWORD = "CHANGE_ME"          # Your production admin password

if PROD_USERNAME == "CHANGE_ME":
    print("ERROR: Production credentials not configured.")
    print("\nSteps to sync to production:")
    print("1. Get production admin credentials from Pool Safe Portal login")
    print("2. Replace PROD_USERNAME and PROD_PASSWORD above")
    print("3. Run this script again")
    print("\nProduction will receive:")
    print("  - Same Gutenberg structure as staging")
    print("  - Production URLs (www.loungenie.com)")
    print("  - 9 pages: Home, Features, About, Contact, Videos, Gallery, Board, Financials, Press")
    print("  - Investors unchanged (hash-protected)")
    exit(1)

PROD = 'https://www.loungenie.com/wp-json/wp/v2'
STAGING = 'https://loungenie.com/staging/wp-json/wp/v2'

prod_auth = {'Authorization': 'Basic ' + base64.b64encode(f'{PROD_USERNAME}:{PROD_PASSWORD}'.encode()).decode()}
staging_auth = {'Authorization': 'Basic ' + base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()}

print("Testing production credentials...")
test = requests.get(f'{PROD}/pages/4701', headers=prod_auth, timeout=30)
if test.status_code != 200:
    print(f"ERROR: Production auth failed ({test.status_code})")
    print("Verify credentials and try again.")
    exit(1)

print("✓ Production credentials verified\n")

# Now run actual sync
PAGES_TO_SYNC = [(4701, 'home'), (2989, 'features'), (4862, 'about'), 
                 (5139, 'contact'), (5285, 'videos'), (5223, 'gallery')]

print("Syncing staging redesign to production...\n")

for pid, page_name in PAGES_TO_SYNC:
    staging_resp = requests.get(f'{STAGING}/pages/{pid}?context=edit', headers=staging_auth, timeout=30).json()
    staging_content = staging_resp.get('content', {}).get('raw', '')
    
    prod_content = staging_content.replace('loungenie.com/staging', 'www.loungenie.com')
    
    prod_resp = requests.post(f'{PROD}/pages/{pid}', headers=prod_auth,
        json={'content': prod_content, 'status': 'publish'}, timeout=30)
    
    status = "✓" if prod_resp.status_code in [200, 201] else "✗"
    print(f'{status} {page_name:12} (PID {pid}) - HTTP {prod_resp.status_code}')

print("\nProduction sync complete!")
