import requests, re

def try_login(user, pw):
    s = requests.Session()
    s.headers['User-Agent'] = 'Mozilla/5.0'
    s.get('https://www.loungenie.com/wp-login.php', timeout=20)
    r = s.post('https://www.loungenie.com/wp-login.php',
        data={'log': user, 'pwd': pw, 'wp-submit': 'Log In',
              'redirect_to': '/wp-admin/', 'testcookie': '1'},
        timeout=20, allow_redirects=True)
    cookies = list(s.cookies.keys())
    logged_in = any('wordpress_logged_in' in c for c in cookies)
    m = re.search(r'id=["\']login_error["\'][^>]*>(.*?)</div>', r.text, re.S|re.I)
    err = re.sub(r'<[^>]+>', '', m.group(1)).strip() if m else ''
    print(f'{user!r:40s}  logged_in={logged_in}  err={err[:80] or "none"}')
    return logged_in, s

for user, pw in [
    ('copilot',              '2m$RX0kkSykqGFj^25Nl@tPg'),
    ('copilot@loungenie.com','2m$RX0kkSykqGFj^25Nl@tPg'),
    ('fabdi',                '2m$RX0kkSykqGFj^25Nl@tPg'),
    ('fabdi@loungenie.com',  '2m$RX0kkSykqGFj^25Nl@tPg'),
    ('admin',                '2m$RX0kkSykqGFj^25Nl@tPg'),
    ('admin',                'LounGenie21!'),
]:
    try_login(user, pw)
