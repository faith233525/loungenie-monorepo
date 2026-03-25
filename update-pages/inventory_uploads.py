from ftplib import FTP
import os,csv
host='ftp.poolsafeinc.com'
user='copilot@loungenie.com'
passwd=os.environ.get('FTP_PASSWORD')
base='wp-content/uploads'
ftp=FTP(host,timeout=30)
ftp.login(user,passwd)
try:
    ftp.cwd(base)
except Exception as e:
    print('CWD failed:',e)
    ftp.quit(); raise

# recursive walk
folders_stats={}

def walk(path):
    try:
        ftp.cwd(path)
    except Exception:
        return
    items=ftp.nlst()
    files=[i for i in items if '.' in i]
    total_size=0
    file_count=0
    for f in files:
        try:
            s=ftp.size(f) or 0
        except Exception:
            s=0
        total_size+=s
        file_count+=1
    folders_stats[path]={'files':file_count,'bytes':total_size}
    # dive into subdirs
    dirs=[i for i in items if i not in ('.','..') and '.' not in i]
    for d in dirs:
        walk(path+'/'+d)
    ftp.cwd('..')

walk('.')
out='c:\\temp\\uploads_inventory.csv'
os.makedirs(os.path.dirname(out),exist_ok=True)
with open(out,'w',newline='',encoding='utf8') as f:
    w=csv.DictWriter(f,fieldnames=['folder','file_count','bytes'])
    w.writeheader()
    for k,v in sorted(folders_stats.items()):
        w.writerow({'folder':k,'file_count':v['files'],'bytes':v['bytes']})
print('Wrote',out)
ftp.quit()
