$ftpHost = 'sh-cp9.yyz2.servername.online'
$ftpUser = 'copilot@loungenie.com'
$ftpPass = 'LounGenie21!'
$path = '/public_html/staging_loungenie'

$uri = "ftp://$ftpHost$path/"
$req = [System.Net.FtpWebRequest]::Create($uri)
$req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
$req.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
$req.EnableSsl = $false
$req.UsePassive = $true

try {
    $resp = $req.GetResponse()
    $sr = New-Object System.IO.StreamReader($resp.GetResponseStream())
    $sr.ReadToEnd() | Out-File -FilePath .\ftp_core_listing.txt -Encoding UTF8
    $resp.Close()
    Write-Host 'LIST_OK'
} catch {
    $_.Exception.Message | Out-File -FilePath .\ftp_core_error.txt -Encoding UTF8
    Write-Host 'LIST_ERR'
}
