$urls = @("https://loungenie.com/staging/wp-content/uploads/loungenie-home.html","https://loungenie.com/wp-content/uploads/loungenie-home.html","https://loungenie.com/staging/loungenie-home.html","https://loungenie.com/loungenie-home.html")
foreach ($u in $urls) {
    try {
        $r = Invoke-WebRequest $u -UseBasicParsing -ErrorAction Stop
        $out = (Split-Path -Leaf $u)
        $r.Content | Out-File -FilePath (".\exports\public_" + $out) -Encoding utf8
        Write-Host "Success: $u saved to exports\public_$out"
    } catch {
        Write-Host "Not found: $u"
    }
}
