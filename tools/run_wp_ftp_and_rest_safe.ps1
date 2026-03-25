param(
    [string] $BaseUrl = "https://loungenie.com/staging",
    [string] $FtpRemotePath = "/public_html/wp-content/uploads",
    [string] $FtpLocalBackup = ".\ftp_backup_safe"
)

function New-FtpRequest($uri, $method, $useSsl, $ftpUser, $ftpPass) {
    $r = [System.Net.FtpWebRequest]::Create($uri)
    $r.Method = $method
    $r.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $r.UseBinary = $true
    $r.UsePassive = $true
    $r.EnableSsl = $useSsl
    return $r
}

function Download-FtpDirectoryRecursive([string]$ftpBaseUri, [string]$remotePath, [string]$localPath, [string]$ftpUser, [string]$ftpPass, [bool]$DryRun = $true) {
    if (-not (Test-Path $localPath)) { New-Item -ItemType Directory -Path $localPath | Out-Null }
    if ($ftpBaseUri -notmatch '^(ftp|ftps)://') { $ftpBaseUri = "ftp://$ftpBaseUri" }

    $ftpType = if ($env:FTP_TYPE) { $env:FTP_TYPE.ToLower() } else { 'auto' }
    $ftpRetries = if ($env:FTP_RETRIES) { [int]$env:FTP_RETRIES } else { 3 }
    $ftpRetryDelay = if ($env:FTP_RETRY_DELAY) { [int]$env:FTP_RETRY_DELAY } else { 2 }
    if ($env:FTP_ONLY -and $env:FTP_ONLY.ToLower() -eq 'true') { $ftpType = 'ftp' }
    switch ($ftpType) {
        'ftps' { $modes = @($true) }
        'ftp' { $modes = @($false) }
        default { $modes = @($true, $false) }
    }

    function Normalize-RemotePath([string]$p) {
        if (-not $p) { return '/' }
        $hasLeading = $p.StartsWith('/')
        $segments = $p -split '/' | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' }
        $stack = @()
        foreach ($seg in $segments) {
            if ($seg -eq '.') { continue }
            if ($seg -eq '..') { if ($stack.Count -gt 0) { $stack = $stack[0..($stack.Count - 2)] } ; continue }
            $stack += $seg
        }
        $out = ($stack -join '/')
        if ($hasLeading) { $out = '/' + $out }
        if ($out -eq '') { $out = '/' }
        return $out
    }

    $remotePath = Normalize-RemotePath $remotePath
    $listUri = "$ftpBaseUri$remotePath"
    $entries = @()
    $listed = $false
    foreach ($trySsl in $modes) {
        try {
            $req = New-FtpRequest $listUri ([System.Net.WebRequestMethods+Ftp]::ListDirectory) $trySsl $ftpUser $ftpPass
            $resp = $req.GetResponse()
            $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
            while (-not $reader.EndOfStream) { $entries += $reader.ReadLine() }
            $reader.Close(); $resp.Close()
            $listed = $true; break
        }
        catch { }
    }
    if (-not $listed) { Write-Warning "Could not list $remotePath"; return }

    foreach ($name in $entries) {
        if ([string]::IsNullOrWhiteSpace($name)) { continue }
        $name = $name.Trim()
        if ($name -in @('.', '..')) { continue }
        $remoteItem = Normalize-RemotePath ("$remotePath/$name")
        $localItem = Join-Path $localPath $name

        # detect directory
        $isDir = $false
        foreach ($trySsl in $modes) {
            try { $req2 = New-FtpRequest ("$ftpBaseUri$remoteItem") ([System.Net.WebRequestMethods+Ftp]::ListDirectory) $trySsl $ftpUser $ftpPass; $resp2 = $req2.GetResponse(); $resp2.Close(); $isDir = $true; break } catch { }
        }
        if ($isDir) { Write-Output "DRY_RUN: would enter dir $remoteItem"; continue }

        # file
        if ($DryRun) { Write-Output "DRY_RUN: would download file $remoteItem -> $localItem"; if (-not $global:FTP_DRY_RUN) { $global:FTP_DRY_RUN = 0 }; $global:FTP_DRY_RUN = $global:FTP_DRY_RUN + 1; continue }

        $downloaded = $false
        foreach ($trySsl in $modes) {
            for ($attempt = 1; $attempt -le $ftpRetries; $attempt++) {
                try {
                    $fileUri = "$ftpBaseUri$remoteItem"
                    $reqF = New-FtpRequest $fileUri ([System.Net.WebRequestMethods+Ftp]::DownloadFile) $trySsl $ftpUser $ftpPass
                    $respF = $reqF.GetResponse(); $stream = $respF.GetResponseStream();
                    if (-not (Test-Path (Split-Path $localItem))) { New-Item -ItemType Directory -Path (Split-Path $localItem) -Force | Out-Null }
                    $out = [System.IO.File]::OpenWrite($localItem)
                    $buffer = New-Object byte[] 8192
                    while (($read = $stream.Read($buffer, 0, $buffer.Length)) -gt 0) { $out.Write($buffer, 0, $read) }
                    $out.Close(); $stream.Close(); $respF.Close()
                    $downloaded = $true
                    if (-not $global:FTP_SUCCESS) { $global:FTP_SUCCESS = 0 }
                    $global:FTP_SUCCESS = $global:FTP_SUCCESS + 1
                    break
                }
                catch {
                    $m = $_.Exception.Message
                    if ($m -match '550') { Write-Warning "(550) $remoteItem : $m"; if (-not $global:FTP_550) { $global:FTP_550 = 0 }; $global:FTP_550 = $global:FTP_550 + 1; break }
                    Write-Warning "Attempt $attempt failed for ${remoteItem}: $m"
                    if ($attempt -lt $ftpRetries) { Start-Sleep -Seconds $ftpRetryDelay }
                }
            }
            if ($downloaded) { break }
        }
        if (-not $downloaded) { if (-not $global:FTP_FAIL) { $global:FTP_FAIL = 0 }; $global:FTP_FAIL = $global:FTP_FAIL + 1; Write-Warning "Failed to download $remoteItem" }
    }
}

