<#
Reusable FTP uploader for HostPapa-style hosts.
Usage: .\tools\upload_helper.ps1 -LocalPath 'build\preview\kadence_export_helper.php' -RemoteName 'kadence-export-helper.php'
Environment variables used if parameters not provided: FTP_HOST, FTP_USER, FTP_PASS
#>
param(
    [string]$LocalPath = 'build\preview\kadence_export_helper.php',
    [string]$RemoteName = '',
    [string]$FtpHost = $env:FTP_HOST,
    [string]$FtpUser = $env:FTP_USER,
    [string]$FtpPass = $env:FTP_PASS
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path $LocalPath)) { Throw "Local file not found: $LocalPath" }
if (-not $RemoteName) { $RemoteName = [System.IO.Path]::GetFileName($LocalPath) }
if (-not $FtpHost) { Throw 'FTP host not provided in args or FTP_HOST env var.' }
if (-not $FtpUser -or -not $FtpPass) { Throw 'FTP credentials missing in args or env.' }

$remotePath = "//public_html/wp-content/uploads"
$remoteUri = "ftp://$FtpHost$remotePath/$RemoteName"

Write-Host "Uploading $LocalPath -> $remoteUri"

try {
    $wc = New-Object System.Net.WebClient
    $wc.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
    $wc.UploadFile($remoteUri, $LocalPath)
    Write-Host 'Upload successful.'
    exit 0
} catch {
    Write-Host 'Upload failed:' $_.Exception.Message
    exit 2
} finally {
    if ($wc) { $wc.Dispose() }
}
