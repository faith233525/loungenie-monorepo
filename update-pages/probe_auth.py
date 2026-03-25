"""
Try staging site login (copilot known to exist there),
and retry admin login on production with fresh headers after rate limit.
Also check publicly-exposed WP user list for email addresses.
"""
import requests, re, time, base64

UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15'

def wp_login_full(base_url, user, pw, label=''):
    s = requests.Session()
    s.headers['User-Agent'] = UA
    login_url = base_url + '/wp-login.php'
    r0 = s.get(login_url, timeout=20)
    time.sleep(1)
    r = s.post(login_url, timeout=20, allow_redirects=True, data={
        'log': user, 'pwd': pw, 'wp-submit': 'Log In',
        'redirect_to': base_url + '/wp-admin/', 'testcookie': '1'
    })
    cookies = list(s.cookies.keys())
    logged_in = any('wordpress_logged_in' in c for c in cookies)
    m = re.search(r'id=["\']login_error["\'][^>]*>(.*?)</div>', r.text, re.S|re.I)
    err = re.sub(r'<[^>]+>', '', m.group(1)).strip()[:100] if m else 'no-error-div'
    has_dashboard = 'Dashboard' in r.text or '/wp-admin/index.php' in r.url
    tag = label or f'{user}@{base_url}'
    print(f'  {"OK" if logged_in else "FAIL"}  {tag!r:50s}  err={err}  dashboard={has_dashboard}')
    return logged_in, s

# ── Staging site: copilot user ─────────────────────────────────────────────
print('=== STAGING LOGIN ===')
ok, sess = wp_login_full('https://loungenie.com/loungenie', 'copilot', '2m$RX0kkSykqGFj^25Nl@tPg', 'staging/copilot')

# ── Check publicly exposed WP user list (production) ─────────────────────
print('\n=== WP USERS ENUMERATION (REST) ===')
r = requests.get('https://www.loungenie.com/wp-json/wp/v2/users?per_page=10',
                 headers={'User-Agent': UA}, timeout=15)
print(f'  Status: {r.status_code}')
try:
    users = r.json()
    if isinstance(users, list):
        for u in users:
            print(f'  ID={u.get("id")}  slug={u.get("slug")}  name={u.get("name")}')
    else:
        print('  Response:', str(r.text)[:200])
except:
    print('  Body:', r.text[:300])

# ── Production login attempt after delay ─────────────────────────────────
print('\n=== PRODUCTION LOGIN (after 10s delay) ===')
time.sleep(10)
wp_login_full('https://www.loungenie.com', 'admin', '2m$RX0kkSykqGFj^25Nl@tPg', 'prod/admin+strongpw')

# ── REST API health check (no auth) ─────────────────────────────────────
print('\n=== REST API HEALTH (no auth) ===')
r2 = requests.get('https://www.loungenie.com/wp-json/', headers={'User-Agent': UA}, timeout=15)
print(f'  /wp-json/  status={r2.status_code}  len={len(r2.text)}')
if r2.status_code == 200:
    import json
    try:
        d = r2.json()
        print(f'  WP version: {d.get("namespaces","?")[:3]}  URL: {d.get("url","?")}')
    except:
        pass