# Simple entry that only runs backup if RUN_FTP_BACKUP env var is true
function Backup-FtpUploadsSafe {
    param($ftpHost, $ftpUser, $ftpPass, $dryRun = $true)
    if ($ftpHost -notmatch '^ftp://') { $ftpBase = $ftpHost } else { $ftpBase = $ftpHost }
    Write-Output "Starting safe FTP backup (dryRun=$dryRun) from $ftpHost -> $FtpLocalBackup"
    Download-FtpDirectoryRecursive $ftpBase $FtpRemotePath $FtpLocalBackup $ftpUser $ftpPass $dryRun
    Write-Output "Done safe backup"
    $succ = if ($global:FTP_SUCCESS) { $global:FTP_SUCCESS } else { 0 }
    $fail = if ($global:FTP_FAIL) { $global:FTP_FAIL } else { 0 }
    $dry = if ($global:FTP_DRY_RUN) { $global:FTP_DRY_RUN } else { 0 }
    $f550 = if ($global:FTP_550) { $global:FTP_550 } else { 0 }
    Write-Output "Summary: successes=$succ fails=$fail dry_attempts=$dry 550s=$f550"
}

# Run if env instructs
$runFtp = $false
if ($env:RUN_FTP_BACKUP) { $runFtp = $env:RUN_FTP_BACKUP.ToLower() -in @('1', 'true', 'yes') }
if ($runFtp) {
    $ftpCred = @{ host = $env:FTP_HOST; user = $env:FTP_USER; pass = $env:FTP_PASS }
    Backup-FtpUploadsSafe $ftpCred.host $ftpCred.user $ftpCred.pass ($env:DRY_RUN -and $env:DRY_RUN.ToLower() -in @('1', 'true', 'yes'))
}
else { Write-Output "Skipping FTP backup (RUN_FTP_BACKUP not set)" }
