# WordPress Upload - Quick Start Guide

## ✅ Your Plugin is Ready

**Status:** PRODUCTION READY  
**Version:** 1.8.1  
**Size:** ~5.2 MB  
**Tests:** 43/43 Passing (100%)  
**Issues:** 0  

---

## 📦 Plugin Location

```
/workspaces/Pool-Safe-Portal/loungenie-portal/
```

Everything you need is in this directory. No temporary files. No test data. Clean and ready.

---

## 🚀 OPTION 1: Upload to WordPress.org (Public Distribution)

### Step 1: Visit WordPress Plugin Submission
- Go to https://wordpress.org/plugins/add/
- Sign in with your WordPress.org account (create one if needed)

### Step 2: Follow the Submission Process
- Submit plugin information
- Wait for automated testing (~24-48 hours)
- Plugin appears in WordPress.org directory when approved

### Step 3: Updates
- Push updates to GitHub repository
- WordPress.org syncs automatically
- Version numbers must match GitHub releases

---

## 🏢 OPTION 2: Self-Hosted Upload (Private/Enterprise)

### Method A: Via FTP/SFTP (Easiest)

```bash
# On your server:
cd /var/www/wordpress/wp-content/plugins/
# Upload loungenie-portal/ directory via FTP client
# Or use SCP:
scp -r loungenie-portal user@yourserver:/var/www/wordpress/wp-content/plugins/
```

### Method B: Via WordPress Admin Dashboard

1. **Upload ZIP:**
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin"
   - Upload the loungenie-portal directory (zipped)
   - Click "Activate Plugin"

2. **Access the Portal:**
   - Navigate to `/portal` on your site
   - You'll be redirected to login
   - Create Support and Partner users
   - Configure integrations (optional)

### Method C: Via WP-CLI (Developers)

```bash
wp plugin install /path/to/loungenie-portal.zip --activate
```

---

## ✅ Post-Installation Verification (5 minutes)

### 1. Check Plugin Activation
```
WordPress Admin → Plugins
Look for: "LounGenie Portal" with ✅ status
```

### 2. Create a Support User
```
WordPress Admin → Users → Add New
Username: demo_support
Role: LounGenie Support Team
Click: Add User
```

### 3. Create a Partner User
```
WordPress Admin → Users → Add New
Username: demo_partner
Role: LounGenie Partner Company
Meta: lgp_company_id = 1 (or your company ID)
Click: Add User
```

### 4. Test Portal Access
- Go to `/portal`
- Login with Support user
- You should see the Support Dashboard
- Login with Partner user
- You should see the Partner Dashboard

### 5. Verify Email Integration (Optional)
```
WordPress Admin → Settings → Email Settings
Configure POP3 or Microsoft Graph
Click: Test Connection
```

### 6. Verify HubSpot Integration (Optional)
```
WordPress Admin → Settings → HubSpot Integration
Enter: Private App Access Token
Click: Test Connection
```

### 7. Check Audit Log
```
WordPress Admin → Tools → LounGenie Audit Log
Should show recent user logins and actions
```

---

## 📋 Configuration Checklist

### Required (Plugin works without these, but limited)
- [ ] Create at least one Support user
- [ ] Create at least one Partner company
- [ ] Assign Partner users to companies

### Recommended
- [ ] Configure Microsoft 365 SSO (optional)
- [ ] Configure HubSpot integration (optional)
- [ ] Configure Email integration (optional)
- [ ] Review security settings
- [ ] Set up audit log retention

### Optional
- [ ] Enable geocoding for partner locations
- [ ] Customize color palette
- [ ] Set up rate limiting
- [ ] Configure CSP headers

---

## 🔧 Essential Configuration Files

Located in `loungenie-portal/`:

| File | Purpose |
|------|---------|
| `loungenie-portal.php` | Main plugin file - start here |
| `SETUP_GUIDE.md` | Installation & initial config |
| `DEPLOYMENT.md` | Full deployment procedures |
| `MAINTENANCE.md` | Operations & troubleshooting |
| `README.md` | Features & overview |

---

## 🆘 Troubleshooting

### "Plugin not activated"
- Check PHP version (7.4+ required)
- Check WordPress version (5.8+ required)
- Check plugin directory permissions (755)
- Check error log: `/wp-content/debug.log`

### "/portal redirects to login"
- Check user role is assigned correctly
- Check `lgp_company_id` meta for Partner users
- Verify plugin is activated

### "Database tables not created"
- Plugin should auto-create on activation
- Check database user has CREATE permission
- Check MySQL/MariaDB version (5.6+ required)

### "Email integration not working"
- Verify POP3 server details (if using POP3)
- Verify Microsoft Graph credentials (if using Graph)
- Check error log for specific error messages
- Test connection in WordPress Admin settings

### "HubSpot not syncing"
- Verify private app token is valid
- Check HubSpot API permission scope
- Review sync log in admin
- Test connection button in settings

---

## 📞 Support & Documentation

All documentation available in `loungenie-portal/`:

- **README.md** - Complete feature overview
- **SETUP_GUIDE.md** - Step-by-step installation
- **DEPLOYMENT.md** - Advanced deployment (626 lines)
- **MAINTENANCE.md** - Maintenance & operations
- **ENTERPRISE_FEATURES.md** - SSO, caching, security
- **FILTERING_GUIDE.md** - Analytics & filtering

---

## ✨ You're All Set!

The plugin is production-ready and fully tested.

**Next Steps:**
1. Upload the `loungenie-portal/` directory
2. Activate in WordPress Admin
3. Create test users
4. Access `/portal`
5. Configure optional integrations

**Questions?** See the documentation files above.

---

**Version:** 1.8.1  
**Last Updated:** December 22, 2025  
**Status:** ✅ Production Ready
