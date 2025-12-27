# LounGenie Portal - Deployment Guide v1.8.1

**Version:** 1.8.1  
**Last Updated:** December 22, 2025  
**Status:** ✅ Ready for Production Deployment

---

## Quick Start

### For Immediate Deployment

```bash
# 1. Clone or download the plugin
git clone https://github.com/your-org/loungenie-portal.git

# 2. Upload to WordPress
cp -r loungenie-portal /var/www/html/wp-content/plugins/

# 3. Activate in WordPress Admin
# Navigate to: WordPress Admin → Plugins → Activate "LounGenie Portal"

# 4. Access the portal
# Visit: https://yoursite.com/portal
```

---

## Deployment Checklist

### Pre-Deployment (Before Upload)

- [ ] **Version Numbers Updated**
  - [ ] `loungenie-portal/VERSION` file
  - [ ] Plugin header comment in `loungenie-portal.php`
  - [ ] README badge version
  - [ ] CHANGELOG.md entry

- [ ] **Code Quality Verified**
  - [ ] `composer run cs` passes (WPCS compliance)
  - [ ] `composer run test` passes (PHPUnit tests)
  - [ ] No PHP syntax errors: `php -l includes/*.php`

- [ ] **Documentation Updated**
  - [ ] CHANGELOG.md with new features
  - [ ] README.md with latest info
  - [ ] SETUP_GUIDE.md if process changed
  - [ ] Any new enterprise features documented

- [ ] **Git State Clean**
  - [ ] All changes committed: `git status` is clean
  - [ ] Version tagged: `git tag v1.8.1`
  - [ ] Pushed to remote: `git push origin main && git push origin --tags`

### Upload & Activation

- [ ] **WordPress Environment Verified**
  - [ ] WordPress 5.8+ installed
  - [ ] PHP 7.4+ running
  - [ ] MySQL/MariaDB 5.6+ available
  - [ ] WP-Admin access confirmed

- [ ] **Plugin Uploaded**
  - [ ] Directory: `/wp-content/plugins/loungenie-portal/`
  - [ ] Files readable by web server
  - [ ] No file permission issues

- [ ] **Plugin Activated**
  - [ ] Navigate: Admin → Plugins
  - [ ] Find "LounGenie Portal"
  - [ ] Click "Activate"
  - [ ] No activation errors in debug.log

### Post-Activation Setup

- [ ] **Database Tables Created**
  - [ ] Check: `wp_lgp_companies`
  - [ ] Check: `wp_lgp_units`
  - [ ] Check: `wp_lgp_tickets`
  - [ ] Check: All 9 tables exist

- [ ] **User Roles Created**
  - [ ] `lgp_support` role available
  - [ ] `lgp_partner` role available
  - [ ] Test user can be assigned roles

- [ ] **Portal Route Accessible**
  - [ ] Visit: `/portal` redirects to login if not authenticated
  - [ ] Support user can access dashboard
  - [ ] Partner user can access limited dashboard

### Configuration (Optional)

**If using Microsoft 365 SSO:**
- [ ] Azure AD app created
- [ ] Client ID configured
- [ ] Client Secret configured
- [ ] Tenant ID configured
- [ ] Test SSO login works

**If using HubSpot Integration:**
- [ ] HubSpot API key generated
- [ ] API key configured in WordPress settings
- [ ] Test sync (create company, verify in HubSpot)

**If using Email Pipeline:**
- [ ] Microsoft Graph configured (preferred) OR
- [ ] POP3 settings configured (fallback)
- [ ] Test email ingestion

### Security Verification

- [ ] **HTTPS Enforced**
  - [ ] Site uses HTTPS
  - [ ] CSP headers present (check browser DevTools)
  - [ ] HSTS header set

- [ ] **Access Controls Verified**
  - [ ] Non-authenticated users see login page
  - [ ] Support users have full access
  - [ ] Partner users see only their data
  - [ ] No data leakage between companies

