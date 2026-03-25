import ftplib, os, hashlib, sys
HOST='ftp.poolsafeinc.com'
USER='fabdi21@poolsafeinc.com'
PASS='LounGenie21!'
PORT=21
OUT='artifacts/wp-content-fabdi21-poolsafeinc.tar.gz'
REMOTE='/loungenie.com/staging/wp-content.tar.gz'

os.makedirs('artifacts',exist_ok=True)

def sha256(path):
    h=hashlib.sha256()
    with open(path,'rb') as f:
        for chunk in iter(lambda: f.read(8192),b''):
            h.update(chunk)
    return h.hexdigest()

print('Connecting to',HOST,'as',USER,'and attempting to RETR',REMOTE)
# Try explicit FTPS
try:
    ftps=ftplib.FTP_TLS()
    ftps.connect(HOST,PORT,timeout=30)
    ftps.login(USER,PASS)
    ftps.prot_p()
    with open(OUT,'wb') as f:
        ftps.retrbinary('RETR '+REMOTE,f.write)
    ftps.quit()
    size=os.path.getsize(OUT)
    print('SUCCESS FTPS downloaded to',OUT,size,'bytes')
    print('SHA256:',sha256(OUT))
    sys.exit(0)
except ftplib.error_perm as e:
    print('FTPS perm error:',e)
except Exception as e:
    print('FTPS error:',e)
# Plain FTP fallback
try:
    ftp=ftplib.FTP()
    ftp.connect(HOST,PORT,timeout=30)
    ftp.login(USER,PASS)
    with open(OUT,'wb') as f:
        ftp.retrbinary('RETR '+REMOTE,f.write)
    ftp.quit()
    size=os.path.getsize(OUT)
    print('SUCCESS FTP downloaded to',OUT,size,'bytes')
    print('SHA256:',sha256(OUT))
    sys.exit(0)
except Exception as e:
    print('FTP error:',e)

print('All FTPS/FTP attempts failed')
sys.exit(2)
