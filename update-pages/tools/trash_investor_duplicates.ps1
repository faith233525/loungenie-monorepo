<##
.SYNOPSIS
Trash non-published duplicate pages that match the Investors page title (ID 5668).
##>

if (-not $env:WP_SITE_URL -or -not $env:WP_USER -or -not $env:WP_PASS) {
    Write-Error "Please set WP_SITE_URL, WP_USER, WP_PASS environment variables before running."
    exit 1
}

try {
    $auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes("$($env:WP_USER):$($env:WP_PASS)"))
    $investorId = 5668
    Write-Host "Fetching investors page ID $investorId..."
    $page = Invoke-RestMethod -Uri "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/$investorId" -Headers @{ Authorization = "Basic $auth" } -Method Get -ErrorAction Stop
    $title = $page.title.rendered
    Write-Host "Investors page title: $title"

    Write-Host "Searching for pages matching title..."
    $searchUri = "$($env:WP_SITE_URL)/wp-json/wp/v2/pages?search=$([uri]::EscapeDataString($title))&per_page=50"
    $matches = Invoke-RestMethod -Uri $searchUri -Headers @{ Authorization = "Basic $auth" } -Method Get -ErrorAction Stop

    foreach ($m in $matches) {
        if ($m.id -ne $investorId -and $m.status -ne 'publish') {
            Write-Host "Trashing duplicate: id=$($m.id) status=$($m.status) title=$($m.title.rendered)"
            Invoke-RestMethod -Uri "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/$($m.id)" -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body (@{ status = 'trash' } | ConvertTo-Json) -ErrorAction Stop
        } else {
            Write-Host "Keeping page id=$($m.id) status=$($m.status)"
        }
    }
    Write-Host "Duplicate cleanup complete."
} catch {
    Write-Error "Error during duplicate cleanup: $($_.Exception.Message)"
    exit 1
}
