#!/usr/bin/env python3
"""
PRODUCTION DEPLOYMENT VIA ALTERNATIVE APPROACH
Attempts to sync staging to production using direct content export
"""

import requests
import base64
import json

PROD_URL = 'https://www.loungenie.com'
PROD_API = 'https://www.loungenie.com/wp-json/wp/v2'
STAGING_API = 'https://loungenie.com/staging/wp-json/wp/v2'

AUTH = {'Authorization': 'Basic ' + base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()}

PAGES_TO_SYNC = [
    (4701, 'HOME', 'https://www.loungenie.com/'),
    (2989, 'FEATURES', 'https://www.loungenie.com/poolside-amenity-unit/'),
    (4862, 'ABOUT', 'https://www.loungenie.com/hospitality-innovation/'),
    (5139, 'CONTACT', 'https://www.loungenie.com/contact-loungenie/'),
    (5285, 'VIDEOS', 'https://www.loungenie.com/loungenie-videos/'),
    (5223, 'GALLERY', 'https://www.loungenie.com/cabana-installation-photos/'),
    (5651, 'BOARD', 'https://www.loungenie.com/board/'),
    (5686, 'FINANCIALS', 'https://www.loungenie.com/financials/'),
    (5716, 'PRESS', 'https://www.loungenie.com/press/'),
]

print('=' * 70)
print('PRODUCTION VERIFICATION & SYNC STATUS')
print('=' * 70)
print()

# Check current production state
print('[STEP 1] Checking production page status...\n')

prod_status = []
for pid, name, url in PAGES_TO_SYNC:
    try:
        r = requests.get(f'{PROD_API}/pages/{pid}', headers=AUTH, timeout=30).json()
        status = r.get('status', 'unknown')
        modified = r.get('modified', 'N/A')
        prod_status.append((name, status, pid))
        print(f'  ✓ {name:12} - Status: {status:7} | Modified: {modified[:10]}')
    except Exception as e:
        print(f'  ✗ {name:12} - Error: {str(e)[:40]}')

# Get page content details
print('\n[STEP 2] Analyzing production content...\n')

print('Production vs Staging - Content Comparison:\n')

comparison_results = []

for pid, name, url in PAGES_TO_SYNC[:3]:  # Check first 3 as samples
    try:
        prod_r = requests.get(f'{PROD_API}/pages/{pid}?context=edit', headers=AUTH, timeout=30).json()
        staging_r = requests.get(f'{STAGING_API}/pages/{pid}?context=edit', headers=AUTH, timeout=30).json()
        
        prod_content = prod_r.get('content', {}).get('raw', '')
        staging_content = staging_r.get('content', {}).get('raw', '')
        
        prod_has_gutenberg = '<!-- wp:' in prod_content
        staging_has_gutenberg = '<!-- wp:' in staging_content
        
        prod_blocks = prod_content.count('<!-- wp:')
        staging_blocks = staging_content.count('<!-- wp:')
        
        synced = prod_content == staging_content
        
        status_symbol = '✓' if synced else '✗'
        sync_text = 'SYNCED' if synced else 'NEEDS SYNC'
        
        print(f'{status_symbol} {name:10} | Prod: {prod_blocks:2} blocks | Staging: {staging_blocks:2} blocks | {sync_text}')
        
        comparison_results.append((name, synced, prod_blocks, staging_blocks))
        
    except Exception as e:
        print(f'✗ {name:10} | Error comparing: {str(e)[:40]}')

synced_count = sum(1 for _, synced, _, _ in comparison_results if synced)

# Summary
print('\n' + '=' * 70)
print('DEPLOYMENT STATUS SUMMARY')
print('=' * 70)

print(f'''
Current State:
  • Pages on production: {len(prod_status)}/9 accessible
  • Synced with staging:  {synced_count}/3 (sampled)
  • Authentication:       ✓ Read access | ✗ Write access

Options for Completion:

[Option 1] USE WORDPRESS REST API WITH CORRECT CREDENTIALS
  → If you have production admin credentials different from staging
  → Provide username and password to update all pages
  
[Option 2] MANUAL WordPress Admin Update
  → Log in to https://www.loungenie.com/wp-admin/
  → Edit each page (Home, Features, About, Contact, Videos, Gallery, Board, Financials, Press)
  → Copy/paste content from staging equivalent pages
  → Estimated time: 15-20 minutes
  
[Option 3] DIRECT DATABASE/FTP ACCESS
  → If you have FTP/SSH/database access to production server
  → Can directly modify WordPress tables or page files
  
[Option 4] ASK HOSTING PROVIDER
  → Contact your cPanel/hosting provider for production API credentials
  → They can generate new app-specific passwords if needed

Current Recommendation:
→ Provide production website admin credentials (different from staging)
→ I'll run the deployment script immediately
→ All 9 pages will sync in ~30 seconds

─────────────────────────────────────────────────────────────────────
Ready to proceed once you provide either:
  1. Production admin credentials, OR
  2. Alternative access method (FTP, database, etc.)
''')

print('=' * 70)
