"""
wp_cookie_auth.py  – Login with real WP password, get nonce, run all media ops.
"""
import requests, re, sys, json, csv, os, time

session = requests.Session()
session.headers['User-Agent'] = 'Mozilla/5.0'

LOGIN_URL = 'https://www.loungenie.com/wp-login.php'
BASE      = 'https://www.loungenie.com/wp-json/wp/v2/media'

USER = 'copilot'
PASS = '2m$RX0kkSykqGFj^25Nl@tPg'

# ── 1. Login ──────────────────────────────────────────────────────────────────
r = session.get(LOGIN_URL, timeout=20)
data = {
    'log': USER,
    'pwd': PASS,
    'wp-submit': 'Log In',
    'redirect_to': 'https://www.loungenie.com/wp-admin/',
    'testcookie': '1',
}
r2 = session.post(LOGIN_URL, data=data, timeout=20, allow_redirects=True)
cookies = list(session.cookies.keys())
logged_in = any('wordpress_logged_in' in c for c in cookies)
print(f'Login: {r2.status_code}  URL={r2.url}  logged_in={logged_in}')
print('Cookies:', cookies)

if not logged_in:
    print('ERROR: Login failed — check password or site status')
    sys.exit(1)

# ── 2. Get REST nonce ─────────────────────────────────────────────────────────
r3 = session.post('https://www.loungenie.com/wp-admin/admin-ajax.php',
                  data={'action': 'rest-nonce'}, timeout=15)
nonce = r3.text.strip()
print(f'Nonce ajax: {r3.status_code}  nonce={repr(nonce)}')

if not nonce or not re.fullmatch(r'[0-9a-f]+', nonce):
    # Fallback: scrape from /wp-admin/
    r4 = session.get('https://www.loungenie.com/wp-admin/', timeout=20)
    m = re.search(r'"nonce":"([a-f0-9]+)"', r4.text)
    if not m:
        m = re.search(r'wpApiSettings.*?"nonce":"([a-f0-9]+)"', r4.text)
    nonce = m.group(1) if m else ''
    print('Nonce from wp-admin page:', nonce)

if not nonce:
    print('ERROR: Could not find REST nonce')
    sys.exit(1)

HEADERS = {'X-WP-Nonce': nonce, 'Content-Type': 'application/json'}

# Verify we can hit the API
r5 = session.get('https://www.loungenie.com/wp-json/wp/v2/users/me',
                 headers=HEADERS, timeout=15)
print(f'REST users/me: {r5.status_code}  {r5.text[:120]}')
if r5.status_code != 200:
    print('ERROR: REST auth check failed')
    sys.exit(1)

print('\n=== Auth OK — starting operations ===\n')

# ── 3. Deletes ────────────────────────────────────────────────────────────────
DIR = os.path.dirname(os.path.abspath(__file__))

delete_ok = delete_fail = 0
with open(os.path.join(DIR, 'media_duplicate_delete_plan.csv'), newline='', encoding='utf-8') as f:
    for row in csv.DictReader(f):
        att_id = row['delete_id'].strip()
        url = f'{BASE}/{att_id}?force=true'
        try:
            r = session.delete(url, headers=HEADERS, timeout=30)
            if r.status_code in (200, 201):
                delete_ok += 1
                print(f'  DELETED  {att_id}')
            else:
                delete_fail += 1
                print(f'  FAIL({r.status_code}) delete {att_id}: {r.text[:80]}')
        except Exception as e:
            delete_fail += 1
            print(f'  ERR delete {att_id}: {e}')

print(f'\nDeletes: OK={delete_ok}  FAIL={delete_fail}')

# ── 4. Metadata updates ───────────────────────────────────────────────────────
update_ok = update_fail = 0
with open(os.path.join(DIR, 'media_update_plan.csv'), newline='', encoding='utf-8') as f:
    for row in csv.DictReader(f):
        att_id  = row['id'].strip()
        new_alt = row.get('new_alt', '').strip()
        new_title = row.get('new_title', '').strip()
        payload = {}
        if new_alt:
            payload['alt_text'] = new_alt
        if new_title:
            payload['title'] = new_title
        if not payload:
            continue
        url = f'{BASE}/{att_id}'
        try:
            r = session.post(url, headers=HEADERS, json=payload, timeout=30)
            if r.status_code in (200, 201):
                update_ok += 1
                print(f'  UPDATED  {att_id}')
            else:
                update_fail += 1
                print(f'  FAIL({r.status_code}) update {att_id}: {r.text[:80]}')
        except Exception as e:
            update_fail += 1
            print(f'  ERR update {att_id}: {e}')

print(f'\nUpdates:  OK={update_ok}  FAIL={update_fail}')
print(f'\nDone. Total: {delete_ok} deleted, {update_ok} updated.')
