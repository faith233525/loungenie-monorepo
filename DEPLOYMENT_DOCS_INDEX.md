# Deployment & Git Version Control Documentation Index

This directory contains comprehensive documentation about Git version control and deployment systems for the Loungenie monorepo.

## 🚀 Start Here

**New to deployment?** Start with these documents in order:

1. **[QUICK_DEPLOYMENT_STATUS.md](QUICK_DEPLOYMENT_STATUS.md)** ⭐ START HERE
   - Quick answers to "Is my deployment working?"
   - Current status summary
   - Immediate action steps
   - Best for: Quick status check

2. **[DEPLOYMENT_HEALTH_CHECK.md](DEPLOYMENT_HEALTH_CHECK.md)** 📋 DETAILED GUIDE
   - How to verify each deployment method
   - Troubleshooting common issues
   - Testing procedures
   - Best for: In-depth troubleshooting

3. **[GIT_VERSION_CONTROL.md](GIT_VERSION_CONTROL.md)** 🔧 TECHNICAL REFERENCE
   - How Git version control is configured
   - All 14 GitHub Actions workflows
   - Best practices and patterns
   - Best for: Understanding the system

## 📚 All Documentation

### Deployment Status & Health

| Document | Purpose | When to Use |
|----------|---------|-------------|
| [QUICK_DEPLOYMENT_STATUS.md](QUICK_DEPLOYMENT_STATUS.md) | Quick status check and answers | "Is it working?" |
| [DEPLOYMENT_HEALTH_CHECK.md](DEPLOYMENT_HEALTH_CHECK.md) | Comprehensive verification guide | Troubleshooting |
| [DEPLOYMENT_STATUS.md](DEPLOYMENT_STATUS.md) | WordPress REST API deployment status | WordPress page sync |

### Technical Configuration

| Document | Purpose | When to Use |
|----------|---------|-------------|
| [GIT_VERSION_CONTROL.md](GIT_VERSION_CONTROL.md) | Git configuration in workflows | Understanding setup |
| [DEPLOYING.md](DEPLOYING.md) | General deployment guide | Setting up deployment |
| [.cpanel.yml](.cpanel.yml) | cPanel deployment tasks | cPanel configuration |

### Reference & Notes

| Document | Purpose | When to Use |
|----------|---------|-------------|
| [DEPLOY_NOTES.md](DEPLOY_NOTES.md) | Deployment notes | Historical reference |
| [DEPLOY_RULES.md](DEPLOY_RULES.md) | Deployment rules | Policy reference |

## 🎯 Common Scenarios

### "I just pushed to main - did it deploy?"

1. Read: [QUICK_DEPLOYMENT_STATUS.md](QUICK_DEPLOYMENT_STATUS.md) - Section "Did the pull occur?"
2. Check workflow: `gh run list --workflow=cpanel-pull-deploy.yml --limit 1`
3. Verify in cPanel: Git Version Control → Check commit SHA

### "Something broke - how do I fix it?"

1. Read: [DEPLOYMENT_HEALTH_CHECK.md](DEPLOYMENT_HEALTH_CHECK.md) - Section "Common Issues & Solutions"
2. Check recent workflow runs: `gh run list --limit 5`
3. View failure logs: `gh run view RUN_ID`

### "How do I manually deploy?"

1. Read: [QUICK_DEPLOYMENT_STATUS.md](QUICK_DEPLOYMENT_STATUS.md) - Section "Option 1: Manual Pull in cPanel"
2. Or read: [DEPLOYMENT_HEALTH_CHECK.md](DEPLOYMENT_HEALTH_CHECK.md) - Section "If You Want Manual Deployment"

### "I want to understand the system"

1. Read: [GIT_VERSION_CONTROL.md](GIT_VERSION_CONTROL.md) - Full technical overview
2. Explore: `.github/workflows/` - All workflow files
3. Read: [DEPLOYING.md](DEPLOYING.md) - General deployment setup

## 🔄 Deployment Methods

This repository supports multiple deployment methods:

### 1. cPanel Git Pull (Automatic) ⭐ PRIMARY

- **Workflow:** `.github/workflows/cpanel-pull-deploy.yml`
- **Trigger:** Push to `main` branch
- **How:** Calls cPanel API to pull from GitHub
- **Status:** Fixed in this PR (was returning 415 error)
- **Docs:** [QUICK_DEPLOYMENT_STATUS.md](QUICK_DEPLOYMENT_STATUS.md)

### 2. SSH Rsync

- **Workflow:** `.github/workflows/deploy-cpanel.yml`
- **Trigger:** Manual or workflow dispatch
- **How:** Syncs files via SSH/rsync
- **Docs:** [DEPLOYMENT_HEALTH_CHECK.md](DEPLOYMENT_HEALTH_CHECK.md)

### 3. FTP Upload

- **Workflows:** `deploy-staging-ftp.yml`, `deploy-portal.yml`, `automated-staging-deploy.yml`
- **Trigger:** Manual or workflow dispatch
- **How:** Uploads files via FTP
- **Docs:** [DEPLOYING.md](DEPLOYING.md)

### 4. WordPress REST API

- **Workflow:** `.github/workflows/apply-site-staging.yml`
- **Trigger:** Manual or workflow dispatch
- **How:** Updates WordPress pages via REST API
- **Docs:** [DEPLOYMENT_STATUS.md](DEPLOYMENT_STATUS.md)

## 📊 Current Status (2026-03-26)

| Component | Status | Action Needed |
|-----------|--------|---------------|
| Git Version Control | ✅ Working | None |
| cPanel Git Pull | 🔧 Fixed in PR | Merge this PR |
| SSH Deployment | ⚠️ Needs review | Check workflow logs |
| FTP Deployment | ✅ Available | Configure secrets |
| WordPress REST API | ⚠️ Needs secrets | Add WP credentials |

## 🛠️ Quick Commands

```bash
# Check if deployment is working
gh run list --workflow=cpanel-pull-deploy.yml --limit 5

# View most recent deployment
gh run view $(gh run list --workflow=cpanel-pull-deploy.yml --limit 1 --json databaseId --jq '.[0].databaseId')

# Manually trigger deployment
gh workflow run cpanel-pull-deploy.yml

# Watch workflow in real-time
gh run watch

# Check what changed in last deployment
git --no-pager log origin/main -5 --oneline
```

## 🆘 Getting Help

1. **Quick Questions:** See [QUICK_DEPLOYMENT_STATUS.md](QUICK_DEPLOYMENT_STATUS.md)
2. **Troubleshooting:** See [DEPLOYMENT_HEALTH_CHECK.md](DEPLOYMENT_HEALTH_CHECK.md)
3. **Technical Details:** See [GIT_VERSION_CONTROL.md](GIT_VERSION_CONTROL.md)
4. **Open an Issue:** If docs don't answer your question

## 📝 Recent Changes

**2026-03-26 - This PR:**
- ✅ Fixed cPanel Git pull API call (415 error)
- ✅ Added comprehensive deployment health check guide
- ✅ Added quick deployment status reference
- ✅ Updated all documentation with cross-references

**Previous:**
- Updated all workflows to `actions/checkout@v5`
- Added `submodules: false` to prevent git errors
- Updated Python workflows to `actions/setup-python@v6`

## 🔗 External Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [cPanel Git Version Control](https://docs.cpanel.net/cpanel/files/git-version-control/)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)

---

**Last Updated:** 2026-03-26  
**Maintained By:** Repository maintainers  
**Questions?** Start with [QUICK_DEPLOYMENT_STATUS.md](QUICK_DEPLOYMENT_STATUS.md)
