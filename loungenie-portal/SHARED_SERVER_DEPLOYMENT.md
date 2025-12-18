# Shared Server Deployment Guide
## LounGenie Portal v1.8.0

---

## QUICK START (5 minutes)

### 1. Upload Plugin
```bash
# Via FTP/File Manager
- Download: loungenie-portal.zip (or clone)
- Upload to: wp-content/plugins/
- Extract (if needed)
```

### 2. Activate in WordPress
```
- Login to wp-admin
- Plugins → Installed Plugins
- Click "Activate" on LounGenie Portal
```

### 3. Configure Basic Settings
```
- Go to LounGenie Portal menu
- Configure company settings
- Set user roles (Partner/Support)
- Test dashboard access
```

### 4. Verify Installation
```
- Dashboard should load
- No errors in browser console
- Check wp-admin > Tools > LounGenie Health Check
```

---

## STEP-BY-STEP DEPLOYMENT

### Step 1: Pre-Deployment Checklist

**Check Requirements:**
```
✅ PHP 7.4+ installed
✅ MySQL 5.7+ or MariaDB 10.2+
✅ WordPress 5.8+
✅ 50MB disk space available
✅ 64MB memory limit recommended
✅ File upload support (2MB+)
```

**Check Access:**
```
✅ FTP/SSH access to server
✅ WordPress admin access
✅ Database access (if needed for debugging)
✅ Error log access
```

### Step 2: File Upload

**Using File Manager (Hosting Control Panel):**
```
1. Connect to server
2. Navigate to wp-content/plugins/
3. Create folder: loungenie-portal (if not exists)
4. Upload plugin files to this folder
5. Ensure correct file permissions (644 for files, 755 for folders)
```

**Using FTP Client:**
```
1. Connect via FTP
2. Navigate to wp-content/plugins/
3. Upload loungenie-portal folder
4. Set permissions:
   - Folders: 755
   - PHP files: 644
   - .htaccess: 644
```

**Using SSH (if available):**
```bash
cd /home/username/public_html/wp-content/plugins
wget https://github.com/faith233525/Pool-Safe-Portal/archive/main.zip
unzip main.zip
mv Pool-Safe-Portal-main/loungenie-portal .
rm -rf Pool-Safe-Portal-main main.zip
chmod -R 755 loungenie-portal/wp-content/
chmod -R 644 loungenie-portal/wp-content/*.php
```

### Step 3: Database Preparation

**Automatic (Recommended):**
```
- Plugin creates tables on activation
- No manual SQL needed
- Takes < 5 seconds
```

**Manual (if needed):**
```sql
-- Tables created automatically, but you can verify:
SHOW TABLES LIKE 'wp_lgp_%';

-- You should see:
-- wp_lgp_companies
-- wp_lgp_units
-- wp_lgp_tickets
-- wp_lgp_attachments
-- wp_lgp_audit_log
-- (and more...)
```

### Step 4: WordPress Activation

**Via WordPress Admin:**
```
1. Login to wp-admin
2. Plugins → Installed Plugins
3. Find "LounGenie Portal"
4. Click "Activate"
5. Wait for page to redirect
6. Check for any error messages
```

**Verify Activation:**
```
- New menu item appears: "LounGenie Portal"
- No fatal errors in error_log
- Database tables created (phpmyadmin shows wp_lgp_* tables)
```

### Step 5: Post-Activation Setup

**Configure Company Profile:**
```
1. Go to LounGenie Portal → Companies
2. Create or configure your company
3. Set company name, logo, contact info
4. Configure service offerings
```

**Set User Roles:**
```
1. Go to LounGenie Portal → Users
2. Assign roles:
   - Partner: Field service teams
   - Support: Administrative support
3. Configure user permissions
```

**Enable Features:**
```
1. Go to Settings → Features
2. Enable/disable as needed:
   - Training Videos
   - Attachments
   - Audit Log
   - Email Notifications
3. Save settings
```

---

## VERIFICATION STEPS

### 1. Activation Verification
```
✅ Plugin appears in Plugins list
✅ Plugin menu appears in wp-admin
✅ No errors on activation page
✅ Check wp-content/debug.log (should be empty or warnings only)
```

### 2. Database Verification
```
✅ Tables created in database
✅ No MySQL errors
✅ Data can be inserted/retrieved
✅ Relationships working
```

### 3. Frontend Verification
```
✅ Dashboard loads (if user has access)
✅ No fatal PHP errors
✅ CSS/JS files loading
✅ No 404 errors in console
```

### 4. API Verification
```
✅ REST endpoints responding
✅ Authentication working
✅ Nonces being generated
✅ Security headers present
```

