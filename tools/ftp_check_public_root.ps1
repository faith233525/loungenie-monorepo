<#
List files in public_html root and download kadence-export-mu-root.dat if present
#>
$ErrorActionPreference = 'Stop'

$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS

if (-not $ftpHost) { Write-Host 'FTP_HOST not set'; exit 2 }

$uri = "ftp://$ftpHost//public_html/"
Write-Host "Listing $uri"

try {
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
    $resp = $req.GetResponse()
    $sr = New-Object System.IO.StreamReader $resp.GetResponseStream()
    $files = @()
    while (-not $sr.EndOfStream) { $files += $sr.ReadLine() }
    $sr.Close(); $resp.Close()
} catch {
    Write-Host "FTP list failed:" $_.Exception.Message
    exit 3
}

Write-Host "Files in public_html:"; $files | ForEach-Object { Write-Host " - " $_ }

$target = 'kadence-export-mu-root.dat'
if ($files -contains $target) {
    $remote = "ftp://$ftpHost//public_html/$target"
    $out = Join-Path (Get-Location) ("exports\$target")
    $wc = New-Object System.Net.WebClient
    $wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
    try { $wc.DownloadFile($remote,$out); Write-Host "Downloaded $target to $out"; exit 0 } catch { Write-Host "Download failed:" $_.Exception.Message; exit 4 }
} else {
    Write-Host "$target not found in public_html."; exit 5
}
