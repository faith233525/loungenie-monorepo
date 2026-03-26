import os
import ftplib
from pathlib import Path

FTP_HOST = os.environ.get('FTP_HOST')
FTP_USER = os.environ.get('FTP_USER')
FTP_PASS = os.environ.get('FTP_PASS')

if not FTP_HOST or not FTP_USER or not FTP_PASS:
    raise SystemExit('FTP_HOST, FTP_USER and FTP_PASS must be set in environment')

# Candidate webroot folders to try
CANDIDATES = ['public_html', 'www', 'htdocs', 'httpdocs', 'public', 'public_html/loungenie', '.']
STAGING_NAME = 'staging_loungenie'

LOCAL_REPO = Path(__file__).resolve().parents[1]
THEMES_DIR = LOCAL_REPO / 'loungenie-monorepo' / 'themes'
PLUGINS_DIR = LOCAL_REPO / 'loungenie-monorepo' / 'plugins'

print(f'Local repo root: {LOCAL_REPO}')
print(f'Themes dir: {THEMES_DIR}')
print(f'Plugins dir: {PLUGINS_DIR}')

ftp = ftplib.FTP(FTP_HOST)
print('Connecting to FTP host...')
ftp.login(FTP_USER, FTP_PASS)
print('Connected. Current directory:', ftp.pwd())

# Find webroot
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

# Create staging path
staging_root = f"{webroot}/{STAGING_NAME}"
print('Creating staging root at:', staging_root)

# Helper to make nested dirs
def ftp_makedirs(path):
    parts = [p for p in path.split('/') if p]
    cur = ''
    for p in parts:
        cur = cur + '/' + p
        try:
            ftp.mkd(cur)
            print('Created:', cur)
        except Exception as e:
            # exists or error
            #print('mkd:', cur, '->', e)
            pass

# Create staging wp-content structure
ftp_makedirs(staging_root + '/wp-content/themes')
ftp_makedirs(staging_root + '/wp-content/plugins')

# Upload helper
def upload_dir(local_dir: Path, remote_dir: str):
    if not local_dir.exists():
        print('Local dir not found, skipping:', local_dir)
        return
    for root, dirs, files in os.walk(local_dir):
        rel = os.path.relpath(root, str(local_dir))
        if rel == '.':
            target_dir = remote_dir
        else:
            target_dir = remote_dir + '/' + rel.replace('\\', '/')
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

# Upload themes
upload_dir(THEMES_DIR, staging_root + '/wp-content/themes')
# Upload plugins
upload_dir(PLUGINS_DIR, staging_root + '/wp-content/plugins')

print('Upload complete. Staging site files are at:', staging_root)
print('Please note this does NOT copy database; to fully stage the site you must create a DB copy and update wp-config.php, or configure WordPress in this staging folder.')

ftp.quit()
