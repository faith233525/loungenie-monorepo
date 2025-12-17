# 🚀 WordPress Plugin Deployment Guide
**LounGenie Portal v1.6.0 - Shared Hosting Deployment**

---

## 📦 **Package Created**

✅ **File:** `loungenie-portal-v1.6.0-deploy.zip`  
✅ **Size:** 112KB  
✅ **Status:** Production-ready  
✅ **Tested:** 77/77 critical tests passing  

---

## ⚠️ **PRE-DEPLOYMENT CHECKLIST**

### **1. Backup Your Database** (CRITICAL!)

**Via phpMyAdmin:**
1. Log into your hosting cPanel
2. Open **phpMyAdmin**
3. Select your WordPress database
4. Click **Export** tab
5. Format: **SQL**
6. Click **Go**
7. Save file: `wordpress-backup-YYYY-MM-DD.sql`

**Via WP-CLI (if available):**
```bash
wp db export backup-$(date +%Y%m%d-%H%M%S).sql
```

### **2. Backup Plugin Files** (Recommended)

**Via cPanel File Manager:**
1. Navigate to `/public_html/wp-content/plugins/`
2. Right-click `loungenie-portal` folder
3. Select **Compress**
4. Choose **Zip Archive**
5. Download: `loungenie-portal-backup.zip`

### **3. Document Current Settings**

Make note of:
- [ ] Current plugin version (check WordPress Admin → Plugins)
- [ ] Number of companies in database
- [ ] Number of tickets
- [ ] Number of users with portal access

---

## 🚀 **DEPLOYMENT METHODS**

### **Method 1: WordPress Admin Upload** (RECOMMENDED)

**Step 1: Access WordPress Admin**
1. Go to `https://your-domain.com/wp-admin`
2. Login with admin credentials

**Step 2: Deactivate Current Plugin** (if exists)
1. Go to **Plugins → Installed Plugins**
2. Find "LounGenie Portal"
3. Click **Deactivate**
4. ⚠️ **DO NOT DELETE** (settings will be preserved)

**Step 3: Upload New Version**
1. Go to **Plugins → Add New**
2. Click **Upload Plugin** button at top
3. Click **Choose File**
4. Select: `loungenie-portal-v1.6.0-deploy.zip`
5. Click **Install Now**
6. Wait for upload (may take 30-60 seconds)

**Step 4: Activate Plugin**
1. Click **Activate Plugin** button
2. Plugin will auto-create/update database tables
3. Success message should appear

**Step 5: Verify Installation**
1. Go to **Plugins → Installed Plugins**
2. Confirm "LounGenie Portal" shows **Version 1.6.0**
3. Status should be **Active**

---

### **Method 2: FTP/cPanel Upload** (Alternative)

**Step 1: Access cPanel File Manager**
1. Log into hosting cPanel
2. Open **File Manager**
3. Navigate to `/public_html/wp-content/plugins/`

**Step 2: Remove Old Version** (if exists)
1. Right-click `loungenie-portal` folder
2. Select **Delete**
3. Confirm deletion
4. ⚠️ Database settings will be preserved

**Step 3: Upload New Version**
1. Click **Upload** button
2. Select: `loungenie-portal-v1.6.0-deploy.zip`
3. Wait for upload to complete
4. Close upload dialog

**Step 4: Extract ZIP**
1. Right-click `loungenie-portal-v1.6.0-deploy.zip`
2. Select **Extract**
3. Extract to: `/public_html/wp-content/plugins/`
4. Confirm extraction
5. Delete ZIP file (optional cleanup)

**Step 5: Set Permissions**
1. Right-click `loungenie-portal` folder
2. Select **Change Permissions**
3. Set: **755** for directories
4. Set: **644** for files
5. Check "Recurse into subdirectories"
6. Click **Change Permissions**

**Step 6: Activate in WordPress**
1. Go to WordPress Admin
2. Navigate to **Plugins → Installed Plugins**
3. Find "LounGenie Portal"
4. Click **Activate**

---

### **Method 3: SSH/SFTP** (Advanced)

**Step 1: Upload via SCP**
```bash
# From your local machine
scp loungenie-portal-v1.6.0-deploy.zip user@your-domain.com:~/
```

