if (-not (Test-Path (Join-Path (Get-Location) 'exports'))) { New-Item -ItemType Directory -Path (Join-Path (Get-Location) 'exports') | Out-Null }
$urls = @(
    'https://loungenie.com/staging/wp-content/kadence-export-helper.php?t=export-kadence',
    'https://loungenie.com/wp-content/kadence-export-helper.php?t=export-kadence',
    'https://loungenie.com/kadence-export-helper.php?t=export-kadence'
)

foreach ($u in $urls) {
    Write-Host "Requesting $u"
    try {
        $r=Invoke-WebRequest -Uri $u -UseBasicParsing -TimeoutSec 30 -ErrorAction Stop
        $safe = ($u -replace '[:/\\?=]','_')
        $out = Join-Path (Get-Location) ("exports\\kadence_export_" + $safe + ".txt")
        $r.Content | Out-File $out -Encoding utf8
        Write-Host "Saved $out"
        break
    } catch {
        Write-Host "Request failed for $u :" $_.Exception.Message
    }
}

# Search and download any .dat via FTP
. .\tools\ftp_get_kadence_dat.ps1
