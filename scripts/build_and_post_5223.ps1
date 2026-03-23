# Build JSON payload from Kadence HTML and POST to WP REST API
$content = Get-Content -Raw -Path 'gallery_5223_kadence.html'
$payload = @{ content = @{ raw = $content } } | ConvertTo-Json -Depth 20
$payload | Out-File post_gallery_5223_kadence.json -Encoding utf8
Write-Output "payload created: post_gallery_5223_kadence.json"

# POST using curl via cmd to avoid PowerShell @file parsing
$curlCmd = 'cmd /c curl -s -u "copilot:COPILOT_APP_PASS" -X POST -H "Content-Type: application/json" --data @post_gallery_5223_kadence.json "https://loungenie.com/staging/wp-json/wp/v2/pages/5223" -o gallery_5223_update_result.json -w "%{http_code}"'
Write-Output "Running: $curlCmd"
Invoke-Expression $curlCmd
Write-Output "curl finished"
