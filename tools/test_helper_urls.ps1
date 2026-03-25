$urls=@('https://loungenie.com/kadence-export-helper.php?t=export-kadence','https://loungenie.com/staging/kadence-export-helper.php?t=export-kadence')
if (-not (Test-Path (Join-Path (Get-Location) 'exports'))) { New-Item -ItemType Directory -Path (Join-Path (Get-Location) 'exports') | Out-Null }
foreach ($u in $urls) {
    Write-Host "Requesting $u"
    try {
        $r=Invoke-WebRequest -Uri $u -UseBasicParsing -TimeoutSec 30 -ErrorAction Stop
        $status=$r.StatusCode
        $len=($r.Content).Length
        Write-Host "OK $status length:$len"
        $fn = Join-Path (Get-Location) ('exports\\kadence_resp_' + ($u -replace '[:/\\?=]','_') + '.txt')
        $r.Content | Out-File $fn -Encoding utf8
        Write-Host "Saved $fn"
    } catch {
        Write-Host "Failed: $($_.Exception.Message)"
    }
}
