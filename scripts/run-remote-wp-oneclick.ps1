<#
Uploads a one-time PHP installer to the staging site, invokes it to finish
the WordPress install and activate the portal theme/plugin, then deletes it.
Requires env vars: FTP_HOST, FTP_USER, FTP_PASS, WP_SITE_URL
#>

Param()

$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS
$siteUrl = $env:WP_SITE_URL
if (-not ($ftpHost -and $ftpUser -and $ftpPass -and $siteUrl)) { Write-Error 'Required env vars: FTP_HOST, FTP_USER, FTP_PASS, WP_SITE_URL'; exit 1 }

# Generate token and admin credentials
$token = [guid]::NewGuid().ToString()
$adminUser = 'copilot_admin'
$adminPass = [System.Web.Security.Membership]::GeneratePassword(16,2)
$adminEmail = 'admin@loungenie.com'

$php = "<?php\n"
$php += "if (!isset(\\$_GET['t']) || \\$_GET['t'] !== '$token') { http_response_code(403); echo 'Forbidden'; exit; }\n"
$php += "define('WP_INSTALLING', true);\n"
$php += "require_once __DIR__ . '/wp-load.php';\n"
$php += "if (function_exists('is_blog_installed') && is_blog_installed()) { echo 'Already installed'; exit; }\n"
$php += "if (!function_exists('wp_install')) { require_once ABSPATH . 'wp-admin/includes/upgrade.php'; }\n"
$php += "\$result = wp_install('Loungenie Staging', '$adminUser', '$adminEmail', true, '', '$adminPass');\n"
$php += "if (is_wp_error(\$result)) { echo 'Install error: '.\$result->get_error_message(); } else { echo 'Installed. Admin: $adminUser'; }\n"
$php += "if (function_exists('switch_theme')) { switch_theme('loungenie-portal-theme'); echo ' Theme activated.'; }\n"
$php += "if (function_exists('activate_plugin')) { activate_plugin('loungenie-portal-plugin/loungenie-portal-plugin.php'); echo ' Plugin activated.'; }\n"
$php += "?>"

$local = Join-Path (Get-Location) 'wp-oneclick-install.php'
Set-Content -Path $local -Value $php -Encoding UTF8 -Force
Write-Host "Wrote local installer $local"

$remoteUri = "ftp://$ftpHost/public_html/staging_loungenie/wp-oneclick-install.php"

$wc = New-Object System.Net.WebClient
$wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
try {
    $wc.UploadFile($remoteUri, $local) | Out-Null
    Write-Host "Uploaded installer to $remoteUri"
} catch {
    Write-Error "Upload failed: $($_.Exception.Message)"
    exit 1
}

# Invoke via HTTP
$url = $siteUrl.TrimEnd('/') + '/staging_loungenie/wp-oneclick-install.php?t=' + $token
Write-Host "Invoking installer: $url"
try {
    $resp = Invoke-WebRequest -Uri $url -UseBasicParsing -SkipCertificateCheck -TimeoutSec 120
    $body = $resp.Content
    Write-Host "Installer response:"
    Write-Host $body
} catch {
    Write-Error "Installer request failed: $($_.Exception.Message)"
}

# Delete remote file
try {
    $delReq = [System.Net.FtpWebRequest]::Create($remoteUri)
    $delReq.Method = [System.Net.WebRequestMethods+Ftp]::DeleteFile
    $delReq.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
    $delResp = $delReq.GetResponse()
    $delResp.Close()
    Write-Host "Deleted remote installer"
} catch {
    Write-Warning "Could not delete remote installer: $($_.Exception.Message)"
}

Write-Host "One-click install script finished. Admin user: $adminUser (password shown once above if install succeeded)."
