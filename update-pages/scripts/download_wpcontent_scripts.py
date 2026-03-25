import ftplib, os, hashlib, sys
HOST='ftp.poolsafeinc.com'
USER='scripts@loungenie.com'
PORT=21
PASS='p7DZFL-}0&o6uoc='
OUTDIR='artifacts'
os.makedirs(OUTDIR,exist_ok=True)
paths=['/loungenie.com/staging/wp-content.tar.gz','/staging/wp-content.tar.gz','/staging/wp-content/wp-content.tar.gz','/home/pools425/loungenie.com/staging/wp-content.tar.gz']

def sha256(path):
    import hashlib
    h=hashlib.sha256()
    with open(path,'rb') as f:
        for chunk in iter(lambda: f.read(8192),b''):
            h.update(chunk)
    return h.hexdigest()

for i,p in enumerate(paths, start=1):
    out=os.path.join(OUTDIR,f'wp-content-scripts-attempt-{i}.tar.gz')
    print(f'Trying {p} -> {out}')
    try:
        ftps=ftplib.FTP_TLS()
        ftps.connect(HOST,PORT,timeout=30)
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
    # try plain FTP fallback
    try:
        ftp=ftplib.FTP()
        ftp.connect(HOST,PORT,timeout=30)
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
