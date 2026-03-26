param()

$local = Join-Path $PWD 'wp-oneclick-install-2.php'
if(-not (Test-Path $local)) { Write-Host "Local installer not found: $local"; exit 1 }

$dest = "ftp://ftp.poolsafeinc.com/public_html/staging_loungenie/wp-content/uploads/wp-oneclick-install-2.php"
$wc = New-Object System.Net.WebClient
$wc.Credentials = New-Object System.Net.NetworkCredential($env:FTP_USER, $env:FTP_PASS)

try {
    $wc.UploadFile($dest,'STOR',$local)
    Write-Host "Uploaded to uploads: $dest"
} catch {
    Write-Host "Upload failed: $_"
    exit 1
}

$ip = '66.102.133.37'
$token = (Select-String -Path $local -Pattern "\$token = '([0-9a-f\-]+)'" | ForEach-Object { $_.Matches[0].Groups[1].Value })
if(-not $token){ Write-Host 'Token not found in local installer'; exit 1 }

$call = "https://$ip/staging_loungenie/wp-content/uploads/wp-oneclick-install-2.php?t=$token"
Write-Host "Calling: $call"

$respFile = Join-Path $PWD 'installer_uploads_response.txt'
$rcmd = "curl.exe -s -k -H 'Host: www.loungenie.com' -H 'Cache-Control: no-cache' -L '$call' -w '\nHTTP_CODE:%{http_code}' -o '$respFile'"
Write-Host $rcmd
Invoke-Expression $rcmd
Write-Host "Response saved to $respFile"
