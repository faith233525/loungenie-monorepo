#!/usr/bin/env bash
set -euo pipefail

if [ $# -lt 1 ]; then
  echo "Usage: $0 owner/repo"
  exit 1
fi

REPO="$1"

echo "Enabling auto-merge on repository $REPO"
gh api --method PATCH "/repos/$REPO" -f allow_auto_merge=true

echo "Setting branch protection for deploy-staging (require 1 review, enforce admins)"
gh api --method PUT "/repos/$REPO/branches/deploy-staging/protection" -f required_status_checks='{"strict":true,"contexts":[]}' -f enforce_admins=true -f required_pull_request_reviews='{"required_approving_review_count":1}'

echo "Done. Visit https://github.com/$REPO/settings to review settings."
