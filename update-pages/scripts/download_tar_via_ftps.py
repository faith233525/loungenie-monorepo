import ftplib
import ssl
import os
import hashlib
import sys

HOST = "ftp.poolsafeinc.com"
USER = "backup@loungenie.com"
PASS = "LounGenie21!"
# Try multiple likely remote locations (some hosts expose different FTP roots)
REMOTE_CANDIDATES = [
    "/loungenie.com/staging/wp-content/aiowps_backups.tar.gz",
    "/loungenie.com/public_html/staging/wp-content/aiowps_backups.tar.gz",
    "/public_html/staging/wp-content/aiowps_backups.tar.gz",
    "/staging/wp-content/aiowps_backups.tar.gz",
    "/home/pools425/loungenie.com/staging/wp-content/aiowps_backups.tar.gz",
]
# also try variants without leading slash
REMOTE_CANDIDATES += [
    p.lstrip("/") for p in list(REMOTE_CANDIDATES)
]
LOCAL_DIR = "artifacts"
LOCAL_NAME = "aiowps_backups.tar.gz"

os.makedirs(LOCAL_DIR, exist_ok=True)
local_path = os.path.join(LOCAL_DIR, LOCAL_NAME)

def sha256_of(path):
    h = hashlib.sha256()
    with open(path, "rb") as f:
        for chunk in iter(lambda: f.read(8192), b""):
            h.update(chunk)
    return h.hexdigest()

def download():
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    try:
        ftps = ftplib.FTP_TLS(context=ctx, timeout=30)
        ftps.connect(HOST, 21, timeout=30)
        ftps.auth()  # upgrade to TLS
        ftps.prot_p()
        ftps.login(USER, PASS)
        ftps.set_pasv(True)
        downloaded = False
        for REMOTE_PATH in REMOTE_CANDIDATES:
            try:
                dirname, filename = os.path.split(REMOTE_PATH)
                print(f"Attempting: {REMOTE_PATH}")
                # Try direct RETR with the candidate path first
                with open(local_path, "wb") as f:
                    ftps.retrbinary(f"RETR {REMOTE_PATH}", f.write)
                downloaded = True
                print(f"Downloaded via RETR {REMOTE_PATH}")
                break
            except Exception as e:
                # If direct RETR failed, try changing cwd + RETR filename
                try:
                    if dirname:
                        try:
                            ftps.cwd(dirname)
                        except Exception:
                            ftps.cwd(dirname.lstrip("/"))
                    with open(local_path, "wb") as f:
                        ftps.retrbinary(f"RETR {filename}", f.write)
                    downloaded = True
                    print(f"Downloaded via cwd {dirname} + RETR {filename}")
                    break
                except Exception:
                    print(f"Candidate failed: {REMOTE_PATH}")
                    if os.path.exists(local_path):
                        try:
                            os.remove(local_path)
                        except Exception:
                            pass
                    continue

        if not downloaded:
            # Provide some diagnostic listing to help locate the file
            try:
                print("Remote root listing:")
                print(ftps.nlst())
                print("Remote /loungenie.com listing:")
                try:
                    print(ftps.nlst('loungenie.com'))
                except Exception:
                    pass
            except Exception as e:
                print("Listing failed:", e)
            raise RuntimeError("All remote path candidates failed")
        ftps.quit()
        print("Download completed")
        print("Computing SHA256...")
        print(sha256_of(local_path))
    except Exception as e:
        print("ERROR:", e)
        if os.path.exists(local_path):
            try:
                os.remove(local_path)
            except Exception:
                pass
        sys.exit(2)

if __name__ == '__main__':
    download()
