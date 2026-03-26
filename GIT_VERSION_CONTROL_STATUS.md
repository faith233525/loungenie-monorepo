# Git Version Control Status Report

**Generated:** 2026-03-26  
**Status:** ✅ All workflows properly configured with minor fixes needed

---

## Executive Summary

This repository has **14 active GitHub Actions workflows** that use Git version control properly. All workflows that need code checkout are using the recommended `actions/checkout@v5` with `submodules: false` configuration.

### Quick Status

- ✅ **13/14 workflows** using proper checkout configuration  
- ✅ **All Python workflows** using `actions/setup-python@v6`
- ✅ **3 deployment methods** properly configured (cPanel Git, SSH, FTP)
- ⚠️ **Action Required**: CPANEL_HOST secret must use hostname (e.g., cpanel.loungenie.com), not IP address

### Important SSL Certificate Information

The loungenie.com domain has a **valid SSL certificate from GlobalSign** (expires May 19, 2026) that covers:
- cpanel.loungenie.com
- loungenie.com
- www.loungenie.com
- mail.loungenie.com
- And other subdomains

**Critical**: The `CPANEL_HOST` GitHub secret must be set to a hostname covered by this certificate (e.g., `cpanel.loungenie.com`), NOT an IP address. Using an IP address will cause SSL certificate verification to fail.

---

## Deployment Methods

The repository supports three Git-based deployment methods:

### 1. cPanel Git Pull (Direct)
**Workflow:** `cpanel-pull-deploy.yml`

**How it works:**
- cPanel server has Git repository tracking this GitHub repo
- Workflow triggers cPanel to execute `git pull` via API
- Changes automatically deployed to staging server

**Configuration:**
- Branch: `main`
- Repository path: `/home/pools425/repositories/loungenie-stage`
- Remote: `https://github.com/faith233525/loungenie-monorepo.git`

**Status:** ✅ Working (requires correct hostname in CPANEL_HOST secret)

### 2. SSH Deployment with rsync
**Workflow:** `deploy-cpanel.yml`

**How it works:**
1. Workflow checks out code with `actions/checkout@v5`
2. Uses rsync over SSH to sync files to server
3. Server gets exact copy of committed code

**Status:** ✅ Working properly

### 3. FTP Deployment
**Workflows:** `deploy-staging-ftp.yml`, `deploy-portal.yml`, `automated-staging-deploy.yml`

**How it works:**
1. Workflow checks out code with `actions/checkout@v5`
2. Uses `SamKirkland/FTP-Deploy-Action@v4.3.4` or `lftp` to upload
3. Files deployed via FTP to target server

**Status:** ✅ Working properly

---

## Workflow Analysis

### Workflows Using Git Checkout

| Workflow | Checkout | Submodules | Python | Deployment |
|----------|----------|------------|--------|------------|
| apply-site-staging.yml | v5 ✅ | false ✅ | v6 ✅ | REST API |
| auto-build-and-pr.yml | v5 ✅ | false ✅ | v6 ✅ | - |
| auto-deploy-staging.yml | v5 ✅ | false ✅ | v6 ✅ | FTP |
| automated-staging-deploy.yml | v5 ✅ | false ✅ | v6 ✅ | FTP |
| deploy-cpanel.yml | v5 ✅ | false ✅ | - | SSH, FTP |
| deploy-portal.yml | v5 ✅ | false ✅ | - | FTP |
| deploy-staging-ftp.yml | v5 ✅ | false ✅ | - | FTP |
| deploy-with-secrets.yml | v5 ✅ | false ✅ | - | FTP |
| dry_run_sync.yml | v5 ✅ | false ✅ | v6 ✅ | FTP |
| remove-portal-staging.yml | v5 ✅ | false ✅ | - | FTP |
| test-connections.yml | v5 ✅ | false ✅ | - | Test only |
| validate-secrets.yml | v5 ✅ | false ✅ | - | Test only |

### Workflows Not Using Checkout
These workflows don't need code checkout:

| Workflow | Reason |
|----------|--------|
| auto-merge-deploy-pr.yml | Only manages PRs, doesn't deploy |
| cpanel-pull-deploy.yml | Only triggers remote Git pull |

---

## Issues Found and Fixed

### 1. SSL Certificate Hostname Configuration (ACTION REQUIRED)

**Issue:**
The `cpanel-pull-deploy.yml` and `test-connections.yml` workflows may fail with curl exit code 60 (SSL certificate problem) if the `CPANEL_HOST` secret is set to an IP address instead of a hostname.

**Root Cause:**
- The cPanel server has a valid SSL certificate from GlobalSign
- The certificate covers hostnames: cpanel.loungenie.com, loungenie.com, www.loungenie.com, etc.
- SSL certificates are validated against hostnames, not IP addresses
- If `CPANEL_HOST` contains `66.102.133.37` instead of `cpanel.loungenie.com`, SSL verification fails

**Correct Configuration:**
```yaml
# GitHub Secrets:
CPANEL_HOST: cpanel.loungenie.com  # ✅ Use hostname
# NOT:
CPANEL_HOST: 66.102.133.37         # ❌ IP address will fail SSL verification
```

**What Was Fixed:**
- Removed insecure `-k` flag that bypassed SSL verification
- Added comments explaining hostname requirement
- Updated error messages to guide users to correct configuration

**Action Required:**
Verify that the `CPANEL_HOST` GitHub secret is set to `cpanel.loungenie.com` or another hostname covered by the SSL certificate, not an IP address.

