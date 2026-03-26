 $wp_user = $env:WP_REST_USER
 $wp_pass = $env:WP_REST_PASS
 $site = $env:WP_SITE_URL
if (-not $site -or $site -eq '') { $site = 'https://loungenie.com/stage' }

Write-Output "Using site: $site"
Write-Output "Notifying LiteSpeed to rebuild CSS at $site/wp-json/litespeed/v1/notify_ccss"
try {
  $cred = New-Object System.Management.Automation.PSCredential($wp_user,(ConvertTo-SecureString $wp_pass -AsPlainText -Force))
  Invoke-RestMethod -Uri "$site/wp-json/litespeed/v1/notify_ccss" -Method Post -Credential $cred -ErrorAction Stop
  Write-Output "Notify request succeeded"
} catch {
  Write-Warning "Notify request failed: $_"
}

try {
  $resp = Invoke-WebRequest -Uri "$site/" -UseBasicParsing -ErrorAction Stop
  $html = $resp.Content
  if ($html -match 'lg9-shell-inline') {
    Write-Output 'Inline CSS present in homepage HTML'
  } else {
    Write-Output 'Inline CSS NOT present in homepage HTML'
  }
} catch {
  Write-Warning "Failed to fetch homepage: $_"
}
