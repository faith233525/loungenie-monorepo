param(
    [string]$LocalPath = 'build\preview\kadence_export_helper.php',
    [string]$RemoteName = 'kadence-export-helper.php'
)

$ErrorActionPreference = 'Stop'

$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS

if (-not $ftpHost) { Throw 'FTP_HOST not set' }
if (-not (Test-Path $LocalPath)) { Throw "Local file not found: $LocalPath" }

$remote = "ftp://$ftpHost//public_html/wp-content/$RemoteName"
Write-Host "Uploading $LocalPath -> $remote"

$wc = New-Object System.Net.WebClient
$wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
$wc.UploadFile($remote, $LocalPath)
Write-Host 'Upload finished.'
