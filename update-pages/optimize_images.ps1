# WordPress Image Optimization Script
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$baseUrl = "https://www.loungenie.com"
$headers = @{ "User-Agent" = "Mozilla/5.0" }
if ($env:WP_USER -and $env:WP_APP_PASSWORD) {
    $pair = "$($env:WP_USER)`:$($env:WP_APP_PASSWORD)"
    $b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
    $headers.Authorization = "Basic $b64"
}

function Get-WPItemsPaged {
    param([string]$endpoint)
    $items = @()
    $page = 1
    while ($true) {
        $url = "${baseUrl}/wp-json/wp/v2/${endpoint}?per_page=100&page=${page}"
        try {
            $resp = Invoke-RestMethod -Uri $url -Headers $headers -ErrorAction Stop
        }
        catch {
            Write-Host "Request failed on page ${page}: $($_.Exception.Message)" -ForegroundColor Red
            break
        }
        if (-not $resp) { break }
        $items += $resp
        if ($resp.Count -lt 100) { break }
        $page++
    }
    return $items
}

# Get all media
$response = Get-WPItemsPaged -endpoint "media"
Write-Host "Processing $(@($response).Count) images..." -ForegroundColor Cyan

# Define image metadata improvements
$improvements = @{
    "IMG_2"      = "LounGenie product showcase image"
    "DSC"        = "LounGenie installation documentation photo"
    "cowabunga"  = "LounGenie deployment at water park"
    "grove"      = "LounGenie installation at resort"
    "hilton"     = "LounGenie amenity setup at Hilton"
    "screenshot" = "LounGenie system screenshot"
}

# Sample updates
$updates = 0
Write-Host "Sample Updates:"
foreach ($image in $response | Select-Object -First 10) {
    if (-not $image.alt_text -or $image.alt_text -eq "") {
        Write-Host "ID $($image.id): '$($image.title.rendered)' - ALT TEXT MISSING" -ForegroundColor Yellow
    }
}

