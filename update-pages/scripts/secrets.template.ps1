<#
secrets.template.ps1

Copy this file to `scripts\secrets.ps1` and edit the credential line.
The `secrets.ps1` file is gitignored by default and should NEVER be committed.

Example (replace with your actual app password):

$env:WP_AUTH = 'copilot:nbe2 1zsu upGv JS7h 8qVJ swch
Save as `scripts\secrets.ps1` and then run the one-click script:

# PowerShell (current session)
.\scripts\secrets.ps1
.\scripts\run_restore_oneclick.ps1

# Or set environment variable in your shell session temporarily:
$env:WP_AUTH = 'copilot:YOUR_APP_PASSWORD_HERE'
.\scripts\run_restore_oneclick.ps1

