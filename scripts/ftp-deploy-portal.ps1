<#
Uploads portal themes and plugins from the monorepo to the staging site's wp-content via FTP.
Requires env vars: FTP_HOST, FTP_USER, FTP_PASS
#>
Param()

$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS
if (-not ($ftpHost -and $ftpUser -and $ftpPass)) { Write-Error 'FTP env vars not set'; exit 1 }

function Upload-DirToFtp([string]$localDir, [string]$remoteBase) {
    if (-not (Test-Path $localDir)) { Write-Host "Local dir missing: $localDir"; return }
    Get-ChildItem -Path $localDir -Recurse -File | ForEach-Object {
        $rel = $_.FullName.Substring($localDir.Length) -replace '^\\','/'
        $remotePath = "$remoteBase/$rel"
        $remoteDir = [System.IO.Path]::GetDirectoryName($remotePath) -replace '\\','/'
        # create remote dirs (best-effort)
        Try {
            $parts = $remoteDir.TrimStart('/').Split('/')
            $cur = ''
            foreach ($p in $parts) { $cur = $cur + '/' + $p; $uri = "ftp://$ftpHost$cur"; $req = [System.Net.FtpWebRequest]::Create($uri); $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory; $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass); $req.GetResponse() | Out-Null }
        } Catch { }
        # upload file
        $uriFile = "ftp://$ftpHost$remotePath"
        $wc = New-Object System.Net.WebClient
        $wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
        Write-Host "Uploading $($_.FullName) -> $uriFile"
        $wc.UploadFile($uriFile, $_.FullName) | Out-Null
    }
}

$repoRoot = Join-Path -Path (Get-Location) -ChildPath 'loungenie-monorepo'
$themes = Join-Path $repoRoot 'themes'
$plugins = Join-Path $repoRoot 'plugins'
$remoteBase = '/public_html/staging_loungenie/wp-content'

Upload-DirToFtp $themes ($remoteBase + '/themes')
Upload-DirToFtp $plugins ($remoteBase + '/plugins')

Write-Host 'Portal deploy complete.'
