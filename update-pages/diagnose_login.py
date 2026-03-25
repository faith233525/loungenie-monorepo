"""Diagnose WP login and test all available credentials."""
import requests, re, base64

session = requests.Session()
session.headers['User-Agent'] = 'Mozilla/5.0'

# GET login page
r = session.get('https://www.loungenie.com/wp-login.php', timeout=20)
print('GET status:', r.status_code, 'URL:', r.url)
print('Cookies after GET:', dict(session.cookies))

# Extract hidden form fields
hidden = re.findall(r'<input[^>]+type=["\']hidden["\'][^>]*>', r.text, re.I)
for h in hidden:
    print(' hidden:', h[:140])

form_data = {}
for h in hidden:
    name_m  = re.search(r'name=["\']([^"\']+)', h)
    value_m = re.search(r'value=["\']([^"\']*)', h)
    if name_m:
        form_data[name_m.group(1)] = value_m.group(1) if value_m else ''

print('Form data:', form_data)

# POST login
form_data.update({'log': 'copilot', 'pwd': '2m$RX0kkSykqGFj^25Nl@tPg',
                  'wp-submit': 'Log In', 'testcookie': '1',
                  'redirect_to': 'https://www.loungenie.com/wp-admin/'})
r2 = session.post('https://www.loungenie.com/wp-login.php',
                  data=form_data, timeout=20, allow_redirects=True)
print('\nPOST status:', r2.status_code, 'URL:', r2.url)
print('Cookies after POST:', dict(session.cookies))
print('Body snippet:', r2.text[:400])

# Check login error message
err = re.search(r'class="login-error[^"]*"[^>]*>(.*?)</div>', r2.text, re.S)
if err:
    print('WP error:', re.sub(r'<[^>]+>', '', err.group(1)).strip())
