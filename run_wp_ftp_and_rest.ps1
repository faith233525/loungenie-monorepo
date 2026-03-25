param(
    [string]$BaseUrl = "https://loungenie.com/staging",
    [string]$CsvPath = ".\bulk_posts.csv",
    [string]$FtpRemotePath = "/public_html/wp-content/uploads",
    [string]$FtpLocalBackup = ".\ftp_backup"
)

# Read environment or prompt for credentials
$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS
$wpUser  = $env:WP_USER
$wpPass  = $env:WP_PASS

function PromptIfMissing($name, [bool]$secret = $false) {
    $val = (Get-Item -Path "env:$name" -ErrorAction SilentlyContinue).Value
    if ($val) { return $val }
    if ($secret) {
        $s = Read-Host -AsSecureString "$name (enter)"
        $b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s)
        try { return [Runtime.InteropServices.Marshal]::PtrToStringAuto($b) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) }
    }
    else {
        return Read-Host "$name (enter)"
    }
}

if (-not $ftpHost) { $ftpHost = PromptIfMissing 'FTP_HOST' }
if (-not $ftpUser) { $ftpUser = PromptIfMissing 'FTP_USER' }
if (-not $ftpPass) { $ftpPass = PromptIfMissing 'FTP_PASS' -AsSecureString:$false }
if (-not $wpUser)  { $wpUser  = PromptIfMissing 'WP_USER' }
if (-not $wpPass)  { $wpPass  = PromptIfMissing 'WP_PASS' -AsSecureString:$false }

# DRY_RUN default true
$DryRun = $true
if ($env:DRY_RUN -and $env:DRY_RUN -in @('false','0')) { $DryRun = $false }

# Helpers
function New-FtpRequest([string]$uri, [string]$method, [bool]$useSsl) {
    $r = [System.Net.FtpWebRequest]::Create($uri)
    $r.Method = $method
    $r.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $r.UseBinary = $true
    $r.KeepAlive = $false
    $r.EnableSsl = $useSsl
    return $r
}

function Try-ListDirectory([string]$baseUri, [string]$remotePath) {
    $uri = ($baseUri.TrimEnd('/') + '/' + $remotePath.TrimStart('/'))
    try {
        $req = New-FtpRequest $uri ([System.Net.WebRequestMethods+Ftp]::ListDirectory) $false
        $resp = $req.GetResponse()
        $sr = New-Object System.IO.StreamReader($resp.GetResponseStream())
        $txt = $sr.ReadToEnd()
        $sr.Close(); $resp.Close()
        return ,($txt -split "\r?\n" | Where-Object { $_ -ne '' })
    } catch {
        return $null
    }
}

function Is-Directory([string]$baseUri, [string]$remotePath) {
    $r = Try-ListDirectory $baseUri $remotePath
    return ($r -ne $null)
}

function Download-File([string]$baseUri, [string]$remoteFile, [string]$localPath) {
    $uri = ($baseUri.TrimEnd('/') + '/' + $remoteFile.TrimStart('/'))
    if ($DryRun) { Write-Output "DRY_RUN would download: $remoteFile -> $localPath"; return $true }
    try {
        $req = New-FtpRequest $uri ([System.Net.WebRequestMethods+Ftp]::DownloadFile) $false
        $resp = $req.GetResponse()
        $stream = $resp.GetResponseStream()
        $dir = Split-Path $localPath -Parent
        if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
        $out = [System.IO.File]::OpenWrite($localPath)
        $buffer = New-Object byte[] 8192
        while (($read = $stream.Read($buffer, 0, $buffer.Length)) -gt 0) { $out.Write($buffer, 0, $read) }
        $out.Close(); $stream.Close(); $resp.Close()
        Write-Output "Downloaded: $remoteFile -> $localPath"
        return $true
    } catch {
        $msg = $_.Exception.Message
        Write-Warning "Download failed for $remoteFile : $msg"
        return $false
    }
}

function Download-DirectoryRecursive([string]$baseUri, [string]$remotePath, [string]$localPath) {
    $entries = Try-ListDirectory $baseUri $remotePath
    if ($entries -eq $null) { Write-Warning "Could not list directory: $remotePath"; return }
    foreach ($e in $entries) {
        $name = $e.Trim()
        if ($name -in @('.', '..')) { continue }
        $remoteItem = ($remotePath.TrimEnd('/') + '/' + $name).TrimStart('/')
        # normalize
        $remoteItem = $remoteItem -replace '/{2,}','/'
        if (Is-Directory $baseUri $remoteItem) {
            Write-Output "Entering directory: $remoteItem"
            $subLocal = Join-Path $localPath $name
            Download-DirectoryRecursive $baseUri $remoteItem $subLocal
        } else {
            $localFile = Join-Path $localPath $name
            Download-File $baseUri $remoteItem $localFile | Out-Null
        }
    }
}

# Start
Write-Output "Starting FTP backup from host: $ftpHost"
if (-not $ftpHost) { Write-Error "FTP_HOST not provided"; exit 1 }
$baseUri = $ftpHost
if ($baseUri -notmatch '^(ftp|ftps)://') { $baseUri = "ftp://$baseUri" }

Write-Output "Dry run: $DryRun"
Write-Output "Remote path: $FtpRemotePath"
Write-Output "Local backup root: $FtpLocalBackup"

if (-not (Test-Path $FtpLocalBackup)) { New-Item -ItemType Directory -Path $FtpLocalBackup -Force | Out-Null }

try {
    Download-DirectoryRecursive $baseUri $FtpRemotePath $FtpLocalBackup
    Write-Output "FTP backup finished."
} catch {
    Write-Error "Backup encountered error: $_"
}

# Quick check WordPress REST connectivity
if ($wpUser -and $wpPass) {
    try {
        $pair = ($wpUser + ":" + $wpPass)
        $enc = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
        $hdr = @{ Authorization = "Basic $enc" }
        $resp = Invoke-RestMethod -Uri "$BaseUrl/wp-json/" -Headers $hdr -Method Get -ErrorAction Stop
        Write-Output "WordPress REST OK: $($resp.name)"
    } catch {
        Write-Warning "WordPress REST check failed: $($_.Exception.Message)"
    }
} else {
    Write-Warning "WP credentials missing; skipping REST check."
}
