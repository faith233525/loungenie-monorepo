#!/bin/bash

###############################################################################
# GitHub Permissions Verification Script
# 
# Verifies that GitHub repository is properly configured with:
# - Branch protection rules
# - Required secrets
# - Proper environment settings
# - Correct workflow permissions
###############################################################################

set -e

REPO_OWNER=$(git config --get remote.origin.url | sed -E 's/.*[:/]([^/]+)\/.*\.git/\1/')
REPO_NAME=$(git config --get remote.origin.url | sed -E 's/.*\/([^/]+)\.git/\1/')

echo "🔐 GitHub Repository Permissions Verification"
echo "=============================================="
echo "Repository: $REPO_OWNER/$REPO_NAME"
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if gh CLI is installed
if ! command -v gh &> /dev/null; then
    echo -e "${RED}❌ GitHub CLI (gh) not installed${NC}"
    echo "Install from: https://cli.github.com"
    exit 1
fi

# Verify authentication
if ! gh auth status &> /dev/null; then
    echo -e "${RED}❌ Not authenticated with GitHub${NC}"
    echo "Run: gh auth login"
    exit 1
fi

echo -e "${GREEN}✅ GitHub CLI authenticated${NC}"
echo ""

# Check branch protection rules
echo "📋 Checking Branch Protection Rules..."
echo ""

check_branch_protection() {
    local branch=$1
    echo "Branch: $branch"
    
    # Get protection settings
    local protection=$(gh api repos/$REPO_OWNER/$REPO_NAME/branches/$branch/protection 2>/dev/null || echo "{}")
    
    # Check if protected
    if echo "$protection" | jq -e '.protected' > /dev/null 2>&1; then
        echo -e "  ${GREEN}✅ Branch is protected${NC}"
        
        # Check for required status checks
        if echo "$protection" | jq -e '.required_status_checks' > /dev/null 2>&1; then
            local required_checks=$(echo "$protection" | jq '.required_status_checks.contexts | length')
            echo -e "  ${GREEN}✅ Required status checks: $required_checks${NC}"
        fi
        
        # Check for required reviews
        if echo "$protection" | jq -e '.required_pull_request_reviews' > /dev/null 2>&1; then
            local required_approvals=$(echo "$protection" | jq '.required_pull_request_reviews.required_approving_review_count')
            echo -e "  ${GREEN}✅ Required approvals: $required_approvals${NC}"
        fi
    else
        echo -e "  ${YELLOW}⚠️  Branch protection not configured${NC}"
    fi
    echo ""
}

check_branch_protection "main"
check_branch_protection "develop"

# Check secrets
echo "🔑 Checking GitHub Secrets..."
echo ""

check_secret() {
    local secret_name=$1
    
    if gh secret list --repo $REPO_OWNER/$REPO_NAME 2>/dev/null | grep -q "^$secret_name"; then
        echo -e "${GREEN}✅ $secret_name${NC}"
    else
        echo -e "${YELLOW}⚠️  $secret_name (not configured)${NC}"
    fi
}

REQUIRED_SECRETS=(
    "STAGING_HOST"
    "STAGING_USER"
    "STAGING_SSH_KEY"
    "STAGING_PATH"
    "PRODUCTION_HOST"
    "PRODUCTION_USER"
    "PRODUCTION_SSH_KEY"
    "PRODUCTION_PATH"
)

for secret in "${REQUIRED_SECRETS[@]}"; do
    check_secret "$secret"
done

echo ""

# Check environments
echo "🌍 Checking GitHub Environments..."
echo ""

check_environment() {
    local env_name=$1
    
    if gh api repos/$REPO_OWNER/$REPO_NAME/environments/$env_name > /dev/null 2>&1; then
        echo -e "${GREEN}✅ $env_name environment exists${NC}"
    else
        echo -e "${YELLOW}⚠️  $env_name environment not configured${NC}"
    fi
}

check_environment "staging"
check_environment "production"

echo ""

# Check workflows
echo "🚀 Checking GitHub Workflows..."
echo ""

if gh workflow list --repo $REPO_OWNER/$REPO_NAME &> /dev/null; then
    local workflow_count=$(gh workflow list --repo $REPO_OWNER/$REPO_NAME | wc -l)
    echo -e "${GREEN}✅ Workflows configured: $workflow_count${NC}"
else
    echo -e "${YELLOW}⚠️  No workflows found${NC}"
fi

echo ""

# Check collaborators
echo "👥 Checking Repository Collaborators..."
echo ""

local collaborators=$(gh api repos/$REPO_OWNER/$REPO_NAME/collaborators --paginate | jq 'length')
echo -e "${GREEN}✅ Total collaborators: $collaborators${NC}"

echo ""

# Summary
echo "📊 Permission Summary"
echo "===================="
echo ""
echo "✅ Repository: $REPO_OWNER/$REPO_NAME"
echo "✅ Verification completed"
echo ""
echo "📋 Next Steps:"
echo "  1. Review branch protection rules"
echo "  2. Configure missing secrets"
echo "  3. Set up deployment environments"
echo "  4. Enable required workflows"
echo "  5. Test deployment pipeline"
echo ""
echo "📚 See .github/GITHUB_SETTINGS.md for full configuration guide"