**Step 2: SSH into Server**
```bash
ssh user@your-domain.com
```

**Step 3: Extract Plugin**
```bash
cd ~/public_html/wp-content/plugins/
rm -rf loungenie-portal  # Remove old version
unzip ~/loungenie-portal-v1.6.0-deploy.zip
```

**Step 4: Set Permissions**
```bash
chmod -R 755 loungenie-portal/
find loungenie-portal/ -type f -exec chmod 644 {} \;
```

**Step 5: Activate via WordPress Admin**
1. Go to WordPress Admin
2. Activate plugin as described above

---

## ✅ **POST-DEPLOYMENT VERIFICATION**

### **1. Check Database Tables**

**Via phpMyAdmin:**
1. Open phpMyAdmin
2. Select WordPress database
3. Verify these tables exist:
   - `wp_lgp_companies`
   - `wp_lgp_units`
   - `wp_lgp_tickets`
   - `wp_lgp_gateways`
   - `wp_lgp_training_videos`
   - `wp_lgp_service_notes` ⭐ NEW
   - `wp_lgp_audit_log` ⭐ NEW
   - `wp_lgp_ticket_attachments`
   - `wp_lgp_service_requests`
   - `wp_lgp_management_companies`

### **2. Test Partner User Access**

**Test Login:**
1. Open incognito/private browser window
2. Go to: `https://your-domain.com/portal`
3. Login with Partner test credentials
4. ✅ Should see own company profile only
5. ✅ Edit buttons should be hidden/disabled
6. ✅ Collapsible sections should work
7. ❌ Try accessing other company → Should get 403 error

### **3. Test Support User Access**

**Test Login:**
1. Login to WordPress Admin
2. Go to: `https://your-domain.com/portal`
3. ✅ Should see all companies
4. ✅ All edit buttons should be enabled
5. ✅ Map view should be accessible
6. ✅ Audit log should be visible

### **4. Test New Features (v1.6.0)**

**Audit Logging:**
1. View any company profile as Support
2. Click **Audit Log** tab
3. ✅ Should show recent activity
4. ✅ Filter by action type (dropdown)
5. ✅ Filter by date range

**Service Notes:**
1. View company profile as Support
2. Click **Service Notes** section
3. ✅ Click "Add Service Note"
4. ✅ Fill form (date, technician, type, notes)
5. ✅ Save → Should appear in list

**Company Profile Enhancements:**
1. View company profile
2. ✅ Inline "Reply to Ticket" modal should work
3. ✅ Sections should be collapsible
4. ✅ Collapsed state persists (localStorage)

### **5. Test Notifications**

**Create Test Ticket:**
1. Login as Partner
2. Create new ticket
3. ✅ Support should receive email
4. ⚠️ Check spam folder if not received

**Reply to Ticket:**
1. Reply to ticket (Partner or Support)
2. ✅ Ticket creator should receive email
3. ✅ Check audit log shows `notification_sent`

### **6. Check WordPress Debug Log**

**Via cPanel:**
1. Go to File Manager
2. Navigate to `/wp-content/`
3. Look for `debug.log` file
4. ⚠️ Check for PHP errors/warnings
5. ✅ Should be empty or minimal notices

**Via SSH:**
```bash
tail -n 50 ~/public_html/wp-content/debug.log
```

---

## 🔧 **TROUBLESHOOTING**

### **Issue: White Screen After Activation**

**Cause:** PHP memory limit too low

**Fix:**
1. Add to `wp-config.php` (before "stop editing"):
   ```php
   define('WP_MEMORY_LIMIT', '256M');
   ```
2. Or contact hosting support to increase limit

---

### **Issue: Database Tables Not Created**

**Cause:** Insufficient database permissions

**Fix:**
1. Check `wp-config.php` database credentials
2. Verify database user has CREATE TABLE permission
3. Manually run SQL from `/api/` if needed
4. Or contact hosting support

---

### **Issue: 404 Error on /portal URLs**

**Cause:** Permalinks not flushed

