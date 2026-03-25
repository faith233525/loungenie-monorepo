param(
    [string]$LocalFile = 'copilot_media_operations.php'
)
. .\tools\session_workspace.ps1
$ErrorActionPreference='Stop'
$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS
if (-not $ftpHost) { Throw 'FTP_HOST not set' }
if (-not (Test-Path $LocalFile)) { Throw "Local file not found: $LocalFile" }
$remoteDir = '/public_html/wp-content/mu-plugins'
$remote = "ftp://$ftpHost//$remoteDir/$(Split-Path $LocalFile -Leaf)"
Write-Host "Ensuring remote dir: $remoteDir"
# create directory
$segments = $remoteDir -split '/'
$path = ''
foreach ($seg in $segments) {
    if ([string]::IsNullOrWhiteSpace($seg)) { continue }
    $path = "$path/$seg"
    $uri = "ftp://$ftpHost$path"
    try {
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
        $req.UsePassive = $true
        $req.GetResponse() | Out-Null
    } catch {
        # ignore
    }
}
Write-Host "Uploading $LocalFile -> $remote"
$wc = New-Object System.Net.WebClient
$wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
$wc.UploadFile($remote, $LocalFile)
Write-Host 'Upload finished.'

# Verify listing
. .\tools\ftp_list_dir.ps1 -Paths '/public_html/wp-content/mu-plugins','/public_html/wp-content','/public_html'

# Trigger plugin via HTTP (root and staging)
$urls = @('https://loungenie.com/?copilot_run=media_ops_run_7z9k','https://loungenie.com/staging/?copilot_run=media_ops_run_7z9k')
if (-not (Test-Path (Join-Path (Get-Location) 'exports'))) { New-Item -ItemType Directory -Path (Join-Path (Get-Location) 'exports') | Out-Null }
foreach ($u in $urls) {
    try {
        Write-Host "Requesting $u"
        $r = Invoke-WebRequest -Uri $u -UseBasicParsing -TimeoutSec 60 -ErrorAction Stop
        $safe = ($u -replace '[:/\\?=]','_')
        $out = Join-Path (Get-Location) ("exports\\media_ops_response_" + $safe + ".json")
        $r.Content | Out-File -FilePath $out -Encoding utf8
        Write-Host "Saved $out"
    } catch {
        Write-Host "Request failed for $u :" $_.Exception.Message
    }
}
