<#
Download images listed in tools/image_inventory.csv from production and upload to staging uploads via FTP.
Requires stored FTP creds at $env:USERPROFILE\.config\loungenie\ftp_cred.xml
#>

$csv = Join-Path $PSScriptRoot 'image_inventory.csv'
if (-not (Test-Path $csv)) { Write-Error "Missing $csv"; exit 1 }

$credDir = Join-Path $env:USERPROFILE '.config\loungenie'
$ftpFile = Join-Path $credDir 'ftp_cred.xml'
if (-not (Test-Path $ftpFile)) { Write-Error "No stored FTP credential found at $ftpFile"; exit 1 }
$ftpCred = Import-Clixml -Path $ftpFile

function SecureString-ToPlainText { param([System.Security.SecureString]$s) $bstr=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); try{[Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)} finally{[Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)} }
$ftpUser = $ftpCred.UserName
$ftpPass = SecureString-ToPlainText $ftpCred.Password
$ftpHost = 'ftp.loungenie.com'

Get-Content $csv | Select-Object -Skip 1 | ForEach-Object {
    $parts = $_ -split ',',3
    if ($parts.Length -lt 1) { return }
    $remotePath = $parts[0].Trim()
    if (-not $remotePath) { return }
    $url = 'https://loungenie.com' + $remotePath
    $fileName = [System.IO.Path]::GetFileName($remotePath)
    $localTmp = Join-Path $env:TEMP $fileName
    try {
        Write-Output "Downloading $url -> $localTmp"
        Invoke-WebRequest -Uri $url -OutFile $localTmp -ErrorAction Stop
    } catch {
        Write-Warning ("Download failed for " + $url + ": " + $_.Exception.Message)
        return
    }
    $remoteUploadPath = '/public_html' + ([System.IO.Path]::GetDirectoryName($remotePath).Replace('\','/')) + '/'
    Write-Output "Uploading $localTmp to $remoteUploadPath on FTP host $ftpHost"
    pwsh -NoProfile -ExecutionPolicy Bypass -File .\ftp_upload_file.ps1 -LocalFile $localTmp -FtpHost $ftpHost -FtpUser $ftpUser -FtpPass $ftpPass -RemotePath $remoteUploadPath
    Remove-Item $localTmp -Force -ErrorAction SilentlyContinue
}
