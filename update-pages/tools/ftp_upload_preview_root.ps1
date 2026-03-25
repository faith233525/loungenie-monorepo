. .\tools\session_workspace.ps1
$local = '.\build\preview\loungenie-home-preview.php'
$remotePath = '/loungenie-home-preview.php'
$uri = "ftp://$($env:FTP_HOST)$remotePath"
try {
    $wc = New-Object System.Net.WebClient
    $wc.Credentials = New-Object System.Net.NetworkCredential($env:FTP_USER, $env:FTP_PASS)
    $wc.UploadFile($uri, 'STOR', $local)
    Write-Host "Uploaded $local -> $uri"
} catch {
    Write-Error "FTP upload failed: $($_.Exception.Message)"
}
