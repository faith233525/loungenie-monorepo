# Apply About template to page 4862
if (-not $env:WP_SITE_URL -or -not $env:WP_USER -or -not $env:WP_PASS) {
    Write-Error "Set WP_SITE_URL, WP_USER, WP_PASS before running"
    exit 1
}
$txt = Get-Content -Raw -Path .\tools\mass_update_pages.ps1
$pattern = "'about'\s*\{\s*return @'(?s)(.*?)'@"
$m = [regex]::Match($txt, $pattern, [Text.RegularExpressions.RegexOptions]::Singleline)
if (-not $m.Success) { Write-Error "about template not found in mass_update_pages.ps1"; exit 1 }
$content = $m.Groups[1].Value
$auth = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("$($env:WP_USER):$($env:WP_PASS)"))
$uri = "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/4862"
$body = @{ content = $content } | ConvertTo-Json -Depth 8
try {
    $resp = Invoke-RestMethod -Uri $uri -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type'='application/json' } -Body $body -ErrorAction Stop
    Write-Output "Applied about page 4862 -> status: $($resp.status) id: $($resp.id)"
} catch {
    Write-Error "Failed to apply about page: $($_.Exception.Message)"
    exit 1
}
