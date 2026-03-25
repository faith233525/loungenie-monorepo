. .\tools\session_workspace.ps1
$remotePath = "/wp-content/mu-plugins/block.html"
$uri = "ftp://$($env:FTP_HOST)$remotePath"
try {
    $wc = New-Object System.Net.WebClient
    $wc.Credentials = New-Object System.Net.NetworkCredential($env:FTP_USER, $env:FTP_PASS)
    $out = Join-Path (Get-Location) 'exports\ftp_block.html'
    $wc.DownloadFile($uri, $out)
    Write-Host "Downloaded FTP file to $out"
} catch {
    Write-Error "FTP download failed: $($_.Exception.Message)"
}
