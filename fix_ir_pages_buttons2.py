import requests, base64

b = 'https://www.loungenie.com/wp-json/wp/v2'
token = 'admin:i6IM cqLZ vQDC pIRk nKFr g35i'
h = {'Authorization': 'Basic ' + base64.b64encode(token.encode()).decode()}

ROOT = 'https://www.loungenie.com'

# Old raw HTML ir-actions block (same for all 3 pages)
old_btn_block = (
    '<!-- wp:html -->\n'
    f'<div class="ir-actions">'
    f'<a class="ir-action-btn" href="{ROOT}/index.php/financials/">Latest Financials</a>'
    f'<a class="ir-action-btn" href="{ROOT}/index.php/press/">Latest Press Release</a>'
    '</div>\n'
    '<!-- /wp:html -->'
)

# New wp:buttons block (same for all 3 pages)
new_btn_block = (
    '<!-- wp:buttons {"className":"ir-actions"} -->\n'
    '<div class="wp-block-buttons ir-actions">'
    '<!-- wp:button {"className":"ir-action-btn"} -->'
    '<div class="wp-block-button ir-action-btn">'
    f'<a class="wp-block-button__link wp-element-button" href="{ROOT}/index.php/financials/">Latest Financials</a>'
    '</div>\n'
    '<!-- /wp:button -->\n\n'
    '<!-- wp:button {"className":"ir-action-btn"} -->'
    '<div class="wp-block-button ir-action-btn">'
    f'<a class="wp-block-button__link wp-element-button" href="{ROOT}/index.php/press/">Latest Press Release</a>'
    '</div>\n'
    '<!-- /wp:button --></div>\n'
    '<!-- /wp:buttons -->'
)

# Toolbar-note patterns per page
toolbar_notes = {
    5651: '<!-- wp:paragraph {"className":"ir-toolbar-note"} -->\n<p class="ir-toolbar-note">Board and governance profile. </p>\n<!-- /wp:paragraph -->',
    5686: '<!-- wp:paragraph {"className":"ir-toolbar-note"} -->\n<p class="ir-toolbar-note">Financial reporting center. </p>\n<!-- /wp:paragraph -->',
    5716: '<!-- wp:paragraph {"className":"ir-toolbar-note"} -->\n<p class="ir-toolbar-note">Press and announcement center.</p>\n<!-- /wp:paragraph -->',
}

pages = [(5651, 'board'), (5686, 'financials'), (5716, 'press')]

for pid, name in pages:
    j = requests.get(f'{b}/pages/{pid}?context=edit', headers=h, timeout=30).json()
    c = j.get('content', {}).get('raw', '')
    print(f'\n=== {name} (pid={pid}) ===')
    changed = False

    # Fix ir-actions block
    if old_btn_block in c:
        c = c.replace(old_btn_block, new_btn_block)
        print('  btn_block_replaced=True')
        changed = True
    else:
        print('  btn_block_replaced=False (not found)')

    # Remove toolbar-note
    note = toolbar_notes[pid]
    if note in c:
        c = c.replace(note, '')
        print('  note_removed=True')
        changed = True
    else:
        print('  note_removed=False (not found)')

    if changed:
        r = requests.post(
            f'{b}/pages/{pid}',
            headers={**h, 'Content-Type': 'application/json'},
            json={'content': c},
            timeout=30,
        )
        print(f'  POST status={r.status_code}')
    else:
        print('  NO changes — skipping POST')

print('\nDone.')
