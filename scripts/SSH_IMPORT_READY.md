Summary of SSH key import attempts and next steps

Purpose
- Consolidate all information needed to add/authorize the SSH public key for `pools425` on the host so automated steps can continue without further prompts.

Files & Artifacts
- Public key (generated locally): `id_copilot.pub` (workspace root)
- Private key (generated locally): `id_copilot` (workspace root)
- Import script: `scripts/import-ssh-key-via-cpanel.ps1` (hardened with TLS1.2, retries, logging)

What I attempted
- Called cPanel UAPI endpoint `SSH/import_authorized_key` via `scripts/import-ssh-key-via-cpanel.ps1`.
- First attempt timed out (408). After hardening the script and retrying, cPanel responded with an error: the `import_authorized_key` function is not available in the host's `SSH` module (response shows errors array and status 0).

Current status
- Automated API import: not available on this host (UAPI function missing).
- No public key was added by API. See `cpanel_import_error.log` (if present) for raw error lines.

Automated follow-up options (pick one)
1) Add via cPanel (recommended manual step you can perform):
   - Log into cPanel for `pools425` (use your token or web login).
   - Open "SSH Access" → "Manage SSH Keys" → "Import Key".
   - Paste the contents of `id_copilot.pub` and name it `copilot_key`.
   - After import, click "Manage" and "Authorize" for the key (or move the public key into `~/.ssh/authorized_keys`).

2) Add via cPanel token (you can do this yourself):
   - If you prefer using the cPanel token, note that the UAPI method `SSH/import_authorized_key` is not present on this host. Use the cPanel UI instead.

3) If you want me to proceed without importing an SSH key:
   - I can continue using available methods (FTP + tokenized PHP installers + Softaculous guidance) but earlier installer probes returned 404. The most reliable route to complete the WordPress install and activate the portal is SSH/WP-CLI or running Softaculous from the cPanel UI.

How I will proceed once the key is present (fully automated):
- Verify the key file exists on the server (authorized), then run:

```powershell
# test SSH (from this workstation)
ssh -i id_copilot pools425@poolsafeinc.com -o StrictHostKeyChecking=no

# run WP-CLI remote steps (examples after SSH established)
ssh -i id_copilot pools425@poolsafeinc.com "cd public_html/staging_loungenie && wp core install --url='https://loungenie.com/staging' --title='Loungenie Staging' --admin_user='admin' --admin_password='CHANGE_ME' --admin_email='you@example.com'"
```

Notes & safety
- This file intentionally does NOT include private credentials or the cPanel API token. The token was used in-session for diagnostics but should NOT be written to disk.
- `id_copilot` (private key) is present in the repo root now — if you want the private key removed after use, tell me and I'll delete it and update the todo list.

Next action required (automated if you say "add via cPanel"):
- If you reply "add via cPanel" I'll assume you've imported/authorized the key and will immediately attempt an SSH connection and, if successful, finish the WordPress install and activate the `portal` theme/plugin.

If you prefer manual import, perform the cPanel UI steps above and then reply "done" and I'll continue automatically.

Change log
- 2026-03-25: Created. Contains results of API import attempts and exact next-step commands.
