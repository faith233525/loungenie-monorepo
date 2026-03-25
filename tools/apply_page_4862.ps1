$content = Get-Content -Raw "tools/page_4862_about.html"
$pair = "{0}:{1}" -f $env:WP_USER, $env:WP_PASS
$auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
$body = @{ content = $content } | ConvertTo-Json -Depth 10
$uri = "$env:WP_SITE_URL/wp-json/wp/v2/pages/4862"
try {
    $resp = Invoke-RestMethod -Uri $uri -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $body -ErrorAction Stop
    Write-Output "Updated page 4862 -> status: $($resp.status) id: $($resp.id)"
}
catch {
    Write-Error "Failed to update page 4862: $($_.Exception.Message)"
    exit 1
}
