#!/usr/bin/env bash

###############################################################################
# QUICK REFERENCE - GitHub Workflows & Permissions
# 
# One-page reference for all workflows, permissions, and commands
# Created: January 1, 2026
###############################################################################

cat << 'EOF'

╔════════════════════════════════════════════════════════════════════════════╗
║                 🚀 GITHUB WORKFLOW QUICK REFERENCE 🚀                      ║
║                    LounGenie Portal WordPress Plugin                       ║
╚════════════════════════════════════════════════════════════════════════════╝

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 WORKFLOWS AT A GLANCE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. CI VALIDATION (ci-validation.yml)
   ├─ Triggers: Push to main/develop, PR created, Manual
   ├─ Jobs: 6 (PHP, WPCS, Validation, Security, Perms, JSON)
   ├─ Time: 8-10 minutes
   ├─ Status Checks: 6 required for merge
   └─ Status: Automatic on every push ✅

2. DEPLOYMENT (deployment.yml)
   ├─ Triggers: Tag push, Manual
   ├─ Jobs: 5 (Pre-check, Build, Stage Deploy, Prod Deploy, Post-check)
   ├─ Time: 17-20 minutes
   ├─ Stages: Staging (auto) → Production (approval)
   └─ Status: Automatic on v* tag ✅

3. PERMISSIONS AUDIT (permissions-audit.yml)
   ├─ Triggers: Weekly (Monday 2 AM), Manual
   ├─ Jobs: 4 (Permissions, API Security, Data Access, File Perms)
   ├─ Time: 10 minutes
   ├─ Report: Uploaded as artifact
   └─ Status: Scheduled weekly ✅

4. PR REVIEW (pr-review.yml)
   ├─ Triggers: PR created/updated
   ├─ Jobs: 5 (Auto-review, Coverage, Dependencies, Docs, Comment)
   ├─ Time: <2 minutes
   ├─ Comment: Auto-posted on PR
   └─ Status: On every PR ✅

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔐 PERMISSION ROLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

👑 OWNER/ADMIN
   Full repository access
   Can override branch protection
   Can deploy to production
   Can modify settings
   Can manage users

👨‍💼 MAINTAINER
   Merge pull requests
   Deploy to staging/production
   Review and approve PRs
   Create releases
   Cannot modify settings

👨‍💻 DEVELOPER
   Create feature branches
   Submit pull requests
   Review code
   Cannot push to main/develop
   Cannot merge PRs

👁️  READ-ONLY
   View code and issues
   Comment on PRs
   No write access
   View workflows

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⚡ QUICK COMMANDS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

VERIFICATION & SETUP:
  .github/scripts/verify-permissions.sh    # Check current setup
  .github/scripts/setup-github-permissions.sh  # Initial setup

WORKFLOWS:
  gh workflow list                         # List all workflows
  gh run list                              # View recent runs
  gh run list --workflow ci-validation.yml # Filter by workflow
  gh run view <id> --log                  # View run logs
  gh run rerun <id>                       # Re-run failed workflow

SECRETS:
  gh secret list                          # List all secrets
  gh secret set NAME --body "value"       # Add/update secret
  gh secret delete NAME                   # Delete secret

BRANCHES:
  gh api repos/faith233525/Pool-Safe-Portal/branches/main/protection
  # View branch protection rules

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 BRANCH PROTECTION RULES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

MAIN BRANCH:
  ✅ 1 approval required
  ✅ All CI checks required
  ✅ Stale reviews dismissed
  ✅ Code owners required
  ❌ Force pushes disabled
  ❌ Deletions disabled

DEVELOP BRANCH:
  ✅ 1 approval required
  ✅ CI checks required
  ✅ Auto-merge enabled (squash)
  ❌ Force pushes disabled
  ❌ Deletions disabled

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔑 REQUIRED SECRETS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STAGING DEPLOYMENT:
  STAGING_HOST           - Server hostname
  STAGING_USER           - SSH user
  STAGING_SSH_KEY        - Private key (base64)
  STAGING_PATH           - Plugin directory path

PRODUCTION DEPLOYMENT:
  PRODUCTION_HOST        - Server hostname
  PRODUCTION_USER        - SSH user
  PRODUCTION_SSH_KEY     - Private key (base64)
  PRODUCTION_PATH        - Plugin directory path

