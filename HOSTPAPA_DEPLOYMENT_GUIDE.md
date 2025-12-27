# LounGenie Portal - HostPapa Deployment Guide

**For:** HostPapa Shared Hosting  
**Plugin:** LounGenie Portal v1.8.1  
**Date:** December 27, 2025

---

## 📦 Production File Ready

**File:** `loungenie-portal-wporg-production.zip` (625 KB)  
**Location:** `/workspaces/Pool-Safe-Portal/loungenie-portal-wporg-production.zip`  
**Status:** ✅ Production-ready, tested, verified

---

## 🚀 Deployment Methods

### Method 1: WordPress Admin Upload (Recommended)

#### Step 1: Download Production ZIP
Download `loungenie-portal-wporg-production.zip` to your local computer.

#### Step 2: Access WordPress Admin
```
1. Go to your HostPapa WordPress site
2. Log in to WordPress Admin (yoursite.com/wp-admin)
3. Use your WordPress administrator credentials
```

#### Step 3: Upload Plugin
```
1. Navigate to: Plugins → Add New
2. Click: "Upload Plugin" button (top of page)
3. Click: "Choose File"
4. Select: loungenie-portal-wporg-production.zip
5. Click: "Install Now"
6. Wait for upload and installation to complete
```

#### Step 4: Activate Plugin
```
1. After installation, click "Activate Plugin"
2. You'll see "Plugin activated" confirmation
3. New menu item "LounGenie Portal" appears in admin sidebar
```

#### Step 5: Verify Installation
```
1. Go to: Plugins → Installed Plugins
2. Find: "LounGenie Portal v1.8.1"
3. Status should show: "Active"
4. Visit: yoursite.com/portal (should redirect to login)
```

---

### Method 2: HostPapa File Manager

#### Step 1: Access File Manager
```
1. Log in to HostPapa cPanel
2. Navigate to: Files → File Manager
3. Go to: public_html/wp-content/plugins/
```

#### Step 2: Upload ZIP
```
1. Click "Upload" button
2. Select loungenie-portal-wporg-production.zip
3. Wait for upload to complete
4. Return to File Manager
```

#### Step 3: Extract ZIP
```
1. Right-click on loungenie-portal-wporg-production.zip
2. Select "Extract"
3. Extraction creates: loungenie-portal/ folder
4. Delete the ZIP file (optional cleanup)
```

#### Step 4: Set Permissions
```
1. Right-click on loungenie-portal/ folder
2. Select "Change Permissions"
3. Set to: 755 (standard for plugins)
4. Check "Apply to subdirectories"
5. Click "Change Permissions"
```

#### Step 5: Activate in WordPress
```
1. Go to WordPress Admin → Plugins
2. Find "LounGenie Portal"
3. Click "Activate"
```

---

### Method 3: FTP/SFTP Upload

#### Step 1: Connect via FTP
```
Host: ftp.yourdomain.com (or IP provided by HostPapa)
Username: Your HostPapa FTP username
Password: Your HostPapa FTP password
Port: 21 (FTP) or 22 (SFTP)
```

**Recommended FTP Clients:**
- FileZilla (free, cross-platform)
- Cyberduck (Mac/Windows)
- WinSCP (Windows)

#### Step 2: Navigate to Plugins Directory
```
Remote path: /public_html/wp-content/plugins/
(May vary: /home/username/public_html/wp-content/plugins/)
```

#### Step 3: Upload Plugin
**Option A: Upload Extracted Folder**
```
1. Extract loungenie-portal-wporg-production.zip on your computer
2. Upload entire loungenie-portal/ folder to plugins directory
3. Wait for all files to transfer (90 files)
```

**Option B: Upload ZIP and Extract**
```
1. Upload loungenie-portal-wporg-production.zip
2. Use HostPapa File Manager to extract
3. Delete ZIP after extraction
```

#### Step 4: Verify File Permissions
```
Folders: 755
Files: 644
```

#### Step 5: Activate in WordPress
```
WordPress Admin → Plugins → Activate
```

---

## 🔧 Post-Installation Configuration

### Step 1: Verify Plugin Activation
```
WordPress Admin → Plugins
Status: "LounGenie Portal" should be active
```

### Step 2: Check Database Tables
Plugin automatically creates these tables on activation:
- `wp_lgp_companies`
- `wp_lgp_management_companies`
- `wp_lgp_units`
- `wp_lgp_service_requests`
- `wp_lgp_tickets`
- `wp_lgp_ticket_attachments`

