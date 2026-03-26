# Downloads WordPress latest, uploads to staging via FTP, and attempts non-interactive install
# Requires env vars: FTP_HOST, FTP_USER, FTP_PASS, WP_SITE_URL

Param()

$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS
$siteUrl = $env:WP_SITE_URL
if (-not ($ftpHost -and $ftpUser -and $ftpPass -and $siteUrl)) {
    Write-Error "Required env vars: FTP_HOST, FTP_USER, FTP_PASS, WP_SITE_URL"
    exit 1
}

$temp = Join-Path $env:TEMP "wp_upload_$(Get-Date -UFormat %s)"
New-Item -ItemType Directory -Path $temp | Out-Null
$zip = Join-Path $temp 'latest.zip'
$extract = Join-Path $temp 'wp'

Write-Host "Downloading WordPress latest.zip"
Invoke-WebRequest -Uri 'https://wordpress.org/latest.zip' -OutFile $zip -UseBasicParsing

Write-Host "Extracting"
Expand-Archive -Path $zip -DestinationPath $extract
$wpDir = Join-Path $extract 'wordpress'

# FTP base path
$remoteBase = "/public_html/staging_loungenie"
$baseUri = "ftp://$ftpHost"

function Ensure-FtpDir([string]$dir) {
    $parts = $dir.TrimStart('/').Split('/')
    $cur = ''
    foreach ($p in $parts) {
        $cur = $cur + '/' + $p
        $uri = "ftp://$ftpHost$cur"
        try {
            $req = [System.Net.FtpWebRequest]::Create($uri)
            $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
            $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
            $req.GetResponse() | Out-Null
            Write-Host "Created $uri"
        } catch {
            # directory may already exist; ignore
        }
    }
}

function Upload-FileToFtp([string]$local,[string]$remotePath) {
    $uri = "ftp://$ftpHost$remotePath"
    $wc = New-Object System.Net.WebClient
    $wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser,$ftpPass)
    Write-Host "Uploading $local -> $uri"
    $wc.UploadFile($uri, $local) | Out-Null
}

# Ensure remoteBase exists
Ensure-FtpDir $remoteBase

# Recursively upload files from $wpDir to $remoteBase
Get-ChildItem -Path $wpDir -Recurse -File | ForEach-Object {
    $rel = $_.FullName.Substring($wpDir.Length) -replace "^\\","/"
    $remotePath = "$remoteBase/$rel"
    $remoteDir = [System.IO.Path]::GetDirectoryName($remotePath) -replace '\\','/'
    Ensure-FtpDir $remoteDir
    Upload-FileToFtp $_.FullName $remotePath
}

Write-Host "Upload complete. Verifying install page"
$installUrl = $siteUrl.TrimEnd('/') + '/staging_loungenie/wp-admin/install.php'
try {
    $resp = Invoke-WebRequest -Uri $installUrl -UseBasicParsing -SkipCertificateCheck -Method Get
    if ($resp.StatusCode -eq 200 -and $resp.Content -match 'Welcome') {
        Write-Host "Install page reachable"
    } else {
        Write-Host "Install page fetched (status $($resp.StatusCode))"
    }
} catch {
    Write-Warning "Could not fetch install page: $($_.Exception.Message)"
}

# Attempt non-interactive install if WP config exists and install is pending
$adminUser = 'admin'
$adminPass = [System.Web.Security.Membership]::GeneratePassword(16,2)
$adminEmail = 'admin@loungenie.com'

# POST install
$installAction = $siteUrl.TrimEnd('/') + '/staging_loungenie/wp-admin/install.php?step=2'
$fields = @{ weblog_title = 'Loungenie Staging'; user_name = $adminUser; admin_password = $adminPass; admin_password2 = $adminPass; admin_email = $adminEmail }
try {
    $resp2 = Invoke-WebRequest -Uri $installAction -Method Post -Body $fields -UseBasicParsing -SkipCertificateCheck -AllowUnencryptedAuthentication
    if ($resp2.StatusCode -eq 200 -and $resp2.Content -match 'Success') {
        Write-Host "WordPress install appears successful. Admin: $adminUser"
    } else {
        Write-Host "Install POST returned status $($resp2.StatusCode). Check manually: $installUrl"
    }
} catch {
    Write-Warning "Install POST failed: $($_.Exception.Message)"
}

Write-Host "Cleaning up local temp"
Remove-Item -Recurse -Force $temp

Write-Host "Done. If install didn't finish, use Softaculous or cPanel installer."