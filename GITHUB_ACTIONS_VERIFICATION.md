# GitHub Actions Workflow Verification Guide

This guide explains how to verify and monitor the GitHub Actions CI/CD workflow after merging PR #2 (copilot/implement-portal-routing) into the main branch.

## Overview

The GitHub Actions workflow (`.github/workflows/loungenie-portal-ci.yml`) automatically runs when code is pushed to the main branch or when a pull request is created. It performs:

- **PHP Unit Tests** - Validates PHP code functionality
- **JavaScript Tests** - Validates JavaScript code using Jest
- **CodeQL Security Scan** - Scans for security vulnerabilities (PHP + JavaScript)
- **REST API Tests** - Tests API endpoints
- **Deployment Artifact Generation** - Creates deployable ZIP file

## Workflow Trigger

The workflow triggers automatically when:

1. **PR #2 is merged into main** - This is the primary trigger for your deployment
2. **Code is pushed to main branch** - Any future code commits
3. **Pull requests are opened** - For future development
4. **Manual trigger** - Via GitHub Actions UI (workflow_dispatch)

## Step-by-Step Verification

### Step 1: Access GitHub Actions

1. Navigate to your repository: `https://github.com/faith233525/Pool-Safe-Portal`
2. Click the **Actions** tab at the top
3. You should see the list of workflow runs

### Step 2: Identify the Workflow Run

After merging PR #2, you should see a new workflow run:

- **Name**: "LounGenie Portal CI/CD" or similar
- **Event**: "push" or "pull_request"
- **Branch**: main
- **Triggered by**: The merge commit
- **Status**: 🟡 Running / ✅ Success / ❌ Failed

### Step 3: Monitor Workflow Progress

Click on the workflow run to see detailed progress:

#### Job 1: PHP Tests
- Sets up PHP 7.4+ environment
- Installs Composer dependencies
- Runs PHPUnit tests
- Reports test results

**Expected Duration**: 2-5 minutes

**Success Criteria**: 
- ✅ All PHP unit tests pass
- ✅ No syntax errors
- ✅ No fatal errors

#### Job 2: JavaScript Tests
- Sets up Node.js environment
- Installs npm dependencies
- Runs Jest tests
- Validates JavaScript code

**Expected Duration**: 1-3 minutes

**Success Criteria**:
- ✅ All Jest tests pass
- ✅ No JavaScript errors
- ✅ Code coverage meets threshold

#### Job 3: CodeQL Security Scan
- Analyzes PHP and JavaScript code
- Scans for security vulnerabilities
- Checks for:
  - SQL injection vulnerabilities
  - Cross-site scripting (XSS)
  - Code injection
  - Path traversal
  - Insecure authentication

**Expected Duration**: 5-10 minutes

**Success Criteria**:
- ✅ Zero critical vulnerabilities
- ✅ Zero high vulnerabilities
- ⚠️ Medium/low vulnerabilities acceptable with review
- ✅ Security-events successfully uploaded

#### Job 4: REST API Tests
- Tests API endpoints under `/wp-json/lgp/v1/` or `/psp/v1/`
- Validates authentication
- Tests CRUD operations
- Checks response formats

**Expected Duration**: 2-4 minutes

**Success Criteria**:
- ✅ All API endpoints respond correctly
- ✅ Authentication works
- ✅ Data validation passes

#### Job 5: Build Deployment Artifact
- Minifies assets
- Creates deployment ZIP
- Uploads artifact to GitHub

**Expected Duration**: 1-2 minutes

**Success Criteria**:
- ✅ ZIP file created successfully
- ✅ All required files included
- ✅ Artifact uploaded and available for download

### Step 4: Review Test Results

Click on each job to see detailed logs:

```
> Setup
  ✓ Check out code
  ✓ Set up environment

> Run Tests
  ✓ Install dependencies
  ✓ Run test suite
  ✓ Generate coverage report

> Upload Results
  ✓ Upload test results
  ✓ Upload coverage report
```

### Step 5: Check CodeQL Results

1. Go to **Security** tab in your repository
2. Click **Code scanning alerts**
3. Review any alerts found by CodeQL
4. Expected status: **No vulnerabilities found** (0 alerts)

If alerts are found:
- Review each alert
- Determine if it's a false positive
- Fix legitimate security issues
- Re-run the workflow

### Step 6: Download Deployment Artifact

Once the workflow completes successfully:

1. Scroll to the bottom of the workflow run page
2. Find **Artifacts** section
3. Click to download:
   - `loungenie-portal-deployment.zip` or
   - `poolsafe-portal-deployment.zip`
4. Save the ZIP file for WordPress deployment

**Artifact Contents**:
- Plugin PHP files
- Minified CSS/JS assets
- Templates
- Configuration files
- README and documentation

## Workflow File Location

After PR #2 is merged, the workflow file will be at:

```
.github/workflows/loungenie-portal-ci.yml
```

## Expected Workflow Configuration

The workflow should include:

### CodeQL Languages
```yaml
- name: Initialize CodeQL
  uses: github/codeql-action/codeql-init@v4
  with:
    languages:
      - javascript
      - php
```

**Note**: The languages must be in multiline list format (not comma-separated) to avoid YAML syntax errors.

### Permissions
```yaml
permissions:
  contents: read
  security-events: write
  actions: read
```

### PHP Version Matrix
```yaml
strategy:
  matrix:
    php: ['7.4', '8.0', '8.1']
```

## Monitoring and Notifications

### Email Notifications

