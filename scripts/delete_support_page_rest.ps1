param(
    [string]$Slug = "support"
)

if (-not $env:WP_SITE_URL) {
    Write-Error "WP_SITE_URL is not set. Please set it to your WordPress site URL (e.g., https://loungenie.com/stage)."
    exit 1
}

if (-not $env:WP_REST_USER -or -not $env:WP_REST_PASS) {
    Write-Error "WP_REST_USER or WP_REST_PASS is not set. Set your REST credentials before running this script."
    exit 1
}

$pair = "{0}:{1}" -f $env:WP_REST_USER, $env:WP_REST_PASS
$bytes = [Text.Encoding]::ASCII.GetBytes($pair)
$auth  = [Convert]::ToBase64String($bytes)
$headers = @{ Authorization = "Basic $auth" }

Write-Host "Looking up page with slug '$Slug' on $env:WP_SITE_URL..."

try {
    $page = Invoke-RestMethod -Uri "$env:WP_SITE_URL/wp-json/wp/v2/pages?slug=$Slug" -Headers $headers -Method Get -ErrorAction Stop
} catch {
    Write-Error "Failed to query pages: $($_.Exception.Message)"
    exit 1
}

if (-not $page -or -not $page[0].id) {
    Write-Host "No page found for slug '$Slug'. Nothing to delete."
    exit 0
}

$id = $page[0].id
Write-Host "Deleting page id $id (slug '$Slug') on $env:WP_SITE_URL..."

try {
    $deleted = Invoke-RestMethod -Uri "$env:WP_SITE_URL/wp-json/wp/v2/pages/$id?force=true" -Headers $headers -Method Delete -ErrorAction Stop
} catch {
    $msg = $_.Exception.Message
    Write-Error ("Failed to delete page id {0}: {1}" -f $id, $msg)
    exit 1
}

Write-Host ("Deleted page id {0} (slug '{1}') on {2}." -f $id, $Slug, $env:WP_SITE_URL)
