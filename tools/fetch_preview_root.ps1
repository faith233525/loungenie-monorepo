try {
    $r = Invoke-WebRequest 'https://loungenie.com/loungenie-home-preview.php' -UseBasicParsing -ErrorAction Stop
    $r.Content | Out-File .\exports\preview_root_downloaded.html -Encoding utf8
    Write-Host 'Saved preview_root_downloaded.html'
} catch {
    Write-Host 'Fetch failed:'
    Write-Host $_.Exception.Message
}
