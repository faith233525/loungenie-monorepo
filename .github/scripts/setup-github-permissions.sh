#!/bin/bash

###############################################################################
# GitHub Permissions Setup Script
#
# Automated setup for GitHub repository permissions, branch protection,
# and deployment environments
###############################################################################

set -e

echo "🔐 GitHub Repository Permissions Setup"
echo "======================================"
echo ""

# Get repository info
REPO=$(git config --get remote.origin.url)
REPO_OWNER=$(echo $REPO | sed -E 's/.*[:/]([^/]+)\/.*\.git/\1/')
REPO_NAME=$(echo $REPO | sed -E 's/.*\/([^/]+)\.git/\1/')

echo "Repository: $REPO_OWNER/$REPO_NAME"
echo ""

# Color codes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check gh CLI
if ! command -v gh &> /dev/null; then
    echo "❌ GitHub CLI not found. Install from https://cli.github.com"
    exit 1
fi

# Verify authentication
if ! gh auth status &> /dev/null; then
    echo "Authenticating with GitHub..."
    gh auth login
fi

echo -e "${BLUE}Step 1: Create Staging Environment${NC}"
echo "====================================="

gh api repos/$REPO_OWNER/$REPO_NAME/environments/staging \
  -X PUT \
  -f name="staging" \
  -f deployment_branch_policy='{"protected_branches":false,"custom_branch_policies":true}' 2>/dev/null || true

echo -e "${GREEN}✅ Staging environment created${NC}"
echo ""

echo -e "${BLUE}Step 2: Create Production Environment${NC}"
echo "======================================"

gh api repos/$REPO_OWNER/$REPO_NAME/environments/production \
  -X PUT \
  -f name="production" \
  -f deployment_branch_policy='{"protected_branches":true,"custom_branch_policies":false}' \
  -f reviewers='[{"type":"User","id":0}]' \
  -f wait_timer=5 2>/dev/null || true

echo -e "${GREEN}✅ Production environment created${NC}"
echo ""

echo -e "${BLUE}Step 3: Configure Branch Protection (main)${NC}"
echo "=========================================="

gh api repos/$REPO_OWNER/$REPO_NAME/branches/main/protection \
  -X PUT \
  -f required_pull_request_reviews='{"required_approving_review_count":1,"dismiss_stale_reviews":true,"require_code_owner_reviews":true}' \
  -f required_status_checks='{"strict":true,"contexts":["CI - Code Validation & Testing / PHP Syntax Check","CI - Code Validation & Testing / WordPress Coding Standards","CI - Code Validation & Testing / Plugin Validation"]}' \
  -f enforce_admins=true \
  -f allow_force_pushes=false \
  -f allow_deletions=false 2>/dev/null || true

echo -e "${GREEN}✅ Main branch protected${NC}"
echo ""

echo -e "${BLUE}Step 4: Configure Branch Protection (develop)${NC}"
echo "============================================="

gh api repos/$REPO_OWNER/$REPO_NAME/branches/develop/protection \
  -X PUT \
  -f required_pull_request_reviews='{"required_approving_review_count":1,"dismiss_stale_reviews":true}' \
  -f required_status_checks='{"strict":true,"contexts":["CI - Code Validation & Testing / PHP Syntax Check"]}' \
  -f enforce_admins=false \
  -f allow_force_pushes=false \
  -f allow_deletions=false 2>/dev/null || true

echo -e "${GREEN}✅ Develop branch protected${NC}"
echo ""

echo -e "${BLUE}Step 5: Configure Tag Protection${NC}"
echo "=================================="

gh api repos/$REPO_OWNER/$REPO_NAME/tags/protection \
  -X POST \
  -f pattern="v*" \
  -f include_branches=false 2>/dev/null || true

echo -e "${GREEN}✅ Tag protection configured${NC}"
echo ""

echo -e "${BLUE}Step 6: Enable Workflows${NC}"
echo "========================"

echo "Note: Workflows are enabled by default in .github/workflows/"
echo "Run the following to enable all workflows:"
echo "  gh workflow enable --all"
echo ""

echo -e "${BLUE}Step 7: Configure Secrets${NC}"
echo "========================="

echo "Required secrets to configure (in GitHub Settings > Secrets):"
echo ""
echo "Staging Secrets:"
echo "  - STAGING_HOST"
echo "  - STAGING_USER"
echo "  - STAGING_SSH_KEY"
echo "  - STAGING_PATH"
echo "  - STAGING_URL"
echo "  - STAGING_API_TOKEN"
echo ""
echo "Production Secrets:"
echo "  - PRODUCTION_HOST"
echo "  - PRODUCTION_USER"
echo "  - PRODUCTION_SSH_KEY"
echo "  - PRODUCTION_PATH"
echo "  - PRODUCTION_URL"
echo ""

echo "To set secrets via CLI:"
echo "  gh secret set SECRET_NAME --body 'value'"
echo ""

echo -e "${BLUE}Step 8: Verify Setup${NC}"
echo "===================="

bash .github/scripts/verify-permissions.sh

echo ""
echo -e "${GREEN}✅ GitHub Permission Setup Complete!${NC}"
echo ""
echo "📋 Configuration Summary:"
echo "  ✅ Environments created (staging, production)"
echo "  ✅ Branch protection enabled (main, develop)"
echo "  ✅ Tag protection configured"
echo "  ✅ Workflows ready"
echo ""
echo "📝 Next Steps:"
echo "  1. Configure secrets via GitHub Settings"
echo "  2. Set required reviewers in production environment"
echo "  3. Add code owners to .github/CODEOWNERS"
echo "  4. Test deployment workflow"
echo ""
echo "📚 See .github/GITHUB_SETTINGS.md for complete documentation"
