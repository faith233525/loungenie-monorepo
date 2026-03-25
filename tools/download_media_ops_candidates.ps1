$ErrorActionPreference='Stop'
$ftpHost=$env:FTP_HOST; $ftpUser=$env:FTP_USER; $ftpPass=$env:FTP_PASS
$candidates=@(
    '/public_html/wp-content/uploads/media_ops_results.json',
    '/public_html/media_ops_results.json',
    '/wp-content/uploads/media_ops_results.json',
    '/media_ops_results.json'
)
if (-not (Test-Path (Join-Path (Get-Location) 'exports'))) { New-Item -ItemType Directory -Path (Join-Path (Get-Location) 'exports') | Out-Null }
foreach ($c in $candidates) {
    $src = "ftp://$ftpHost$($c)"
    $out = Join-Path (Get-Location) ("exports\\media_ops_results" + ($c -replace '[^a-zA-Z0-9]','_') + ".json")
    try {
        Write-Host "Trying $src -> $out"
        $wc = New-Object System.Net.WebClient
        $wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
        $wc.DownloadFile($src,$out)
        Write-Host "Downloaded to $out"; exit 0
    } catch {
        Write-Host "Failed:" $_.Exception.Message
    }
}
Write-Host 'No candidate downloaded.'; exit 2
