<#
.SYNOPSIS
  Import FTP and WP credentials from environment variables and save encrypted to the user profile.

USAGE
  In PowerShell (after setting env vars):
    .\import_env_credentials.ps1

ENV VARS USED
  FTP_HOST, FTP_USER, FTP_PASS
  WP_SITE_URL, WP_USER, WP_PASS

SECURITY
  Credentials are saved with Export-Clixml to %USERPROFILE%\.wordpress-credentials and
  can only be decrypted by the same Windows user account.
#>

param()

$needed = @('FTP_HOST','FTP_USER','FTP_PASS','WP_SITE_URL','WP_USER','WP_PASS')
$missing = @()
foreach ($n in $needed) {
  if (-not (Get-ChildItem env:$n -ErrorAction SilentlyContinue)) { $missing += $n }
}
if ($missing.Count -gt 0) {
    Write-Error "Missing environment variables: $($missing -join ', ')`nPlease set them and re-run this script."
    exit 2
}

$credDir = Join-Path $env:USERPROFILE '.wordpress-credentials'
if (-not (Test-Path $credDir)) { New-Item -Path $credDir -ItemType Directory | Out-Null }

# Create and export FTP credential
$ftpSecure = ConvertTo-SecureString $env:FTP_PASS -AsPlainText -Force
$ftpCred = New-Object System.Management.Automation.PSCredential($env:FTP_USER, $ftpSecure)
$ftpFile = Join-Path $credDir 'ftp_creds.xml'
$ftpCred | Export-Clixml -Path $ftpFile

# Create and export WP credential
$wpSecure = ConvertTo-SecureString $env:WP_PASS -AsPlainText -Force
$wpCred = New-Object System.Management.Automation.PSCredential($env:WP_USER, $wpSecure)
$wpFile = Join-Path $credDir 'wp_creds.xml'
$wpCred | Export-Clixml -Path $wpFile

# Save non-secret site URL for convenience
$siteFile = Join-Path $credDir 'site_url.txt'
Set-Content -Path $siteFile -Value $env:WP_SITE_URL -Encoding UTF8

Write-Host "Imported and saved credentials to: $credDir" -ForegroundColor Green
Write-Host "FTP credential: $ftpFile" -ForegroundColor DarkGreen
Write-Host "WP credential: $wpFile" -ForegroundColor DarkGreen
Write-Host "Site URL: $siteFile" -ForegroundColor DarkGreen

Write-Host "Credentials are encrypted and bound to this Windows user account. Run the uploader or apply scripts in this account to use them." -ForegroundColor Yellow