- [ ] **File Upload Security**
  - [ ] Max file size: 10 MB enforced
  - [ ] Allowed MIME types: JPG, PNG, PDF, TXT, DOC, CSV
  - [ ] Files scanned for malware (if configured)

### Final Validation

- [ ] **Smoke Tests**
  - [ ] Dashboard loads (<3 seconds)
  - [ ] Company list displays
  - [ ] Unit list displays
  - [ ] Create new ticket works
  - [ ] Upload attachment works

- [ ] **Performance**
  - [ ] Dashboard response: <2.5s
  - [ ] API endpoints: <300ms
  - [ ] Map rendering: <1s

- [ ] **Error Logging**
  - [ ] No PHP errors in `debug.log`
  - [ ] No JavaScript console errors
  - [ ] Audit logs being recorded

---

## Release Process

### Step 1: Prepare Version

```bash
cd loungenie-portal

# Update version numbers
echo "1.8.1" > VERSION
# Edit loungenie-portal.php: update plugin version comment
# Edit README.md: update version badge

# Update changelog
# vim CHANGELOG.md
# Add entry:
# ## [1.8.1] - December 22, 2025
# ### Added
# - ...
# ### Fixed
# - ...
```

### Step 2: Test

```bash
# Run test suite
composer test

# Check WordPress Coding Standards
composer run cs

# Fix violations (if any)
composer run cbf
```

### Step 3: Commit

```bash
git add -A
git commit -m "Release v1.8.1: [summary of changes]"
git tag -a v1.8.1 -m "Release version 1.8.1"
git push origin main
git push origin --tags
```

### Step 4: Create Deployment Package

**Option A: Via Git Archive (Recommended)**
```bash
git archive --format zip --output loungenie-portal-v1.8.1.zip --prefix loungenie-portal/ HEAD
```

**Option B: Manual Zip**
```bash
zip -r loungenie-portal-v1.8.1.zip loungenie-portal/ \
  -x "loungenie-portal/vendor/*" \
  -x "loungenie-portal/node_modules/*" \
  -x "loungenie-portal/.git/*" \
  -x "loungenie-portal/composer.lock" \
  -x "loungenie-portal/package-lock.json"
```

### Step 5: Deploy

**To WordPress.org (if applicable):**
```bash
# Follow WordPress.org SVN process
# 1. Checkout trunk from SVN
# 2. Copy files
# 3. Commit with tag for release

svn co https://plugins.svn.wordpress.org/loungenie-portal/trunk loungenie-portal-wporg
cd loungenie-portal-wporg
# Copy updated files
svn add new-files
svn commit -m "Release 1.8.1"
```

**To Private Server:**
1. Upload `loungenie-portal/` directory via SFTP/SCP
2. Set proper permissions: `chmod 755 loungenie-portal/`
3. Activate in WordPress Admin
4. Run smoke tests

---

## Rollback Procedure

If issues occur after deployment:

```bash
# Option 1: Deactivate plugin via WordPress Admin
# Settings → Plugins → Deactivate "LounGenie Portal"

# Option 2: Restore previous version via git
git checkout v1.8.0  # Or previous tag
# Re-upload to server

# Option 3: Remove plugin entirely (if critical)
rm -rf /wp-content/plugins/loungenie-portal/
# Deactivate via database if needed:
# DELETE FROM wp_options WHERE option_name = 'active_plugins'
```

---

## Configuration Reference

### Essential Settings (Required)

None required for basic functionality. Plugin works out of the box.

### Optional Integrations

#### Microsoft 365 SSO
**Settings Page:** WordPress Admin → Settings → M365 SSO

| Setting | Required | Example |
|---------|----------|---------|
| Client ID | Yes | `a1b2c3d4-e5f6...` |
| Client Secret | Yes | `abc123xyz~ABC...` |
| Tenant ID | Yes | `12345678-1234...` |

#### HubSpot Integration
**Settings Page:** WordPress Admin → Settings → HubSpot Integration

| Setting | Required | Example |
|---------|----------|---------|
| API Key | Yes | `pat-na1-abc123...` |

