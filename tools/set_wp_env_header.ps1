# Sets FTP and WP env vars and computes WP_AUTH_HEADER (Base64)
$ErrorActionPreference = 'Stop'

$env:FTP_HOST = 'ftp.loungenie.com'
$env:FTP_USER = 'copilot@loungenie.com'
$env:FTP_PASS = 'LounGenie21!'
$env:WP_SITE_URL = 'https://loungenie.com'
$env:WP_USER = 'copilot'
$env:WP_APP_PASS = 'OvBU Tifi iSpG CeaG 10mk xoty'

$authBytes = [System.Text.Encoding]::UTF8.GetBytes("$($env:WP_USER):$($env:WP_APP_PASS)")
$env:WP_AUTH_HEADER = [Convert]::ToBase64String($authBytes)

Write-Host 'Environment variables set.'
Write-Host 'WP_AUTH_HEADER length:' $env:WP_AUTH_HEADER.Length
