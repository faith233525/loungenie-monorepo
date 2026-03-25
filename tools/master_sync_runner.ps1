<#
master_sync_runner.ps1

Purpose:
 - Single "master" runner to orchestrate the local orchestrator or CI workflow.
 - Designed to run locally (scheduled or interactive) or in CI. Does NOT accept secrets from chat.

Usage examples:
  # one-time dry-run
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\master_sync_runner.ps1 -Mode once

  # loop every 60 minutes for 24 runs (24 hours) in dry-run
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\master_sync_runner.ps1 -Mode loop -IntervalMinutes 60 -MaxRuns 24

  # one-time live run (requires secrets configured in env or local store)
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\master_sync_runner.ps1 -Mode once -Live

Security notes:
 - Do NOT place secrets in scripts. Prefer `Export-Clixml` local store or GitHub Actions secrets.
 - This script will prefer local credential files at %USERPROFILE%\.config\loungenie\ftp_cred.xml and wp_cred.xml.

#>

param(
    [ValidateSet('once','loop')]
    [string]$Mode = 'once',
    [int]$IntervalMinutes = 60,
    [int]$MaxRuns = 24,
    [switch]$Live,
    [string]$CredDir
)

function Get-CredPath { param($name) Join-Path $env:USERPROFILE ".config\loungenie\$name" }

if (-not $CredDir) { $CredDir = Join-Path $env:USERPROFILE '.config\loungenie' }

$ftpFile = Join-Path $CredDir 'ftp_cred.xml'
$wpFile  = Join-Path $CredDir 'wp_cred.xml'

function Has-LocalCreds {
    return (Test-Path $ftpFile -PathType Leaf) -and (Test-Path $wpFile -PathType Leaf)
}

function Run-Orchestrator {
    param([bool]$liveMode)
    $ts = Get-Date -Format yyyyMMdd-HHmmss
    $log = Join-Path $PSScriptRoot "..\logs\master_run_$ts.log"
    New-Item -ItemType Directory -Path (Split-Path $log) -Force | Out-Null
    Write-Output "Running orchestrator (live=$liveMode) -> $log"
    if ($liveMode) {
        pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\run_wp_ftp_and_rest_safe.ps1 -Live *>&1 | Tee-Object -FilePath $log
    } else {
        pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\run_everything_local.ps1 *>&1 | Tee-Object -FilePath $log
    }
    return $log
}

function Commit-ArtifactsAndOpenPr {
    param($logfile)
    if (-not (Get-Command git -ErrorAction SilentlyContinue)) { Write-Output "git not available; skipping commit step."; return }
    git config user.name "automation-bot" || $null
    git config user.email "automation@local" || $null
    $branch = "audit/artifacts-$(Get-Date -Format yyyyMMdd-HHmmss)"
    git checkout -b $branch
    git add artifacts || $null
    git commit -m "chore(audit): add artifacts from master runner" || Write-Output "No artifacts changes to commit"
    try {
        git push --set-upstream origin $branch
        if (Get-Command gh -ErrorAction SilentlyContinue) {
            gh pr create --title "Automated audit artifacts" --body "Artifacts and logs from master runner. See attached log: $logfile" --base main --head $branch || Write-Output "gh pr create failed"
        } else {
            Write-Output "Installed gh CLI to auto-open PR, or create PR manually from branch: $branch"
        }
    } catch {
        Write-Error "Failed to push or create PR: $_"
    }
}

if ($Mode -eq 'once') { $runs = 1 } else { $runs = $MaxRuns }

for ($i = 1; $i -le $runs; $i++) {
    Write-Output "Master runner iteration $i/$runs"
    $liveOk = $false
    if ($Live) {
        if (Has-LocalCreds) { $liveOk = $true } else {
            # check env vars as fallback
            if ($env:WP_USER -and $env:WP_PASS -and $env:FTP_HOST -and $env:FTP_USER -and $env:FTP_PASS) { $liveOk = $true }
        }
    }

    $log = Run-Orchestrator -liveMode:$liveOk

    Commit-ArtifactsAndOpenPr -logfile $log

    if ($Mode -eq 'loop' -and $i -lt $runs) {
        Write-Output "Sleeping $IntervalMinutes minutes before next run..."
        Start-Sleep -Seconds ($IntervalMinutes * 60)
    }
}

Write-Output "Master runner complete. Check logs in logs/ and artifacts/ for outputs."
