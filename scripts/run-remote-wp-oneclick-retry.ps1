<#
Retry uploader/invoker for the one-click installer.
Tries both hostnames (with and without www), keeps installer until success,
and reports installer output. Uses env vars: FTP_HOST, FTP_USER, FTP_PASS, WP_SITE_URL
#>
Param()

$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS
$siteUrl = $env:WP_SITE_URL
if (-not ($ftpHost -and $ftpUser -and $ftpPass -and $siteUrl)) { Write-Error 'Required env vars: FTP_HOST, FTP_USER, FTP_PASS, WP_SITE_URL'; exit 1 }

function Gen-Password { -join (1..16 | ForEach-Object { ([char](Get-Random -Minimum 33 -Maximum 126)) }) }

$token = [guid]::NewGuid().ToString()
$adminUser = 'copilot_admin'
$adminPass = Gen-Password
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

$local = Join-Path (Get-Location) 'wp-oneclick-install-retry.php'
Set-Content -Path $local -Value $php -Encoding UTF8 -Force
Write-Host "Wrote local installer $local"

$remoteUri = "ftp://$ftpHost/public_html/staging_loungenie/wp-oneclick-install-retry.php"
$wc = New-Object System.Net.WebClient
$wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
try {
    $wc.UploadFile($remoteUri, $local) | Out-Null
    Write-Host "Uploaded installer to $remoteUri"
} catch {
    Write-Error "Upload failed: $($_.Exception.Message)"
    exit 1
}

# Try candidate hostnames
$hostsToTry = @()
if ($siteUrl -match '://www\.') { $hostsToTry += $siteUrl.TrimEnd('/') } else { $hostsToTry += $siteUrl.TrimEnd('/'); $hostsToTry += ('https://www.' + ($siteUrl -replace '^https?://','')) }

$success = $false
foreach ($candidate in $hostsToTry) {
    $url = $candidate + '/staging_loungenie/wp-oneclick-install-retry.php?t=' + $token
    Write-Host "Trying installer at: $url"
    try {
        $resp = Invoke-WebRequest -Uri $url -UseBasicParsing -SkipCertificateCheck -TimeoutSec 60
        Write-Host "HTTP status: $($resp.StatusCode)"
        $body = $resp.Content
        Write-Host "Response (truncated):"
        if ($body.Length -gt 4000) { Write-Host $body.Substring(0,4000); Write-Host '... (truncated)' } else { Write-Host $body }
        if ($resp.StatusCode -ge 200 -and $resp.StatusCode -lt 300 -and $body -match 'Installed\.|Already installed') {
            Write-Host "Installer succeeded or already installed on host $candidate"
            $success = $true
            break
        }
    } catch {
        Write-Warning "Request to $candidate failed: $($_.Exception.Message)"
    }
}

if (-not $success) {
    Write-Warning "Installer did not report success on any host. Keeping remote installer for manual retry."
    Write-Host "Remote installer path: $remoteUri (token not printed)"
    exit 2
}

# Delete remote installer if success
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

Write-Host "One-click retry finished. Admin user: $adminUser (password generated locally)."
