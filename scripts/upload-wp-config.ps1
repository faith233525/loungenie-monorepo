Param(
    [string]$StagingRemotePath = '/public_html/staging_loungenie',
    [string]$LocalSample = '..\loungenie-monorepo\wp-config-sample.php'
)

# This script replaces placeholders in wp-config-sample.php with environment variables
# and uploads the result as wp-config.php to the staging path via FTP.

if (-not $env:FTP_HOST -or -not $env:FTP_USER -or -not $env:FTP_PASS) {
    Write-Error "FTP_HOST, FTP_USER and FTP_PASS environment variables are required"
    exit 1
}

$localSamplePath = Join-Path (Get-Location) $LocalSample
if (-not (Test-Path $localSamplePath)) { Write-Error "Sample file not found: $localSamplePath"; exit 1 }

$content = Get-Content $localSamplePath -Raw

function ReplaceOrDefault($key, $default) {
    $v = (Get-Item -Path Env:$key -ErrorAction SilentlyContinue).Value
    if ([string]::IsNullOrEmpty($v)) { return $default } else { return $v }
}

$dbName = ReplaceOrDefault 'STAGING_DB_NAME' 'database_name_here'
$dbUser = ReplaceOrDefault 'STAGING_DB_USER' 'username_here'
$dbPass = ReplaceOrDefault 'STAGING_DB_PASS' 'password_here'
$dbHost = ReplaceOrDefault 'STAGING_DB_HOST' 'localhost'

$content = $content -replace "define\('DB_NAME', '.*'\);", "define('DB_NAME', '$dbName');"
$content = $content -replace "define\('DB_USER', '.*'\);", "define('DB_USER', '$dbUser');"
$content = $content -replace "define\('DB_PASSWORD', '.*'\);", "define('DB_PASSWORD', '$dbPass');"
$content = $content -replace "define\('DB_HOST', '.*'\);", "define('DB_HOST', '$dbHost');"

$temp = New-Item -ItemType File -Path (Join-Path $env:TEMP 'wp-config-staging.php') -Force
Set-Content -Path $temp.FullName -Value $content -Encoding UTF8

Write-Host "Uploading wp-config.php to $StagingRemotePath via FTP..."

$ftpUri = "ftp://$($env:FTP_HOST)$StagingRemotePath/wp-config.php"

$fileStream = [System.IO.File]::OpenRead($temp.FullName)
$req = [System.Net.FtpWebRequest]::Create($ftpUri)
$req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
$req.Credentials = New-Object System.Net.NetworkCredential($env:FTP_USER, $env:FTP_PASS)
$req.UseBinary = $true
$req.UsePassive = $true
$req.KeepAlive = $false

[byte[]]$buffer = New-Object byte[] 4096
while (($read = $fileStream.Read($buffer, 0, $buffer.Length)) -gt 0) {
    $req.GetRequestStream().Write($buffer, 0, $read)
}
$fileStream.Close()

try {
    $resp = $req.GetResponse()
    Write-Host "Upload finished: $($resp.StatusDescription)"
    $resp.Close()
} catch {
    Write-Error "Upload failed: $_"
}

Remove-Item $temp.FullName -Force
