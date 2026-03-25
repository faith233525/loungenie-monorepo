import ftplib, os, hashlib, sys
HOSTS=['ftp.poolsafeinc.com','ftp.loungenie.com']
USER='fabdi'
PASS='LounGenie21!'
PORT=21
OUTDIR='artifacts'
os.makedirs(OUTDIR,exist_ok=True)
paths=['/loungenie.com/staging/wp-content.tar.gz','/staging/wp-content.tar.gz','/staging/wp-content/wp-content.tar.gz','/wp-content.tar.gz','/loungenie.com/staging/wp-content']

def sha256(path):
    h=hashlib.sha256()
    with open(path,'rb') as f:
        for chunk in iter(lambda: f.read(8192),b''):
            h.update(chunk)
    return h.hexdigest()

for host in HOSTS:
    for i,p in enumerate(paths, start=1):
        out=os.path.join(OUTDIR,f'wp-content-fabdi-{host.replace(".","_")}-attempt-{i}.tar.gz')
        print(f'Trying host={host} user={USER} path={p} -> {out}')
        # FTPS explicit
        try:
            ftps=ftplib.FTP_TLS()
            ftps.connect(host,PORT,timeout=30)
            ftps.login(USER,PASS)
            ftps.prot_p()
            with open(out,'wb') as f:
                ftps.retrbinary('RETR '+p,f.write)
            ftps.quit()
            size=os.path.getsize(out)
            print('SUCCESS FTPS',out,size,sha256(out))
            sys.exit(0)
        except ftplib.error_perm as e:
            print('FTPS perm error',e)
        except Exception as e:
            print('FTPS error',e)
        # plain FTP fallback
        try:
            ftp=ftplib.FTP()
            ftp.connect(host,PORT,timeout=30)
            ftp.login(USER,PASS)
            with open(out,'wb') as f:
                ftp.retrbinary('RETR '+p,f.write)
            ftp.quit()
            size=os.path.getsize(out)
            print('SUCCESS FTP',out,size,sha256(out))
            sys.exit(0)
        except Exception as e:
            print('FTP error',e)
print('All attempts failed')
sys.exit(2)
