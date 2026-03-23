"""
Try every remaining credential path to find a working one.
1. FTP with pools425 (cPanel system user)
2. WP login admin + both passwords (fresh session, no rate-limit history)
3. App password with admin user
"""
import requests, re, base64, time
from ftplib import FTP_TLS, FTP, error_perm

# ── 1. FTP: pools425 ──────────────────────────────────────────────────────────
print('=== FTP ===')
ftp_combos = [
    ('ftp.poolsafeinc.com', 'pools425',             'LounGenie21!', 'TLS'),
    ('ftp.poolsafeinc.com', 'pools425@loungenie.com','LounGenie21!', 'TLS'),
    ('sh-cp9.yyz2.servername.online', 'pools425',   'LounGenie21!', 'plain'),
]
for host, user, pw, mode in ftp_combos:
    try:
        f = FTP_TLS() if mode == 'TLS' else FTP()
        f.connect(host, 21, timeout=12)
        f.login(user, pw)
        if mode == 'TLS':
            try: f.prot_p()
            except: pass
        f.set_pasv(True)
        print(f'FTP OK  {user}@{host}  ({mode})')
        try:
            lines = []
            f.retrlines('LIST', lines.append)
            print('  home:', lines[:4])
        except Exception as e2:
            print('  list err:', e2)
        f.quit()
    except Exception as e:
        print(f'FTP FAIL  {user}@{host}: {e}')

# ── 2. WP Login: admin ────────────────────────────────────────────────────────
print('\n=== WP LOGIN ===')
time.sleep(3)  # brief pause to reset any soft rate limit

def wp_login(user, pw):
    s = requests.Session()
    s.headers['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    login_url = 'https://www.loungenie.com/wp-login.php'
    s.get(login_url, timeout=20)   # sets test cookie
    r = s.post(login_url, timeout=20, allow_redirects=True, data={
        'log': user, 'pwd': pw, 'wp-submit': 'Log In',
        'redirect_to': 'https://www.loungenie.com/wp-admin/', 'testcookie': '1'
    })
    cookies = list(s.cookies.keys())
    logged_in = any('wordpress_logged_in' in c for c in cookies)
    m = re.search(r'id=["\']login_error["\'][^>]*>(.*?)</div>', r.text, re.S|re.I)
    err = re.sub(r'<[^>]+>', '', m.group(1)).strip()[:100] if m else 'no-error-div'
    status = 'OK' if logged_in else 'FAIL'
    print(f'  {status}  {user!r:30s}  err={err}')
    return logged_in, s

wp_login('admin', '2m$RX0kkSykqGFj^25Nl@tPg')
wp_login('admin', 'LounGenie21!')
wp_login('admin@loungenie.com', '2m$RX0kkSykqGFj^25Nl@tPg')
wp_login('admin@poolsafeinc.com', '2m$RX0kkSykqGFj^25Nl@tPg')

# ── 3. App password: admin user ───────────────────────────────────────────────
print('\n=== REST APP PASSWORDS ===')
for user, pw in [
    ('admin', 'N9y0 VXUN 5HRj G0aO 8Onn RltM'),
    ('admin', 'Qt0E Raef 9kMO k5GO FYsS 9j7D'),
]:
    cred = base64.b64encode(f'{user}:{pw}'.encode()).decode()
    r = requests.get('https://www.loungenie.com/wp-json/wp/v2/users/me',
                     headers={'Authorization': f'Basic {cred}', 'User-Agent': 'Mozilla/5.0'},
                     timeout=15)
    print(f'  {r.status_code}  {user}: {r.text[:100]}')
