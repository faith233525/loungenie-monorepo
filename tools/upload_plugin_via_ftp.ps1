<#
.SYNOPSIS
  Recursively upload a plugin folder to a remote FTP server's plugins directory using saved FTP credentials.

USAGE
  .\upload_plugin_via_ftp.ps1 -LocalPluginDir build\plugins\loungenie-home -FtpHost ftp.example.com -RemotePath /public_html/wp-content/plugins

NOTES
  - The script will attempt to load FTP credentials from %USERPROFILE%\.wordpress-credentials\ftp_creds.xml
    (created with store_wp_credentials.ps1). If not present it will prompt with Get-Credential.
  - Uses .NET FtpWebRequest to create directories and upload files. Works with plain FTP; TLS/explicit FTP may require adjustments.
#>

param(
    [Parameter(Mandatory=$true)] [string]$LocalPluginDir,
    [Parameter(Mandatory=$true)] [string]$FtpHost,
    [Parameter(Mandatory=$true)] [string]$RemotePath
)

function Get-FtpCredential {
    $credDir = Join-Path $env:USERPROFILE ".wordpress-credentials"
    $file = Join-Path $credDir "ftp_creds.xml"
    if (Test-Path $file) {
        try {
            return Import-Clixml -Path $file
        } catch {
            Write-Warning "Failed to import saved FTP credential: $_. Falling back to prompt."
        }
    }
    return Get-Credential -Message 'FTP username and password'
}

function Ensure-FtpDirectory($FtpHost, $dir, $cred) {
    $segments = $dir -split '/'
    $path = ''
    foreach ($seg in $segments) {
        if ([string]::IsNullOrWhiteSpace($seg)) { continue }
        $path = "$path/$seg"
        $uri = "ftp://$FtpHost$path"
        try {
            $req = [System.Net.FtpWebRequest]::Create($uri)
            $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
            $req.Credentials = New-Object System.Net.NetworkCredential($cred.UserName, ($cred.GetNetworkCredential().Password))
            $req.UsePassive = $true
            $req.GetResponse() | Out-Null
        } catch {
            # Directory may already exist; ignore errors
        }
    }
}

function Upload-File($FtpHost, $remoteFilePath, $localFile, $cred) {
    $uri = "ftp://$FtpHost$remoteFilePath"
    $bytes = [System.IO.File]::ReadAllBytes($localFile)
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $req.Credentials = New-Object System.Net.NetworkCredential($cred.UserName, ($cred.GetNetworkCredential().Password))
    $req.UseBinary = $true
    $req.UsePassive = $true
    $req.ContentLength = $bytes.Length
    $rs = $req.GetRequestStream()
    $rs.Write($bytes,0,$bytes.Length)
    $rs.Close()
    $req.GetResponse() | Out-Null
    Write-Host "Uploaded: $localFile -> $uri"
}

if (-not (Test-Path $LocalPluginDir)) { Write-Error "Local plugin directory not found: $LocalPluginDir"; exit 1 }

$localBase = (Get-Item $LocalPluginDir).FullName

$cred = Get-FtpCredential

# Ensure remote path exists step-by-step
Ensure-FtpDirectory -FtpHost $FtpHost -dir $RemotePath -cred $cred

# Upload files recursively
$files = Get-ChildItem -Path $localBase -Recurse -File
foreach ($f in $files) {
    $relative = $f.FullName.Substring($localBase.Length).TrimStart('\','/') -replace '\\','/'
    $remoteFile = ($RemotePath.TrimEnd('/') + '/' + $relative.TrimStart('/'))
    Upload-File -FtpHost $FtpHost -remoteFilePath $remoteFile -localFile $f.FullName -cred $cred
}

Write-Host "Upload complete. Activate the plugin from WP Admin or via WP CLI." -ForegroundColor Green
