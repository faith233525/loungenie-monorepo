. .\tools\session_workspace.ps1
try {
    $r = Invoke-WebRequest 'https://loungenie.com/staging/' -UseBasicParsing -ErrorAction Stop
    $r.Content | Out-File .\exports\trigger_home_request.html -Encoding utf8
    Write-Host 'Saved trigger_home_request.html'
} catch {
    Write-Error "Failed fetch: $($_.Exception.Message)"
}

$pair = "{0}:{1}" -f $env:WP_USER,$env:WP_PASS
$auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
$uri = "$env:WP_SITE_URL/wp-json/wp/v2/pages?per_page=100"
try {
    $pages = Invoke-RestMethod -Uri $uri -Method Get -Headers @{ Authorization = "Basic $auth" } -ErrorAction Stop
    foreach ($p in $pages) {
        $title = $p.title.rendered -replace "`n"," "
        Write-Host "id: $($p.id)  slug: $($p.slug)  status: $($p.status)  title: $title"
    }
} catch {
    Write-Error "Failed to list pages: $($_.Exception.Message)"
}
