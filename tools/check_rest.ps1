try {
    . .\tools\session_workspace.ps1
} catch {
    Write-Error "Failed to load session workspace: $_"
    exit 1
}

if (-not $env:WP_SITE_URL) { Write-Error "WP_SITE_URL not set"; exit 2 }

$root = "$($env:WP_SITE_URL)/wp-json/"
Write-Host "Checking REST root: $root"
try {
    $r = Invoke-RestMethod -Uri $root -Method Get -ErrorAction Stop
    Write-Host "REST root reachable. Namespaces: $($r.namespaces -join ', ')" -ForegroundColor Green
} catch {
    Write-Error "REST root unreachable: $($_.Exception.Message)"
}

$pagesUri = "$($env:WP_SITE_URL)/wp-json/wp/v2/pages?per_page=5"
Write-Host "Checking pages endpoint: $pagesUri"
try {
    $p = Invoke-RestMethod -Uri $pagesUri -Method Get -ErrorAction Stop
    Write-Host "Pages endpoint OK; returned $($p.Count) items (first slug: $($p[0].slug -as [string]))" -ForegroundColor Green
} catch {
    Write-Error "Pages endpoint error: $($_.Exception.Message)"
}
