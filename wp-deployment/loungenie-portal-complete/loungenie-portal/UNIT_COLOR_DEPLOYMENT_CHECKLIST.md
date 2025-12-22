# Unit/Color Tracking - Deployment Checklist

**Version:** 2.0  
**Date:** December 19, 2025

## Pre-Deployment Verification

### ✅ Code Changes
- [ ] All emojis replaced with icon classes
- [ ] New component files created
- [ ] CSS styles added for icons
- [ ] Documentation files created
- [ ] README.md updated

### ✅ Database
- [ ] Migration v1.8.0 ready to run
- [ ] `top_colors` column will be added to `lgp_companies`
- [ ] Initial color aggregates will be populated
- [ ] Backup database before migration

### ✅ Files to Deploy

**New Files:**
```
loungenie-portal/
├── UNIT_COLOR_GUIDANCE.md
├── UNIT_COLOR_IMPLEMENTATION_SUMMARY.md
├── UNIT_COLOR_QUICKREF.md
├── UNIT_COLOR_DEPLOYMENT_CHECKLIST.md (this file)
├── templates/components/component-company-colors.php
├── assets/css/component-company-colors.css
└── assets/css/support-ticket-form.css (updated)
```

**Updated Files:**
```
loungenie-portal/
├── README.md
├── templates/components/support-ticket-form.php
└── tests/performance-benchmark.php
```

**Existing Files (no changes needed):**
```
loungenie-portal/
├── includes/class-lgp-company-colors.php ✓
├── includes/class-lgp-migrations.php ✓
├── api/dashboard.php ✓
├── api/companies.php ✓
└── api/units.php ✓
```

---

## Deployment Steps

### Step 1: Backup

```bash
# Backup database
wp db export backup-$(date +%Y%m%d-%H%M%S).sql

# Backup plugin files
tar -czf loungenie-portal-backup-$(date +%Y%m%d-%H%M%S).tar.gz \
  wp-content/plugins/loungenie-portal/
```

### Step 2: Deploy Files

```bash
# Upload new/updated files via FTP, SFTP, or Git
# Ensure file permissions are correct (644 for files, 755 for directories)

# Verify files uploaded
ls -la wp-content/plugins/loungenie-portal/templates/components/
ls -la wp-content/plugins/loungenie-portal/assets/css/
```

### Step 3: Run Migration

```bash
# Option A: Via WP-CLI
wp eval "LGP_Migrations::migrate_v1_8_0();"

# Option B: Via PHP script
php -r "require 'wp-load.php'; LGP_Migrations::migrate_v1_8_0();"

# Option C: Via WordPress admin (if migration UI exists)
# Navigate to: WP Admin → Tools → LounGenie Migrations
```

### Step 4: Verify Database Changes

```sql
-- Check if column exists
SHOW COLUMNS FROM wp_lgp_companies LIKE 'top_colors';

-- Check initial data
SELECT id, name, top_colors 
FROM wp_lgp_companies 
LIMIT 5;

-- Verify JSON format
SELECT 
  id, 
  name,
  JSON_VALID(top_colors) as is_valid_json,
  top_colors
FROM wp_lgp_companies;
```

### Step 5: Clear Caches

```bash
# WordPress object cache
wp cache flush

# If using Redis/Memcached
redis-cli FLUSHDB
# or
echo "flush_all" | nc localhost 11211

# Browser cache (inform users)
# Ctrl+Shift+R (Chrome/Firefox)
# Cmd+Shift+R (Mac)
```

### Step 6: Test Functionality

#### Test 1: Support User
```bash
# Login as Support
# Expected: See all companies with color aggregates
# Navigate to: /portal → Companies
# Verify: Color badges display correctly
```

#### Test 2: Partner User
```bash
# Login as Partner
# Expected: See only own company with color aggregates
# Navigate to: /portal → Dashboard
# Verify: Color distribution shows for own company only
```

#### Test 3: Support Ticket Form
```bash
# Navigate to: /portal → Support Tickets → New
# Expected: Urgency icons (not emojis) display
# Verify: Normal (green), High (yellow), Critical (red) icons
```

#### Test 4: API Endpoints
```bash
# Test Companies API
curl -X GET "https://yourdomain.com/wp-json/lgp/v1/companies/1" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Expected response includes:
# {
#   "id": 1,
#   "name": "Company Name",
#   "top_colors": "{\"yellow\":10,\"orange\":5}",
#   ...
# }
```