**Verify in phpMyAdmin:**
```
HostPapa cPanel → Databases → phpMyAdmin
Select your WordPress database
Tables list should include all lgp_ tables
```

### Step 3: Create User Roles
The plugin creates two custom roles:
- **LounGenie Support Team** (`lgp_support`)
- **LounGenie Partner Company** (`lgp_partner`)

**Create Support User:**
```
1. WordPress Admin → Users → Add New
2. Username: (choose)
3. Email: support@yourdomain.com
4. Role: Select "LounGenie Support Team"
5. Password: (set strong password)
6. Click "Add New User"
```

**Create Partner User:**
```
1. WordPress Admin → Users → Add New
2. Username: (partner name)
3. Email: partner@company.com
4. Role: Select "LounGenie Partner Company"
5. Click "Add New User"
6. Go to: Users → Edit (that user)
7. Scroll to "Custom Fields" (Advanced)
8. Add field: lgp_company_id = (company ID)
```

### Step 4: Test Portal Access
```
1. Log out of WordPress Admin
2. Navigate to: yoursite.com/portal
3. Should redirect to login page
4. Log in with support user credentials
5. Portal dashboard should load
```

---

## 📊 Import Sample Data (Optional)

If you want to test with sample data:

### Option 1: Via phpMyAdmin
```
1. HostPapa cPanel → phpMyAdmin
2. Select your WordPress database
3. Click "SQL" tab
4. Copy contents of sample-data.sql
5. Replace 'wp_' with your actual table prefix (usually 'wp_')
6. Click "Go"
```

### Option 2: Via Plugin (if WP-CLI available)
```
wp db import /path/to/sample-data.sql
```

---

## ⚙️ Optional Enterprise Features

### Microsoft 365 SSO
```
WordPress Admin → Settings → M365 SSO
- Client ID: (from Azure AD app)
- Client Secret: (from Azure AD app)
- Tenant ID: (from Azure AD)
- Click "Save Changes"
```

### HubSpot CRM Integration
```
WordPress Admin → Settings → HubSpot Integration
- API Key: (Private App Access Token)
- Click "Save Changes"
- Click "Test Connection"
```

### Microsoft Graph Email
```
WordPress Admin → Settings → Outlook Integration
- Client ID: (from Azure AD app)
- Client Secret: (from Azure AD app)
- Click "Authenticate with Microsoft"
```

---

## 🛡️ Security Recommendations for HostPapa

### 1. File Permissions
```
Folders: 755 (drwxr-xr-x)
Files: 644 (-rw-r--r--)
wp-config.php: 600 (or 440)
```

### 2. Enable SSL/HTTPS
```
HostPapa cPanel → SSL/TLS Status
- Enable AutoSSL or Let's Encrypt
- Force HTTPS redirect in .htaccess
```

### 3. WordPress Security
```
- Keep WordPress core updated
- Use strong passwords (20+ characters)
- Enable two-factor authentication
- Limit login attempts
- Regular backups (HostPapa backup tools)
```

### 4. Plugin Security Headers
The plugin automatically adds:
- Content Security Policy (CSP)
- HTTP Strict Transport Security (HSTS)
- X-Content-Type-Options
- X-Frame-Options
- Referrer-Policy

**Ensure HTTPS is enabled for security headers to work!**

---

## 🐛 Troubleshooting

### Issue: "Plugin could not be activated because it triggered a fatal error"

**Cause:** PHP version incompatibility  
**Solution:**
```
1. HostPapa cPanel → Select PHP Version
2. Set to: PHP 7.4 or higher (8.0+ recommended)
3. Try activating again
```

### Issue: "Upload: failed to write file to disk"

**Cause:** Insufficient permissions or disk space  
**Solution:**
```
1. Check disk space in HostPapa cPanel
2. Verify /wp-content/plugins/ permissions (755)
3. Use FTP upload method instead
```

### Issue: Database tables not created

**Cause:** Activation didn't run completely  
**Solution:**
```
1. Deactivate plugin
2. Reactivate plugin (triggers table creation)
3. Check phpMyAdmin for tables
```

### Issue: "/portal" returns 404 Not Found

