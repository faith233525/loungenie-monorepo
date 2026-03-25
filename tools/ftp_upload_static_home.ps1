. .\tools\session_workspace.ps1
$local = '.\tools\page_4701_home.html'
$remotePath = '/wp-content/uploads/loungenie-home.html'
$uri = "ftp://$($env:FTP_HOST)$remotePath"
try {
    $wc = New-Object System.Net.WebClient
    $wc.Credentials = New-Object System.Net.NetworkCredential($env:FTP_USER, $env:FTP_PASS)
    $wc.UploadFile($uri, 'STOR', $local)
    Write-Host "Uploaded $local -> $uri"
} catch {
    Write-Error "FTP upload failed: $($_.Exception.Message)"
}
