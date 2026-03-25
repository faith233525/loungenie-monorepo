param(
  [string] $BaseUrl = $env:STAGING_URL
)

if (-not $BaseUrl) { Write-Error "STAGING_URL not provided via env or parameter"; exit 2 }

if (-not $env:WP_USER -or -not $env:WP_PASS) { Write-Error "WP_USER/WP_PASS missing"; exit 2 }

function Get-AuthHeader($user, $pass) {
    $bytes = [System.Text.Encoding]::UTF8.GetBytes("$user`:$pass")
    $b64 = [Convert]::ToBase64String($bytes)
    return @{ Authorization = "Basic $b64"; "Content-Type" = "application/json" }
}

$patchDir = Join-Path (Get-Location) 'content\patches'
if (-not (Test-Path $patchDir)) { Write-Output "No patches directory found at $patchDir"; exit 0 }

$creds = @{ user = $env:WP_USER; pass = $env:WP_PASS }
$hdr = Get-AuthHeader $creds.user $creds.pass

Get-ChildItem -Path $patchDir -Filter *.json | ForEach-Object {
    $file = $_.FullName
    try {
        $json = Get-Content $file -Raw | ConvertFrom-Json
    } catch {
        Write-Warning "Skipping invalid JSON: $file"; return
    }
    if (-not $json.slug) { Write-Warning "Patch missing slug: $file"; return }
    if ($json.slug -match 'investors') { Write-Output "Skipping investors page: $($json.slug)"; return }

    Write-Output "Applying patch for slug: $($json.slug)"
    $findUrl = "$BaseUrl/wp-json/wp/v2/pages?slug=$($json.slug)"
    $found = Invoke-RestMethod -Uri $findUrl -Method Get -Headers $hdr -ErrorAction SilentlyContinue
    if (-not $found -or $found.Count -eq 0) {
        Write-Warning "Page not found for slug $($json.slug) - creating draft"
        $body = @{ title = $json.title; content = $json.content; status = ($json.status ? $json.status : 'draft') } | ConvertTo-Json
        Invoke-RestMethod -Uri "$BaseUrl/wp-json/wp/v2/pages" -Method Post -Headers $hdr -Body $body
    } else {
        $id = $found[0].id
        $body = @{}
        if ($json.title) { $body.title = $json.title }
        if ($json.content) { $body.content = $json.content }
        if ($json.status) { $body.status = $json.status }
        if ($body.Keys.Count -eq 0) { Write-Warning "No updatable fields in $file"; return }
        $bodyJson = $body | ConvertTo-Json
        $updateUrl = "$BaseUrl/wp-json/wp/v2/pages/$id"
        Invoke-RestMethod -Uri $updateUrl -Method Post -Headers $hdr -Body $bodyJson
        Write-Output "Updated page id $id (slug: $($json.slug))"
    }
}

Write-Output "Patches complete"
