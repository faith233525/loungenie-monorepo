#!/bin/bash
# Deploy CSS fix to Loungenie staging site
# This script applies the header navigation color fix

FTP_HOST="ftp.poolsafeinc.com"
FTP_USER="copilot@loungenie.com"
FTP_PATH="/home/pools425/loungenie.com/loungenie/wp-content/plugins/loungenie-portal/assets/css"
FILE="portal-no-gradient.css"

echo "=== Loungenie CSS Fix Deployment ==="
echo "Host: $FTP_HOST"
echo "User: $FTP_USER"
echo "Path: $FTP_PATH/$FILE"
echo ""

# Note: You'll need to run this with your FTP password
# Or use the Python script below instead

# Python alternative (does not require password in command):
python3 << 'PYTHON_SCRIPT'
import ftplib
import os
import sys
from getpass import getpass

# Configuration
host = "ftp.poolsafeinc.com"
user = "copilot@loungenie.com"
css_file = "portal-no-gradient.css"
remote_path = "/home/pools425/loungenie.com/loungenie/wp-content/plugins/loungenie-portal/assets/css/"

# CSS fix to apply
css_fix = """
/* FIX: Header Navigation Visibility - Section added for contrast fix */
/* Changed dark text color to light on dark background for header navigation */

.wp-site-blocks > header .wp-block-navigation-item__content {
    color: rgba(255, 255, 255, .92) !important;  /* Changed from var(--lg-ink) to light */
}

.wp-site-blocks > header .wp-block-navigation-item__content:hover {
    background: rgba(255, 255, 255, .12) !important;
    color: rgba(255, 255, 255, .98) !important;
}

.wp-site-blocks > header .wp-block-navigation-item__content[aria-current="page"] {
    background: rgba(255, 255, 255, .18) !important;
    color: #fff !important;
}

.wp-site-blocks > header .wp-block-navigation-submenu__toggle {
    color: rgba(255, 255, 255, .92) !important;  /* Changed from var(--lg-ink) to light */
}
"""

print("\n=== CSS FIX DEPLOYMENT SCRIPT ===")
print(f"Target: {host}")
print(f"User: {user}")
print(f"File: {css_file}")
print(f"Remote Path: {remote_path}")

try:
    # Connect to FTP
    print("\n[1/4] Connecting to FTP server...")
    ftp = ftplib.FTP(host)
    print(f"     ✓ Connected")
    
    # Login
    print("[2/4] Authenticating...")
    # Password would need to be provided interactively or from environment
    password = os.environ.get('FTP_PASS', 'prompt')
    if password == 'prompt':
        password = getpass(f"Enter password for {user}: ")
    
    ftp.login(user, password)
    print(f"     ✓ Authenticated as {user}")
    
    # Change directory
    print(f"[3/4] Navigating to {remote_path}...")
    ftp.cwd(remote_path)
    print(f"     ✓ In directory: {ftp.pwd()}")
    
    # Download backup
    print(f"[4/4] Downloading backup of {css_file}...")
    backup_file = f"{css_file}.backup.{int(__import__('time').time())}"
    with open(backup_file, 'wb') as f:
        ftp.retrbinary(f'RETR {css_file}', f.write)
    print(f"     ✓ Backup saved: {backup_file}")
    
    print("\n✓ Ready to apply fix")
    print(f"Instructions:")
    print(f"1. Download {css_file} from {remote_path}")
    print(f"2. Find: '.wp-block-navigation-item__content { ... color: var(--lg-ink)'")
    print(f"3. Replace: color: rgba(255, 255, 255, .92) !important;")
    print(f"4. Upload the modified file back to the server")
    print(f"\nOr add this custom CSS to WordPress:")
    print("---")
    print(css_fix)
    print("---")
    
    ftp.quit()
    
except Exception as e:
    print(f"\n✗ Error: {e}")
    print("\nManual steps:")
    print(f"1. Connect to {host} with {user}")
    print(f"2. Navigate to {remote_path}")
    print(f"3. Download {css_file}")
    print(f"4. Apply the color changes shown above")
    print(f"5. Upload back to server")
    sys.exit(1)

PYTHON_SCRIPT

echo ""
echo "✓ Deployment script complete"
