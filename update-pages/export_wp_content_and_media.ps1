# PowerShell script to export WordPress posts, pages, and media to CSV
# Update these variables with your credentials and site info
$site = "https://www.loungenie.com"

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

function Get-WPItems {
    param(
        [string]$endpoint
    )
    $items = @()
    $page = 1
    while ($true) {
        $url = "${site}/wp-json/wp/v2/${endpoint}?per_page=100&page=${page}"
        try {
            $response = Invoke-RestMethod -Uri $url -ErrorAction Stop
        }
        catch {
            Write-Host "Request failed for $endpoint page ${page}: $($_.Exception.Message)" -ForegroundColor Red
            break
        }
        if (-not $response) { break }
        $items += $response
        if ($response.Count -lt 100) { break }
        $page++
    }
    return $items
}

# Export posts and pages
$posts = Get-WPItems -endpoint "posts"
$pages = Get-WPItems -endpoint "pages"

$posts | Select-Object @{n = 'type'; e = { 'post' } }, id, @{n = 'title'; e = { $_.title.rendered } }, slug, status, date, author, @{n = 'excerpt'; e = { $_.excerpt.rendered } }, @{n = 'content'; e = { $_.content.rendered } } | Export-Csv -Path "wp_posts.csv" -NoTypeInformation -Encoding UTF8
$pages | Select-Object @{n = 'type'; e = { 'page' } }, id, @{n = 'title'; e = { $_.title.rendered } }, slug, status, date, author, @{n = 'excerpt'; e = { $_.excerpt.rendered } }, @{n = 'content'; e = { $_.content.rendered } } | Export-Csv -Path "wp_pages.csv" -NoTypeInformation -Encoding UTF8

# Export media (images, etc.)
$media = Get-WPItems -endpoint "media"
$media | Select-Object id,
@{n = 'title'; e = { $_.title.rendered } },
slug,
@{n = 'alt_text'; e = { $_.alt_text } },
media_type,
mime_type,
source_url,
@{n = 'file'; e = { $_.media_details.file } },
@{n = 'filesize'; e = { $_.media_details.filesize } },
@{n = 'width'; e = { $_.media_details.width } },
@{n = 'height'; e = { $_.media_details.height } },
date,
author | Export-Csv -Path "wp_media.csv" -NoTypeInformation -Encoding UTF8

Write-Host "Export complete: wp_posts.csv, wp_pages.csv, wp_media.csv"
