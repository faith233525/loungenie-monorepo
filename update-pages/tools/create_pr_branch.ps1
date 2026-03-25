<#
Helper to create a feature branch and commit PR-ready files locally.
This script does not run on CI and does not contain secrets.

Usage:
  pwsh .\tools\create_pr_branch.ps1 -Branch feature/staging-remediations -Message "Add header/footer remediation"

#>

param(
    [string]$Branch = 'feature/staging-remediations',
    [string]$Message = 'PR: Kadence/Gutenberg best-practices and header/footer remediation'
)

Write-Host "Creating branch $Branch and committing PR-ready files..."

git checkout -b $Branch
git add docs/KADENCE_GUTENBERG_BEST_PRACTICES.md assets/css/responsive-logo-carousel.css patches/header_footer_remediation.md wp-rest-imports/*.json 2>$null
git commit -m $Message
Write-Host "Branch created and files committed. Push with: git push -u origin $Branch"
