# Git Version Control in GitHub Actions Workflows

This repository uses Git version control for all deployment and automation workflows. This document explains how Git is configured and why it's important.

## Overview

All 14 GitHub Actions workflows in this repository properly use Git version control through the `actions/checkout` action. This ensures:

1. **Code Synchronization**: Workflows always work with the latest committed code
2. **Version Tracking**: Every deployment is tied to a specific Git commit
3. **Rollback Capability**: Problems can be traced to specific commits
4. **Audit Trail**: Full history of what was deployed and when

## Current Configuration

### Checkout Action Version

All workflows use **`actions/checkout@v5`** with the following configuration:

```yaml
- uses: actions/checkout@v5
  with:
    submodules: false
```

### Why These Settings?

#### `actions/checkout@v5`
- **Node.js 24 Support**: v5 is compatible with Node.js 24, avoiding deprecation warnings
- **Latest Features**: Includes security updates and bug fixes
- **Future-Proof**: Recommended version for all new workflows

#### `submodules: false`
- **Prevents Git Errors**: Avoids git exit code 128 errors from missing or misconfigured submodules
- **Faster Checkout**: Skips submodule initialization when not needed
- **Explicit Configuration**: Makes it clear the repo doesn't rely on submodules

### Python Setup Version

Workflows that use Python have been updated to **`actions/setup-python@v6`**:

```yaml
- uses: actions/setup-python@v6
  with:
    python-version: "3.11"
```

This provides:
- Node.js 24 compatibility
- Latest Python installation features
- Improved caching capabilities

## Workflows Using Git Version Control

All 14 workflows in `.github/workflows/` use Git version control:

1. **apply-site-staging.yml** - WordPress page sync
2. **auto-build-and-pr.yml** - Automated PR creation
3. **auto-deploy-staging.yml** - LG9 plugin deployment
4. **auto-merge-deploy-pr.yml** - PR automation
5. **automated-staging-deploy.yml** - Full staging deployment
6. **cpanel-pull-deploy.yml** - cPanel Git pull trigger
7. **deploy-cpanel.yml** - cPanel deployment (SSH/FTP)
8. **deploy-portal.yml** - Portal plugin deployment
9. **deploy-staging-ftp.yml** - FTP staging deployment
10. **deploy-with-secrets.yml** - Secret-based deployment
11. **dry_run_sync.yml** - Dry run testing
12. **remove-portal-staging.yml** - Portal cleanup
13. **test-connections.yml** - Connection testing
14. **validate-secrets.yml** - Secret validation

## Git-Based Deployment Methods

The repository supports three Git-based deployment methods:

### 1. Direct Git Pull (cPanel)

The `cpanel-pull-deploy.yml` workflow triggers cPanel to pull from the Git repository:

```yaml
- name: Trigger cPanel Pull
  run: |
    URL="https://${CPANEL_HOST}:2083/execute/Git/update_repo?repo=${REPO}"
    curl -X POST "$URL" -H "Authorization: cpanel ${CPANEL_USER}:${CPANEL_API_TOKEN}"
```

**How it works:**
- cPanel has a Git repository configured pointing to this GitHub repo
- The workflow calls the cPanel Git API to trigger a `git pull`
- Changes are automatically deployed to the staging server

**Branch:** The cPanel staging environment is configured to track the `main` branch

### 2. SSH Deployment with Git

The `deploy-cpanel.yml` workflow uses rsync over SSH after checking out code:

```yaml
- uses: actions/checkout@v5
  with:
    submodules: false
- name: Rsync files to server
  run: |
    rsync -avz --delete -e "ssh -i ~/.ssh/deploy_key" "$ARTIFACT_DIR/" "$USER@$HOST:$PATH_ON_SERVER/"
```

**How it works:**
- Workflow checks out the Git repository
- Uses rsync to sync files to the server via SSH
- Ensures server has exact copy of the committed code

### 3. FTP Deployment with Git Checkout

Multiple workflows use FTP after checking out code:

```yaml
- uses: actions/checkout@v5
  with:
    submodules: false
- uses: SamKirkland/FTP-Deploy-Action@v4.3.4
  with:
    server: ${{ secrets.FTP_HOST }}
    local-dir: .
```

**How it works:**
- Workflow checks out the Git repository
- Uploads files via FTP to the target server
- Maintains Git commit tracking in workflow logs

## Best Practices

### When Adding New Workflows

Always include the proper checkout configuration:

```yaml
jobs:
  your-job:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v5
        with:
          submodules: false
      
      # Your workflow steps here
```

### For Python Workflows

Include both checkout and Python setup:

```yaml
steps:
  - uses: actions/checkout@v5
    with:
      submodules: false
  
  - uses: actions/setup-python@v6
    with:
      python-version: "3.11"
```

### Avoiding Common Mistakes

❌ **Don't use outdated versions:**
```yaml
- uses: actions/checkout@v4  # Old version
```

✅ **Use current versions:**
```yaml
- uses: actions/checkout@v5
  with:
    submodules: false
```

❌ **Don't omit submodules parameter:**
```yaml
- uses: actions/checkout@v5  # Can cause git errors
```

✅ **Explicitly disable submodules:**
```yaml
- uses: actions/checkout@v5
  with:
    submodules: false
```

## Troubleshooting

### "Node.js 20 actions are deprecated"

**Symptom:** Warning messages about Node.js 20 deprecation

**Solution:** Update to v5/v6 actions as documented above

### "fatal: No url found for submodule"

**Symptom:** Git exit code 128 errors about submodules

**Solution:** Add `submodules: false` to checkout action

### "Permission denied (publickey)"

**Symptom:** Git operations fail with SSH errors

**Solution:** Verify SSH keys are properly configured in secrets

## Version History

- **2026-03-26**: Updated all workflows to actions/checkout@v5 and actions/setup-python@v6
- Previous: Used actions/checkout@v4 without submodules configuration

## Related Documentation

- [DEPLOYING.md](DEPLOYING.md) - General deployment guide
- [DEPLOYMENT_STATUS.md](DEPLOYMENT_STATUS.md) - Current deployment status
- [.cpanel.yml](.cpanel.yml) - cPanel deployment configuration
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [actions/checkout Documentation](https://github.com/actions/checkout)

## Maintaining Git Version Control

To ensure Git version control remains properly configured:

1. **Always use the checkout action** in new workflows
2. **Keep action versions up to date** (monitor for deprecation warnings)
3. **Include submodules: false** unless you specifically need submodules
4. **Document any Git-related changes** in this file
5. **Test workflows** after making Git configuration changes

---

**Last Updated:** 2026-03-26  
**Maintained By:** Repository maintainers  
**Questions?** Open an issue or contact the development team