SET SECRETS:
  gh secret set STAGING_HOST --body "staging.example.com"
  gh secret set STAGING_USER --body "deploy"
  gh secret set STAGING_SSH_KEY < key.b64
  gh secret set STAGING_PATH --body "/var/www/.../plugins"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🚀 TYPICAL WORKFLOW
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. DEVELOPER:
   git checkout -b feature/my-feature
   # Make changes...
   git push origin feature/my-feature
   # Create PR to develop

2. CI AUTOMATICALLY:
   ✅ Run all 6 validation tests (8-10 min)
   ✅ Post comments on PR
   ✅ Require approval

3. MAINTAINER:
   Review code → Approve → Merge to develop (auto-squash)

4. RELEASE (CREATE TAG):
   git tag -a v1.x.x -m "Release"
   git push origin v1.x.x

5. DEPLOYMENT AUTOMATICALLY:
   ✅ Deploy to staging (automatic)
   ✅ Deploy to production (requires approval)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📁 DOCUMENTATION INDEX
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

.github/README.md                    Quick overview (5 min read)
.github/WORKFLOW_SETUP.md            Complete guide (50 pages)
.github/PERMISSION_MATRIX.md         Access control details
.github/GITHUB_SETTINGS.md           Configuration reference
WORKFLOW_PERMISSIONS_COMPLETE.md     This summary document

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⚙️  CONFIGURATION CHECKLIST
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

INITIAL SETUP:
  ☐ Run .github/scripts/verify-permissions.sh
  ☐ Review .github/README.md
  ☐ Review .github/WORKFLOW_SETUP.md
  ☐ Configure all secrets in GitHub Settings
  ☐ Test workflow with test push
  ☐ Test PR review workflow
  ☐ Verify branch protection rules

ONGOING:
  ☐ Monitor workflow runs
  ☐ Review audit logs weekly
  ☐ Rotate SSH keys quarterly
  ☐ Update permissions as needed
  ☐ Test disaster recovery plan

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ STATUS CHECKS REQUIRED FOR MAIN BRANCH MERGE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ CI - Code Validation & Testing / PHP Syntax Check
✅ CI - Code Validation & Testing / WordPress Coding Standards
✅ CI - Code Validation & Testing / Plugin Validation
✅ CI - Code Validation & Testing / Security & Sanitization Check
✅ CI - Code Validation & Testing / File Permissions Check
✅ CI - Code Validation & Testing / JSON & Config Validation
✅ 1 Maintainer Approval

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🎯 SUCCESS INDICATORS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ All workflows show green checkmarks
✅ CI validation runs in 8-10 minutes
✅ PR review comments appear automatically
✅ Deployment triggers on tag push
✅ Staging deployment automatic
✅ Production deployment waits for approval
✅ Weekly audit runs successfully
✅ Branch protection prevents direct pushes to main

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
❓ TROUBLESHOOTING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WORKFLOW NOT RUNNING:
  → Check: gh workflow list
  → Enable: gh workflow enable ci-validation

CI CHECKS FAILING:
  → Run locally: bash bin/test-plugin.sh
  → View logs: gh run view <id> --log
  → Re-run: gh run rerun <id>

CAN'T MERGE PR:
  → Check: All CI checks pass (green)
  → Get: Maintainer approval
  → Update: Branch from main

DEPLOYMENT FAILING:
  → Check: SSH key configured
  → Verify: PRODUCTION_SSH_KEY secret
  → Test: SSH manually from GitHub

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🎓 BEST PRACTICES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DO:
  ✅ Create feature branches for changes
  ✅ Run tests locally before pushing
  ✅ Write meaningful commit messages
  ✅ Use PR template when available
  ✅ Wait for all CI checks before merge
  ✅ Document code with comments
  ✅ Keep dependencies updated

DON'T:
  ❌ Push directly to main/develop
  ❌ Skip CI checks
  ❌ Merge without review
  ❌ Force push to protected branches
  ❌ Store secrets in code
  ❌ Ignore workflow failures
  ❌ Deploy untested code

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📞 NEED HELP?
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DOCUMENTATION:
  .github/README.md                   - Quick start
  .github/WORKFLOW_SETUP.md           - Complete guide
  .github/PERMISSION_MATRIX.md        - Access control
  WORKFLOW_PERMISSIONS_COMPLETE.md    - Full summary

VERIFY SETUP:
  .github/scripts/verify-permissions.sh

COMMON ISSUES:
  gh run view <id> --log              - See what failed

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🎉 COMPLETE WORKFLOW & PERMISSIONS SYSTEM READY! 🎉

Status: ✅ Production Ready
Created: January 1, 2026
Last Updated: January 1, 2026

EOF
