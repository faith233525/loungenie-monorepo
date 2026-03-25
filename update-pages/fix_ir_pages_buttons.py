import requests, base64, re

b = 'https://www.loungenie.com/wp-json/wp/v2'
h = {'Authorization': 'Basic ' + base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()}

ROOT = 'https://www.loungenie.com/loungenie'

old_css = (
    '.ir-action-btn { display:inline-flex; align-items:center; justify-content:center; '
    'padding:9px 14px; border-radius:10px; font-size:13px; font-weight:800; '
    'text-decoration:none; border:1px solid #d1e5f9; background:#eef6ff; color:#174168; }\n'
    '.ir-action-btn:hover { background:#0a4f95; border-color:#0a4f95; color:#fff; }'
)
new_css = (
    '.ir-actions .wp-block-button { margin:0; }\n'
    '.ir-actions .wp-block-button__link { display:inline-flex; align-items:center; '
    'justify-content:center; padding:9px 14px; border-radius:10px; font-size:13px; '
    'font-weight:800; text-decoration:none; border:1px solid #d1e5f9; background:#eef6ff; '
    'color:#174168; white-space:nowrap; }\n'
    '.ir-actions .wp-block-button__link:hover { background:#0a4f95; border-color:#0a4f95; color:#fff; }'
)

old_focus = '.ir-toolbar-links a:focus, .ir-action-btn:focus { outline:3px solid #8ed9ff; outline-offset:2px; }'
new_focus = '.ir-toolbar-links a:focus, .ir-actions .wp-block-button__link:focus { outline:3px solid #8ed9ff; outline-offset:2px; }'

old_btn = (
    f'<div class="ir-actions">'
    f'<a class="ir-action-btn" href="{ROOT}/index.php/financials/">Latest Financials</a>'
    f'<a class="ir-action-btn" href="{ROOT}/index.php/press/">Latest Press Release</a>'
    f'</div>'
)
new_btn = (
    '<div class="wp-block-buttons ir-actions">'
    '<!-- wp:button {"className":"ir-action-btn"} -->'
    '<div class="wp-block-button ir-action-btn">'
    f'<a class="wp-block-button__link wp-element-button" href="{ROOT}/index.php/financials/">Latest Financials</a>'
    '</div>\n<!-- /wp:button -->\n\n'
    '<!-- wp:button {"className":"ir-action-btn"} -->'
    '<div class="wp-block-button ir-action-btn">'
    f'<a class="wp-block-button__link wp-element-button" href="{ROOT}/index.php/press/">Latest Press Release</a>'
    '</div>\n<!-- /wp:button --></div>'
)

# Also need to fix the wp:html wrapping block comment around ir-actions
# The live content has: <!-- wp:html -->\n<div class="ir-actions">...</div>\n<!-- /wp:html -->
# Which should become: <!-- wp:buttons {"className":"ir-actions"} -->\n<div class="wp-block-buttons ir-actions">...</div>\n<!-- /wp:buttons -->

pages = [
    (5651, 'board', 'Board and governance profile'),
    (5686, 'financials', 'Financial reporting center'),
    (5716, 'press', 'Press and announcement center'),
]

for pid, name, note_text in pages:
    j = requests.get(f'{b}/pages/{pid}?context=edit', headers=h, timeout=30).json()
    c = j.get('content', {}).get('raw', '')
    print(f'\n=== {name} (pid={pid}) len={len(c)} ===')
    print(f'  has .ir-action-btn CSS: {(".ir-action-btn {" in c)}')

    changed = False

    # CSS fix
    if old_css in c:
        c = c.replace(old_css, new_css)
        print('  css_replaced=True')
        changed = True
    else:
        print('  css_replaced=False (not found)')

    # Focus fix
    if old_focus in c:
        c = c.replace(old_focus, new_focus)
        print('  focus_replaced=True')
        changed = True
    else:
        print('  focus_replaced=False (not found)')

    # Button HTML fix — first check what's actually in the content
    m = re.search(r'<div class="ir-actions">.*?</div>', c, re.S)
    if m:
        actual = m.group(0)
        if actual == old_btn:
            # Wrap the block comment change too
            old_block = f'<!-- wp:html -->\n{old_btn}\n<!-- /wp:html -->'
            new_block = f'<!-- wp:buttons {{"className":"ir-actions"}} -->\n{new_btn}\n<!-- /wp:buttons -->'
            if old_block in c:
                c = c.replace(old_block, new_block)
                print('  btn_block_replaced=True')
                changed = True
            else:
                # Just replace the inner div
                c = c.replace(old_btn, new_btn)
                print('  btn_inner_replaced=True')
                changed = True
        else:
            print(f'  btn_MISMATCH: {repr(actual[:200])}')
    else:
        print('  ir-actions div NOT FOUND in live content')

    # Remove toolbar-note
    note_pat = (
        f'<!-- wp:paragraph {{"className":"ir-toolbar-note"}} -->'
        f'<p class="ir-toolbar-note">{note_text}. Shell is locked; edit content below in the editor.</p>'
        f'<!-- /wp:paragraph -->'
    )
    if note_pat in c:
        c = c.replace(note_pat, '')
        print('  note_removed=True')
        changed = True
    else:
        # Try partial match
        if 'ir-toolbar-note' in c and note_text[:15] in c:
            print('  note: ir-toolbar-note present but exact pattern mismatch')
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
        print('  NO changes made - skipping POST')

print('\nDone.')