GitHub sends email notifications for:
- ✅ Workflow succeeded
- ❌ Workflow failed
- ⚠️ Workflow cancelled

Check your GitHub notification settings:
1. GitHub Settings → Notifications
2. Enable "GitHub Actions" notifications

### Slack Integration (Optional)

To get Slack notifications:

1. Add this step to the workflow:
```yaml
- name: Slack Notification
  uses: 8398a7/action-slack@v3
  with:
    status: ${{ job.status }}
    webhook_url: ${{ secrets.SLACK_WEBHOOK }}
  if: always()
```

2. Add `SLACK_WEBHOOK` to repository secrets

### Status Badge

Add a status badge to your README.md:

```markdown
![CI/CD](https://github.com/faith233525/Pool-Safe-Portal/actions/workflows/loungenie-portal-ci.yml/badge.svg)
```

This shows the current workflow status in your repository.

## Troubleshooting

### Workflow Failed: YAML Syntax Error

**Symptom**: Error message like "A sequence was not expected"

**Solution**: 
- Check CodeQL languages configuration
- Use multiline list format:
  ```yaml
  languages:
    - javascript
    - php
  ```
- NOT comma-separated: `languages: javascript, php`

### Workflow Failed: Permissions Error

**Symptom**: "Resource not accessible by integration"

**Solution**:
- Add required permissions at workflow level:
  ```yaml
  permissions:
    security-events: write
    contents: read
  ```

### Workflow Failed: Tests Failed

**Symptom**: PHP or Jest tests failing

**Solution**:
1. Review test output in workflow logs
2. Fix failing tests locally
3. Push fix to trigger new workflow run
4. Only fix tests related to your changes

### Workflow Failed: Artifact Upload

**Symptom**: "Unable to upload artifact"

**Solution**:
- Check artifact name doesn't contain invalid characters
- Verify files exist before upload
- Check artifact size (GitHub has size limits)

### CodeQL Scan Timeout

**Symptom**: CodeQL job times out after 45+ minutes

**Solution**:
- Reduce codebase size by excluding vendor directories
- Add timeout configuration:
  ```yaml
  timeout-minutes: 30
  ```
- Split CodeQL into separate workflows for PHP and JavaScript

### No Artifacts Generated

**Symptom**: Artifacts section is empty

**Solution**:
- Verify artifact upload step completed successfully
- Check artifact retention settings (default: 90 days)
- Ensure artifact path is correct in workflow

## Workflow Best Practices

### 1. Caching Dependencies

Speed up workflow with caching:

```yaml
- name: Cache Composer dependencies
  uses: actions/cache@v3
  with:
    path: vendor
    key: composer-${{ hashFiles('composer.lock') }}

- name: Cache npm dependencies
  uses: actions/cache@v3
  with:
    path: node_modules
    key: npm-${{ hashFiles('package-lock.json') }}
```

### 2. Parallel Jobs

Run independent jobs in parallel to save time:

```yaml
jobs:
  php-tests:
    runs-on: ubuntu-latest
  js-tests:
    runs-on: ubuntu-latest
    # Both run simultaneously
```

### 3. Conditional Execution

Skip unnecessary jobs:

```yaml
- name: Run tests
  if: github.event_name == 'pull_request'
```

### 4. Matrix Testing

Test multiple PHP versions:

```yaml
strategy:
  matrix:
    php: ['7.4', '8.0', '8.1', '8.2']
```

## Security Scan Results

### Zero Vulnerabilities Expected

The PR #2 description states:
- ✅ **CodeQL: 0 vulnerabilities**
- ✅ Prepared statements (no SQL injection)
- ✅ Output escaping (no XSS)
- ✅ Nonce verification (CSRF protection)
- ✅ OAuth 2.0 security
- ✅ bcrypt password hashing

### If Vulnerabilities Found

1. Review each alert in Security tab
2. Determine severity (critical, high, medium, low)
3. Check if false positive
4. Fix legitimate issues:
   - Add input validation
   - Use prepared statements
   - Escape output
   - Implement proper authentication checks
5. Re-run CodeQL scan
6. Document any accepted risks

## Continuous Deployment

### Automatic Deployment (Future Enhancement)

The workflow currently generates artifacts. To enable automatic deployment:

1. Add deployment step:
```yaml
- name: Deploy to WordPress
  uses: easingthemes/ssh-deploy@v2
  with:
    SSH_PRIVATE_KEY: ${{ secrets.SSH_KEY }}
    REMOTE_HOST: ${{ secrets.HOST }}
    REMOTE_USER: ${{ secrets.USERNAME }}
    TARGET: /path/to/wordpress/wp-content/plugins/
```

2. Add required secrets in repository settings

3. Configure staging/production environments

## Next Steps

After verifying the workflow runs successfully:

1. ✅ Download deployment artifact
2. ✅ Review CodeQL security scan results
3. ✅ Deploy to staging environment
4. ✅ Test deployed plugin thoroughly
5. ✅ Configure Azure AD and WordPress SSO
6. ✅ Import sample data
7. ✅ Conduct stakeholder review
8. ✅ Deploy to production
9. ✅ Monitor performance and errors
10. ✅ Set up automated monitoring

## Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [CodeQL Documentation](https://codeql.github.com/docs/)
- [Artifact Upload/Download](https://github.com/actions/upload-artifact)
- [GitHub Security Features](https://docs.github.com/en/code-security)

## Support

For workflow issues:

- Check workflow run logs for detailed errors
- Review GitHub Actions status page
- Check repository permissions
- Verify secrets are configured correctly
- Review workflow file syntax
