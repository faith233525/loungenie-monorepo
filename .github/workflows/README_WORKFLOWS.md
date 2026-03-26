# 📘 Page Update Workflow System

## Overview

This directory contains a comprehensive GitHub Actions workflow system for updating WordPress pages on the LounGenie website. The workflow automates page updates using the WordPress REST API with Kadence/Gutenberg blocks.

## 🗂️ Files in This Directory

### Workflow File
- **`update-pages-manual.yml`** - The main GitHub Actions workflow that automates page updates

### Documentation
- **`UPDATE_PAGES_WORKFLOW_GUIDE.md`** - Complete guide with setup, examples, and troubleshooting
- **`QUICK_REFERENCE.md`** - One-page quick start guide
- **`README_WORKFLOWS.md`** - This file (overview of all workflows)

## 🎯 What This Workflow Does

```
┌─────────────────────────────────────────────────────────────┐
│                   Update Pages Workflow                      │
└─────────────────────────────────────────────────────────────┘
                              ↓
         ┌────────────────────┼────────────────────┐
         ↓                    ↓                    ↓
   Validate Prerequisites  Update Pages    Verify Results
   - Check secrets        - Validate media  - Check accessibility
   - Verify credentials   - Create backups  - Test pages
                          - Apply updates
                          - Save artifacts
```

## 🚀 Quick Start

### 1. First-Time Setup (Required)

Add these secrets in **Repository Settings → Secrets and variables → Actions**:

| Secret | Description | Example |
|--------|-------------|---------|
| `WP_USERNAME` | WordPress admin username | `copilot` |
| `WP_APP_PASSWORD` | WordPress Application Password | `xxxx xxxx xxxx xxxx` |
| `WP_SITE_URL` | Full WordPress site URL | `https://loungenie.com/staging` |

### 2. Run the Workflow

**Via GitHub UI:**
1. Go to **Actions** tab
2. Select **"Update Pages - Manual"**
3. Click **"Run workflow"**
4. Configure options and click **"Run workflow"**

**Via GitHub CLI:**
```bash
gh workflow run "Update Pages - Manual" \
  --field pages_to_update="all" \
  --field dry_run="true"
```

### 3. Review Results

- Check workflow logs in the Actions tab
- Download artifacts (backups and results)
- Verify changes on the WordPress site

## 📋 Workflow Options

When running the workflow, you can configure:

| Option | Default | Description |
|--------|---------|-------------|
| **pages_to_update** | `all` | Which pages to update (`all`, `home,about,contact`, or `4701,4862`) |
| **dry_run** | `true` | Preview changes without applying (`true` = safe preview, `false` = apply) |
| **create_backup** | `true` | Create backups before updating |
| **exclude_investor_pages** | `true` | Automatically skip investor pages |
| **validate_media_first** | `true` | Validate media IDs before updating |

## 🛡️ Safety Features

### Built-in Protections
- ✅ **Dry Run Mode (Default)** - Preview changes before applying
- ✅ **Automatic Backups** - Original content saved for 30 days
- ✅ **Investor Page Protection** - Excludes sensitive pages by default
- ✅ **Media Validation** - Checks all images exist before updating
- ✅ **Stop on Error** - Prevents cascading failures
- ✅ **Secret Validation** - Verifies credentials before running

### Backup & Recovery
- Backups stored as workflow artifacts (30-day retention)
- Each backup includes full page JSON
- Easy restoration via WordPress REST API or admin panel

## 📊 Workflow Stages

### Stage 1: Validation
```yaml
✓ Check required secrets exist
✓ Verify WordPress credentials
✓ Validate media_lookup.json
✓ Confirm prerequisites met
```

### Stage 2: Update
```yaml
✓ Checkout repository
✓ Install Python dependencies
✓ Validate media files
✓ Create backup directory
✓ Determine pages to update
✓ Run update script
✓ Upload artifacts
```

### Stage 3: Verification
```yaml
✓ Check pages accessible
✓ Run verification scripts
✓ Generate summary report
```

## 🎨 Use Cases

### Use Case 1: Safe Preview (Recommended First Run)
**Scenario:** Want to see what would change without risk

**Configuration:**
```
pages_to_update: all
dry_run: true
create_backup: true
```

**Result:** See complete list of changes, zero risk

### Use Case 2: Update Homepage
**Scenario:** New homepage design ready to deploy

**Configuration:**
```
pages_to_update: home
dry_run: false
create_backup: true
```

**Result:** Homepage updated, original backed up

### Use Case 3: Bulk Page Update
**Scenario:** Redesign multiple pages at once

**Configuration:**
```
pages_to_update: home,about,contact,features
dry_run: false
create_backup: true
```

**Result:** All specified pages updated together

### Use Case 4: Update by Page ID
**Scenario:** Know exact WordPress page IDs to update

**Configuration:**
```
pages_to_update: 4701,4862,5139
dry_run: false
create_backup: true
```

**Result:** Specific pages updated by ID

## 🔧 How It Works

