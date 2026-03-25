$ErrorActionPreference='Stop'
$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS
if (-not $ftpHost) { Throw 'FTP_HOST not set' }
$src = "ftp://$ftpHost//public_html/wp-content/uploads/media_ops_results.json"
$out = Join-Path (Get-Location) 'exports\\media_ops_results.json'
if (-not (Test-Path (Split-Path $out))) { New-Item -ItemType Directory -Path (Split-Path $out) | Out-Null }
$wc = New-Object System.Net.WebClient
$wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
try {
    $wc.DownloadFile($src,$out)
    Write-Host "Downloaded to $out"
} catch {
    Write-Host "Download failed:" $_.Exception.Message
    exit 2
}