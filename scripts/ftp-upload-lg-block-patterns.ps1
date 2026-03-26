<#
Uploads the lg-block-patterns plugin from this repo to the staging site's wp-content via FTP.
Requires env vars: FTP_HOST, FTP_USER, FTP_PASS (no passwords are stored in this file).
#>
Param()

$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS
if (-not ($ftpHost -and $ftpUser -and $ftpPass)) {
    Write-Error 'FTP env vars not set (FTP_HOST, FTP_USER, FTP_PASS).'
    exit 1
}

function Upload-DirToFtp([string]$localDir, [string]$remoteBase) {
    if (-not (Test-Path $localDir)) {
        Write-Host "Local dir missing: $localDir"
        return
    }
    Get-ChildItem -Path $localDir -Recurse -File | ForEach-Object {
        $rel = $_.FullName.Substring($localDir.Length) -replace '^\\','/'
        # Build a clean POSIX-style remote path under the plugin directory
        $remotePath = ($remoteBase.TrimEnd('/') + $rel) -replace '\\','/'
        $remoteDir = [System.IO.Path]::GetDirectoryName($remotePath) -replace '\\','/'
        # create remote dirs (best-effort)
        try {
            $parts = $remoteDir.TrimStart('/').Split('/')
            $cur = ''
            foreach ($p in $parts) {
                $cur = $cur + '/' + $p
                $uri = "ftp://$ftpHost$cur"
                $req = [System.Net.FtpWebRequest]::Create($uri)
                $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
                $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
                $req.GetResponse() | Out-Null
            }
        }
        catch {
            # ignore directory creation errors (likely already exists)
        }
        # upload file
        $uriFile = "ftp://$ftpHost$remotePath"
        Write-Host "Uploading $($_.FullName) -> $uriFile"
        try {
            $uriReq = [System.Uri]::new($uriFile)
            $req = [System.Net.FtpWebRequest]::Create($uriReq)
            $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $req.UsePassive = $true
            $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
            $fileBytes = [System.IO.File]::ReadAllBytes($_.FullName)
            $req.ContentLength = $fileBytes.Length
            $rs = $req.GetRequestStream()
            $rs.Write($fileBytes, 0, $fileBytes.Length)
            $rs.Close()
            $resp = $req.GetResponse()
            $resp.Close()
        } catch {
            Write-Host "FtpWebRequest failed, falling back to WebClient: $_"
            $wc = New-Object System.Net.WebClient
            $wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
            $wc.UploadFile($uriFile, $_.FullName) | Out-Null
        }
    }
}

$localPlugin = Join-Path (Get-Location) 'wp-content/plugins/lg-block-patterns'
$remoteBase = '/public_html/staging_loungenie/wp-content/plugins/lg-block-patterns'

Upload-DirToFtp $localPlugin $remoteBase

Write-Host 'lg-block-patterns plugin deploy complete.'