#### Email Pipeline
**Settings Page:** WordPress Admin → Settings → Email Integration

| Setting | Required | Example |
|---------|----------|---------|
| Provider | Yes | `graph` or `pop3` |
| Mailbox (Graph) | Yes | `support@company.com` |
| Tenant ID (Graph) | Yes | `12345678-1234...` |
| Client ID (Graph) | Yes | `a1b2c3d4-e5f6...` |
| POP3 Server | If POP3 | `mail.example.com` |
| POP3 Username | If POP3 | `user@example.com` |

---

## Troubleshooting

### Plugin Won't Activate

**Error:** "Plugin could not be activated because it triggered a fatal error."

**Solution:**
1. Check `wp-content/debug.log` for error
2. Verify PHP version is 7.4+
3. Verify WordPress version is 5.8+
4. Check file permissions

### Database Tables Not Created

**Error:** "Database error: [1146] Table 'wp_lgp_companies' doesn't exist"

**Solution:**
1. Reactivate plugin: Deactivate → Activate
2. Run database migration: 
   ```bash
   wp plugin activate loungenie-portal --allow-root
   ```
3. Check `wp_options` for `lgp_db_version`

### Portal Route Not Found

**Error:** 404 when visiting `/portal`

**Solution:**
1. Verify plugin activated
2. Flush rewrite rules:
   ```bash
   wp rewrite flush --hard --allow-root
   ```
3. Check `.htaccess` (if using Apache)

### Email Not Syncing

**Error:** No tickets being created from emails

**Solution:**
1. Check Graph API configuration (if using Microsoft)
2. Verify POP3 credentials (if using fallback)
3. Check `wp-content/debug.log` for API errors
4. Manually test email fetch: 
   ```php
   LGP_Email_Handler::process_emails();
   ```

### Performance Issues

**Slow dashboard load (>3 seconds)**

**Solutions:**
1. Enable caching: Install Redis Object Cache plugin
2. Check database indexes: `SHOW INDEXES FROM wp_lgp_companies;`
3. Monitor query time: `set global slow_query_log = 'ON';`
4. Review audit logs: Large audit tables slow queries
   - Archive old logs: `DELETE FROM wp_lgp_audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);`

---

## Support & Monitoring

### Health Check

Run monthly health checks:

```bash
# Check database
wp db check --allow-root

# Verify cron jobs
wp cron test --allow-root

# Check plugin status
wp plugin list --allow-root
```

### Monitoring Dashboard

Create a simple monitoring page to check:
- [x] Portal loads without errors
- [x] Database connection working
- [x] Email pipeline functioning (if configured)
- [x] HubSpot sync working (if configured)
- [x] File uploads accepted

### Log Rotation

Configure log rotation for `wp-content/debug.log`:

```bash
# /etc/logrotate.d/wordpress
/var/www/html/wp-content/debug.log {
    daily
    rotate 7
    compress
    delaycompress
    notifempty
}
```

---

## Version History

| Version | Date | Changes | Status |
|---------|------|---------|--------|
| 1.8.1 | Dec 22, 2025 | IDE warnings suppressed, repo cleaned, docs organized | ✅ Current |
| 1.8.0 | Dec 2025 | Microsoft Graph API integration | ✅ Stable |
| 1.7.0 | Nov 2025 | HubSpot CRM sync | ✅ Stable |
| 1.0.0 | Sep 2025 | Initial release | ✅ Archive |

---

## Next Steps

**Phase 7: Maintenance & Follow-up**

After successful deployment:
1. Monitor error logs for 7 days
2. Gather user feedback
3. Schedule quarterly security audits
4. Plan next feature release

See [../docs/archive/COMPREHENSIVE_AUDIT_AND_PLAN.md](../docs/archive/COMPREHENSIVE_AUDIT_AND_PLAN.md) for long-term roadmap.

---

**Deployment Guide Created:** December 22, 2025  
**Plugin Version:** 1.8.1  
**Compatibility:** WordPress 5.8+, PHP 7.4+  
**Support:** Check README.md for contact information
