import os
import ftplib
from pathlib import Path

FTP_HOST = os.environ.get('FTP_HOST') or 'ftp.loungenie.com'
FTP_USER = os.environ.get('FTP_USER') or 'copilot@loungenie.com'
FTP_PASS = os.environ.get('FTP_PASS') or 'LounGenie21!'

if not FTP_HOST or not FTP_USER or not FTP_PASS:
    raise SystemExit('FTP_HOST, FTP_USER and FTP_PASS must be set in environment')

LOCAL_REPO = Path(__file__).resolve().parents[1]
PORTAL_DIR = LOCAL_REPO / 'loungenie-monorepo' / 'portal'
if not PORTAL_DIR.exists():
    raise SystemExit(f'Portal directory not found: {PORTAL_DIR}')

print(f'Local repo root: {LOCAL_REPO}')
print(f'Portal dir: {PORTAL_DIR}')

ftp = ftplib.FTP(FTP_HOST)
print('Connecting to FTP host...')
ftp.login(FTP_USER, FTP_PASS)
print('Connected. Current directory:', ftp.pwd())

# Determine webroot candidates
CANDIDATES = ['public_html', 'www', 'htdocs', 'httpdocs', 'public', '.']
webroot = None
for candidate in CANDIDATES:
    try:
        ftp.cwd(candidate)
        webroot = ftp.pwd()
        print('Found webroot candidate:', candidate, '->', webroot)
        break
    except Exception:
        pass

if webroot is None:
    webroot = ftp.pwd()
    print('Using current FTP directory as webroot:', webroot)

remote_plugins = webroot + '/wp-content/plugins'
print('Ensuring remote plugins path:', remote_plugins)

# Helper to make nested dirs
def ftp_makedirs(path):
    parts = [p for p in path.split('/') if p]
    cur = ''
    for p in parts:
        cur = cur + '/' + p
        try:
            ftp.mkd(cur)
            print('Created:', cur)
        except Exception:
            pass

ftp_makedirs(remote_plugins)

# Upload portal directory
for root, dirs, files in os.walk(PORTAL_DIR):
    rel = os.path.relpath(root, str(PORTAL_DIR))
    if rel == '.':
        target_dir = remote_plugins + '/loungenie-portal'
    else:
        target_dir = remote_plugins + '/loungenie-portal/' + rel.replace('\\','/')
    ftp_makedirs(target_dir)
    for f in files:
        local_path = Path(root) / f
        remote_path = target_dir + '/' + f
        print(f'Uploading {local_path} -> {remote_path}')
        with open(local_path, 'rb') as fh:
            try:
                ftp.storbinary('STOR ' + remote_path, fh)
            except Exception as e:
                print('Upload failed for', local_path, e)

print('Upload complete. Portal plugin uploaded to:', remote_plugins + '/loungenie-portal')
ftp.quit()
