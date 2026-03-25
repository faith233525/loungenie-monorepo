<#
.SYNOPSIS
  Prompt for and securely store FTP and WordPress credentials for local use.

USAGE
  .\store_wp_credentials.ps1

Notes:
- Credentials are saved under %USERPROFILE%\.wordpress-credentials as encrypted XML
  (Export-Clixml) which can only be decrypted by the same Windows user account.
#>

param()

$base = Join-Path $env:USERPROFILE ".wordpress-credentials"
if (-not (Test-Path $base)) { New-Item -Path $base -ItemType Directory | Out-Null }

# FTP credential
Write-Host "Enter FTP credentials to save (will be stored encrypted for this user)." -ForegroundColor Cyan
$ftpCred = Get-Credential -Message 'FTP username (e.g. user@host) and password'
$ftpFile = Join-Path $base "ftp_creds.xml"
$ftpCred | Export-Clixml -Path $ftpFile
Write-Host "Saved FTP credential to: $ftpFile" -ForegroundColor Green

# WordPress credential (for REST or Admin)
Write-Host "Enter WordPress credential to save (username + password)." -ForegroundColor Cyan
$wpCred = Get-Credential -Message 'WordPress username and password (for REST or admin)'
$wpFile = Join-Path $base "wp_creds.xml"
$wpCred | Export-Clixml -Path $wpFile
Write-Host "Saved WP credential to: $wpFile" -ForegroundColor Green

Write-Host "Credentials saved and encrypted to your user profile. Use read_stored_credential.ps1 to load them." -ForegroundColor Yellow
