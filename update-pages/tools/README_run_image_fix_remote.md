run_image_fix_remote.ps1 — README

Purpose

This PowerShell script runs WP-CLI commands on the remote server via SSH to perform a dry-run (or apply) of the image URL fixes. It avoids sending secrets through chat — you run it locally and enter your SSH password when prompted by SSH.

How it works

- Prompts for SSH host, user, and remote WordPress site path.
- Creates a timestamped DB export on the remote server using `wp db export`.
- Runs `wp search-replace` for configured patterns in dry-run mode (or applies if you select apply).
- Saves dry-run/apply logs locally in the current directory.

Prereqs

- `ssh` available in your PATH (Windows OpenSSH or PuTTY/Plink not supported by this script; use OpenSSH `ssh`).
- Remote server with `wp` (WP-CLI) in PATH for the SSH user.
- The SSH user must have permissions to run WP-CLI and write the DB export in the site directory.

Usage

1. Open PowerShell on your machine and `cd` to the repo root (where `tools\run_image_fix_remote.ps1` lives).
2. Run the script:

```powershell
powershell -ExecutionPolicy Bypass -File tools\run_image_fix_remote.ps1
```

3. Enter the SSH host, user, and site path when prompted. Answer `no` to "Apply changes?" to run dry-run only. Inspect the generated `loungenie-searchreplace-dryrun-<timestamp>.txt`.
4. Paste the dry-run output here and I will review. If approved, re-run the script and answer `yes` to apply.

Notes

- The script saves a remote DB export file named `loungenie-backup-<timestamp>.sql` in the remote site root. Keep that for rollback.
- After applying, purge any CDN or LiteSpeed caches.
- If you must use password-based SSH, OpenSSH will prompt for the password interactively; the script does not handle password piping.
