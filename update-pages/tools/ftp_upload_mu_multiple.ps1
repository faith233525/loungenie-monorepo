<#
Upload MU-plugin to multiple candidate mu-plugins paths to ensure it runs for the correct WP install.
#>
$ErrorActionPreference = 'Stop'

$local = 'build\mu-plugins\kadence_export_mu.php'
$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS

if (-not (Test-Path $local)) { Throw "Local file not found: $local" }
if (-not $ftpHost) { Throw 'FTP_HOST not set' }

$targets = @(
    "//public_html/wp-content/mu-plugins/kadence_export_mu.php",
    "//public_html/loungenie.com/wp-content/mu-plugins/kadence_export_mu.php",
    "//public_html/loungenie/wp-content/mu-plugins/kadence_export_mu.php",
    "//public_html/wordpress/wp-content/mu-plugins/kadence_export_mu.php"
)

foreach ($t in $targets) {
    $remote = "ftp://$ftpHost$t"
    Write-Host "Uploading to $remote"
    $wc = New-Object System.Net.WebClient
    $wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
    try { $wc.UploadFile($remote, $local); Write-Host 'OK' } catch { Write-Host 'Failed:' $_.Exception.Message }
}
