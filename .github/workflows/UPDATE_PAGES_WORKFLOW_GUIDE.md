# Update Pages Workflow - User Guide

## Overview

The **Update Pages - Manual** workflow automates the process of updating WordPress pages on your LounGenie website using Kadence/Gutenberg blocks via the WordPress REST API.

## 🎯 Features

- ✅ **Manual trigger** - Run on-demand when you're ready to update pages
- ✅ **Flexible page selection** - Update all pages, specific pages by name or ID
- ✅ **Dry run mode** - Preview changes without applying them
- ✅ **Automatic backups** - Saves original content before making changes
- ✅ **Media validation** - Verifies all media IDs exist before updating
- ✅ **Safety features** - Can exclude investor pages automatically
- ✅ **Detailed logging** - Full audit trail of changes
- ✅ **Artifact storage** - Backups and results saved for 30 days

## 🚀 Quick Start

### 1. Prerequisites

First, ensure you have these repository secrets configured:

Go to: **Repository Settings → Secrets and variables → Actions**

Add these secrets:
- `WP_USERNAME` - WordPress admin username (e.g., `copilot` or your admin user)
- `WP_APP_PASSWORD` - WordPress Application Password (not your login password!)
- `WP_SITE_URL` - Full site URL (e.g., `https://loungenie.com/staging` or `https://loungenie.com`)

#### How to create a WordPress Application Password:

