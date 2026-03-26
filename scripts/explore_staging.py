import ftplib

ftp = ftplib.FTP()
ftp.connect("ftp.loungenie.com", 21, timeout=15)
ftp.login("copilot@loungenie.com", "LounGenie21!")
ftp.set_pasv(True)

def ls(path):
    items = []
    try:
        ftp.cwd(path)
        ftp.retrlines("LIST", items.append)
    except Exception as e:
        return [f"ERROR: {e}"]
    return items

for base in ["loungenie.com/stage", "loungenie.com/staging", "stage", "staging"]:
    result = ls(f"/{base}/wp-content/plugins")
    if not any("ERROR" in r for r in result):
        print(f"\n=== PLUGINS at /{base}/wp-content/plugins ===")
        for r in result: print(r)
        result2 = ls(f"/{base}/wp-content/themes")
        print(f"\n=== THEMES at /{base}/wp-content/themes ===")
        for r in result2: print(r)
        result3 = ls(f"/{base}/wp-content/mu-plugins")
        print(f"\n=== MU-PLUGINS at /{base}/wp-content/mu-plugins ===")
        for r in result3: print(r)
        break
    else:
        print(f"/{base}: {result[0]}")

ftp.quit()
