param()

$ftpHost = 'ftp.poolsafeinc.com'
$remotePath = '/public_html/staging_loungenie/wp-content/uploads/'
$user = $env:FTP_USER
$pass = $env:FTP_PASS

$uri = "ftp://$ftpHost$remotePath"
try {
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
    $req.Credentials = New-Object System.Net.NetworkCredential($user,$pass)
    $req.UsePassive = $true
    $req.UseBinary = $true
    $resp = $req.GetResponse()
    $resp.Close()
    Write-Host 'MKD_OK'
} catch {
    Write-Host 'MKD_ERR' $_.Exception.Message
}
