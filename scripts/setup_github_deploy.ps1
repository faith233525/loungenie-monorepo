param(
    [Parameter(Mandatory=$true)]
    [string]$Repo
)

$required = @('FTP_HOST','FTP_USER','FTP_PASS','WP_REST_USER','WP_REST_PASS','WP_SITE_URL')
$optional = @('CPANEL_TOKEN')
foreach ($name in $required) {
    $val = (Get-Item -Path env:$name -ErrorAction SilentlyContinue).Value
    if (-not $val) {
        Write-Error "$name environment variable is required. Set it before running this script."
        exit 1
    }
}

Write-Host "Setting repository secrets for $Repo"
gh secret set FTP_HOST --body $env:FTP_HOST --repo $Repo
gh secret set FTP_USER --body $env:FTP_USER --repo $Repo
gh secret set FTP_PASS --body $env:FTP_PASS --repo $Repo
foreach ($name in $optional) {
    $val = (Get-Item -Path env:$name -ErrorAction SilentlyContinue).Value
    if ($val) {
        gh secret set $name --body $val --repo $Repo
    } else {
        Write-Host "Optional secret $name not set; skipping."
    }
}
gh secret set WP_REST_USER --body $env:WP_REST_USER --repo $Repo
gh secret set WP_REST_PASS --body $env:WP_REST_PASS --repo $Repo
gh secret set WP_SITE_URL --body $env:WP_SITE_URL --repo $Repo

Write-Host "Creating and pushing deploy-staging branch"
git fetch origin
git checkout -b deploy-staging
git push -u origin deploy-staging

Write-Host "Applying branch protection to 'deploy-staging' (require 1 review, enforce admins)"
gh api --method PUT "/repos/$Repo/branches/deploy-staging/protection" -f required_status_checks='{"strict":true,"contexts":[]}' -f enforce_admins=true -f required_pull_request_reviews='{"required_approving_review_count":1}'

Write-Host "Dispatching the auto-deploy workflow"
gh workflow run auto-deploy-staging.yml -R $Repo

Write-Host "Done. Monitor Actions: https://github.com/$Repo/actions"
