from ftplib import FTP
import os
import csv
import re
host='ftp.poolsafeinc.com'
user='copilot@loungenie.com'
passwd=os.environ.get('FTP_PASSWORD')
base='wp-content/plugins'
ftp=FTP(host,timeout=30)
ftp.login(user,passwd)
ftp.cwd(base)
items=[i for i in ftp.nlst() if i not in ('.','..')]
rows=[]
for it in items:
    row={'folder':it,'plugin_file':'','plugin_name':'','version':''}
    try:
        ftp.cwd(it)
    except Exception:
        # not a folder or not accessible
        rows.append(row)
        continue
    files=ftp.nlst()
    php_files=[f for f in files if f.lower().endswith('.php')]
    if php_files:
        fname=php_files[0]
        row['plugin_file']=fname
        try:
            # retrieve first few KB to parse header
            data=[]
            def cb(x):
                data.append(x.decode('utf8',errors='ignore'))
                if sum(len(s) for s in data)>8192:
                    raise StopIteration
            try:
                ftp.retrbinary('RETR '+fname, cb)
            except StopIteration:
                pass
            txt=''.join(data)
            m_name=re.search(r"^\s*\*?\s*Plugin\s+Name:\s*(.+)$", txt, re.I|re.M)
            m_ver=re.search(r"^\s*\*?\s*Version:\s*(.+)$", txt, re.I|re.M)
            if m_name:
                row['plugin_name']=m_name.group(1).strip()
            if m_ver:
                row['version']=m_ver.group(1).strip()
        except Exception:
            pass
    # go back
    ftp.cwd('..')
    rows.append(row)

out='c:\\temp\\plugins_inventory.csv'
os.makedirs(os.path.dirname(out),exist_ok=True)
with open(out,'w',newline='',encoding='utf8') as f:
    w=csv.DictWriter(f,fieldnames=['folder','plugin_file','plugin_name','version'])
    w.writeheader()
    for r in rows:
        w.writerow(r)
print('Wrote',out)
ftp.quit()
