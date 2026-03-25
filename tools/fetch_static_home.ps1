try {
    $r = Invoke-WebRequest 'https://loungenie.com/staging/wp-content/uploads/loungenie-home.html' -UseBasicParsing -ErrorAction Stop
    $r.Content | Out-File .\exports\static_home_downloaded.html -Encoding utf8
    Write-Host 'Saved static_home_downloaded.html'
} catch {
    Write-Host 'Fetch failed:'
    Write-Host $_.Exception.Message
}
