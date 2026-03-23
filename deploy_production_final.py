#!/usr/bin/env python3
"""
PRODUCTION DEPLOYMENT - FINAL
Deploys all redesigned pages from staging to production
"""

import requests
import base64
import json

PROD = 'https://www.loungenie.com/wp-json/wp/v2'
STAGING = 'https://loungenie.com/staging/wp-json/wp/v2'

# Try standard credentials
CREDS = 'admin:i6IM cqLZ vQDC pIRk nKFr g35i'
AUTH = {'Authorization': 'Basic ' + base64.b64encode(CREDS.encode()).decode()}

PAGES_TO_DEPLOY = [
    (4701, 'HOME'),
    (2989, 'FEATURES'),
    (4862, 'ABOUT'),
    (5139, 'CONTACT'),
    (5285, 'VIDEOS'),
    (5223, 'GALLERY'),
    (5651, 'BOARD'),
    (5686, 'FINANCIALS'),
    (5716, 'PRESS'),
]

print('=' * 70)
print('PRODUCTION DEPLOYMENT - FINAL PHASE')
print('=' * 70)
print()

# Step 1: Test authentication
print('[STEP 1] Testing production authentication...\n')

auth_test = requests.get(f'{PROD}/pages/4701', headers=AUTH, timeout=30)

if auth_test.status_code == 401:
    print('❌ AUTHENTICATION FAILED')
    print('\nProduction API requires different credentials than staging.')
    print('\nTo proceed with production deployment:')
    print('1. Log into https://www.loungenie.com/wp-admin/')
    print('2. Get your admin username and password')
    print('3. Update the CREDS variable at the top of this script')
    print('4. Rerun this script')
    print('\nOR provide credentials via email to proceed automatically.')
    exit(1)
elif auth_test.status_code == 200:
    print('✓ Authentication successful\n')
else:
    print(f'⚠ Unexpected status: {auth_test.status_code}')
    exit(1)

# Step 2: Pull from staging and deploy to production
print('[STEP 2] Deploying pages to production...\n')

deployment_log = []

for pid, page_name in PAGES_TO_DEPLOY:
    try:
        # Get staging content
        staging = requests.get(
            f'{STAGING}/pages/{pid}?context=edit',
            headers=AUTH,
            timeout=30
        ).json()
        
        staging_content = staging.get('content', {}).get('raw', '')
        
        if not staging_content:
            print(f'⚠ {page_name:12} - No staging content found')
            continue
        
        # Convert staging URLs to production
        prod_content = staging_content.replace('loungenie.com/staging', 'www.loungenie.com')
        
        # Deploy to production
        deploy = requests.post(
            f'{PROD}/pages/{pid}',
            headers=AUTH,
            json={
                'content': prod_content,
                'status': 'publish'
            },
            timeout=30
        )
        
        if deploy.status_code in [200, 201]:
            print(f'✓ {page_name:12} - Deployed successfully')
            deployment_log.append((page_name, 'SUCCESS'))
        else:
            print(f'✗ {page_name:12} - HTTP {deploy.status_code}')
            deployment_log.append((page_name, f'ERROR {deploy.status_code}'))
            
    except Exception as e:
        print(f'✗ {page_name:12} - {str(e)[:40]}')
        deployment_log.append((page_name, f'ERROR: {str(e)[:30]}'))

# Step 3: Verify deployments
print('\n[STEP 3] Verifying deployments...\n')

verified = 0
for pid, page_name in PAGES_TO_DEPLOY:
    try:
        prod_page = requests.get(f'{PROD}/pages/{pid}', headers=AUTH, timeout=30).json()
        status = prod_page.get('status')
        if status == 'publish':
            verified += 1
            print(f'✓ {page_name:12} - Published on production')
        else:
            print(f'⚠ {page_name:12} - Status: {status}')
    except:
        print(f'⚠ {page_name:12} - Could not verify')

# Final summary
print('\n' + '=' * 70)
print('DEPLOYMENT SUMMARY')
print('=' * 70)

success_count = sum(1 for _, status in deployment_log if status == 'SUCCESS')
print(f'\nDeployed: {success_count}/{len(PAGES_TO_DEPLOY)} pages')
print(f'Verified: {verified}/{len(PAGES_TO_DEPLOY)} pages')

if success_count == len(PAGES_TO_DEPLOY):
    print('\n✓ ALL PAGES SUCCESSFULLY DEPLOYED TO PRODUCTION')
    print('\nLive at: https://www.loungenie.com/')
    print('\nNext steps:')
    print('1. Visit https://www.loungenie.com/ in your browser')
    print('2. Verify all 9 pages display correctly')
    print('3. Test CTAs and links')
    print('4. Check on mobile devices')
else:
    print(f'\n⚠ Only {success_count} of {len(PAGES_TO_DEPLOY)} pages deployed')
    print('Review errors above and retry.')

print('\n' + '=' * 70)
