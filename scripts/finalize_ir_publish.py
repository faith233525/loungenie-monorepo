#!/usr/bin/env python3
"""
Scan IR drafts, upload missing media via FTPS, replace URLs, and publish pages.
"""
import re
import json
import sys
from pathlib import Path

BASE = "https://loungenie.com/staging"
USER = "copilot"
PASS = "SBlI yPMK 5crY p3Lo FOtF M3Tw"
FTP_HOST = 'ftp.poolsafeinc.com'
FTP_USER = 'copilot21@loungenie.com'
FTP_PASS = 'Quiet4Peg21!'

ART = Path(r"c:/Users/pools/Documents/wordpress-develop/artifacts")
REPORT = ART / 'ir-finalize-report.json'
ART.mkdir(parents=True, exist_ok=True)

try:
    import requests
except Exception:
    print('Please install requests')
    raise

requests.packages.urllib3.disable_warnings()

from ftplib import FTP_TLS, error_perm

# Load created drafts
DRAFTS_FILE = ART / 'ir-drafts-created.json'
if not DRAFTS_FILE.exists():
    print('Drafts file not found:', DRAFTS_FILE)
    sys.exit(1)

drafts = json.loads(DRAFTS_FILE.read_text())

# regex to find src/href to uploads
URL_REGEX = re.compile(r'(https?:)?//[^\s"\']*?/wp-content/uploads/[^"\'>]+|/staging/wp-content/uploads/[^"\'>]+|/wp-content/uploads/[^"\'>]+')

# candidate FTP base paths to try when downloading
FTP_BASES = [
    '/home/pools425/loungenie.com/loungenie',
    '/home/pools425/public_html',
    '/home/pools425',
    '/public_html',
    '/public_html/staging',
    '/loungenie',
    '/loungenie/staging',
    '/',
]

session = requests.Session()
session.auth = (USER, PASS)

report = []

# helper: try HEAD for URL
def url_exists(url):
    try:
        r = session.head(url, allow_redirects=True, verify=False, timeout=15)
        return r.status_code == 200
    except Exception:
        return False

# helper: download from FTP possible paths
def download_via_ftp(remote_path, dest_path):
    try:
        ft = FTP_TLS(FTP_HOST, timeout=30)
        ft.set_debuglevel(0)
        ft.auth()
        ft.prot_p()
        ft.login(FTP_USER, FTP_PASS)
        for base in FTP_BASES:
            cand = remote_path
            # normalize: remove leading /staging or leading domain parts
            if cand.startswith('/staging/'):
                cand_try = cand[len('/staging'):]
            else:
                cand_try = cand
            # try with base appended
            try_paths = [cand_try, base + cand_try]
            for p in try_paths:
                try:
                    dest_path.parent.mkdir(parents=True, exist_ok=True)
                    with open(dest_path, 'wb') as f:
                        ft.retrbinary(f'RETR {p}', f.write)
                    ft.quit()
                    return True
                except error_perm:
                    continue
                except Exception:
                    continue
        try:
            ft.quit()
        except Exception:
            pass
    except Exception:
        pass
    return False

# helper: upload media via REST
def upload_media(file_path, filename):
    url = f"{BASE}/wp-json/wp/v2/media"
    headers = {'Content-Disposition': f'attachment; filename="{filename}"'}
    try:
        with open(file_path, 'rb') as f:
            r = session.post(url, headers=headers, data=f, verify=False)
        r.raise_for_status()
        return r.json()
    except Exception as e:
        print('Upload failed', filename, e)
        return None

for d in drafts:
    sid = d['draft_id']
    print('Processing draft', sid)
    page = session.get(f"{BASE}/wp-json/wp/v2/pages/{sid}", verify=False).json()
    content = page.get('content', {}).get('raw') or page.get('content', {}).get('rendered') or page.get('content')

    found = URL_REGEX.findall(content)
    found = list(set(found))
    assets = []
    for match in found:
        # normalize to full URL
        url = match
        if url.startswith('//'):
            url = 'https:' + url
        elif url.startswith('/staging'):
            url = BASE + url[len('/staging'):]
        elif url.startswith('/wp-content'):
            url = BASE + url
        assets.append(url)

    updated_content = content
    asset_reports = []
    for asset_url in assets:
        ok = url_exists(asset_url)
        if ok:
            asset_reports.append({'url': asset_url, 'status': 'exists'})
            continue
        # try to map path after domain to ftp path
        # extract path part after domain
        path_part = asset_url.split('/', 3)[-1] if '://' in asset_url else asset_url.lstrip('/')
        path_part = '/' + path_part
        # if path contains staging prefix, keep it
        # attempt download
        local_tmp = ART / 'media' / Path(path_part).name
        success = download_via_ftp(path_part, local_tmp)
        if not success:
            asset_reports.append({'url': asset_url, 'status': 'missing', 'action': 'ftp-not-found'})
            continue
        # upload
        uploaded = upload_media(local_tmp, local_tmp.name)
        if not uploaded:
            asset_reports.append({'url': asset_url, 'status': 'upload-failed'})
            continue
        new_url = uploaded.get('source_url') or uploaded.get('guid', {}).get('rendered')
        if new_url:
            updated_content = updated_content.replace(asset_url, new_url)
            asset_reports.append({'url': asset_url, 'status': 'uploaded', 'new_url': new_url, 'media_id': uploaded.get('id')})
        else:
            asset_reports.append({'url': asset_url, 'status': 'upload-no-url'})

    # update page content and publish
    update_payload = {'content': updated_content, 'status': 'publish'}
    r = session.post(f"{BASE}/wp-json/wp/v2/pages/{sid}", json=update_payload, verify=False)
    if r.ok:
        print('Published', sid)
        page_res = r.json()
        report.append({'draft_id': sid, 'result': 'published', 'assets': asset_reports, 'page': page_res})
    else:
        print('Failed to publish', sid, r.status_code, r.text)
        report.append({'draft_id': sid, 'result': 'publish_failed', 'status_code': r.status_code, 'text': r.text, 'assets': asset_reports})

REPORT.write_text(json.dumps(report, indent=2))
print('Report written to', REPORT)