#### Test 5: Color Aggregation
```bash
# Create a new unit with color_tag
wp eval "
global \$wpdb;
\$wpdb->insert(
  \$wpdb->prefix . 'lgp_units',
  array(
    'company_id' => 1,
    'color_tag' => 'blue',
    'status' => 'active'
  )
);
"

# Verify color aggregate updated
wp eval "var_dump(LGP_Company_Colors::get_company_colors(1));"

# Expected: Should include 'blue' with count incremented
```

---

## Post-Deployment Verification

### ✅ Visual Checks
- [ ] No emojis visible anywhere in UI
- [ ] Color icons render properly
- [ ] Urgency icons display with correct colors
- [ ] Color badges show in company lists
- [ ] Responsive layout works on mobile

### ✅ Functional Checks
- [ ] Support can view all companies
- [ ] Partners can only view own company
- [ ] Color aggregates update when units change
- [ ] Cache invalidation works properly
- [ ] API returns correct `top_colors` data

### ✅ Performance Checks
- [ ] Page load times acceptable
- [ ] Cache hit rate is good (check logs)
- [ ] Database queries optimized
- [ ] No N+1 query issues

---

## Rollback Plan

If issues occur, rollback in reverse order:

### Rollback Step 1: Restore Database
```bash
# Restore from backup
wp db import backup-YYYYMMDD-HHMMSS.sql

# Verify data restored
wp db query "SELECT COUNT(*) FROM wp_lgp_companies;"
```

### Rollback Step 2: Restore Files
```bash
# Extract backup
tar -xzf loungenie-portal-backup-YYYYMMDD-HHMMSS.tar.gz

# Copy to plugin directory
cp -r wp-content/plugins/loungenie-portal/* /path/to/wp-content/plugins/loungenie-portal/
```

### Rollback Step 3: Clear Caches
```bash
wp cache flush
```

### Rollback Step 4: Verify
- [ ] Site loads correctly
- [ ] No errors in logs
- [ ] Users can login and access features

---

## Monitoring

### After Deployment

**First 24 Hours:**
- Monitor error logs for PHP errors
- Check browser console for JavaScript errors
- Monitor API response times
- Track user feedback

**First Week:**
- Review cache hit rates
- Analyze database query performance
- Collect user feedback on icon clarity
- Monitor for any role-based access issues

**Log Files to Monitor:**
```bash
# PHP error log
tail -f /var/log/php-errors.log

# WordPress debug log
tail -f wp-content/debug.log

# Web server error log
tail -f /var/log/nginx/error.log  # or apache2/error.log
```

**Metrics to Track:**
- Page load time for `/portal`
- API response time for `/wp-json/lgp/v1/companies`
- Cache hit rate for color aggregates
- User satisfaction (feedback forms)

---

## Troubleshooting

### Issue: Icons Not Displaying

**Solution:**
```bash
# Clear browser cache
# Check CSS file loaded
# View page source → search for "component-company-colors.css"

# If missing, check plugin enqueue
wp eval "
do_action('wp_enqueue_scripts');
global \$wp_styles;
var_dump(\$wp_styles->registered);
"
```

### Issue: Color Aggregates Empty

**Solution:**
```bash
# Manually refresh for one company
wp eval "LGP_Company_Colors::refresh_company_colors(1);"

# Batch refresh all companies
wp eval "LGP_Company_Colors::batch_refresh();"

# Check units table has color_tag data
wp db query "SELECT company_id, color_tag, COUNT(*) as cnt FROM wp_lgp_units GROUP BY company_id, color_tag;"
```

### Issue: Role-Based Access Not Working

**Solution:**
```bash
# Check user role
wp user get USER_ID --field=roles

# Verify company assignment for Partners
wp user meta get USER_ID lgp_company_id

# Test permission check
wp eval "var_dump(LGP_Auth::is_support());"
wp eval "var_dump(LGP_Auth::is_partner());"
```

---

## Success Criteria

Deployment is successful when:

✅ All automated tests pass  
✅ Support users see all companies with color aggregates  
✅ Partner users see only their company with color aggregates  
✅ No emojis visible in UI  
✅ Icons display correctly (urgency & colors)  
✅ API endpoints return correct `top_colors` data  
✅ Cache invalidation works when units change  
✅ No PHP/JavaScript errors in logs  
✅ Page load performance acceptable (<2s)  
✅ Mobile layout responsive and functional  

---

## Contact

**For deployment issues:**
- Check documentation in `UNIT_COLOR_GUIDANCE.md`
- Review logs (PHP, WordPress, web server)
- Test with simplified cases (single company)
- Escalate to development team if needed

---

**Deployment Status:** ⏳ Ready for Deployment  
**Last Updated:** December 19, 2025
