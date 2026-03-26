$url = 'https://loungenie.com/stage/'
Write-Output "Fetching $url"
try {
  $resp = Invoke-WebRequest -Uri $url -UseBasicParsing -ErrorAction Stop
  $html = $resp.Content
  $patterns = @('lg9-shell-inline','lg-block-patterns','lg9-site','lg9')
  $found = $false
  foreach ($p in $patterns) {
    $matches = Select-String -InputObject $html -Pattern $p -AllMatches
    if ($matches) {
      Write-Output "Matches for pattern '$p':"
      $matches | ForEach-Object { Write-Output $_.Line }
      $found = $true
    }
  }
  if (-not $found) { Write-Output 'No LG9 markers found in homepage HTML' }
} catch {
  Write-Warning "Failed to fetch homepage: $_"
}
