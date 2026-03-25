<#
.SYNOPSIS
  Load saved credentials into the current PowerShell session and provide helper functions
  to run WP REST calls and FTP uploads without re-entering credentials.

USAGE
  In PowerShell: `pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\session_workspace.ps1`
  After running, use provided functions: `Invoke-WpPageUpdate -PageId 4862 -HtmlFile 'tools/page_4862_about.html'`
  or `Upload-PluginDir -LocalDir 'build\plugins\loungenie-home' -RemotePath '/wp-content/plugins'`

NOTES
  - Requires that `tools/import_env_credentials.ps1` or `tools/store_wp_credentials.ps1` has run
    and left `ftp_creds.xml`, `wp_creds.xml` and `site_url.txt` in %USERPROFILE%\.wordpress-credentials
  - `FTP_HOST` will be prompted if not already set in environment.
#>

function Load-StoredCredentials {
    $credDir = Join-Path $env:USERPROFILE '.wordpress-credentials'
    if (-not (Test-Path $credDir)) { throw "Credential directory not found: $credDir" }

    $ftpFile = Join-Path $credDir 'ftp_creds.xml'
    $wpFile  = Join-Path $credDir 'wp_creds.xml'
    $siteFile = Join-Path $credDir 'site_url.txt'

    if (Test-Path $ftpFile) { $global:FTP_CRED = Import-Clixml -Path $ftpFile } else { $global:FTP_CRED = $null }
    if (Test-Path $wpFile)  { $global:WP_CRED  = Import-Clixml -Path $wpFile } else { $global:WP_CRED = $null }
    if (Test-Path $siteFile) { $global:WP_SITE = Get-Content -Path $siteFile -Raw } else { $global:WP_SITE = $null }

    if (-not $global:WP_CRED) { Write-Warning "No WP credential found in $wpFile" }
    if (-not $global:FTP_CRED) { Write-Warning "No FTP credential found in $ftpFile" }
    if (-not $global:WP_SITE) { Write-Warning "No site URL found in $siteFile" }

    # Export as env vars for existing scripts that read them
    if ($global:WP_CRED) {
        $env:WP_USER = $global:WP_CRED.UserName
        $env:WP_PASS = $global:WP_CRED.GetNetworkCredential().Password
    }
    if ($global:WP_SITE) { $env:WP_SITE_URL = $global:WP_SITE }
    if ($global:FTP_CRED) {
        $env:FTP_USER = $global:FTP_CRED.UserName
        $env:FTP_PASS = $global:FTP_CRED.GetNetworkCredential().Password
    }

    if (-not $env:FTP_HOST) {
        $env:FTP_HOST = Read-Host 'Enter FTP host (e.g. ftp.example.com)'
    }

    Write-Host "Credentials loaded into session env vars: WP_USER, WP_PASS, WP_SITE_URL, FTP_USER, FTP_PASS, FTP_HOST" -ForegroundColor Green
}

function Invoke-WpPageUpdate {
    param(
        [Parameter(Mandatory=$true)] [int]$PageId,
        [Parameter(Mandatory=$true)] [string]$HtmlFile
    )
    if (-not (Test-Path $HtmlFile)) { throw "HTML file not found: $HtmlFile" }
    if (-not $env:WP_SITE_URL) { throw "WP_SITE_URL not set in env. Run Load-StoredCredentials first." }
    $content = Get-Content -Raw $HtmlFile
    $pair = "{0}:{1}" -f $env:WP_USER, $env:WP_PASS
    $auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
    $body = @{ content = $content } | ConvertTo-Json -Depth 10
    $uri = "$env:WP_SITE_URL/wp-json/wp/v2/pages/$PageId"
    try {
        $resp = Invoke-RestMethod -Uri $uri -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $body -ErrorAction Stop
        Write-Host "Updated page $PageId -> status: $($resp.status) id: $($resp.id)" -ForegroundColor Green
        return $resp
    } catch {
        Write-Error ([string]::Format('Failed to update page {0}: {1}',$PageId,$_.Exception.Message))
        return $null
    }
}

function Upload-PluginDir {
    param(
        [Parameter(Mandatory=$true)] [string]$LocalDir,
        [Parameter(Mandatory=$true)] [string]$RemotePath
    )
    if (-not (Test-Path $LocalDir)) { throw "LocalDir not found: $LocalDir" }
    if (-not $env:FTP_HOST) { throw "FTP_HOST not set. Run Load-StoredCredentials first or set env var." }
    & .\tools\upload_plugin_via_ftp.ps1 -LocalPluginDir $LocalDir -FtpHost $env:FTP_HOST -RemotePath $RemotePath
}

function Show-SessionHelp {
    Write-Host "Available helpers:" -ForegroundColor Cyan
    Write-Host "  Load-StoredCredentials            - load creds into session env vars"
    Write-Host "  Invoke-WpPageUpdate -PageId -HtmlFile - update a WP page via REST using session creds"
    Write-Host "  Upload-PluginDir -LocalDir -RemotePath - upload plugin dir via FTP using session creds"
    Write-Host "  (You can still call existing scripts directly: .\tools\apply_page_4701.ps1 etc)" -ForegroundColor Yellow
}

# Run loader automatically when script is executed interactively
if ($Host.Name -ne 'ServerHost') {
    Load-StoredCredentials
    Show-SessionHelp
    # Keep session interactive: start a new shell scope
    if ($MyInvocation.ExpectingInput) { } else { Write-Host "Session ready. Run commands now." -ForegroundColor Green }
}
