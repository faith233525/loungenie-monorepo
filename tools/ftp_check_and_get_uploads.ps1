<#
Checks the site's uploads directory via FTP (HostPapa double-slash) and downloads any .dat found.
Also reports any helper PHP files matching kadence-export/helper patterns.
#>
$ErrorActionPreference = 'Stop'

$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS

if (-not $ftpHost) { Write-Host 'FTP_HOST not set'; exit 2 }

$uri = "ftp://$ftpHost//public_html/wp-content/uploads/"
Write-Host "Listing $uri"

try {
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
    $req.UsePassive = $true
    $req.UseBinary = $true
    $resp = $req.GetResponse()
    $sr = New-Object System.IO.StreamReader $resp.GetResponseStream()
    $files = @()
    while (-not $sr.EndOfStream) { $files += $sr.ReadLine() }
    $sr.Close(); $resp.Close()
} catch {
    Write-Host "FTP list failed:" $_.Exception.Message
    exit 3
}

if (-not $files -or $files.Count -eq 0) { Write-Host 'No files returned from uploads.'; exit 4 }

Write-Host "Found files count: $($files.Count)"
$files | ForEach-Object { Write-Host " - " $_ }

$dat = $files | Where-Object { $_ -like '*.dat' } | Select-Object -First 1
if ($dat) {
    Write-Host "Found .dat: $dat -- downloading..."
    $out = Join-Path (Get-Location) ("exports\$dat")
    $remote = "ftp://$ftpHost//public_html/wp-content/uploads/$dat"
    $wc = New-Object System.Net.WebClient
    $wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
    try { $wc.DownloadFile($remote,$out); Write-Host "Downloaded to $out"; exit 0 } catch { Write-Host "Download failed:" $_.Exception.Message; exit 5 }
}

Write-Host 'No .dat found. Searching for helper PHP files.'
$helpers = $files | Where-Object { $_ -match 'kadence' -and $_ -match '\.php$' }
if ($helpers) { Write-Host 'Helpers:'; $helpers | ForEach-Object { Write-Host " - $_" }; exit 6 }

Write-Host 'No relevant files found in uploads.'
exit 7
