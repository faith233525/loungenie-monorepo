<#
One-click wrapper: sets `WP_AUTH` and runs `run_restore_with_safety.ps1`.

Usage:
  - Edit the `$cred` line to set `username:app-password`, or run as-is if already correct.
  - Then run: `powershell -ExecutionPolicy Bypass -File .\scripts\run_restore_oneclick.ps1`
#>

# Prefer using environment variable `WP_AUTH` or a local `scripts\secrets.ps1` file.
# The secrets file should set `$env:WP_AUTH = 'username:application-password'`.

# Attempt to use existing environment variable
if (-not $env:WP_AUTH) {
  $secretsFile = Join-Path $PSScriptRoot 'secrets.ps1'
  if (Test-Path $secretsFile) {
    . $secretsFile
  }
}

if (-not $env:WP_AUTH) {
  Write-Host "WP_AUTH is not set. Create 'scripts\secrets.ps1' from 'scripts\secrets.template.ps1' or set the environment variable before running this script."
  $cred = Read-Host -Prompt "Enter credentials (username:application-password)"
  if (-not $cred) { Write-Error "No credentials provided. Aborting."; exit 1 }
  $env:WP_AUTH = $cred
} else {
  Write-Host "Using WP_AUTH from environment."
}

Write-Host "Launching restore script..."
& .\scripts\run_restore_with_safety.ps1
