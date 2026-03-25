<#
Search common uploads folders for Kadence `.dat` export files and download the first match.
Tries both relative and double-slash absolute paths to handle HostPapa chroot.
Saves result into the local `exports/` folder.
#>
$ErrorActionPreference = 'Stop'
if (-not (Test-Path -Path (Join-Path (Get-Location) 'exports'))) {
    New-Item -Path (Join-Path (Get-Location) 'exports') -ItemType Directory | Out-Null
}

$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS

if (-not $ftpHost) { Throw 'FTP_HOST not set in environment.' }

$candidates = @(
    '/wp-content/uploads/kadence-library',
    '/wp-content/uploads/kadence',
    '/wp-content/uploads',
    '/wp-content'
)

function ListFtpDir($uri) {
    try {
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
        $req.UsePassive = $true
        $req.UseBinary = $true
        $resp = $req.GetResponse()
        $sr = New-Object System.IO.StreamReader $resp.GetResponseStream()
        $list = @()
        while (-not $sr.EndOfStream) { $list += $sr.ReadLine() }
        $sr.Close(); $resp.Close()
        return $list
    } catch {
        return $null
    }
}

foreach ($c in $candidates) {
    foreach ($prefix in @('', '//public_html')) {
        $path = ($prefix + $c).TrimEnd('/')
        $uri = "ftp://$ftpHost$path/"
        Write-Host "Listing $uri"
        $list = ListFtpDir $uri
        if ($list -and $list.Count -gt 0) {
            $dat = $list | Where-Object { $_ -match '\.dat$' -or $_ -match 'kadence' } | Select-Object -First 1
            if ($dat) {
                $remoteFile = $uri + $dat
                $safeName = ($dat -replace '[^a-zA-Z0-9._-]', '_')
                $out = Join-Path (Get-Location) ("exports\$safeName")
                Write-Host "Found: $remoteFile`nDownloading to $out"
                $wc = New-Object System.Net.WebClient
                $wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
                try { $wc.DownloadFile($remoteFile, $out); Write-Host 'Download complete.'; exit 0 } catch { Write-Host 'Download failed:' $_.Exception.Message }
            }
        }
    }
}

Write-Host 'No .dat/Kadence export found in candidate locations.'
exit 2
