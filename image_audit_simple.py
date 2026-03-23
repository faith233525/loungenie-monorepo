from ftplib import FTP
import os,csv,sys
host='ftp.poolsafeinc.com'
user='copilot@loungenie.com'
passwd=os.environ.get('FTP_PASSWORD')
if not passwd:
    print('set FTP_PASSWORD'); sys.exit(1)
start='wp-content/uploads'
threshold_kb=50
out='c:\\\temp\\image_audit_simple.csv'
ftp=FTP(host,timeout=30)
ftp.login(user,passwd)

rows=[]

def walk(path):
    try:
        ftp.cwd(path)
    except Exception as e:
        return
    try:
        items=ftp.nlst()
    except Exception:
        return
    files=[i for i in items if '.' in i]
    for f in files:
        full=path.rstrip('/') + '/' + f
        try:
            s=ftp.size(f)
        except Exception:
            s=None
        rows.append((full, s or 0))
    dirs=[i for i in items if i not in ('.','..') and '.' not in i]
    for d in dirs:
        walk(path+'/'+d)
    ftp.cwd('..')

walk(start)
rows.sort(key=lambda x: x[1], reverse=True)
os.makedirs(os.path.dirname(out), exist_ok=True)
with open(out,'w',newline='',encoding='utf8') as f:
    w=csv.writer(f)
    w.writerow(['path','size_bytes'])
    for p,s in rows:
        if s >= threshold_kb*1024:
            w.writerow([p,s])
print('Wrote',out)
ftp.quit()
