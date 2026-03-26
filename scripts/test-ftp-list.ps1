param()

$ftpHost = 'ftp.poolsafeinc.com'
$remotePath = '/public_html/staging_loungenie/'
$user = 'copilot@loungenie.com'
$pass = 'LounGenie21!'

$uri = "ftp://$ftpHost$remotePath"
try {
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
    $req.Credentials = New-Object System.Net.NetworkCredential($user,$pass)
    $req.UsePassive = $true
    $req.UseBinary = $true
    $resp = $req.GetResponse()
    $sr = New-Object System.IO.StreamReader($resp.GetResponseStream())
    $content = $sr.ReadToEnd()
    $sr.Close()
    $resp.Close()
    $content | Out-File -FilePath .\ftp_list.txt -Encoding UTF8
    Write-Host 'FTP_OK'
} catch {
    Write-Host 'FTP_ERR' $_.Exception.Message
}
