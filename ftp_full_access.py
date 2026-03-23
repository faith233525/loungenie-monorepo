import ftplib
import os
import time

def is_wordpress_site(ftp):
    try:
        files = ftp.nlst()
    except Exception:
        return False
    required = {"wp-config.php", "wp-content", "wp-includes", "wp-admin"}
    return all(any(f == r or f.endswith("/"+r) for f in files) for r in required)

def audit_wordpress_sites(ftp):
    print("\n--- WordPress Site Audit ---")
    # Always start in the staging directory
    try:
        ftp.cwd('/home/pools425/public_html/staging')
        print("Changed to staging directory: /home/pools425/public_html/staging")
    except Exception as e:
        print(f"Failed to change to staging directory: {e}")
        return
    entries = []
    ftp.retrlines('LIST', entries.append)
    dirs = []
    for entry in entries:
        parts = entry.split()
        if len(parts) > 0 and (entry.startswith('d') or parts[0].startswith('d')):
            dirs.append(parts[-1])
    found = []
    # Check root (now staging root)
    if is_wordpress_site(ftp):
        found.append(('.', 'Staging Root'))
    # Check each directory in staging root
    for d in dirs:
        try:
            ftp.cwd(d)
        except Exception:
            continue
        if is_wordpress_site(ftp):
            found.append((d, 'Direct child'))
        # Check one level deeper
        sub_entries = []
        ftp.retrlines('LIST', sub_entries.append)
        subdirs = []
        for s in sub_entries:
            sp = s.split()
            if len(sp) > 0 and (s.startswith('d') or sp[0].startswith('d')):
                subdirs.append(sp[-1])
        for sd in subdirs:
            try:
                ftp.cwd(sd)
            except Exception:
                continue
            if is_wordpress_site(ftp):
                found.append((f"{d}/{sd}", 'Nested'))
            try:
                ftp.cwd('..')
            except Exception:
                pass
        try:
            ftp.cwd('..')
        except Exception:
            pass
    if found:
        print("Possible WordPress sites detected:")
        for path, level in found:
            print(f"  - {path} ({level})")
    else:
        print("No WordPress sites detected in root or immediate subdirectories.")


def retry_ftp_connection(host, user, password, retries=3, delay=5):
    """Retry FTP connection in case of temporary errors."""
    for attempt in range(retries):
        try:
            ftp = ftplib.FTP(host)
            ftp.login(user, password)
            print("Successfully connected to FTP server.")
            return ftp
        except ftplib.error_temp as e:
            print(f"Temporary error during FTP connection: {e}")
            if attempt < retries - 1:
                print(f"Retrying in {delay} seconds...")
                time.sleep(delay)
            else:
                print("Max retries reached. Unable to connect.")
                raise
        except Exception as e:
            print(f"Unexpected error: {e}")
            raise

def get_ftp_credentials():
    host = os.environ.get("FTP_HOST")
    user = os.environ.get("FTP_USER")
    password = os.environ.get("FTP_PASS")
    if host and user and password:
        print("Using FTP credentials from environment variables.")
        return host, user, password
    # Fallback to interactive prompt
    host = input("FTP Host (default: ftp.loungenie.com): ") or "ftp.loungenie.com"
    user = input("FTP Username: ")
    password = input("FTP Password: ")
    return host, user, password

def pick_ftp_directory(ftp):
    print("\nAvailable directories in FTP root:")
    entries = []
    ftp.retrlines('LIST', entries.append)
    dirs = []
    for entry in entries:
        parts = entry.split()
        if len(parts) > 0 and (entry.startswith('d') or parts[0].startswith('d')):
            dirs.append(parts[-1])
    if not dirs:
        print("No directories found in root.")
        return None
    print("  [0] ALL DIRECTORIES")
    for idx, d in enumerate(dirs):
        print(f"  [{idx+1}] {d}")
    while True:
        sel = input(f"Select directory [0-{len(dirs)}]: ")
        if sel.isdigit():
            sel = int(sel)
            if sel == 0:
                return 'ALL_DIRECTORIES', dirs
            elif 1 <= sel <= len(dirs):
                return dirs[sel-1], None
        print("Invalid selection. Try again.")

def list_files(ftp):
    print("Listing files:")
    ftp.retrlines('LIST')

def navigate_to_directory(ftp, target_path):
    """
    Navigate to a target directory. Supports both absolute and relative paths.
    Handles errors like '421 Home directory not available'.
    """
    try:
        ftp.cwd(target_path)
        print(f"Successfully navigated to: {target_path}")
        return True
    except ftplib.error_temp as e:
        print(f"Temporary error navigating to {target_path}: {e}")
        if "Home directory not available" in str(e):
            print("The home directory is not available. Attempting to list root directories.")
            try:
                list_files(ftp)
            except Exception as list_error:
                print(f"Failed to list root directories: {list_error}")
        return False
    except Exception as e:
        print(f"Failed to navigate to {target_path}: {e}")
        # Attempt relative navigation if absolute path fails
        print("Attempting relative navigation...")
        parts = target_path.strip('/').split('/')
        for part in parts:
            try:
                ftp.cwd(part)
                print(f"Navigated to: {part}")
            except Exception as rel_e:
                print(f"Failed to navigate to {part}: {rel_e}")
                return False
        return True

def main():
    FTP_HOST, FTP_USER, FTP_PASS = get_ftp_credentials()
    ftp = None
    try:
        ftp = retry_ftp_connection(FTP_HOST, FTP_USER, FTP_PASS)
        print("\nRoot directory contents after login:")
        list_files(ftp)
        # Check for accessible directories
        FTP_DIR = "/"  # Start from root directory
        if navigate_to_directory(ftp, FTP_DIR):
            print(f"Successfully navigated to {FTP_DIR}")
            print("Listing contents to check for accessible directories:")
            list_files(ftp)
    except Exception as e:
        print(f"Error during FTP operations: {e}")
    finally:
        try:
            if ftp is not None:
                ftp.quit()
        except Exception as quit_error:
            print(f"Error during FTP quit: {quit_error}")

if __name__ == "__main__":
    main()
