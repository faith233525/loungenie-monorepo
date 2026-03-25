param(
    [int]$PageId = 4701,
    [string]$HtmlFile = '.\tools\page_4701_home.html'
)

. .\tools\session_workspace.ps1

if (-not (Test-Path $HtmlFile)) { throw "HtmlFile missing: $HtmlFile" }
$content = Get-Content -Raw $HtmlFile
$pair = "{0}:{1}" -f $env:WP_USER, $env:WP_PASS
$auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
$uri = "$env:WP_SITE_URL/wp-json/wp/v2/pages/$PageId"
$body = @{ content = $content } | ConvertTo-Json -Depth 10
try {
    $resp = Invoke-RestMethod -Uri $uri -Method Put -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $body -ErrorAction Stop
    Write-Host "Updated page $PageId -> id: $($resp.id) status: $($resp.status)" -ForegroundColor Green
    $resp | ConvertTo-Json | Out-File .\exports\("page_${PageId}_update_response.json") -Encoding utf8
} catch {
    Write-Error ("Failed to update page {0}: {1}" -f $PageId, $_.Exception.Message)
}
