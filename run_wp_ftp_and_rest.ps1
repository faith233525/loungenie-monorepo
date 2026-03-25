param(
    [string] $BaseUrl = "https://loungenie.com/staging",
    [string] $CsvPath = ".\bulk_posts.csv",
    [string] $FtpRemotePath = "/public_html/wp-content/uploads",
    [string] $FtpLocalBackup = ".\ftp_backup"
)

function Read-CredentialInteractive([string]$promptUser = "Username", [bool]$AllowPrompt = $true){
    # 1) env vars
    $user = $env:WP_USER
    $pass = $env:WP_PASS
    if ($user -and $pass) { Write-Output "Using WP credentials from environment"; return @{ user = $user; pass = $pass } }

    # 2) Windows Credential Manager (requires CredentialManager module)
    if (Get-Module -ListAvailable -Name CredentialManager) {
        try {
            Import-Module CredentialManager -ErrorAction SilentlyContinue
            $stored = Get-StoredCredential -Target "WP" -ErrorAction SilentlyContinue
            if ($stored) { Write-Output "Using WP credentials from Windows Credential Manager (target=WP)"; return @{ user = $stored.Username; pass = $stored.Password } }
        } catch { }
    }

    if (-not $AllowPrompt -or $env:GITHUB_ACTIONS) {
        Write-Error "WP credentials not found in environment or Credential Manager and interactive prompts are disabled. Set `WP_USER`/`WP_PASS` as secrets in CI or store credentials locally."
        exit 2
    }

    # 3) Interactive prompt fallback
    if (-not $user) { $user = Read-Host "$promptUser" }
    $secure = Read-Host "$promptUser app password (input hidden)" -AsSecureString
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    $pass = [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    return @{ user = $user; pass = $pass }
}

function Read-FtpCredentialInteractive(){
    param(
        [bool]$AllowPrompt = $true
    )

    # Use non-reserved variable name to avoid colliding with automatic $Host
    $ftpHostEnv = $env:FTP_HOST
    $ftpUserEnv = $env:FTP_USER
    $ftpPassEnv = $env:FTP_PASS
    if ($ftpHostEnv -and $ftpUserEnv -and $ftpPassEnv) {
        Write-Output "Using FTP credentials from environment"
        return @{ host = $ftpHostEnv; user = $ftpUserEnv; pass = $ftpPassEnv }
    }

    # Windows Credential Manager
    if (Get-Module -ListAvailable -Name CredentialManager) {
        try {
            Import-Module CredentialManager -ErrorAction SilentlyContinue
            $stored = Get-StoredCredential -Target "FTP" -ErrorAction SilentlyContinue
            if ($stored) {
                $hostPrompt = $env:FTP_HOST
                if (-not $hostPrompt) { $hostPrompt = Read-Host "FTP host (e.g. ftp.example.com)" }
                Write-Output "Using FTP credentials from Windows Credential Manager (target=FTP)"
                return @{ host = $hostPrompt; user = $stored.Username; pass = $stored.Password }
            }
        } catch { }
    }

    if (-not $AllowPrompt -or $env:GITHUB_ACTIONS) {
        Write-Error "FTP credentials not found in environment or Credential Manager and interactive prompts are disabled. Set `FTP_HOST`, `FTP_USER`, `FTP_PASS` as secrets in CI or store credentials locally."
        exit 2
    }

    # Interactive prompt fallback
    $ftpHost = Read-Host "FTP host (e.g. ftp.example.com)"
    $ftpUser = Read-Host "FTP user"
    $secure = Read-Host "FTP password (input hidden)" -AsSecureString
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    $ftpPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    return @{ host = $ftpHost; user = $ftpUser; pass = $ftpPass }
}

function Get-AuthHeader($user, $pass){
    $bytes = [System.Text.Encoding]::UTF8.GetBytes("$user`:$pass")
    $b64 = [Convert]::ToBase64String($bytes)
    return "Basic $b64"
}

function Download-FtpDirectoryRecursive([string]$ftpBaseUri, [string]$remotePath, [string]$localPath, [string]$ftpUser, [string]$ftpPass){
    if (-not (Test-Path $localPath)) { New-Item -ItemType Directory -Path $localPath | Out-Null }

    $listUri = "$ftpBaseUri$remotePath"
    if ($listUri -notmatch '^(ftp|ftps)://') { $listUri = "ftp://$listUri" }

    Write-Output "Listing: $remotePath"
    $entries = @()

    # Helper to create request
    function New-FtpRequest($uri, $method, $useSsl) {
        $r = [System.Net.FtpWebRequest]::Create($uri)
        $r.Method = $method
        $r.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $r.UseBinary = $true
        $r.UsePassive = $true
        $r.EnableSsl = $useSsl
        return $r
    }

    # Optionally allow untrusted certs if env var set (CI only if explicit)
    if ($env:ALLOW_UNTRUSTED_FTP_CERT -eq 'true') {
        [System.Net.ServicePointManager]::ServerCertificateValidationCallback = { param($a,$b,$c,$d) return $true }
    }

    # Decide modes to try based on FTP_TYPE env var: 'ftps', 'ftp', or 'auto' (default)
    $ftpType = if ($env:FTP_TYPE) { $env:FTP_TYPE.ToLower() } else { 'auto' }
    switch ($ftpType) {
        'ftps' { $modes = @($true) }
        'ftp'  { $modes = @($false) }
        default { $modes = @($true, $false) }
    }

    $listed = $false
    foreach ($trySsl in $modes) {
        try {
            $req = New-FtpRequest $listUri ([System.Net.WebRequestMethods+Ftp]::ListDirectory) $trySsl
            $resp = $req.GetResponse()
            $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
            while (-not $reader.EndOfStream) { $entries += $reader.ReadLine() }
            $reader.Close(); $resp.Close()
            $listed = $true
            if ($trySsl) { Write-Output "Listed using FTPS (explicit TLS)" } else { Write-Output "Listed using plain FTP" }
            break
        } catch {
            Write-Warning "List attempt with useSsl=$trySsl failed: $_"
        }
    }
    if (-not $listed) {
        $msg = "Could not list $remotePath after attempts (modes: $($modes -join ','))"
        if ($ftpType -eq 'ftps') { throw $msg }
        Write-Warning $msg
        return
    }

    foreach ($name in $entries) {
        if ([string]::IsNullOrWhiteSpace($name)) { continue }
        $remoteItem = "$remotePath/$name" -replace '//','/'
        $localItem = Join-Path $localPath $name

        # Determine if item is directory by attempting to list it
        $testUri = "$ftpBaseUri$remoteItem"
        if ($testUri -notmatch '^(ftp|ftps)://') { $testUri = "ftp://$testUri" }
        $isDir = $false
        foreach ($trySsl in $modes) {
            try {
                $req2 = New-FtpRequest $testUri ([System.Net.WebRequestMethods+Ftp]::ListDirectory) $trySsl
                $resp2 = $req2.GetResponse()
                $resp2.Close()
                $isDir = $true
                break
            } catch {
            }
        }

        if ($isDir) {
            Write-Output "Entering directory: $remoteItem"
            Download-FtpDirectoryRecursive $ftpBaseUri $remoteItem $localItem $ftpUser $ftpPass
        } else {
            Write-Output "Downloading file: $remoteItem -> $localItem"
            if (-not (Test-Path (Split-Path $localItem))) { New-Item -ItemType Directory -Path (Split-Path $localItem) -Force | Out-Null }
            $downloaded = $false
            foreach ($trySsl in $modes) {
                try {
                    $fileUri = "$ftpBaseUri$remoteItem"
                    if ($fileUri -notmatch '^(ftp|ftps)://') { $fileUri = "ftp://$fileUri" }
                    $reqF = New-FtpRequest $fileUri ([System.Net.WebRequestMethods+Ftp]::DownloadFile) $trySsl
                    $respF = $reqF.GetResponse()
                    $stream = $respF.GetResponseStream()
                    $out = [System.IO.File]::OpenWrite($localItem)
                    $buffer = New-Object byte[] 8192
                    while (($read = $stream.Read($buffer, 0, $buffer.Length)) -gt 0) { $out.Write($buffer, 0, $read) }
                    $out.Close(); $stream.Close(); $respF.Close()
                    $downloaded = $true
                    break
                } catch {
                    Write-Warning "Download attempt with useSsl=$trySsl failed for $remoteItem: $_"
                }
            }
            if (-not $downloaded) {
                $msg2 = "Failed to download $remoteItem after attempts (modes: $($modes -join ','))"
                if ($ftpType -eq 'ftps') { throw $msg2 }
                Write-Warning $msg2
            }
        }
    }
}

function Backup-FtpUploads {
    param($ftpCred)
    $ftpHost = $ftpCred.host
    $ftpUser = $ftpCred.user
    $ftpPass = $ftpCred.pass

    # Normalize host
    if ($ftpHost -notmatch '^ftp://') { $ftpBase = "ftp://$ftpHost" } else { $ftpBase = $ftpHost }

    $remote = $FtpRemotePath
    Write-Output "Starting FTP backup of '$remote' from $ftpHost to $FtpLocalBackup"
    Download-FtpDirectoryRecursive $ftpBase $remote $FtpLocalBackup $ftpUser $ftpPass
    Write-Output "FTP backup complete: $FtpLocalBackup"
}

function Test-SinglePost {
    param($title="TEST POST from script", $content="Body created by script")
    $wpCred = Read-CredentialInteractive "WP username"
    $hdr = @{ Authorization = Get-AuthHeader $wpCred.user $wpCred.pass; "Content-Type" = "application/json" }
    $body = @{ title = $title; content = $content; status = "draft" } | ConvertTo-Json
    $url = "$BaseUrl/wp-json/wp/v2/posts"
    Write-Output "Posting test post to $url"
    Invoke-RestMethod -Uri $url -Method Post -Headers $hdr -Body $body
}

function Create-HeaderPost {
    param($name="Header / Nav Test", $html="<p>Header content</p>")
    $wpCred = Read-CredentialInteractive "WP username"
    $hdr = @{ Authorization = Get-AuthHeader $wpCred.user $wpCred.pass; "Content-Type" = "application/json" }
    $body = @{ title = $name; content = $html; status = "publish" } | ConvertTo-Json
    $url = "$BaseUrl/wp-json/wp/v2/posts"
    Write-Output "Creating header/nav post: $name"
    Invoke-RestMethod -Uri $url -Method Post -Headers $hdr -Body $body
}

function Bulk-PostFromCsv {
    param($csvPath = $CsvPath)
    if (-not (Test-Path $csvPath)) { Write-Warning "CSV not found: $csvPath"; return }
    $rows = Import-Csv -Path $csvPath
    $wpCred = Read-CredentialInteractive "WP username"
    $hdr = @{ Authorization = Get-AuthHeader $wpCred.user $wpCred.pass; "Content-Type" = "application/json" }
    $url = "$BaseUrl/wp-json/wp/v2/posts"
    foreach ($r in $rows) {
        $title = $r.title
        $content = $r.content
        $status = if ($r.status) { $r.status } else { "draft" }
        $body = @{ title = $title; content = $content; status = $status } | ConvertTo-Json
        Write-Output "Posting: $title"
        try { Invoke-RestMethod -Uri $url -Method Post -Headers $hdr -Body $body -ErrorAction Stop } catch { Write-Warning "Failed to post '$title' : $_" }
    }
}

function Run-LocalAudits {
    Write-Output "Running local audits (if Python and scripts exist)..."
    if (Test-Path .\audit_pages_image_flags.py) { python .\audit_pages_image_flags.py }
    if (Test-Path .\audit_home_page_detailed.py) { python .\audit_home_page_detailed.py }
}

# --- Main interactive menu ---
Write-Output "=== run_wp_ftp_and_rest.ps1 ==="
Write-Output "This script prompts for credentials interactively. Do NOT paste secrets into chat."


# CI-friendly non-interactive control via env vars:
# RUN_FTP_BACKUP (true/false), RUN_TEST_POST, RUN_HEADER_POST, RUN_BULK_POST, RUN_AUDITS
function Get-RunFlag($name, $default) {
    $v = $env:$name
    if ($v) { return $v.ToLower() -in @('1','true','yes') }
    return $default
}

$runFtp = Get-RunFlag 'RUN_FTP_BACKUP' $false
$runTest = Get-RunFlag 'RUN_TEST_POST' $false
$runHeader = Get-RunFlag 'RUN_HEADER_POST' $false
$runBulk = Get-RunFlag 'RUN_BULK_POST' $false
$runAudits = Get-RunFlag 'RUN_AUDITS' $false

# If running in GitHub Actions and flags unspecified, default to safe CI behavior
if ($env:GITHUB_ACTIONS) {
    if (-not $env:RUN_FTP_BACKUP) { $runFtp = $true }
    if (-not $env:RUN_TEST_POST) { $runTest = $false }
    if (-not $env:RUN_HEADER_POST) { $runHeader = $false }
    if (-not $env:RUN_BULK_POST) { $runBulk = $false }
    if (-not $env:RUN_AUDITS) { $runAudits = $true }
}

# Acquire FTP creds once if needed
if ($runFtp) { $ftpCred = Read-FtpCredentialInteractive -AllowPrompt:($not $env:GITHUB_ACTIONS) }

Write-Output "Will backup remote path: $FtpRemotePath -> local: $FtpLocalBackup"
if ($runFtp) { Backup-FtpUploads -ftpCred $ftpCred } else { Write-Output "Skipping FTP backup" }

if ($runTest) { Test-SinglePost } else { Write-Output "Skipping test POST" }

if ($runHeader) { Create-HeaderPost } else { Write-Output "Skipping header/nav post" }

if ($runBulk) { Bulk-PostFromCsv -csvPath $CsvPath } else { Write-Output "Skipping bulk CSV posts" }

if ($runAudits) { Run-LocalAudits } else { Write-Output "Skipping local audits" }

Write-Output "Done. Remember to rotate any app passwords you used."
