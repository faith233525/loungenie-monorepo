. .\tools\session_workspace.ps1
try {
    $r = Invoke-WebRequest 'https://loungenie.com/staging/wp-content/mu-plugins/block.html' -UseBasicParsing -ErrorAction Stop
    $r.Content | Out-File -FilePath .\exports\remote_mu_block.html -Encoding utf8
    Write-Host 'Saved remote MU block.html to exports/remote_mu_block.html'
} catch {
    Write-Host 'Failed fetching remote mu-plugins block.html'
    Write-Host $_.Exception.Message
}
