$pair = "{0}:{1}" -f $env:WP_USER, $env:WP_PASS
$auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
$body = @{ show_on_front = "page"; page_on_front = 4701 } | ConvertTo-Json -Depth 10
$uri = "$env:WP_SITE_URL/wp-json/wp/v2/settings"
try {
    $resp = Invoke-RestMethod -Uri $uri -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $body -ErrorAction Stop
    Write-Output "Updated settings -> show_on_front: $($resp.show_on_front) page_on_front: $($resp.page_on_front)"
}
catch {
    Write-Error "Failed to update settings: $($_.Exception.Message)"
    exit 1
}
