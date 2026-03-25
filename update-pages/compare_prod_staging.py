#!/usr/bin/env python3
import requests, base64

PROD = 'https://www.loungenie.com/wp-json/wp/v2'
STAGING = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = {'Authorization': 'Basic ' + base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()}

pages = [(4701, 'HOME'), (2989, 'FEATURES')]

print('Comparing Staging vs Production content:\n')

for pid, name in pages:
    prod = requests.get(f'{PROD}/pages/{pid}?context=edit', headers=AUTH, timeout=30).json()
    staging = requests.get(f'{STAGING}/pages/{pid}?context=edit', headers=AUTH, timeout=30).json()
    
    prod_content = prod.get('content', {}).get('raw', '')
    staging_content = staging.get('content', {}).get('raw', '')
    
    prod_blocks = prod_content.count('<!-- wp:')
    staging_blocks = staging_content.count('<!-- wp:')
    
    match = prod_content == staging_content
    sync_status = "YES - Already synced!" if match else "NO - needs sync"
    
    print(f'{name}:')
    print(f'  Production: {prod_blocks:2} Gutenberg blocks ({len(prod_content):5} chars)')
    print(f'  Staging:    {staging_blocks:2} Gutenberg blocks ({len(staging_content):5} chars)')
    print(f'  Match: {sync_status}')
    print()
