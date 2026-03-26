# 🚀 Quick Reference: Update Pages Workflow

## One-Line Summary
Automate WordPress page updates with Kadence blocks via GitHub Actions - safe, tested, with backups.

## 🎯 Most Common Use Cases

### 1️⃣ Preview Changes (Safe - No Risk)
```
Actions → Update Pages - Manual → Run workflow
Pages: all
Dry run: ✅ true
Create backup: ✅ true
```
**Result:** See what would change without actually changing anything

### 2️⃣ Update Homepage
```
Actions → Update Pages - Manual → Run workflow
Pages: home
Dry run: ❌ false
Create backup: ✅ true
```
**Result:** Updates homepage only, keeps backup

### 3️⃣ Update Multiple Pages
```
Actions → Update Pages - Manual → Run workflow
Pages: home,about,contact,features
Dry run: ❌ false
Create backup: ✅ true
```
**Result:** Updates several pages at once

## 📍 Quick Access

**Run Workflow:** GitHub.com → Actions → "Update Pages - Manual" → Run workflow

## ⚙️ Required Setup (One Time Only)

Repository Settings → Secrets → Actions → New secret:

| Secret Name | Value | Example |
|-------------|-------|---------|
| `WP_USERNAME` | WordPress admin username | `copilot` |
| `WP_APP_PASSWORD` | Application Password | `abcd 1234 efgh 5678 ijkl` |
| `WP_SITE_URL` | Full site URL | `https://loungenie.com/staging` |

## 🎨 Page Options

| Input | Meaning |
|-------|---------|
| `all` | Every page in content/ |
| `home` | Homepage only |
| `home,about,contact` | Multiple specific pages |
| `4701,4862` | Pages by WordPress ID |

## 🛡️ Safety Defaults

- ✅ Dry run enabled (preview mode)
- ✅ Backups created automatically
- ✅ Investor pages excluded
- ✅ Media validated first
- ✅ Stops on any error

## 📊 What You Get

### With Dry Run = true (Default)
- ✓ List of pages that would be updated
- ✓ Validation results
- ✓ Zero risk - nothing changes

### With Dry Run = false
- ✓ Pages actually updated
- ✓ Backups saved (30 days)
- ✓ Results logged (14 days)
- ✓ Verification report

## 🔥 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| "Secrets missing" | Add WP_USERNAME, WP_APP_PASSWORD, WP_SITE_URL |
| "401 Unauthorized" | Create new Application Password in WordPress |
| "Media not found" | Update media_lookup.json with media IDs |
| Nothing updates | Set dry_run to false |

## 📱 Create Application Password

WordPress Admin → Users → Profile → Application Passwords:
1. Name it "GitHub Actions"
2. Click "Add New"
3. Copy password (shows once!)
4. Add as WP_APP_PASSWORD secret

## 🎓 Best Practice Flow

```
1. DRY RUN (dry_run: true)     ← Start here - safe!
   ↓
2. Review logs
   ↓
3. LIVE RUN (dry_run: false)   ← Apply changes
   ↓
4. Download backups
   ↓
5. Verify on website
```

## 📦 Where Stuff Lives

```
Repository Root
├── .github/workflows/
│   ├── update-pages-manual.yml          ← The workflow
│   └── UPDATE_PAGES_WORKFLOW_GUIDE.md   ← Full guide
└── update-pages/
    ├── content/                          ← Page content (JSON)
    │   ├── home.json
    │   ├── about.json
    │   └── ...
    ├── media_lookup.json                 ← Media file → ID mapping
    └── scripts/
        └── update_pages.py               ← Core update script
```

## 🎯 Decision Tree

**Do you want to:**

- **See what would change?** → Run with `dry_run: true`
- **Actually update pages?** → Run with `dry_run: false`
- **Update everything?** → Use `pages: all`
- **Update specific pages?** → Use `pages: home,about`
- **First time running?** → Start with `dry_run: true`
- **Need to rollback?** → Download backup artifacts, use WordPress editor

## 💡 Pro Tips

1. **Always dry-run first** - It's free and shows exactly what will happen
2. **Keep backups** - They're saved automatically for 30 days
3. **Update staging first** - Test before production
4. **One page at a time** - When testing a new page format
5. **Check artifacts** - Download them for your records

## ⚡ Emergency Rollback

If something goes wrong:

1. Go to Actions → workflow run → Artifacts
2. Download "page-backups-XXX"
3. Extract JSON files
4. Use WordPress REST API or admin to restore
5. Or: Revert via WordPress admin (Pages → Revisions)

## 📞 Help

- **Full Guide:** `.github/workflows/UPDATE_PAGES_WORKFLOW_GUIDE.md`
- **Update Pages README:** `update-pages/README_UPDATE_PAGES.md`
- **Workflow Logs:** Check GitHub Actions logs for details

## ✅ Pre-Flight Checklist

Before running workflow:

- [ ] Secrets configured?
- [ ] Content files ready?
- [ ] Media uploaded to WordPress?
- [ ] Dry-run test completed?
- [ ] Know which pages updating?
- [ ] Team notified?

---

**Need more detail?** See full guide: `UPDATE_PAGES_WORKFLOW_GUIDE.md`