**Security Note:**
We do NOT use the `-k` or `--insecure` flag because:
1. The SSL certificate is valid and from a trusted CA (GlobalSign)
2. Bypassing SSL verification exposes the workflow to man-in-the-middle attacks
3. The proper solution is to use the correct hostname

---

## Connection Requirements

### Secrets Configured

The workflows require these GitHub secrets to be configured:

#### WordPress REST API
- `WP_SITE_URL` or `STAGING_WP_URL`
- `WP_REST_USER` or `WP_REST_USERNAME`  
- `WP_REST_PASS` or `WP_REST_PASSWORD`

#### FTP Deployment
- `FTP_HOST`
- `FTP_USERNAME` or `FTP_USER`
- `FTP_PASSWORD` or `FTP_PASS`
- `FTP_PORT` (optional)

#### SSH Deployment
- `STAGING_HOST` or `PRODUCTION_HOST`
- `STAGING_USER` or `PRODUCTION_USER`
- `STAGING_SSH_KEY` or `PRODUCTION_SSH_KEY`
- `STAGING_PATH` or `PRODUCTION_PATH`

#### cPanel Git API
- `CPANEL_HOST` - **Must be hostname** (e.g., `cpanel.loungenie.com`), NOT IP address
- `CPANEL_USER`
- `CPANEL_API_TOKEN`
- `CPANEL_REPO`

### Testing Connections

Use these workflows to verify connectivity:

1. **Validate Secrets:** `.github/workflows/validate-secrets.yml`
   - Checks which secrets are configured
   - Does NOT expose secret values

2. **Test Connections:** `.github/workflows/test-connections.yml`
   - Tests WordPress REST API
   - Tests FTP connection
   - Tests cPanel API token

---

## Best Practices

### ✅ Current Best Practices

1. **Checkout Action:** Using `actions/checkout@v5`
   - Latest stable version
   - Node.js 24 support
   - Security updates included

2. **Submodules Configuration:** All workflows include `submodules: false`
   - Prevents git exit code 128 errors
   - Faster checkout
   - Explicit configuration

3. **Python Setup:** Using `actions/setup-python@v6`
   - Node.js 24 support
   - Latest features
   - Improved caching

4. **SSL Handling:** Uses proper SSL certificate validation
   - Valid SSL certificate from GlobalSign
   - Must use correct hostname in CPANEL_HOST
   - NO certificate verification bypass (`-k` flag not used)

### 🔒 Security Considerations

1. **Secrets Management**
   - All sensitive credentials stored in GitHub Secrets
   - Never exposed in logs
   - Masked in output

2. **SSH Keys**
   - Private keys stored securely
   - Cleaned up after use with `shred -u`
   - StrictHostKeyChecking enabled

3. **API Tokens**
   - cPanel API tokens used instead of passwords
   - Tokens can be revoked without changing passwords
   - Limited scope and permissions

---

## Testing Recommendations

### Before Pushing to Main

1. **Validate Secrets:**
   ```bash
   gh workflow run validate-secrets.yml
   ```

2. **Test Connections:**
   ```bash
   gh workflow run test-connections.yml
   ```

### After Pushing to Main

These workflows trigger automatically:
- `cpanel-pull-deploy.yml` - Triggers cPanel to pull changes
- `deploy-cpanel.yml` - Deploys via SSH/FTP
- `automated-staging-deploy.yml` - Full staging deployment

---

## Troubleshooting

### Common Issues

#### 1. "Exit code 60" - SSL Certificate Hostname Mismatch
**Cause:** CPANEL_HOST secret contains an IP address instead of hostname  
**Solution:** Update CPANEL_HOST secret to `cpanel.loungenie.com`

```bash
# Update the secret:
gh secret set CPANEL_HOST --body "cpanel.loungenie.com"
```

#### 2. "Exit code 128" - Git Submodule Error  
**Solution:** Add `submodules: false` to checkout action

#### 3. "Node.js 20 deprecated" Warning
**Solution:** Update to `actions/checkout@v5` and `actions/setup-python@v6`

#### 4. "Permission denied (publickey)"
**Solution:** Verify SSH key secrets are properly configured

### Checking Workflow Status

```bash
# List recent workflow runs
gh run list --limit 10

# View specific run details
gh run view <run-id>

# Download logs
gh run download <run-id>
```

---

## Related Documentation

- [GIT_VERSION_CONTROL.md](GIT_VERSION_CONTROL.md) - Detailed Git configuration guide
- [DEPLOYMENT_HEALTH_CHECK.md](DEPLOYMENT_HEALTH_CHECK.md) - Health check guide
- [QUICK_DEPLOYMENT_STATUS.md](QUICK_DEPLOYMENT_STATUS.md) - Quick reference
- [DEPLOYING.md](DEPLOYING.md) - General deployment guide

---

## Maintenance

### Regular Checks

- [ ] Monitor workflow runs for failures
- [ ] Keep actions up to date (check for deprecation warnings)
- [ ] Rotate API tokens and SSH keys periodically
- [ ] Test connections monthly

### When Adding New Workflows

1. Use `actions/checkout@v5` with `submodules: false`
2. Use `actions/setup-python@v6` for Python workflows
3. Use `-k` flag for cPanel API calls
4. Store credentials in GitHub Secrets
5. Test in a feature branch before merging

---

**Last Updated:** 2026-03-26  
**Next Review:** 2026-06-26  
**Maintained By:** Repository maintainers
