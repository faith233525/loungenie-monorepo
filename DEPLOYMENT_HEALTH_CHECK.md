# Deployment Health Check & Status Guide

This guide helps you verify if your git version control and deployment systems are working correctly.

## Quick Status Check

### Is Git Version Control Working? ✅ YES

All GitHub Actions workflows use proper Git version control with `actions/checkout@v5`. Every deployment is tracked to a specific Git commit.

**Current Configuration:**
- ✅ All 14 workflows use `actions/checkout@v5`
- ✅ Submodules properly disabled (`submodules: false`)
- ✅ Python workflows use `actions/setup-python@v6`
- ✅ Git commits are being tracked and pushed to GitHub

## Deployment Methods Status

### 1. cPanel Git Pull (Main Branch) 🔧 NEEDS FIX

**Status:** API call returns 415 error (fixed in this PR)

**How It Works:**
- cPanel has a Git repository configured at `/home/pools425/repositories/loungenie-stage`
- Tracks the `main` branch of this GitHub repository
- When code is pushed to `main`, GitHub Actions workflow triggers cPanel to pull updates

**Workflow:** `.github/workflows/cpanel-pull-deploy.yml`

**Recent Run:** 2026-03-26T20:17:17Z - Completed but received 415 error
- **Issue:** Missing `Content-Type: application/json` header in API call
- **Fix Applied:** Added proper Content-Type header to curl request

**How to Check if It's Working:**
```bash
# Check recent workflow runs
gh run list --workflow=cpanel-pull-deploy.yml --limit 5

# View logs of most recent run
gh run view $(gh run list --workflow=cpanel-pull-deploy.yml --limit 1 --json databaseId --jq '.[0].databaseId')
```

**Manual Trigger:**
```bash
# Trigger a pull manually
gh workflow run cpanel-pull-deploy.yml
```

### 2. SSH Deployment (Rsync) ⚠️ PARTIAL

**Status:** Workflow exists but recent runs failed

**How It Works:**
- Checks out code from Git
- Uses rsync to sync files to server via SSH
- Ensures server has exact copy of committed code

**Workflow:** `.github/workflows/deploy-cpanel.yml`

**Recent Run:** Multiple failures on main branch
- Check the workflow logs for specific error details

**How to Check:**
```bash
gh run list --workflow=deploy-cpanel.yml --limit 5
```

### 3. FTP Deployment with Git Checkout ✅ AVAILABLE

**Status:** Multiple FTP workflows available

**Workflows:**
- `deploy-staging-ftp.yml` - FTP staging deployment
- `deploy-portal.yml` - Portal plugin deployment
- `automated-staging-deploy.yml` - Full staging deployment

**How It Works:**
- Checks out code from Git repository
- Uploads files via FTP to target server
- Git commit tracking maintained in workflow logs

### 4. WordPress REST API Sync ⚠️ NEEDS CREDENTIALS

**Status:** Workflow exists but needs secrets configured

**Workflow:** `.github/workflows/apply-site-staging.yml`

**Missing:** WordPress REST API credentials (see DEPLOYMENT_STATUS.md)

## Did the Pull Occur?

### Check cPanel Directly

1. **Log into cPanel** at your hosting provider
2. **Navigate to:** Git Version Control
3. **Look for:**
   - Repository path: `/home/pools425/repositories/loungenie-stage`
   - Currently checked-out branch: `main`
   - Last pull timestamp
4. **Check the commit SHA** - it should match your latest GitHub commit on `main`

### Check Latest Commit on Main Branch

```bash
# View latest commit on main
git log origin/main -1 --oneline

# Current main branch commit:
# 1ab13ef Merge pull request #6 from faith233525/copilot/check-git-version-control
```

### Check Your Staging Site

