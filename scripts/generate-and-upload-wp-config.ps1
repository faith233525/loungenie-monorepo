<#
Generates a minimal wp-config.php from staging-db-credentials.json
and uploads it to the staging site via FTP using env vars:
  FTP_HOST, FTP_USER, FTP_PASS
#>

Param()

$credFile = Join-Path -Path (Get-Location) -ChildPath 'staging-db-credentials.json'
if (-not (Test-Path $credFile)) {
    Write-Error "Missing $credFile. Run uapi-capture.ps1 first."
    exit 1
}
$cred = Get-Content $credFile | ConvertFrom-Json

$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS
if (-not ($ftpHost -and $ftpUser -and $ftpPass)) {
    Write-Error "FTP environment variables not set. Please set FTP_HOST, FTP_USER, FTP_PASS."
    exit 1
}

function New-Salt([int]$len=64) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_[]{}<>~`+=,.;:/?'
    -join (1..$len | ForEach-Object { $chars[(Get-Random -Maximum $chars.Length)] })
}

$salts = @()
foreach ($k in @('AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT')) {
    $salts += "define('$k', '" + (New-Salt) + "');"
}

$php = @"
<?php
// Auto-generated wp-config.php for staging
define('DB_NAME', '${($cred.database)}');
define('DB_USER', '${($cred.db_user)}');
define('DB_PASSWORD', '${($cred.db_pass)}');
define('DB_HOST', 'localhost');

define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// Authentication Unique Keys and Salts.
"@

$php += "`n" + ($salts -join "`n") + "`n"
$php += @"
// WordPress Database Table prefix
\$table_prefix = 'wp_';

// For developers: WordPress debugging mode.
define('WP_DEBUG', false);

if ( !defined('ABSPATH') )
    define('ABSPATH', dirname(__FILE__) . '/');

require_once ABSPATH . 'wp-settings.php';
"@

$localPath = Join-Path -Path (Get-Location) -ChildPath 'staging-wp-config.php'
Set-Content -Path $localPath -Value $php -Encoding UTF8 -Force
Write-Host "Wrote $localPath"

# Upload via FTP
$remoteUri = "ftp://$ftpHost/public_html/staging_loungenie/wp-config.php"
Write-Host "Uploading to $remoteUri"

$wc = New-Object System.Net.WebClient
$wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
try {
    $wc.UploadFile($remoteUri, $localPath) | Out-Null
    Write-Host "Upload succeeded."
} catch {
    Write-Error "Upload failed: $($_.Exception.Message)"
    exit 1
}

# Verify by downloading the file back
$verifyLocal = Join-Path -Path (Get-Location) -ChildPath 'staging-wp-config.downloaded.php'
try {
    $wc.DownloadFile($remoteUri, $verifyLocal)
    Write-Host "Downloaded remote file to $verifyLocal (size: $(Get-Item $verifyLocal).Length bytes)"
} catch {
    Write-Warning "Could not download remote file for verification: $($_.Exception.Message)"
}

Write-Host "Done. Remember to remove staging-db-credentials.json after use."
