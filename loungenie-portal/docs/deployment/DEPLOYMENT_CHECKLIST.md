# 🚀 Shared Server Deployment Checklist

## Pre-Deployment Verification

### 1. Server Requirements Check
- [ ] PHP version 7.4+ (`php -v`)
- [ ] MySQL/MariaDB 5.7+ compatible
- [ ] Memory limit ≥ 64MB (check with `define('WP_MEMORY_LIMIT', '64M');`)
- [ ] Max execution time ≥ 30 seconds
- [ ] File upload limit ≥ 50MB
- [ ] Write permissions on wp-content/uploads

### 2. Plugin Files Audit
- [ ] All PHP files present in `/loungenie-portal/`
- [ ] CSS files in `/assets/css/` (6 files)
- [ ] JavaScript files in `/assets/js/` (8+ files)
- [ ] Templates in `/templates/` (12 files)
- [ ] API endpoints in `/api/` (8 files)
- [ ] Database classes in `/includes/` (21+ files)
- [ ] Main plugin file: `loungenie-portal.php`

### 3. Code Quality Verification
```bash
# Run syntax check
find loungenie-portal -name "*.php" -exec php -l {} \;

# Verify no fatal errors
php -d error_reporting=E_ALL loungenie-portal/loungenie-portal.php
```
- [ ] No PHP syntax errors
- [ ] No missing dependencies
- [ ] All namespaces properly defined
- [ ] No hardcoded paths

## Deployment Steps

### Step 1: Prepare Plugin Files
```bash
# Remove development files
cd loungenie-portal
rm -rf .git .github node_modules tests
rm -f *.md composer.json package.json

# Keep only production files
ls -la
```
- [ ] Development files removed
- [ ] Plugin folder < 100MB
- [ ] No unnecessary documentation

### Step 2: Upload to Shared Server
```bash
# Via FTP/SFTP
scp -r loungenie-portal user@host.com:wp-content/plugins/

# Or via hosting control panel file manager
# Upload to: wp-content/plugins/loungenie-portal/
```
- [ ] All files uploaded
- [ ] File permissions set to 644 (files) / 755 (directories)
- [ ] No corrupted files during transfer

### Step 3: Database Initialization
```bash
# Create database tables if needed
wp db tables

# Run migrations if applicable
wp loungenie-portal migrate
```
- [ ] Database tables created
- [ ] No duplicate tables
- [ ] Indexes created

### Step 4: WordPress Plugin Activation
1. Log into WordPress admin
2. Go to Plugins → Installed Plugins
3. Find "LounGenie Portal"
4. Click "Activate"
- [ ] Plugin activates without errors
- [ ] No white screen of death
- [ ] Admin menu appears

### Step 5: Initial Configuration
```
Settings → LounGenie Portal → General Settings
```
- [ ] Portal name configured
- [ ] Support email set
- [ ] Timezone correct
- [ ] Default role assigned

### Step 6: Test Core Functionality

#### 6.1 User Roles
- [ ] Partner role created
- [ ] Support role created
- [ ] Users can log in
- [ ] Role-based access works

#### 6.2 Dashboard
- [ ] Partner dashboard loads (< 2s)
- [ ] Support dashboard loads (< 2s)
- [ ] Widgets display correctly
- [ ] Navigation works

#### 6.3 Database Operations
```bash
# Test query performance
wp db query "SELECT COUNT(*) FROM wp_posts;"
wp db query "SELECT COUNT(*) FROM wp_users;"
```
- [ ] Queries execute < 50ms
- [ ] No timeout errors
- [ ] Data displayed correctly

#### 6.4 API Endpoints
```bash
# Test REST API
curl -u username:password http://yoursite.com/wp-json/lgp/v1/companies
curl -u username:password http://yoursite.com/wp-json/lgp/v1/tickets
```
- [ ] Endpoints respond with 200 status
- [ ] JSON responses valid
- [ ] Authentication works

#### 6.5 Assets Loading
- [ ] CSS files load without 404 errors
- [ ] JavaScript files execute
- [ ] Design system colors visible
- [ ] Layout responsive on mobile

### Step 7: Performance Optimization

#### 7.1 Run Optimization Script
```bash
cd wp-content/plugins/loungenie-portal
chmod +x scripts/optimize-shared-server.sh
./scripts/optimize-shared-server.sh
```
- [ ] Vendor directory optimized
- [ ] Cache directories created
- [ ] .htaccess configured
- [ ] .user.ini created

#### 7.2 Enable Caching
```php
// In wp-config.php
define('WP_CACHE', true);
```
- [ ] WP Super Cache installed
- [ ] Caching enabled
- [ ] Cache dir writable

#### 7.3 Database Optimization
```bash
wp db optimize
wp db repair
```
- [ ] Database optimized
- [ ] No errors in process
- [ ] Performance improved

### Step 8: Security Hardening

#### 8.1 File Permissions
```bash
# Set secure permissions
find loungenie-portal -type f -exec chmod 644 {} \;
find loungenie-portal -type d -exec chmod 755 {} \;
```
- [ ] Configuration files protected
- [ ] Upload directories writable
- [ ] Scripts not executable by web