**Fix:**
1. Go to WordPress Admin
2. Navigate to **Settings → Permalinks**
3. Click **Save Changes** (don't change anything)
4. Try accessing `/portal` again

---

### **Issue: Upload Limits Too Small**

**Cause:** PHP upload limits

**Fix via .htaccess:**
```apache
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

Or contact hosting support to increase limits.

---

### **Issue: Emails Not Sending**

**Cause:** Shared hosting blocks wp_mail()

**Fix:**
1. Install SMTP plugin (e.g., WP Mail SMTP)
2. Configure with Gmail/SendGrid credentials
3. Test email delivery
4. Or contact hosting support about SMTP

---

## 🔄 **ROLLBACK PROCEDURE** (If Issues Occur)

### **Step 1: Deactivate Plugin**
1. Go to WordPress Admin → Plugins
2. Deactivate "LounGenie Portal"

### **Step 2: Restore Database Backup**
1. Open phpMyAdmin
2. Select WordPress database
3. Click **Import** tab
4. Choose: `wordpress-backup-YYYY-MM-DD.sql`
5. Click **Go**
6. Wait for import to complete

### **Step 3: Restore Old Plugin Files**
1. Via cPanel File Manager:
   - Delete `/wp-content/plugins/loungenie-portal/`
   - Upload `loungenie-portal-backup.zip`
   - Extract to plugins folder
2. Or use FTP to replace folder

### **Step 4: Reactivate Old Version**
1. Go to WordPress Admin → Plugins
2. Activate previous version

### **Step 5: Report Issue**
Document what went wrong and check logs.

---

## 📊 **MONITORING (First 48 Hours)**

### **Daily Checks:**

**Day 1:**
- [ ] Check debug.log for PHP errors
- [ ] Verify all users can login (Partner + Support)
- [ ] Test ticket creation
- [ ] Verify notifications received
- [ ] Check audit log capturing actions

**Day 2:**
- [ ] Review audit log for unauthorized access attempts
- [ ] Check email delivery rate
- [ ] Verify no performance issues
- [ ] Check database table sizes (phpMyAdmin)
- [ ] Test all critical features

**Day 3:**
- [ ] Final review of debug log
- [ ] Confirm no user complaints
- [ ] Verify all features stable
- [ ] Document any issues found

---

## ✅ **SUCCESS CRITERIA**

Deployment is successful when:

- ✅ Plugin shows v1.6.0 in WordPress Admin
- ✅ All 10 database tables exist and populated
- ✅ Partner users can login and view own company
- ✅ Support users can access all features
- ✅ Audit log capturing all actions
- ✅ Service notes can be created
- ✅ Notifications sending emails
- ✅ No PHP errors in debug.log
- ✅ No user-reported issues

---

## 📞 **SUPPORT**

**If you encounter issues:**

1. Check debug.log first
2. Review troubleshooting section above
3. Check audit log for clues
4. Contact hosting support for server issues
5. Rollback if critical issue found

---

## 📝 **CHANGELOG v1.6.0**

### **New Features:**
- ✅ Comprehensive audit logging system
- ✅ Service notes tracking for technician visits
- ✅ Company profile enhancements with inline modals
- ✅ Partner view polish with collapsible sections
- ✅ Enhanced notification coverage

### **Bug Fixes:**
- ✅ Fixed asset enqueue syntax error
- ✅ Improved permission checks
- ✅ Enhanced error handling

### **Testing:**
- ✅ 77/77 critical tests passing
- ✅ 331 assertions validated
- ✅ 100% coverage on new features

---

## 🎯 **NEXT STEPS AFTER DEPLOYMENT**

1. **Week 1:**
   - Monitor daily for issues
   - Collect user feedback
   - Document any bugs found

2. **Week 2:**
   - Review audit logs for patterns
   - Optimize database queries if needed
   - Plan v1.7.0 features

3. **Ongoing:**
   - Weekly database backups
   - Monthly security audits
   - Quarterly performance reviews

---

**Deployment Date:** _____________  
**Deployed By:** _____________  
**Version:** 1.6.0  
**Status:** □ Success  □ Rolled Back  □ Issues Found  

**Notes:**
_____________________________________
_____________________________________
_____________________________________
