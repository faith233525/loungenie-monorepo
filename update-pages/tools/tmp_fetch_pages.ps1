$ids = @(4701,4862,2989,5223,5285,5139)
if (-not (Test-Path -Path ./.tmp)) { New-Item -ItemType Directory -Path ./.tmp | Out-Null }
foreach ($id in $ids) {
    try {
        $u = "https://loungenie.com/staging/wp-json/wp/v2/pages/$id"
        $p = Invoke-RestMethod -Uri $u -Method Get -ErrorAction Stop
        $json = $p | ConvertTo-Json -Depth 12
        Set-Content -Path ".\.tmp\page_$id.json" -Value $json -Encoding UTF8
        Write-Output "WROTE $id"
    } catch {
        Write-Output ("ERR {0}: {1}" -f $id, $_.Exception.Message)
    }
}
