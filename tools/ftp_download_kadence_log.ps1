. .\tools\session_workspace.ps1
$ErrorActionPreference = 'Stop'
$remotePath = '/public_html/wp-content/uploads/kadence-export-mu.log'
$uri = "ftp://$($env:FTP_HOST)$remotePath"
try {
    if (-not (Test-Path (Join-Path (Get-Location) 'exports'))) { New-Item -ItemType Directory -Path (Join-Path (Get-Location) 'exports') | Out-Null }
    $wc = New-Object System.Net.WebClient
    $wc.Credentials = New-Object System.Net.NetworkCredential($env:FTP_USER, $env:FTP_PASS)
    $out = Join-Path (Get-Location) 'exports\kadence-export-mu.log'
    $wc.DownloadFile($uri, $out)
    Write-Host "Downloaded FTP file to $out"
} catch {
    Write-Host "FTP download failed: $($_.Exception.Message)"
}