#### 8.2 Security Settings
- [ ] Sensitive files excluded from backups
- [ ] Error reporting disabled in production
- [ ] Debug mode off
- [ ] Security headers enabled

#### 8.3 Backups
```bash
# Create initial backup
wp db export loungenie-portal-initial.sql
```
- [ ] Database backed up
- [ ] Plugin files backed up
- [ ] Backup location secure

### Step 9: Monitoring & Health Check

#### 9.1 Enable Diagnostics
```
Admin → LounGenie Diagnostics
```
- [ ] Diagnostics page accessible
- [ ] All checks passing
- [ ] No warnings displayed

#### 9.2 Test Health Endpoint
```bash
curl http://yoursite.com/?lgp_health_check=1
```
- [ ] Returns JSON response
- [ ] Memory usage reasonable
- [ ] Database connection OK

#### 9.3 Server Compatibility Test
```
Access: /wp-content/plugins/loungenie-portal/tests/
shared-server-compatibility.php?run_tests=1
```
- [ ] All tests pass
- [ ] Memory limits OK
- [ ] No critical warnings

## Post-Deployment Verification

### 1. Performance Benchmarks
```bash
# Run performance tests
wp plugin test loungenie-portal --bench
```
Expected results:
- [ ] Page load time < 2 seconds
- [ ] API response < 200ms
- [ ] Database queries < 50ms average
- [ ] Memory usage < 64MB peak

### 2. Error Logging
```bash
# Check for errors
tail -f /path/to/wp-content/debug.log

# Monitor error_log
tail -f /path/to/error_log
```
- [ ] No fatal errors
- [ ] No warnings
- [ ] Clean error logs

### 3. Resource Usage Monitoring
```bash
# Check current load
ps aux | grep apache2
ps aux | grep mysql

# Monitor memory
free -h

# Check disk space
df -h
```
- [ ] CPU load reasonable
- [ ] Memory usage < 80% limit
- [ ] Disk space adequate (> 1GB free)

### 4. User Testing
- [ ] Admin can log in
- [ ] Partners can access portal
- [ ] Support staff can view tickets
- [ ] Upload files work
- [ ] Export functionality works

### 5. Third-Party Integration Testing
- [ ] Email notifications send
- [ ] HubSpot API works (if enabled)
- [ ] Microsoft SSO works (if enabled)
- [ ] Outlook integration works (if enabled)

## Troubleshooting

### Common Issues on Shared Servers

#### Issue: White Screen of Death (WSOD)
**Solution:**
```php
// Add to wp-config.php temporarily
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Check error_log
tail -f /path/to/wp-content/debug.log
```

#### Issue: Memory Exhausted
**Solution:**
```php
// In wp-config.php
define('WP_MEMORY_LIMIT', '128M');
define('WP_MEMORY_LIMIT', '256M'); // For admin

// Disable unnecessary plugins
wp plugin deactivate --all
wp plugin activate loungenie-portal
```

#### Issue: Timeout on Large Operations
**Solution:**
```php
// In loungenie-portal/loungenie-portal.php
set_time_limit(300); // 5 minutes for critical operations
ignore_user_abort(true);
```

#### Issue: Database Connection Issues
**Solution:**
```bash
# Test connection
wp db check

# Repair if needed
wp db repair

# Check max connections
wp db query "SHOW VARIABLES LIKE 'max_connections';"
```

#### Issue: Slow Performance
**Solution:**
1. Run diagnostics: `?lgp_health_check=1`
2. Check transient cache is working
3. Verify .htaccess caching enabled
4. Install WP Super Cache
5. Enable Cloudflare CDN
6. Optimize database: `wp db optimize`

### Rollback Procedure
```bash
# Deactivate plugin
wp plugin deactivate loungenie-portal

# Remove plugin (if needed)
rm -rf wp-content/plugins/loungenie-portal/

# Restore from backup
mysql -u user -p database < backup.sql
```

## Maintenance Schedule

### Daily
- [ ] Monitor error logs
- [ ] Check memory usage
- [ ] Verify site accessibility

### Weekly
- [ ] Run performance benchmarks
- [ ] Check database size
- [ ] Review security logs
- [ ] Test backup restoration

### Monthly
- [ ] Optimize database
- [ ] Update WordPress & plugins
- [ ] Review diagnostics report
- [ ] Clean old transients/cache

### Quarterly
- [ ] Full server audit
- [ ] Security scan
- [ ] Performance optimization
- [ ] Update documentation

## Support Resources

- **Documentation**: See `IMPLEMENTATION_UPDATES.md`
- **Compatibility Tests**: `tests/shared-server-compatibility.php?run_tests=1`
- **Performance Tests**: `tests/performance-benchmark.php?run_benchmarks=1`
- **Diagnostics**: Admin → Settings → LounGenie Diagnostics
- **Health Check**: `?lgp_health_check=1`

## Sign-Off

- [ ] All checks completed
- [ ] Site is live and stable
- [ ] Performance acceptable
- [ ] Security hardened
- [ ] Monitoring enabled
- [ ] Team trained
- [ ] Documentation updated

**Deployed by:** ___________________  
**Date:** ___________________  
**Status:** ✅ PRODUCTION READY
