Param()

Write-Host "Local LG9 deploy wrapper — runs upload, notify, and audit (interactive)."

$zip = Join-Path -Path (Get-Location) -ChildPath 'dist\lg-block-patterns.zip'
if (-not (Test-Path -Path $zip)) {
    Write-Error "Plugin zip not found at $zip. Run build first: python scripts/build_plugin_zip.py or ensure dist/lg-block-patterns.zip exists."
    exit 1
}

# Load local deploy env file if present (.deploy.env)
$deployEnv = Join-Path (Get-Location) '.deploy.env'
if (Test-Path $deployEnv) {
    Write-Host "Loading deploy variables from .deploy.env (local only; do NOT commit this file)."
    Get-Content $deployEnv | ForEach-Object {
        if ($_ -match '^[\s#]') { return }
        if ($_ -match '^\s*$') { return }
        $parts = $_ -split '=', 2
        if ($parts.Count -eq 2) {
            $name = $parts[0].Trim()
            $val = $parts[1].Trim()
            if ($name -and $val) { Set-Item -Path Env:$name -Value $val }
        }
    }
}

Write-Host "Choose upload method:"
Write-Host "  1) cPanel API (recommended if you have a token)"
Write-Host "  2) FTP (username/password)"
Write-Host "  3) Manual WP Admin (I'll open instructions)"

$choice = Read-Host "Enter 1, 2, or 3"

switch ($choice) {
    '1' {
        $cpanel = $env:CPANEL_TOKEN
        if (-not $cpanel) { $cpanel = Read-Host "cPanel API token (paste, input is hidden if supported)" }
        if (-not $cpanel) { Write-Error "No token provided."; exit 1 }
        Write-Host "Calling cPanel uploader script..."
        pwsh -NoProfile -ExecutionPolicy Bypass -File .\scripts\upload_plugin_via_cpanel.ps1 -ZipPath $zip -CpanelToken $cpanel -TargetPath '/public_html/staging_loungenie/wp-content/plugins'
    }
    '2' {
        $ftpHost = $env:FTP_HOST
        if (-not $ftpHost) { $ftpHost = Read-Host "FTP host (e.g. ftp.loungenie.com)" }
        $ftpUser = $env:FTP_USER
        if (-not $ftpUser) { $ftpUser = Read-Host "FTP username" }
        if ($env:FTP_PASS) {
            $plainPass = $env:FTP_PASS
        }
        else {
            $ftpPass = Read-Host -AsSecureString "FTP password"
            $plainPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($ftpPass))
        }
        Write-Host "Uploading via FTP..."
        pwsh -NoProfile -ExecutionPolicy Bypass -File .\scripts\ftp-upload-lg-block-patterns.ps1 -ZipPath $zip -FtpHost $ftpHost -FtpUser $ftpUser -FtpPass $plainPass -RemotePath '/public_html/staging_loungenie/wp-content/plugins'
    }
    '3' {
        Write-Host "Manual WP Admin upload instructions:"
        Write-Host "  1) Sign into WP Admin for staging (https://<staging-site>/wp-admin)."
        Write-Host "  2) Go to Plugins → Add New → Upload Plugin. Choose dist/lg-block-patterns.zip and Install."
        Write-Host "  3) After install, activate the plugin and run the LiteSpeed notifier script below."
    }
    default {
        Write-Error "Invalid choice."; exit 1
    }
}

Write-Host "Running LiteSpeed notify + smoke checks..."
pwsh -NoProfile -ExecutionPolicy Bypass -File .\scripts\notify_litespeed_and_check.ps1

Write-Host "Running quick page audit..."
pwsh -NoProfile -ExecutionPolicy Bypass -Command {
    python tools/page_audit.py --iterations 30 --urls https://loungenie.com/stage/ https://loungenie.com/stage/hospitality-innovation/ https://loungenie.com/stage/support/
}

Write-Host "Local deploy wrapper finished. Check outputs above for upload, notify, and audit results."