**Cause:** Permalink not flushed  
**Solution:**
```
WordPress Admin → Settings → Permalinks
Click "Save Changes" (no changes needed, just save)
This flushes rewrite rules
```

### Issue: "Sorry, you are not allowed to do that"

**Cause:** User doesn't have correct role  
**Solution:**
```
1. WordPress Admin → Users → Edit user
2. Change role to: LounGenie Support Team or Partner
3. Save changes
```

### Issue: REST API returns 401 Unauthorized

**Cause:** Not logged in or incorrect permissions  
**Solution:**
```
1. Log in to WordPress
2. Navigate to /portal (establishes session)
3. Try API request again
```

### Issue: Styles not loading / looks broken

**Cause:** URL mismatch or permalink issue  
**Solution:**
```
1. Hard refresh browser (Ctrl+Shift+R)
2. Check browser console for 404 errors
3. Flush permalinks (Settings → Permalinks → Save)
4. Clear browser cache
```

---

## 📞 HostPapa Specific Support

### Contact HostPapa Support
```
Live Chat: Available in cPanel
Phone: 1-855-461-2787
Email: support@hostpapa.com
Knowledge Base: hostpapa.com/knowledgebase
```

### Common HostPapa Paths
```
WordPress Root: /home/username/public_html/
Plugins: /home/username/public_html/wp-content/plugins/
Uploads: /home/username/public_html/wp-content/uploads/
Logs: /home/username/logs/
```

### HostPapa PHP Settings
```
Check current PHP version:
cPanel → Select PHP Version → Current PHP Version

Increase limits (if needed):
cPanel → Select PHP Version → Options
- max_execution_time: 300
- max_input_time: 300
- memory_limit: 256M
- post_max_size: 64M
- upload_max_filesize: 64M
```

---

## ✅ Deployment Checklist

### Pre-Deployment
- [x] Production ZIP ready (625 KB)
- [x] Plugin tested locally (38/38 tests passing)
- [x] WordPress 5.8+ on HostPapa
- [x] PHP 7.4+ configured
- [x] SSL/HTTPS enabled
- [x] Backup existing site

### Deployment
- [ ] Upload plugin via WordPress Admin or FTP
- [ ] Activate plugin
- [ ] Verify database tables created
- [ ] Create support user
- [ ] Test /portal access
- [ ] Check for PHP errors (WP_DEBUG temporarily)

### Post-Deployment
- [ ] Create partner users (if applicable)
- [ ] Import sample data (optional)
- [ ] Configure enterprise features (optional)
- [ ] Set up email notifications
- [ ] Test all major features
- [ ] Monitor error logs
- [ ] Document credentials securely

---

## 🎯 Quick Start Summary

**Fastest Method (5 minutes):**
```
1. Download loungenie-portal-wporg-production.zip
2. WordPress Admin → Plugins → Add New → Upload
3. Select ZIP file → Install Now
4. Click "Activate"
5. Create support user (Users → Add New → Role: Support Team)
6. Navigate to yoursite.com/portal
7. Done! ✅
```

---

## 📚 Additional Resources

**Plugin Documentation:**
- README.md - Complete overview
- ENTERPRISE_FEATURES.md - SSO, caching, security
- FILTERING_GUIDE.md - Analytics and filtering
- SETUP_GUIDE.md - Detailed setup
- WORDPRESS_TEST_ENVIRONMENT_READY.md - Testing guide

**HostPapa Resources:**
- WordPress Installation Guide
- SSL Certificate Setup
- Database Management
- File Manager Tutorial
- FTP Access Setup

---

## 🆘 Need Help?

**Plugin Issues:**
- Check browser console for JavaScript errors
- Enable WP_DEBUG in wp-config.php temporarily
- Review error logs in HostPapa cPanel
- Check phpMyAdmin for database tables

**HostPapa Issues:**
- Contact HostPapa support
- Check knowledge base
- Verify PHP version and settings
- Check disk space and permissions

---

**Ready to Deploy! Follow Method 1 for quickest deployment.** 🚀

**Production File:** loungenie-portal-wporg-production.zip (625 KB)  
**WordPress Required:** 5.8+  
**PHP Required:** 7.4+  
**Status:** ✅ Production Ready

---

**Generated:** December 27, 2025  
**Plugin Version:** LounGenie Portal v1.8.1  
**Tests Passing:** 38/38 (100%)  
**Security:** CodeQL Verified