### 5. Performance Verification
```
✅ Pages load in < 3 seconds
✅ No timeout errors
✅ Memory usage < 15MB
✅ Database queries < 1 second
```

### Run Automated Test
```
1. Access: yourdomain.com/wp-content/plugins/loungenie-portal/tests/shared-server-test.php
2. Login with admin account (if prompted)
3. Review test results
4. Check for any FAIL status items
5. Address any issues (see Troubleshooting below)
```

---

## TROUBLESHOOTING

### Problem: Plugin Won't Activate

**Symptom:** Activation fails with error message

**Solutions:**
```
1. Check error log:
   - Access wp-content/debug.log
   - Look for PHP errors
   
2. Increase memory limit temporarily:
   - Add to wp-config.php:
   define('WP_MEMORY_LIMIT', '128M');
   
3. Disable other plugins:
   - Temporarily deactivate all plugins
   - Activate LounGenie Portal alone
   - Reactivate other plugins one by one
   
4. Check PHP version:
   - Must be 7.4 or higher
   - Contact hosting provider if needed
   
5. Check database access:
   - Verify MySQL connection
   - Check user permissions
```

### Problem: Dashboard Shows Blank Page

**Symptom:** Dashboard loads but shows nothing

**Solutions:**
```
1. Check browser console for JS errors:
   - Press F12 in browser
   - Check Console tab
   - Note any error messages
   
2. Check server logs:
   - Access error_log in plugin directory
   - Look for PHP warnings/errors
   
3. Verify REST API:
   - Open: /wp-json/wp/v2/posts?per_page=1
   - Should return JSON data
   - If 404, REST API is not working
   
4. Clear cache:
   - Clear WordPress cache (if caching plugin installed)
   - Clear browser cache (CTRL+Shift+Delete)
   - Try again
   
5. Check user roles:
   - Verify user has proper role assigned
   - Check WordPress user permissions
```

### Problem: Slow Performance

**Symptom:** Pages load slowly, timeout errors

**Solutions:**
```
1. Check memory usage:
   - Add to page: <?php echo memory_get_usage(true) / 1024 / 1024; ?>
   - Should be < 10MB
   - If high, increase WP_MEMORY_LIMIT
   
2. Check database queries:
   - Add to wp-config.php: define('SAVEQUERIES', true);
   - Check total query time
   - Look for N+1 queries
   
3. Enable object caching:
   - Install Redis or Memcached plugin
   - Plugin will use automatically
   - Improves performance 2-5x
   
4. Reduce pagination size:
   - Go to Settings → Performance
   - Lower items per page
   - Reduces memory and time per request
   
5. Disable unnecessary features:
   - Go to Settings → Features
   - Disable training videos (frees 2MB)
   - Disable email notifications (frees 1MB)
   - Disable geocoding (frees 1MB)
```

### Problem: File Upload Fails

**Symptom:** Cannot upload attachments

**Solutions:**
```
1. Check uploads directory permissions:
   - wp-content/uploads/ should be 755
   - Use File Manager to set permissions
   - Or via SSH: chmod -R 755 wp-content/uploads/
   
2. Check file size limits:
   - Go to Settings → File Upload
   - Verify max size is acceptable
   - Contact hosting if limit too low
   
3. Check disk space:
   - Hosting control panel → Disk Usage
   - Should have > 1GB free space
   - Delete old files if needed
   
4. Check file types:
   - Only certain types allowed
   - Go to Settings → Security
   - Check allowed file types
   - PDF, DOC, DOCX, TXT, XLS, XLSX
   
5. Check upload directory ownership:
   - Via SSH: ls -l wp-content/uploads/
   - Should be owned by web server user
   - Contact hosting if needed
```

### Problem: Database Errors

**Symptom:** MySQL errors, database connection failed

**Solutions:**
```
1. Check connection parameters:
   - wp-config.php has correct details
   - DB_NAME, DB_USER, DB_PASSWORD correct
   
2. Check user permissions:
   - MySQL user should have:
   - SELECT, INSERT, UPDATE, DELETE, CREATE
   - ALTER, INDEX, DROP
   - Contact hosting support if needed
   
3. Check table space:
   - May have max table size limit
   - Check with hosting support
   - May need to archive old data
   
4. Check database compatibility:
   - Must be MySQL 5.7+ or MariaDB 10.2+
   - Contact hosting to check version
   
5. Check max connections:
   - Some shared servers limit concurrent connections
   - May cause timeouts
   - Contact hosting if frequent timeouts
```

---

## OPTIMIZATION FOR SHARED SERVERS

### 1. Memory Optimization