1. **Visit your staging URL** (e.g., https://loungenie.com/stage)
2. **Verify the changes** you expect to see are visible
3. **Check WordPress version** or any code that displays the commit SHA

## Do You Need to Update from Remote or Deploy HEAD Commit?

### Understanding the Flow

```
Your Code → GitHub (main branch) → GitHub Actions → cPanel Git Pull → Staging Site
```

**When changes are pushed to `main`:**
1. ✅ GitHub receives the commit
2. ✅ GitHub Actions `cpanel-pull-deploy.yml` workflow triggers
3. 🔧 Workflow calls cPanel Git API (currently getting 415 error - fix applied in this PR)
4. ⏳ cPanel should execute `git pull` on its repository
5. ⏳ Your staging site should update automatically

### What You Should Do Now

#### If You Want Automatic Deployment:

**Step 1: Merge This PR**
This PR fixes the 415 error in the cPanel pull workflow.

```bash
# After reviewing and testing, merge this PR
gh pr merge --auto --squash
```

**Step 2: Push to Main Branch**
```bash
# Make sure your changes are on the main branch
git checkout main
git pull
git merge your-feature-branch
git push origin main
```

**Step 3: Watch the Workflow**
```bash
# Monitor the cpanel-pull-deploy workflow
gh run watch
```

**Step 4: Verify on cPanel**
- Log into cPanel
- Check Git Version Control section
- Verify the latest commit SHA matches GitHub
- Check the "Last Pull" timestamp

#### If You Want Manual Deployment:

**Option 1: Trigger Workflow Manually**
```bash
gh workflow run cpanel-pull-deploy.yml
```

**Option 2: Pull Directly in cPanel**
1. Log into cPanel
2. Navigate to Git Version Control
3. Click "Pull or Deploy" button
4. Select your repository
5. Click "Update from Remote"

**Option 3: Deploy via FTP**
```bash
# Use the FTP deployment workflow
gh workflow run deploy-staging-ftp.yml
```

## Common Issues & Solutions

### "415 Unsupported Media Type" in cPanel Pull

**Symptom:** Workflow runs but API returns 415 error

**Cause:** Missing `Content-Type: application/json` header

**Solution:** ✅ Fixed in this PR - the workflow now includes proper headers

### "Did my changes deploy?"

**Check These:**
1. ✅ Did you push to the `main` branch?
2. ✅ Did the `cpanel-pull-deploy.yml` workflow run successfully?
3. ✅ Does the commit SHA in cPanel match GitHub?
4. ✅ Did you clear any caching (browser, CDN, WordPress)?

### "Changes are in GitHub but not on the site"

**Possible Causes:**
1. Workflow didn't run (check Actions tab)
2. cPanel pull failed (check cPanel logs)
3. Caching (clear cache and hard refresh)
4. Wrong branch deployed (check cPanel shows `main`)

**Solution:**
```bash
# Force a deployment
gh workflow run cpanel-pull-deploy.yml

# Or manually pull in cPanel
# cPanel → Git Version Control → Pull or Deploy
```

### "How do I know which commit is deployed?"

**Method 1: Check cPanel**
- Git Version Control → View your repo → See commit SHA

**Method 2: Check Workflow Logs**
```bash
gh run list --workflow=cpanel-pull-deploy.yml --limit 1
```

**Method 3: Add Version to Your Site**
Add this to your WordPress footer or admin:
```php
// Display current Git commit
echo '<!-- Git commit: ' . trim(file_get_contents(__DIR__ . '/.git/refs/heads/main')) . ' -->';
```

## Testing Your Deployment

### Test 1: Verify Git Version Control

```bash
# Check all workflows use proper checkout
grep -r "actions/checkout@v5" .github/workflows/
# Should show all workflow files

# Check submodules are disabled
grep -r "submodules: false" .github/workflows/
# Should show all workflow files
```

### Test 2: Test cPanel API Connection

```bash
# Run the test-connections workflow
gh workflow run test-connections.yml
```

### Test 3: Make a Test Change

1. Create a test file:
```bash
echo "Test deployment $(date)" > test-deployment.txt
git add test-deployment.txt
git commit -m "Test: Verify deployment pipeline"
git push origin main
```

2. Watch the workflow:
```bash
gh run watch
```

3. Check cPanel or your staging site for the file

4. Clean up:
```bash
git rm test-deployment.txt
git commit -m "Clean up test file"
git push origin main
```

## Monitoring & Alerts

### Set Up Notifications

**GitHub Actions:**
1. Go to: GitHub → Your Profile → Settings → Notifications
2. Enable: "Actions" under "Email notifications"
3. Choose: "Only failures" or "All activity"

**Workflow Status Badge:**
Add to your README.md:
```markdown
![cPanel Deploy](https://github.com/faith233525/loungenie-monorepo/actions/workflows/cpanel-pull-deploy.yml/badge.svg)
```

### Regular Health Checks

**Daily:**
- Check GitHub Actions tab for failed workflows
- Verify staging site is accessible

**After Each Push to Main:**
- Verify workflow runs successfully
- Check commit SHA matches in cPanel
- Spot-check a page on staging site

**Weekly:**
- Review all workflow runs for patterns
- Check cPanel disk space and Git repo health
- Verify all secrets are still valid

## Next Steps

1. **Merge This PR** - Fixes the 415 error in cPanel pull workflow
2. **Test the Fix** - Push a change to main and verify the workflow succeeds
3. **Set Up Monitoring** - Enable email notifications for workflow failures
4. **Document Your Specific Setup** - Add your staging URL and cPanel details to this document

## Related Documentation

- [GIT_VERSION_CONTROL.md](GIT_VERSION_CONTROL.md) - Detailed Git configuration
- [DEPLOYING.md](DEPLOYING.md) - General deployment guide
- [DEPLOYMENT_STATUS.md](DEPLOYMENT_STATUS.md) - WordPress REST API deployment
- [.cpanel.yml](.cpanel.yml) - cPanel deployment configuration

## Questions?

- Check the [GitHub Actions documentation](https://docs.github.com/en/actions)
- Review recent workflow runs: `gh run list`
- View workflow logs: `gh run view RUN_ID`
- Open an issue if you need help

---

**Last Updated:** 2026-03-26  
**Status:** Git version control is working ✅ | cPanel pull needs fix 🔧 (fixed in this PR)
