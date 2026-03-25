param(
    [string]$Urls = 'https://loungenie.com/?t=export-kadence&k=export-kadence-token,https://loungenie.com/staging/?t=export-kadence&k=export-kadence-token'
)

$ErrorActionPreference = 'Stop'
if (-not (Test-Path (Join-Path (Get-Location) 'exports'))) { New-Item -ItemType Directory -Path (Join-Path (Get-Location) 'exports') | Out-Null }

$list = $Urls -split ','
foreach ($u in $list) {
    $u = $u.Trim()
    Write-Host "Requesting: $u"
    try {
        $r = Invoke-WebRequest -Uri $u -UseBasicParsing -TimeoutSec 60 -ErrorAction Stop
        $safe = ($u -replace '[:/\\?=]','_')
        $out = Join-Path (Get-Location) ("exports\\kadence_response_$safe.txt")
        $r.Content | Out-File -FilePath $out -Encoding utf8
        Write-Host "Saved: $out"
    } catch {
        $e = $_.Exception
        Write-Host "Request failed for $u"
        Write-Host "Type: " + $e.GetType().FullName
        Write-Host "Message: " + $e.Message
        if ($e.Response) {
            try { Write-Host "Response status:" + $e.Response.StatusCode.Value__ } catch { }
        }
    }
}
