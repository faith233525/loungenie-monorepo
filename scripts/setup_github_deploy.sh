#!/usr/bin/env bash
set -euo pipefail

if [ $# -lt 1 ]; then
  echo "Usage: $0 owner/repo"
  echo "Example: $0 faith233525/loungenie-monorepo"
  exit 1
fi

REPO="$1"

: "${FTP_HOST:?Need FTP_HOST env var}"
: "${FTP_USER:?Need FTP_USER env var}"
: "${FTP_PASS:?Need FTP_PASS env var}"
: "${CPANEL_TOKEN:?Need CPANEL_TOKEN env var}"
: "${WP_REST_USER:?Need WP_REST_USER env var}"
: "${WP_REST_PASS:?Need WP_REST_PASS env var}"
: "${WP_SITE_URL:?Need WP_SITE_URL env var}"

echo "Setting repository secrets for $REPO (uses current GH auth context)."
gh secret set FTP_HOST --body "$FTP_HOST" --repo "$REPO"
gh secret set FTP_USER --body "$FTP_USER" --repo "$REPO"
gh secret set FTP_PASS --body "$FTP_PASS" --repo "$REPO"
gh secret set CPANEL_TOKEN --body "$CPANEL_TOKEN" --repo "$REPO"
gh secret set WP_REST_USER --body "$WP_REST_USER" --repo "$REPO"
gh secret set WP_REST_PASS --body "$WP_REST_PASS" --repo "$REPO"
gh secret set WP_SITE_URL --body "$WP_SITE_URL" --repo "$REPO"

echo "Creating and pushing deploy-staging branch."
git fetch origin
git checkout -b deploy-staging
git push -u origin deploy-staging

echo "Applying branch protection to 'deploy-staging' (require 1 review, enforce admins)."
gh api --method PUT "/repos/$REPO/branches/deploy-staging/protection" -f required_status_checks='{"strict":true,"contexts":[]}' -f enforce_admins=true -f required_pull_request_reviews='{"required_approving_review_count":1}'

echo "Dispatching the auto-deploy workflow"
gh workflow run auto-deploy-staging.yml --repo "$REPO"

echo "Done. Monitor Actions at: https://github.com/$REPO/actions"
