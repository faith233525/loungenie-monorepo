# PowerShell runner to perform WP-CLI dry-run/apply remotely via SSH
# Usage: run this locally in PowerShell. It will SSH to the remote host and run WP-CLI commands.

param()

function Read-Input([string]$prompt, [string]$default = '') {
    if ($default -ne '') {
        $p = Read-Host "$prompt [$default]"
        if ($p -eq '') { return $default }
        return $p
    }
    else {
        return Read-Host $prompt
    }
}

$sshHost = Read-Input "SSH host (e.g. example.com)"
$sshUser = Read-Input "SSH user (e.g. ubuntu)"
$sitePath = Read-Input "Path to WordPress root on remote (e.g. /var/www/html)" "/var/www/html"
$apply = Read-Input "Apply changes? (yes to apply, otherwise dry-run)" "no"

$applyFlag = $false
if ($apply -match '^(y|yes)$') { $applyFlag = $true }

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$dryLog = "loungenie-searchreplace-dryrun-$timestamp.txt"
$applyLog = "loungenie-searchreplace-apply-$timestamp.txt"
$backup = "loungenie-backup-$timestamp.sql"

# Replacement pairs (old|new)
# Replacement pairs: old => new
# Note: avoid literal trademark char to prevent encoding/parsing issues; use percent-encoded and www->non-www
$replacements = @(
    "loungenie%E2%84%A2/wp-content|loungenie/wp-content",
    "https://www.loungenie.com|https://loungenie.com"
)

# Common WP-CLI options
$commonOpts = "--skip-columns=guid --precise --recurse-objects"

Write-Host ("Backup + dry-run will be performed on {0}@{1}:{2}" -f $sshUser, $sshHost, $sitePath)
Write-Host ("Dry-run output will be saved to {0} (in current directory)" -f $dryLog)
if ($applyFlag) { Write-Host "Apply mode: changes WILL be applied and logged to $applyLog" }

# Helper to run an SSH command and capture stdout/stderr
function Run-SSHCommand($cmd) {
    # Build ssh argument: user@host "<cmd>"
    $sshTarget = "$($sshUser)@$($sshHost)"
    $escaped = $cmd -replace '"', '\"'
    $arguments = "$sshTarget `"$escaped`""
    Write-Host ("Running SSH command: {0}" -f $cmd) -ForegroundColor Cyan

    $processInfo = New-Object System.Diagnostics.ProcessStartInfo
    $processInfo.FileName = 'ssh'
    $processInfo.Arguments = $arguments
    $processInfo.RedirectStandardOutput = $true
    $processInfo.RedirectStandardError = $true
    $processInfo.UseShellExecute = $false
    $processInfo.CreateNoWindow = $true

    $p = New-Object System.Diagnostics.Process
    $p.StartInfo = $processInfo
    $p.Start() | Out-Null
    $stdout = $p.StandardOutput.ReadToEnd()
    $stderr = $p.StandardError.ReadToEnd()
    $p.WaitForExit()
    return @{ stdout = $stdout; stderr = $stderr; exitcode = $p.ExitCode }
}

# 1) Create DB backup on remote
$cmdBackup = "cd '$sitePath'; wp db export '$backup'"
$result = Run-SSHCommand $cmdBackup
if ($result.exitcode -ne 0) {
    Write-Host "Backup failed. stderr:" -ForegroundColor Red
    Write-Host $result.stderr
    exit 1
}
Write-Host "Remote DB backup created: $backup" -ForegroundColor Green

# 2) Run replacements (dry-run or apply)
if (-not $applyFlag) {
    Remove-Item -ErrorAction SilentlyContinue $dryLog
}
else {
    Remove-Item -ErrorAction SilentlyContinue $applyLog
}

foreach ($pair in $replacements) {
    $parts = $pair -split '\|'
    $old = $parts[0]
    $new = $parts[1]
    if (-not $applyFlag) {
        $cmd = "cd '$sitePath'; wp search-replace '$old' '$new' $commonOpts --dry-run"
        $out = Run-SSHCommand $cmd
        "--- Pattern: '$old' -> '$new' ---" | Out-File -FilePath $dryLog -Append -Encoding utf8
        $out.stdout | Out-File -FilePath $dryLog -Append -Encoding utf8
        $out.stderr | Out-File -FilePath $dryLog -Append -Encoding utf8
    }
    else {
        $cmd = "cd '$sitePath'; wp search-replace '$old' '$new' $commonOpts"
        $out = Run-SSHCommand $cmd
        "--- Applying: '$old' -> '$new' ---" | Out-File -FilePath $applyLog -Append -Encoding utf8
        $out.stdout | Out-File -FilePath $applyLog -Append -Encoding utf8
        $out.stderr | Out-File -FilePath $applyLog -Append -Encoding utf8
    }
}

# 3) Flush caches if applying
if ($applyFlag) {
    $cmdFlush = "cd '$sitePath'; wp cache flush || true; wp transient delete --all || true"
    $out = Run-SSHCommand $cmdFlush
    $out.stdout | Out-File -FilePath $applyLog -Append -Encoding utf8
    $out.stderr | Out-File -FilePath $applyLog -Append -Encoding utf8
    Write-Host "Apply complete. Review $applyLog and purge CDN/LiteSpeed caches if used." -ForegroundColor Green
}
else {
    Write-Host "Dry-run complete. Review $dryLog and re-run with 'Apply' answer to perform changes." -ForegroundColor Yellow
}

Write-Host "Local logs written in current directory:" -ForegroundColor Cyan
if (-not $applyFlag) { Write-Host " - $dryLog" } else { Write-Host " - $applyLog" }
Write-Host "Remote DB backup: $backup (on remote server in site root)" -ForegroundColor Cyan

exit 0
