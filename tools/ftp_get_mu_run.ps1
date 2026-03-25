. .\tools\session_workspace.ps1
$remotePath = "/wp-content/uploads/loungenie-mu-run.txt"
$uri = "ftp://$($env:FTP_HOST)$remotePath"
try {
    $wc = New-Object System.Net.WebClient
    $wc.Credentials = New-Object System.Net.NetworkCredential($env:FTP_USER, $env:FTP_PASS)
    $out = Join-Path (Get-Location) 'exports\loungenie-mu-run.txt'
    $wc.DownloadFile($uri, $out)
    Write-Host "Downloaded MU probe output to $out"
} catch {
    Write-Error "FTP download failed: $($_.Exception.Message)"
}