**In wp-config.php:**
```php
// Optimize memory usage
define( 'WP_MEMORY_LIMIT', '128M' );
define( 'WP_MAX_MEMORY_LIMIT', '256M' );

// Disable unnecessary features for shared servers
define( 'LOUNGENIE_DISABLE_TRAINING_VIDEOS', true );
define( 'LOUNGENIE_LAZY_LOAD_TRANSIENTS', true );
```

### 2. Database Optimization

**Monthly maintenance:**
```bash
# Via WP-CLI
wp db optimize

# Via SSH
mysql -u username -p database_name
OPTIMIZE TABLE wp_lgp_companies;
OPTIMIZE TABLE wp_lgp_tickets;
OPTIMIZE TABLE wp_lgp_attachments;
OPTIMIZE TABLE wp_lgp_audit_log;

# Or use hosting control panel (phpMyAdmin)
```

### 3. Enable Caching

**Install caching plugin:**
```
1. Plugins → Add New
2. Search: "Redis Cache" or "Memcached"
3. Install and activate
4. LounGenie Portal will use automatically
```

**In wp-config.php:**
```php
// Enable object caching
define( 'WP_CACHE', true );
```

### 4. Asset Optimization

**Enable minification:**
```
1. Plugins → Add New
2. Search: "WP Super Cache" or "W3 Total Cache"
3. Install and activate
4. Configure to minify CSS/JS
```

### 5. Image Optimization

**Install image optimization:**
```
1. Plugins → Add New
2. Search: "WP Smush"
3. Install and activate
4. Configure to auto-compress images
```

---

## SECURITY HARDENING

### Add to wp-config.php:
```php
// Security hardening
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true );
define( 'FORCE_SSL_ADMIN', true );
define( 'FORCE_SSL_LOGIN', true );

// Use security keys
define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
```

### Add .htaccess protection:
```apache
# In wp-content/plugins/loungenie-portal/.htaccess
<FilesMatch "\.php$">
    Order allow,deny
    Allow from all
</FilesMatch>

# Disable direct access to test files
<FilesMatch "^(test|debug)">
    Deny from all
</FilesMatch>
```

---

## MONITORING & MAINTENANCE

### Weekly Checks:
```
□ Check error_log for warnings
□ Monitor database size
□ Review user activity logs
□ Check for plugin updates
```

### Monthly Checks:
```
□ Optimize database: wp db optimize
□ Review backup status
□ Update WordPress core
□ Update all plugins
□ Clear old logs
```

### Quarterly Checks:
```
□ Full security audit
□ Performance review
□ Database integrity check
□ Archive old data
```

---

## SUPPORT & RESOURCES

### Documentation Files:
```
- README.md - Overview and features
- SETUP_GUIDE.md - Detailed setup
- IMPLEMENTATION_UPDATES.md - Recent changes
- FOLDER_STRUCTURE.md - File organization
- SHARED_SERVER_TEST_SUITE.md - Testing guide
```

### Test Your Installation:
```
Access: /wp-content/plugins/loungenie-portal/tests/shared-server-test.php
This runs automated tests and shows compatibility status
```

### Check Health:
```
Go to: LounGenie Portal → Tools → Health Check
Shows system status and recommendations
```

---

## ROLLBACK PROCEDURE

**If problems occur:**

### Step 1: Deactivate Plugin
```
- wp-admin → Plugins
- Find LounGenie Portal
- Click "Deactivate"
```

### Step 2: Disable Via .htaccess (if admin access lost)
```
# Add to .htaccess in WordPress root
RewriteRule ^wp-content/plugins/loungenie-portal/ - [L]
```

### Step 3: Restore From Backup
```
- Use hosting control panel to restore backup
- Or use backup plugin (UpdraftPlus, etc.)
- Restore to point before plugin activation
```

### Step 4: Delete Plugin Files (if needed)
```bash
# Via SSH
rm -rf wp-content/plugins/loungenie-portal/

# Via File Manager
- Delete loungenie-portal folder
- Verify folder no longer exists
```

---

## SUCCESS CRITERIA

Your plugin is ready for shared servers if:

✅ Activates without errors  
✅ Dashboard loads in < 3 seconds  
✅ Memory usage < 15MB on average  
✅ All database queries < 1 second  
✅ File uploads work (tested with 5MB file)  
✅ REST API responds < 500ms  
✅ Transient caching working  
✅ No permission errors  
✅ Compatible with common plugins  
✅ Monitoring in place  

---

**Status:** ✅ Ready for Shared Server Deployment  
**Version:** 1.8.0  
**Last Updated:** December 18, 2025

For updates and support: https://github.com/faith233525/Pool-Safe-Portal
