from ftplib import FTP
import os,re,csv
host='ftp.poolsafeinc.com'
user='copilot@loungenie.com'
passwd=os.environ.get('FTP_PASSWORD')
base='wp-content/themes'
ftp=FTP(host,timeout=30)
ftp.login(user,passwd)
try:
    ftp.cwd(base)
except Exception as e:
    print('CWD failed:',e)
    ftp.quit(); raise
items=[i for i in ftp.nlst() if i not in ('.','..')]
rows=[]
for it in items:
    row={'folder':it,'theme_file':'','theme_name':'','version':''}
    try:
        ftp.cwd(it)
    except Exception:
        rows.append(row); continue
    files=ftp.nlst()
    if 'style.css' in files:
        row['theme_file']='style.css'
        data=[]
        def cb(x): data.append(x.decode('utf8',errors='ignore'))
        try:
            ftp.retrbinary('RETR style.css',cb)
            txt=''.join(data)
            m_name=re.search(r'^\s*Theme\s+Name:\s*(.+)$',txt, re.I|re.M)
            m_ver=re.search(r'^\s*Version:\s*(.+)$',txt, re.I|re.M)
            if m_name: row['theme_name']=m_name.group(1).strip()
            if m_ver: row['version']=m_ver.group(1).strip()
        except Exception:
            pass
    ftp.cwd('..')
    rows.append(row)
out='c:\\temp\\themes_inventory.csv'
os.makedirs(os.path.dirname(out),exist_ok=True)
with open(out,'w',newline='',encoding='utf8') as f:
    w=csv.DictWriter(f,fieldnames=['folder','theme_file','theme_name','version'])
    w.writeheader()
    for r in rows: w.writerow(r)
print('Wrote',out)
ftp.quit()
