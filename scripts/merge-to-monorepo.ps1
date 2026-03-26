<#
Merge multiple GitHub repositories into a single monorepo using `git subtree`.
Safe: creates mirrored backups of each source repo first.

Usage examples:
# Interactive (edit defaults at top or pass parameters):
# powershell -ExecutionPolicy Bypass -File .\scripts\merge-to-monorepo.ps1

# Explicit params:
# powershell -ExecutionPolicy Bypass -File .\scripts\merge-to-monorepo.ps1 -MonorepoDir loungenie-monorepo -MonorepoRemote https://github.com/yourorg/loungenie-monorepo.git

Requirements: Git installed and on PATH. Provide credentials when git prompts (or use credential helper / SSH keys).
#>
param(
    [string]$MonorepoDir = "loungenie-monorepo",
    [string]$MonorepoRemote = "",

    # Repo 1 (Portal)
    [string]$PortalRepo = "https://github.com/faith233525/Pool-Safe-Portal.git",
    [string]$PortalBranch = "main",
    [string]$PortalPrefix = "portal",

    # Repo 2 (Update Pages)
    [string]$UpdateRepo = "https://github.com/faith233525/loungenie-update-pages.git",
    [string]$UpdateBranch = "feat/templates-update",
    [string]$UpdatePrefix = "update-pages",

    # Repo 3 (Site)
    [string]$SiteRepo = "https://github.com/faith233525/Loungenie-site.git",
    [string]$SiteBranch = "main",
    [string]$SitePrefix = "site"
)

function Run($cmd) {
    Write-Host "> $cmd"
    & cmd /c $cmd
    if ($LASTEXITCODE -ne 0) { throw "Command failed: $cmd" }
}

# Ensure git exists
if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Error "git not found on PATH. Install Git and re-run."
    exit 1
}

$cwd = Get-Location
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$backupDir = Join-Path $cwd "repo-backups"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null

Write-Host "Creating mirrored backups (refs, tags) in: $backupDir"

$repos = @(
    @{name = 'portal'; url = $PortalRepo; target = "$backupDir/portal.git" },
    @{name = 'update'; url = $UpdateRepo; target = "$backupDir/update-pages.git" },
    @{name = 'site'; url = $SiteRepo; target = "$backupDir/site.git" }
)

foreach ($r in $repos) {
    if (Test-Path $r.target) {
        Write-Host "Backup already exists: $($r.target) -- skipping clone"
        continue
    }
    Write-Host "Cloning mirror: $($r.url) -> $($r.target)"
    Run "git clone --mirror $($r.url) $($r.target)"
}

# Create monorepo
$monopath = Join-Path $cwd $MonorepoDir
if (Test-Path $monopath) {
    Write-Host "Monorepo path already exists: $monopath"
}
else {
    Write-Host "Creating monorepo at: $monopath"
    New-Item -ItemType Directory -Path $monopath | Out-Null
    Push-Location $monopath
    Run "git init --initial-branch=main"
    Run "git commit --allow-empty -m \"monorepo root\""
    Pop-Location
}

Push-Location $monopath

# Add remote and subtree for each source repo
function AddSubtree($remoteName, $repoUrl, $branch, $prefix) {
    Write-Host "\n--- Importing $remoteName into $prefix/ (branch: $branch) ---"
    # Add remote if missing
    $existing = git remote | Select-String -Pattern "^$remoteName$"
    if (-not $existing) {
        Run "git remote add $remoteName $repoUrl"
    }
    else {
        Write-Host "Remote $remoteName already added"
    }
    Run "git fetch $remoteName --tags"

    # Attempt subtree add; if prefix exists, do subtree merge instead
    if (-not (Test-Path (Join-Path $monopath $prefix))) {
        Run "git subtree add --prefix=$prefix $remoteName $branch"
    }
    else {
        Run "git subtree merge --prefix=$prefix $remoteName $branch"
    }
}

AddSubtree "portal-remote" $PortalRepo $PortalBranch $PortalPrefix
AddSubtree "update-remote" $UpdateRepo $UpdateBranch $UpdatePrefix
AddSubtree "site-remote" $SiteRepo $SiteBranch $SitePrefix

# Push to monorepo remote if given
if ($MonorepoRemote -ne "") {
    Write-Host "Adding monorepo remote: $MonorepoRemote"
    Run "git remote add origin $MonorepoRemote"
    Write-Host "Pushing main to origin"
    Run "git push --set-upstream origin main"
}
else {
    Write-Host "No monorepo remote provided; local monorepo is at: $monopath"
}

Pop-Location
Write-Host "Done. Backups in: $backupDir. Monorepo located at: $monopath"
Write-Host "Next: review monorepo, update CI/workflows, then push to remote."
