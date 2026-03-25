. .\tools\session_workspace.ps1
$ErrorActionPreference='Stop'
$src = "ftp://$($env:FTP_HOST)//public_html/kadence-export-helper.php"
$dest = "ftp://$($env:FTP_HOST)//public_html/loungenie.com/kadence-export-helper.php"
$local = Join-Path (Get-Location) 'exports\\kadence-export-helper-copy.php'
if (-not (Test-Path (Split-Path $local))) { New-Item -ItemType Directory -Path (Split-Path $local) | Out-Null }
$wc=New-Object System.Net.WebClient
$wc.Credentials = New-Object System.Net.NetworkCredential($env:FTP_USER,$env:FTP_PASS)
Write-Host "Downloading $src"
$wc.DownloadFile($src,$local)
Write-Host 'Uploading to lounges site root'
$wc.UploadFile($dest,$local)
Write-Host 'Done'

# Verify and test
. .\tools\ftp_list_dir.ps1 -Paths '/public_html/loungenie.com','/public_html/loungenie.com/wp-content'
try { $r=Invoke-WebRequest -Uri 'https://loungenie.com/kadence-export-helper.php?t=export-kadence' -UseBasicParsing -TimeoutSec 30 -ErrorAction Stop; $r.Content | Out-File 'exports\\kadence_resp_loungenie_root.txt' -Encoding utf8; Write-Host 'Saved exports\\kadence_resp_loungenie_root.txt' } catch { Write-Host 'Request failed:' $_.Exception.Message }
