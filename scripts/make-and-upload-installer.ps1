param()

$token = [guid]::NewGuid().ToString()
$admin_user = 'copilot_admin'
$rnd = -join ((48..122) | Get-Random -Count 16 | ForEach-Object {[char]$_})
$admin_pass = $rnd
$admin_email = 'admin@loungenie.com'
$site_title = 'Staging Loungenie'
$local = Join-Path $PWD 'wp-oneclick-install-2.php'

$php = @'
<?php
$token = '{TOKEN}';
if(!isset($_GET['t']) || $_GET['t'] !== $token){ http_response_code(403); echo 'Forbidden'; exit;}
define('WP_INSTALLING', true);
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
if ( !function_exists('wp_install') ) { require_once ABSPATH . 'wp-admin/includes/upgrade.php'; }
try {
  $result = wp_install('{SITE_TITLE}', '{ADMIN_USER}', '{ADMIN_EMAIL}', true, '', '{ADMIN_PASS}');
  echo 'Installed';
} catch (Exception $e) {
  http_response_code(500);
  echo 'Error: ' . $e->getMessage();
}
?>
'@

$php = $php -replace '{TOKEN}', $token
$php = $php -replace '{SITE_TITLE}', $site_title
$php = $php -replace '{ADMIN_USER}', $admin_user
$php = $php -replace '{ADMIN_EMAIL}', $admin_email
$php = $php -replace '{ADMIN_PASS}', $admin_pass

Set-Content -Path $local -Value $php -Encoding UTF8
Write-Host "Wrote local installer $local"

$uri = "ftp://$env:FTP_HOST/public_html/staging_loungenie/wp-oneclick-install-2.php"
$wc = New-Object System.Net.WebClient
$wc.Credentials = New-Object System.Net.NetworkCredential($env:FTP_USER,$env:FTP_PASS)
$wc.UploadFile($uri,'STOR',$local)
Write-Host "Uploaded installer to $uri"

$ip = ([System.Net.Dns]::GetHostAddresses('www.loungenie.com') | Where-Object { $_.AddressFamily -eq 'InterNetwork' } | Select-Object -First 1)
if(-not $ip){ $ip = ([System.Net.Dns]::GetHostAddresses('loungenie.com') | Where-Object { $_.AddressFamily -eq 'InterNetwork' } | Select-Object -First 1) }
if($ip){ $ip = $ip.ToString() } else { Write-Host 'Could not resolve domain to IP'; exit 1 }
Write-Host "Using IP: $ip"

$call = "http://$ip/staging_loungenie/wp-oneclick-install-2.php?t=$token"
Write-Host "Calling $call with Host header www.loungenie.com"

$respFile = Join-Path $PWD 'installer_response.txt'
$rcmd = "curl.exe -s -k -H 'Host: www.loungenie.com' -H 'Cache-Control: no-cache' -L '$call' -w '\nHTTP_CODE:%{http_code}' -o '$respFile'"
Write-Host $rcmd
Invoke-Expression $rcmd
Write-Host "Response saved to $respFile"
Write-Host "Admin user: $admin_user"
Write-Host "Admin password: $admin_pass"
Write-Host "Token: $token"
Write-Host "Installer path: $uri"
Write-Host "Response file: $respFile"
