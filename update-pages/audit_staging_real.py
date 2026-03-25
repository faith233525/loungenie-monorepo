#!/usr/bin/env python3
"""
STAGING SITE AUDIT - Full Content & Visual Review
"""

import requests
import base64

STAGING = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = {'Authorization': 'Basic ' + base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()}

pages = [(4701, 'HOME'), (2989, 'FEATURES'), (4862, 'ABOUT'), (5139, 'CONTACT'), (5285, 'VIDEOS'), (5223, 'GALLERY')]

print('=' * 70)
print('STAGING SITE FULL AUDIT')
print('=' * 70)
print()

for pid, name in pages:
    r = requests.get(f'{STAGING}/pages/{pid}?context=edit', headers=AUTH, timeout=30).json()
    content = r.get('content', {}).get('raw', '')
    
    print(f'\n{name} (Page {pid}):')
    print('-' * 70)
    
    # Check content length
    print(f'  Content length: {len(content)} characters')
    
    # Check block count
    blocks = content.count('<!-- wp:')
    print(f'  Gutenberg blocks: {blocks}')
    
    # Check for images
    images = content.count('wp-content/uploads')
    print(f'  Image references: {images}')
    
    # Check for button/CTA
    buttons = content.count('wp-block-button')
    print(f'  Button blocks: {buttons}')
    
    # Check for text content
    has_heading = '<!-- wp:heading' in content
    has_paragraph = '<!-- wp:paragraph' in content
    has_cover = '<!-- wp:cover' in content
    
    print(f'  Has heading: {has_heading}')
    print(f'  Has paragraph: {has_paragraph}')
    print(f'  Has cover/hero: {has_cover}')
    
    # Show first 500 chars to see structure
    if len(content) > 0:
        first_500 = content[:500].replace('\n', ' ')
        print(f'\n  First 500 chars:')
        print(f'  {first_500}...')
    else:
        print(f'\n  ⚠ WARNING: NO CONTENT FOUND')

print('\n' + '=' * 70)
print('VISUAL/RENDERING ISSUES TO CHECK:')
print('=' * 70)
print('''
1. Images not loading?
   • Check if URLs point to correct staging path
   • Verify loungenie.com/staging/wp-content/uploads/

2. Layout broken?
   • Check if Gutenberg blocks are rendering
   • Hero section should have background image + overlay
   • Content blocks should stack properly

3. Text not visible?
   • Check if text color vs background has contrast
   • Verify text content is in the blocks

4. Buttons/CTAs not working?
   • Check if links point to correct pages
   • Verify button styling is applied

5. Mobile responsive issues?
   • Check if layout breaks on small screens
   • Verify column blocks have mobile settings
''')
