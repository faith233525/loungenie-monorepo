<#
.SYNOPSIS
Run mass update against staging, reading credentials from environment or a local gitignored env file.

USAGE
Option A (recommended): create a local file `tools/.env.staging` with key=values (see example), then run:
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\run_mass_update_staging.ps1 -Confirm

Option B: export env vars in your shell then run the script.

Notes: Do NOT commit `tools/.env.staging`. An example file `tools/.env.staging.example` is included.
#>

Param(
    [switch]$Confirm
)

function Load-EnvFile {
    param([string]$Path)
    if (-not (Test-Path $Path)) { return }
    Get-Content $Path | Where-Object { $_ -and ($_ -notmatch '^\s*#') } | ForEach-Object {
        $parts = $_ -split '=', 2
        if ($parts.Length -eq 2) {
            $k = $parts[0].Trim()
            $v = $parts[1].Trim()
            Set-Item -Path Env:$k -Value $v
        }
    }
}


# Prefer stored encrypted credentials (created by tools/run_local_orchestrator.ps1)
$credDir = Join-Path $env:USERPROFILE '.config\loungenie'
$ftpCredFile = Join-Path $credDir 'ftp_cred.xml'
$wpCredFile = Join-Path $credDir 'wp_cred.xml'

$localEnvPath = Join-Path -Path (Split-Path -Parent $MyInvocation.MyCommand.Definition) -ChildPath '.env.staging'
$examplePath = Join-Path -Path (Split-Path -Parent $MyInvocation.MyCommand.Definition) -ChildPath '.env.staging.example'

if (Test-Path $ftpCredFile -and Test-Path $wpCredFile) {
    try {
        Write-Output "Loading stored credentials from: $credDir"
        $ftpCred = Import-Clixml -Path $ftpCredFile
        $wpCred = Import-Clixml -Path $wpCredFile

        function SecureString-ToPlainText { param([System.Security.SecureString]$s) $bstr = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); try { [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr) } finally { [System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr) } }

        $env:FTP_USER = $ftpCred.UserName
        $env:FTP_PASS = SecureString-ToPlainText -s $ftpCred.Password
        $env:WP_USER = $wpCred.UserName
        $env:WP_PASS = SecureString-ToPlainText -s $wpCred.Password
    } catch {
        Write-Warning "Failed to load stored creds: $($_.Exception.Message). Falling back to .env.staging or environment variables."
    }
} else {
    Write-Output "No stored encrypted creds found at $credDir. Loading local env file if present: $localEnvPath"
    Load-EnvFile -Path $localEnvPath
}

if (-not $env:FTP_HOST -or -not $env:FTP_USER -or -not $env:FTP_PASS -or -not $env:WP_SITE_URL -or -not $env:WP_USER -or -not $env:WP_PASS) {
    Write-Warning "One or more required env vars are missing. Please set them in the environment or create tools/.env.staging from the example.";
    if (Test-Path $examplePath) { Write-Output "See example at $examplePath" }
    exit 1
}

if (-not $Confirm.IsPresent) {
    Write-Output "Ready to run mass update against: $env:WP_SITE_URL"
    Write-Output "Run again with -Confirm to proceed."
    exit 0
}

Write-Output "Running mass update..."
pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\mass_update_pages.ps1
