# Move kadence-export-helper.php from wp-content to public_html root via FTP
. .\tools\session_workspace.ps1

$ErrorActionPreference = 'Stop'
if (-not $env:FTP_HOST) { Write-Error 'FTP_HOST not set after Load-StoredCredentials'; exit 2 }
$src = "ftp://$($env:FTP_HOST)//public_html/wp-content/kadence-export-helper.php"
$dest = "ftp://$($env:FTP_HOST)//public_html/kadence-export-helper.php"
$local = Join-Path (Get-Location) 'exports\\kadence-export-helper-root.php'
if (-not (Test-Path (Split-Path $local))) { New-Item -ItemType Directory -Path (Split-Path $local) | Out-Null }
$wc = New-Object System.Net.WebClient
$wc.Credentials = New-Object System.Net.NetworkCredential($env:FTP_USER,$env:FTP_PASS)

Write-Host "Downloading $src -> $local"
try {
    $wc.DownloadFile($src,$local)
    Write-Host 'Download OK'
} catch {
    Write-Error "Download failed: $($_.Exception.Message)"
    exit 3
}

Write-Host "Uploading $local -> $dest"
try {
    $wc.UploadFile($dest,$local)
    Write-Host 'Upload OK'
} catch {
    Write-Error "Upload failed: $($_.Exception.Message)"
    exit 4
}

# Verify listing
. .\tools\ftp_list_dir.ps1 -Paths '/public_html'

# Test URLs
$urls = @('https://loungenie.com/kadence-export-helper.php?t=export-kadence','https://loungenie.com/staging/kadence-export-helper.php?t=export-kadence')
foreach ($u in $urls) {
    Write-Host "Requesting $u"
    try {
        $r = Invoke-WebRequest -Uri $u -UseBasicParsing -TimeoutSec 30 -ErrorAction Stop
        $fn = Join-Path (Get-Location) ('exports\\kadence_response_' + ($u -replace '[:/\\?=]','_') + '.txt')
        $r.Content | Out-File -FilePath $fn -Encoding utf8
        Write-Host "Saved $fn"
    } catch {
        Write-Host "Request failed for $u : $($_.Exception.Message)"
    }
}
