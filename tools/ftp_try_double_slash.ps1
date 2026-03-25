# Attempts FTP download using relative and double-slash absolute paths (HostPapa chroot fix)
$ErrorActionPreference = 'Stop'
if (-not (Test-Path -Path (Join-Path (Get-Location) 'exports'))) {
    New-Item -Path (Join-Path (Get-Location) 'exports') -ItemType Directory | Out-Null
}

$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS

Write-Host "Using FTP_HOST=$ftpHost"

$uris = @(
    "ftp://$ftpHost/wp-content/uploads/loungenie-mu-run.txt",
    "ftp://$ftpHost//public_html/wp-content/uploads/loungenie-mu-run.txt"
)

foreach ($u in $uris) {
    Write-Host "Trying $u"
    try {
        $wc = New-Object System.Net.WebClient
        $wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $safeName = ($u -replace '[:\\/]+', '_') -replace '^_+'
        $out = Join-Path (Get-Location) ("exports\mu_run_$safeName.txt")
        $wc.DownloadFile($u, $out)
        Write-Host "Downloaded via $u -> $out"
        exit 0
    } catch {
        Write-Host "Failed: $u -> $($_.Exception.Message)"
    } finally {
        if ($wc) { $wc.Dispose() }
    }
}

Write-Host "All attempts failed."
exit 1
