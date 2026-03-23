from ftplib import FTP
import os,sys,time
host='ftp.poolsafeinc.com'
user='copilot@loungenie.com'
passwd=os.environ.get('FTP_PASSWORD')
if not passwd:
    print('Set FTP_PASSWORD env var'); sys.exit(1)

REMOTE_BASE=''
LOCAL_BASE=os.path.join('c:',os.sep,'temp','wp_backup_'+time.strftime('%Y%m%d-%H%M%S'))

import csv

# We'll back up plugin main php files and theme style.css (reads inventories)
paths = []
plugins_csv = r'c:\temp\plugins_inventory.csv'
themes_csv = r'c:\temp\themes_inventory.csv'
if os.path.exists(plugins_csv):
    with open(plugins_csv, newline='', encoding='utf8') as f:
        r = csv.DictReader(f)
        for row in r:
            folder = row['folder']
            main = row.get('plugin_file','')
            if main:
                paths.append(('wp-content/plugins/'+folder+'/'+main, os.path.join(LOCAL_BASE,'plugins',folder,main)))
            else:
                # fallback: attempt to fetch plugin folder index.php
                paths.append(('wp-content/plugins/'+folder+'/index.php', os.path.join(LOCAL_BASE,'plugins',folder,'index.php')))
else:
    print('plugins inventory CSV not found:', plugins_csv)

if os.path.exists(themes_csv):
    with open(themes_csv, newline='', encoding='utf8') as f:
        r = csv.DictReader(f)
        for row in r:
            folder = row['folder']
            paths.append(('wp-content/themes/'+folder+'/style.css', os.path.join(LOCAL_BASE,'themes',folder,'style.css')))
else:
    print('themes inventory CSV not found:', themes_csv)

os.makedirs(LOCAL_BASE, exist_ok=True)
print('Local backup dir:', LOCAL_BASE)
ftp=FTP(host,timeout=60)
ftp.login(user,passwd)

def safe_makedirs(p):
    os.makedirs(p, exist_ok=True)

for p in paths:
    if isinstance(p, tuple):
        remote, local = p
        print('DL file:', remote, '->', local)
        localdir = os.path.dirname(local)
        safe_makedirs(localdir)
        try:
            with open(local, 'wb') as fh:
                ftp.retrbinary('RETR ' + remote, fh.write)
            print('Saved', local)
        except Exception as e:
            print('Failed to save', remote, e)
    else:
        print('Skipping unknown path type', p)

ftp.quit()
print('Backup complete')
print('Saved to', LOCAL_BASE)