1. Log into WordPress admin
2. Go to **Users → Profile**
3. Scroll to **Application Passwords**
4. Enter a name (e.g., "GitHub Actions")
5. Click **Add New Application Password**
6. Copy the generated password immediately (you won't see it again!)
7. Add it as `WP_APP_PASSWORD` secret in GitHub

### 2. Running the Workflow

#### Option A: Via GitHub UI (Easiest)

1. Go to your repository on GitHub
2. Click the **Actions** tab
3. Select **Update Pages - Manual** from the workflow list
4. Click **Run workflow** button
5. Configure the options:
   - **Pages to update:** Choose what to update
   - **Dry run mode:** Start with `true` to preview changes
   - **Create backup:** Recommended to keep `true`
   - **Exclude investor pages:** Keep `true` for safety
   - **Validate media first:** Keep `true` to check images
6. Click **Run workflow**

#### Option B: Via GitHub CLI

```bash
# Dry run to preview changes
gh workflow run "Update Pages - Manual" \
  --field pages_to_update="all" \
  --field dry_run="true" \
  --field create_backup="true"

# Actually apply changes
gh workflow run "Update Pages - Manual" \
  --field pages_to_update="home,about,contact" \
  --field dry_run="false" \
  --field create_backup="true"
```

## 📖 Configuration Options

### Pages to Update

You can specify which pages to update in several ways:

| Value | Description | Example |
|-------|-------------|---------|
| `all` | Update all pages in the content directory | `all` |
| Page names | Comma-separated page names | `home,about,contact,features` |
| Page IDs | Comma-separated WordPress page IDs | `4701,4862,5139` |

**Available page names:**
- `home` - Homepage
- `about` - About page
- `contact` - Contact page
- `features` - Features page
- `gallery` - Gallery page

### Dry Run Mode

- **`true` (default):** Preview what would be updated without making changes
- **`false`:** Actually apply the updates to WordPress

💡 **Best Practice:** Always run with `dry_run: true` first to preview changes!

### Create Backup

- **`true` (default):** Save original page content before updating
- **`false`:** Skip backup (not recommended)

Backups are stored as workflow artifacts for 30 days.

### Exclude Investor Pages

- **`true` (default):** Automatically skip investor-related pages (5668, 5651, 5686, 5716)
- **`false`:** Allow updating investor pages (use with caution!)

### Validate Media First

- **`true` (default):** Check that all media IDs exist before updating
- **`false`:** Skip media validation

## 📋 Workflow Examples

### Example 1: Preview Updates for All Pages (Safe)

```yaml
Pages to update: all
Dry run mode: true
Create backup: true
Exclude investor pages: true
Validate media first: true
```

This will show you what would be updated without making any changes.

### Example 2: Update Homepage Only

```yaml
Pages to update: home
Dry run mode: false
Create backup: true
Exclude investor pages: true
Validate media first: true
```

Updates just the homepage with backup.

### Example 3: Update Multiple Specific Pages

```yaml
Pages to update: home,about,contact,features
Dry run mode: false
Create backup: true
Exclude investor pages: true
Validate media first: true
```

Updates several pages at once.

### Example 4: Update by Page ID

```yaml
Pages to update: 4701,4862,5139
Dry run mode: false
Create backup: true
Exclude investor pages: true
Validate media first: true
```

Updates specific pages by their WordPress ID.

## 📦 Understanding the Output

### Workflow Artifacts

After the workflow runs, you'll find artifacts containing:

1. **page-backups-XXX** (if backup enabled and not dry-run)
   - Original page content in JSON format
   - Use these to restore pages if needed
   - Retained for 30 days

2. **update-results-XXX** (if not dry-run)
   - API responses from WordPress
   - Log files
   - Validation reports
   - Retained for 14 days

### Summary Report

Each workflow run generates a summary showing:
- Configuration used
- Whether it was a dry run or live update
- Status of the update
- Links to artifacts

## 🔄 Typical Workflow Process

### Phase 1: Setup & Validation
1. Add required secrets to GitHub
2. Prepare your page content in `update-pages/content/` directory
3. Update `media_lookup.json` with media file mappings
4. Run workflow in **dry-run mode** to validate

### Phase 2: Testing
1. Review dry-run results in workflow logs
2. Fix any issues with content or media
3. Run another dry-run if needed
4. Verify all validations pass

### Phase 3: Deployment
1. Run workflow with **dry_run: false**
2. Monitor the workflow logs
3. Download backup artifacts for safekeeping
4. Verify pages on the actual website
5. Document the changes

### Phase 4: Verification (Optional)
1. Use the verify-updates job output
2. Manually check updated pages
3. Test on different devices
4. Verify SEO and accessibility

## 🛡️ Safety Features

### Automatic Backups
Before any page is updated, the original content is saved to `backups/` and uploaded as a workflow artifact.

### Dry Run Mode
Preview exactly what will change before applying updates.

### Investor Page Protection
By default, investor pages are excluded from updates to prevent accidental changes to sensitive content.

### Media Validation
Checks that all referenced media IDs exist in WordPress before attempting updates.

### Stop on Error
If any page update fails, the workflow stops to prevent cascading issues.

## 🔧 Troubleshooting

### Workflow fails at "Validate Prerequisites"

**Problem:** Missing required secrets

**Solution:** Add all three required secrets:
- `WP_USERNAME`
- `WP_APP_PASSWORD`
- `WP_SITE_URL`

### "401 Unauthorized" error

**Problem:** Invalid credentials

**Solution:**
1. Verify `WP_USERNAME` is correct
2. Generate a new Application Password
3. Update `WP_APP_PASSWORD` secret

### "Missing media mapping" warnings

**Problem:** Media files referenced in content don't exist in `media_lookup.json`

**Solution:**
1. Run `scripts/validate_media_lookup.py` locally
2. Add missing media entries to `media_lookup.json`
3. Or update content to use existing media IDs

### Page content not updating

**Problem:** Using dry-run mode or permissions issue

**Solution:**
1. Verify `dry_run` is set to `false`
2. Check WordPress user has page edit permissions
3. Review workflow logs for specific errors

## 📚 Related Documentation

- **Main README:** `update-pages/README_UPDATE_PAGES.md`
- **Page Update Plan:** `update-pages/PAGE_UPDATES_PLAN.md`
- **Implementation Checklist:** `update-pages/IMPLEMENTATION_CHECKLIST.md`
- **Workflow Index:** `update-pages/00_START_HERE_INDEX.md`

## 🤝 Support

For issues or questions:
1. Check the workflow logs in GitHub Actions
2. Review the artifacts for detailed error messages
3. Consult the documentation in `update-pages/` directory
4. Verify all secrets are correctly configured

## 📝 Notes

- **Content Format:** Pages must be in the `update-pages/content/` directory as JSON files
- **Media Files:** Images and media must be uploaded to WordPress first, then mapped in `media_lookup.json`
- **Kadence Blocks:** Content uses Gutenberg/Kadence block format
- **Staging First:** Test on staging environment before production
- **Regular Backups:** Keep workflow artifacts and maintain separate backups

## ✅ Checklist Before Running

- [ ] All secrets configured in GitHub
- [ ] Content files prepared in `content/` directory
- [ ] Media files uploaded to WordPress
- [ ] `media_lookup.json` updated with media IDs
- [ ] Tested with dry-run mode first
- [ ] Verified which pages will be updated
- [ ] Backup strategy in place
- [ ] Team notified of impending changes

---

**Last Updated:** March 2026
**Workflow File:** `.github/workflows/update-pages-manual.yml`
