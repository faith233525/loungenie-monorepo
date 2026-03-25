<#
create_repo.ps1
Creates a GitHub repo (via gh CLI), commits current files, pushes, and can set required secrets.

Usage (PowerShell):
  # Set these first (recommended):
  $env:GH_TOKEN = 'ghp_xxx'            # or authenticate via `gh auth login`
  $RepoName = 'loungenie-update-pages' # change if desired
  .\create_repo.ps1 -RepoName $RepoName -Visibility public

Notes:
- Requires Git and GitHub CLI (`gh`) installed and authenticated.
- The script will not overwrite an existing remote named 'origin' unless -ForceRemote is used.
- To set repository secrets, set environment variables `WP_USERNAME`, `WP_APP_PASSWORD`, `WP_SITE_URL` before running with -SetSecrets.
#>
param(
    [string]$RepoName = "loungenie-update-pages",
    [ValidateSet('public','private')][string]$Visibility = 'public',
    [switch]$SetSecrets,
    [switch]$ForceRemote
)

function Ensure-GitRepo {
    if (-not (Test-Path .git)) {
        git init
    }
}

function Commit-All {
    git add -A
    try {
        git commit -m "chore: initial commit — automation for Kadence page updates"
    } catch {
        Write-Host "Nothing to commit or commit failed: $_"
    }
}

function Create-GitHubRepo {
    $exists = gh repo view $RepoName 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Repository $RepoName already exists on your account or org. Skipping create."
    } else {
        gh repo create $RepoName --$Visibility --source=. --remote=origin --push
        if ($LASTEXITCODE -ne 0) { throw "gh repo create failed" }
    }
}

function Set-RepoSecrets {
    if (-not $SetSecrets) { return }
    $secrets = @('WP_USERNAME','WP_APP_PASSWORD','WP_SITE_URL')
    foreach ($s in $secrets) {
        $val = [Environment]::GetEnvironmentVariable($s)
        if ([string]::IsNullOrEmpty($val)) {
            Write-Host "Skipping secret $s — environment variable not set."
            continue
        }
        $val | gh secret set $s
        if ($LASTEXITCODE -ne 0) { Write-Host "Failed to set secret $s" }
    }
}

# Run
Ensure-GitRepo
Commit-All
if (-not $ForceRemote) {
    if (-not (git remote get-url origin 2>$null)) {
        Create-GitHubRepo
    } else {
        Write-Host "Remote 'origin' already configured. Will push to existing remote."
        git push origin HEAD
    }
} else {
    Create-GitHubRepo
}

Set-RepoSecrets
Write-Host "Done. If you created the repo, confirm on GitHub and trigger the workflow via Actions tab or push."