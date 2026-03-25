#!/usr/bin/env python3
"""
Check what's INSIDE the blocks - see if text content is empty or missing
"""

import requests
import base64
import re

STAGING = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = {'Authorization': 'Basic ' + base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()}

pages = [(2989, 'FEATURES'), (4862, 'ABOUT'), (5139, 'CONTACT')]

for pid, name in pages:
    print(f'\n{"=" * 70}')
    print(f'{name} PAGE CONTENT DUMP')
    print(f'{"=" * 70}')
    
    r = requests.get(f'{STAGING}/pages/{pid}?context=edit', headers=AUTH, timeout=30).json()
    content = r.get('content', {}).get('raw', '')
    
    # Pretty-print the Gutenberg blocks
    print('\nRAW CONTENT:')
    print(content)
    
    # Extract text content
    text_blocks = re.findall(r'<!-- wp:paragraph.*?-->.*?<p>(.*?)</p>', content, re.S)
    heading_blocks = re.findall(r'<!-- wp:heading.*?-->.*?<h[2-6].*?>(.*?)</h[2-6]>', content, re.S)
    
    print(f'\n\nTEXT CONTENT FOUND:')
    print(f'  Headings: {heading_blocks}')
    print(f'  Paragraphs: {text_blocks}')
