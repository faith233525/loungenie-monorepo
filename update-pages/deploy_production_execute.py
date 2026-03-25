#!/usr/bin/env python3
"""
PRODUCTION DEPLOYMENT - FINAL EXECUTION
Deploy all 9 redesigned pages to production with provided credentials
"""

import requests
import base64
import json

PROD_API = 'https://www.loungenie.com/wp-json/wp/v2'
STAGING_API = 'https://loungenie.com/staging/wp-json/wp/v2'

# Production credentials provided by user
PROD_USERNAME = 'Copilot'
PROD_PASSWORD = 'PoolSafeInc21'

prod_auth = {'Authorization': 'Basic ' + base64.b64encode(f'{PROD_USERNAME}:{PROD_PASSWORD}'.encode()).decode()}
staging_auth = {'Authorization': 'Basic ' + base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()}

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
print('PRODUCTION DEPLOYMENT - FINAL EXECUTION')
print('=' * 70)
print()

# Step 1: Verify production credentials
print('[STEP 1] Verifying production credentials...\n')

auth_test = requests.get(f'{PROD_API}/pages/4701', headers=prod_auth, timeout=30)

if auth_test.status_code == 401:
    print('❌ AUTHENTICATION FAILED')
    print('The provided credentials did not work.')
    print('Status code: 401 Unauthorized')
    exit(1)
elif auth_test.status_code == 200:
    print('✓ Production credentials verified\n')
else:
    print(f'⚠ Unexpected status: {auth_test.status_code}')
    exit(1)

# Step 2: Deploy pages
print('[STEP 2] Deploying 9 pages to production...\n')

deployment_results = []
successful_deploys = 0

for pid, page_name in PAGES_TO_DEPLOY:
    try:
        # Fetch staging content
        staging_resp = requests.get(
            f'{STAGING_API}/pages/{pid}?context=edit',
            headers=staging_auth,
            timeout=30
        ).json()
        
        staging_content = staging_resp.get('content', {}).get('raw', '')
        
        if not staging_content:
            print(f'⚠ {page_name:12} - No staging content found')
            deployment_results.append((page_name, 'SKIPPED', 'No content'))
            continue
        
        # Convert staging URLs to production URLs
        prod_content = staging_content.replace('loungenie.com/staging', 'www.loungenie.com')
        
        # Deploy to production
        deploy_resp = requests.post(
            f'{PROD_API}/pages/{pid}',
            headers=prod_auth,
            json={
                'content': prod_content,
                'status': 'publish'
            },
            timeout=30
        )
        
        if deploy_resp.status_code in [200, 201]:
            print(f'✓ {page_name:12} - Deployed successfully')
            deployment_results.append((page_name, 'SUCCESS', deploy_resp.status_code))
            successful_deploys += 1
        else:
            print(f'✗ {page_name:12} - HTTP {deploy_resp.status_code}')
            deployment_results.append((page_name, 'FAILED', deploy_resp.status_code))
            
    except Exception as e:
        print(f'✗ {page_name:12} - Error: {str(e)[:50]}')
        deployment_results.append((page_name, 'ERROR', str(e)[:50]))

# Step 3: Verify deployments
print('\n[STEP 3] Verifying deployments...\n')

verified_count = 0
content_blocks_deployed = {}

for pid, page_name in PAGES_TO_DEPLOY:
    try:
        # Verify on production
        prod_page = requests.get(
            f'{PROD_API}/pages/{pid}?context=edit',
            headers=prod_auth,
            timeout=30
        ).json()
        
        prod_content = prod_page.get('content', {}).get('raw', '')
        blocks = prod_content.count('<!-- wp:')
        status = prod_page.get('status')
        
        if blocks > 0 and status == 'publish':
            verified_count += 1
            content_blocks_deployed[page_name] = blocks
            print(f'✓ {page_name:12} - {blocks:2} Gutenberg blocks | Status: {status}')
        else:
            print(f'⚠ {page_name:12} - {blocks:2} blocks | Status: {status}')
            
    except Exception as e:
        print(f'✗ {page_name:12} - Verification error: {str(e)[:40]}')

# Final Summary
print('\n' + '=' * 70)
print('DEPLOYMENT SUMMARY')
print('=' * 70)

print(f'\nDeployment Results:')
print(f'  • Successfully deployed: {successful_deploys}/{len(PAGES_TO_DEPLOY)} pages')
print(f'  • Verified on production: {verified_count}/{len(PAGES_TO_DEPLOY)} pages')

if content_blocks_deployed:
    print(f'\nContent Blocks Deployed:')
    for name, blocks in sorted(content_blocks_deployed.items()):
        print(f'  • {name:12} - {blocks:2} Gutenberg blocks')

print(f'\nDeployment Details:')
for page_name, status, detail in deployment_results:
    print(f'  {page_name:12} - {status:8} ({detail})')

print('\n' + '=' * 70)

if successful_deploys == len(PAGES_TO_DEPLOY):
    print('✓ ALL 9 PAGES SUCCESSFULLY DEPLOYED TO PRODUCTION')
    print('\n📍 LIVE NOW AT: https://www.loungenie.com/')
    print('\nQA Checklist:')
    print('  1. Visit https://www.loungenie.com/ (Home page)')
    print('  2. Verify hero image and logo grid visible')
    print('  3. Click "Features" link → verify tier breakdown')
    print('  4. Click "About" → verify brand story')
    print('  5. Click "Contact" → verify contact form')
    print('  6. Click "Gallery" → verify 9 images loading')
    print('  7. Check on mobile device for responsive layout')
    print('  8. Test CTAs - verify they link to correct pages')
    print('\nNext: Review production site at https://www.loungenie.com/')
else:
    print(f'⚠ Only {successful_deploys} of {len(PAGES_TO_DEPLOY)} pages deployed')
    print('\nPlease review errors above and contact support if needed.')

print('=' * 70)
