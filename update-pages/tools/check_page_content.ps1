try {
    . .\tools\session_workspace.ps1
} catch {
    Write-Error "Failed to load session workspace: $_"
    exit 1
}

try {
    $p = Invoke-RestMethod -Uri ("$env:WP_SITE_URL/wp-json/wp/v2/pages/4701") -Method Get -ErrorAction Stop
} catch {
    Write-Error "Failed to fetch page 4701: $($_.Exception.Message)"
    exit 2
}

if ($p.content.rendered -match 'lg9-marquee') {
    Write-Host 'MARQUEE FOUND in rendered page content' -ForegroundColor Green
} else {
    Write-Warning 'MARQUEE NOT FOUND in rendered page content — saving rendered HTML to exports/page_4701_content_rendered.html for inspection.'
    if (-not (Test-Path exports)) { New-Item exports -ItemType Directory | Out-Null }
    $p.content.rendered | Out-File -Encoding utf8 exports/page_4701_content_rendered.html
}