### Content Structure
```
update-pages/
├── content/              # Page definitions (JSON)
│   ├── home.json        # Homepage content
│   ├── about.json       # About page content
│   ├── contact.json     # Contact page content
│   └── features.json    # Features page content
├── media_lookup.json    # Maps filenames to WordPress media IDs
├── scripts/
│   └── update_pages.py  # Main update script
├── backups/             # Auto-created page backups
└── outputs/             # Update results and logs
```

### Update Flow
```
1. Read page JSON from content/
   ↓
2. Resolve media filenames to IDs (media_lookup.json)
   ↓
3. Build Gutenberg block markup
   ↓
4. Backup existing page (REST API GET)
   ↓
5. Update page (REST API PATCH)
   ↓
6. Save results and logs
```

### Page JSON Format
```json
{
  "page_id": 4701,
  "title": "Home",
  "blocks": [
    {
      "block": "kadence/rowlayout",
      "attrs": { "className": "hero-section" },
      "innerBlocks": [
        {
          "block": "core/heading",
          "innerHTML": "Welcome to LounGenie"
        }
      ]
    }
  ]
}
```

## 📚 Documentation Reference

| Document | Purpose | Audience |
|----------|---------|----------|
| **QUICK_REFERENCE.md** | Fast lookup guide | Everyone |
| **UPDATE_PAGES_WORKFLOW_GUIDE.md** | Complete documentation | Developers, admins |
| **README_WORKFLOWS.md** | This file - system overview | Technical users |
| `update-pages/README_UPDATE_PAGES.md` | Core update system | Developers |
| `update-pages/PAGE_UPDATES_PLAN.md` | Update strategy | Project managers |

## 🔍 Monitoring & Debugging

### View Workflow Runs
GitHub → Actions → "Update Pages - Manual" → Select run

### Download Artifacts
Workflow run page → Artifacts section → Download:
- `page-backups-XXX` - Original page content (30 days)
- `update-results-XXX` - Logs and results (14 days)

### Check Logs
Each workflow step has detailed logs showing:
- What pages were processed
- API responses
- Any errors or warnings
- Validation results

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| "Missing secrets" | Secrets not configured | Add WP_USERNAME, WP_APP_PASSWORD, WP_SITE_URL |
| "401 Unauthorized" | Invalid credentials | Generate new Application Password |
| "Media not found" | Missing media mapping | Update media_lookup.json |
| "Page not updated" | Dry run mode active | Set dry_run to false |
| "Permission denied" | User lacks permissions | Use admin user or grant page edit rights |

## 🎓 Best Practices

### Before Running
1. ✅ Test content locally first
2. ✅ Validate media files exist in WordPress
3. ✅ Run with dry_run: true initially
4. ✅ Review dry-run results carefully
5. ✅ Update staging before production

### During Execution
1. ✅ Monitor workflow logs in real-time
2. ✅ Watch for validation warnings
3. ✅ Note any skipped pages

### After Completion
1. ✅ Download backup artifacts immediately
2. ✅ Verify changes on actual website
3. ✅ Test on multiple devices
4. ✅ Document what was changed
5. ✅ Keep artifacts for rollback if needed

## 🔄 Integration with Existing Workflows

This workflow complements existing deployment workflows:

| Workflow | Purpose | When to Use |
|----------|---------|-------------|
| **update-pages-manual.yml** | Update page content | Content changes, redesigns |
| `automated-staging-deploy.yml` | Deploy code to staging | Code/plugin updates |
| `deploy-cpanel.yml` | Deploy to cPanel | Production deployments |
| `test-deployment-setup.yml` | Test connections | Verify setup |

## 🆘 Support & Resources

### Getting Help
1. Check workflow logs for error messages
2. Review the comprehensive guide (UPDATE_PAGES_WORKFLOW_GUIDE.md)
3. Check troubleshooting section
4. Review artifacts for detailed information

### Additional Resources
- WordPress REST API: https://developer.wordpress.org/rest-api/
- Gutenberg Blocks: https://developer.wordpress.org/block-editor/
- Kadence Blocks: https://www.kadencewp.com/kadence-blocks/
- GitHub Actions: https://docs.github.com/en/actions

## ✅ Maintenance Checklist

### Weekly
- [ ] Review workflow run history
- [ ] Check artifact storage usage
- [ ] Verify secrets still valid

### Monthly
- [ ] Rotate Application Passwords
- [ ] Archive important backups
- [ ] Review and clean old artifacts
- [ ] Update documentation if needed

### Before Major Updates
- [ ] Test workflow on staging
- [ ] Verify all secrets current
- [ ] Review content changes
- [ ] Ensure backups are current
- [ ] Document changes

## 📝 Version History

- **v1.0.0** (March 2026) - Initial release
  - Manual trigger workflow
  - Dry-run mode
  - Automatic backups
  - Media validation
  - Comprehensive documentation

## 🤝 Contributing

To improve this workflow system:
1. Test changes on a feature branch
2. Update documentation accordingly
3. Test with dry-run mode first
4. Verify on staging before production
5. Document any new features or changes

---

**Questions?** See the full guide in `UPDATE_PAGES_WORKFLOW_GUIDE.md`

**Quick Start?** See `QUICK_REFERENCE.md`

**Need Help?** Check the troubleshooting section in the comprehensive guide.
