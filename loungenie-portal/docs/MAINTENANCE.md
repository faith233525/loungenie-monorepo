# LounGenie Portal - Maintenance & Operations Guide

**Version:** 1.8.1  
**Last Updated:** December 22, 2025  
**Audience:** System Administrators & Operators

---

## Overview

This guide covers post-deployment operations, monitoring, troubleshooting, and long-term maintenance of LounGenie Portal.

---

## Daily Operations

### Morning Checklist (5 minutes)

Every morning, verify:

```bash
# 1. Plugin still activated
wp plugin list --allow-root | grep loungenie-portal

# 2. Check error log for overnight errors
tail -20 /var/www/html/wp-content/debug.log

# 3. Database tables accessible
wp db tables --allow-root | grep lgp_

# 4. No email sync failures (if configured)
wp db query "SELECT COUNT(*) FROM wp_lgp_audit_log WHERE event_type LIKE '%email%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)" --allow-root
```

### Ongoing Monitoring

**Real-Time Alerts (Set Up):**

Configure log monitoring with your host or use a service like:
- Papertrail (log aggregation)
- Datadog (APM + logs)
- New Relic (performance monitoring)
- AWS CloudWatch (if on AWS)

**Key Metrics to Monitor:**
- PHP errors in debug.log
- Database connection errors
- API response time
- Email sync success rate (if using Graph/POP3)
- HubSpot sync status (if configured)

---

## Weekly Maintenance

### Week-Start Validation (30 minutes)

Every Monday, run:

```bash
# 1. Database health check
wp db check --allow-root

# 2. Cron job validation
wp cron test --allow-root

# 3. Plugin update check
wp plugin list --update-available --allow-root

# 4. Verify user permissions
wp user list --role=lgp_support --allow-root | head -10

# 5. Check file permissions
find /var/www/html/wp-content/plugins/loungenie-portal -type f -exec ls -lh {} \; | grep -v "644\|755"
```

### Weekly Backup

```bash
# Database backup
wp db export /backups/loungenie-portal-$(date +%Y%m%d).sql --allow-root

# Plugin files backup
tar -czf /backups/loungenie-portal-files-$(date +%Y%m%d).tar.gz \
  /var/www/html/wp-content/plugins/loungenie-portal

# Set retention: keep 4 weeks of backups
find /backups -name "loungenie-portal-*.sql" -mtime +28 -delete
find /backups -name "loungenie-portal-*.tar.gz" -mtime +28 -delete
```

---

## Monthly Maintenance

### Performance Audit (1 hour)

```bash
# 1. Database slow queries
mysql -u root -p -e "SET GLOBAL slow_query_log = 'ON'; SET GLOBAL long_query_time = 2;"

# 2. Check index effectiveness
wp db query "SELECT OBJECT_SCHEMA, OBJECT_NAME, COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE OBJECT_SCHEMA = DATABASE() GROUP BY OBJECT_NAME" --allow-root

# 3. Review audit logs for anomalies
wp db query "SELECT event_type, COUNT(*) as count FROM wp_lgp_audit_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY event_type" --allow-root

# 4. Check plugin size
du -sh /var/www/html/wp-content/plugins/loungenie-portal

# 5. Review error log
wc -l /var/www/html/wp-content/debug.log
tail -100 /var/www/html/wp-content/debug.log | grep -iE "(error|fatal|deprecated)"
```

### Log Rotation

If `debug.log` exceeds 100 MB:

```bash
# Archive current log
mv /var/www/html/wp-content/debug.log /var/www/html/wp-content/debug-archive-$(date +%Y%m%d).log

# Reset with proper permissions
touch /var/www/html/wp-content/debug.log
chmod 666 /var/www/html/wp-content/debug.log
```

### Cache Cleanup

```bash
# Clear WordPress cache
wp cache flush --allow-root

# Clear transients
wp transient delete-all --allow-root

# Purge old audit logs (keep 90 days)
wp db query "DELETE FROM wp_lgp_audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) LIMIT 1000" --allow-root
```

---

## Quarterly Review (3 months)

### Security Audit

```bash
# 1. Check file permissions
find /var/www/html/wp-content/plugins/loungenie-portal -type f -not -perm 644 -not -perm 755

# 2. Verify SSL/TLS configuration
ssl-test https://yoursite.com

# 3. Review CSP headers
curl -I https://yoursite.com | grep -i "content-security"

# 4. Check for vulnerabilities
wp plugin list --all --allow-root | grep loungenie-portal

# 5. Review user accounts
wp user list --role=lgp_support --allow-root
wp user list --role=lgp_partner --allow-root
# Remove inactive users from previous year
wp user list --role=lgp_partner --allow-root | awk '{print $1}' | while read uid; do
  last_login=$(wp user meta get $uid last_login --allow-root)
  if [ -z "$last_login" ]; then
    echo "Remove user $uid - no login record"
  fi
done
```

### Performance Review

Compare monthly metrics:
- Dashboard load time
- API response times
- Database query performance
- Email sync success rate

If degradation detected:
1. Check database indexes
2. Review cache hit rates
3. Monitor disk I/O
4. Scale resources if needed

---

## Semi-Annual Review (6 months)

### Dependency Updates

```bash
# Check composer updates
cd /var/www/html/wp-content/plugins/loungenie-portal
composer outdated

# Review security advisories
composer audit

# Update safely
composer update --no-dev
wp plugin deactivate loungenie-portal --allow-root
# Upload updated plugin
wp plugin activate loungenie-portal --allow-root

# Test thoroughly
wp plugin verify-checksums loungenie-portal --allow-root
```

### WordPress Compatibility

- [ ] Test on latest WordPress version
- [ ] Run WPCS scan: `composer run cs`
- [ ] Run tests: `composer test`
- [ ] Test on PHP 8.0+ (if available)

### Documentation Review

- [ ] Update CHANGELOG.md with recent fixes
- [ ] Update README.md with latest info
- [ ] Review and update SETUP_GUIDE.md
- [ ] Add new FAQs based on support tickets

---

## Annual Review (12 months)

### Full Audit

1. **Code Quality**
   ```bash
   composer run cs
   composer run cbf
   composer test
   ```

2. **Security Audit**
   - Penetration test portal
   - Review all user accounts
   - Check file integrity
   - Verify backup integrity

3. **Documentation**
   - Archive old status reports
   - Update deployment guides
   - Create new architecture diagrams
   - Document lessons learned

4. **Roadmap Planning**
   - Review user feedback
   - Plan new features
   - Schedule refactoring
   - Plan major version bump (if needed)

---

## Troubleshooting Guide

### Common Issues

#### Issue 1: Database Connection Lost

**Error:** "Error establishing a database connection"

**Solutions:**
```bash
# 1. Verify database connection
wp db check --allow-root

# 2. Restart MySQL
systemctl restart mysql

# 3. Check connection limits
mysql -e "SHOW PROCESSLIST" | wc -l

# 4. Verify plugin database tables
wp db tables --allow-root | grep lgp_
```

#### Issue 2: Email Sync Failures

**Error:** "Email processing failed"

**Solutions:**
```bash
# If using Graph API:
# 1. Check token validity
wp option get lgp_graph_settings --allow-root

# 2. Manually trigger sync
wp eval 'LGP_Email_Handler::process_emails();'

# If using POP3:
# 1. Verify POP3 credentials
wp option get lgp_email_settings --allow-root

# 2. Test POP3 connection
telnet mail.example.com 110

# 3. Check IMAP extension
php -m | grep imap
```

#### Issue 3: High Memory Usage

**Error:** "Allowed memory size exceeded"

**Solutions:**
```bash
# 1. Increase PHP memory
# In wp-config.php:
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

# 2. Identify memory hogs
wp transient list --allow-root | wc -l  # Large transient cache?

# 3. Optimize database queries
wp db query "SHOW TABLE STATUS" --allow-root | grep Data_length

# 4. Check for runaway cron jobs
wp cron test --allow-root
```

#### Issue 4: Slow Dashboard

**Error:** Dashboard loads in >3 seconds

**Solutions:**
```bash
# 1. Enable query logging
define('SAVEQUERIES', true);

# 2. Check database indexes
SHOW INDEXES FROM wp_lgp_units;
SHOW INDEXES FROM wp_lgp_tickets;
SHOW INDEXES FROM wp_lgp_companies;

# 3. Add missing indexes
ALTER TABLE wp_lgp_units ADD INDEX (company_id);
ALTER TABLE wp_lgp_tickets ADD INDEX (service_request_id);

# 4. Enable caching
# Install "Redis Object Cache" plugin
wp plugin install redis-cache --activate --allow-root

# 5. Archive old audit logs
DELETE FROM wp_lgp_audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

#### Issue 5: File Upload Issues

**Error:** "File upload failed" or "Invalid file type"

**Solutions:**
```bash
# 1. Check upload directory permissions
ls -la /var/www/html/wp-content/uploads/lgp-attachments/

# 2. Verify file size limits
php -r "echo ini_get('upload_max_filesize');"

# 3. Check disk space
df -h /var/www/html

# 4. Review file type whitelist
wp option get lgp_file_settings --allow-root

# 5. Clear cache
wp transient delete-all --allow-root
```

---

## Backup & Recovery

### Backup Strategy

**Daily:**
- Automated incremental database backups

**Weekly:**
- Full database dump
- Plugin files archive

**Monthly:**
- Offsite backup copy
- Verify restore procedure

### Restore Procedure

```bash
# 1. Restore database
mysql -u root -p wordpress < /backups/loungenie-portal-20251222.sql

# 2. Restore plugin files
tar -xzf /backups/loungenie-portal-files-20251222.tar.gz -C /

# 3. Fix permissions
chown -R www-data:www-data /var/www/html/wp-content/plugins/loungenie-portal
chmod -R 755 /var/www/html/wp-content/plugins/loungenie-portal

# 4. Clear cache
wp cache flush --allow-root
wp transient delete-all --allow-root

# 5. Verify activation
wp plugin is-active loungenie-portal --allow-root && echo "SUCCESS" || echo "FAILED"
```

---

## Update Procedure

### Minor Updates (1.8.1 → 1.8.2)

```bash
# 1. Backup
wp plugin activate loungenie-portal --allow-root
mysqldump wordpress > /backups/pre-update-$(date +%Y%m%d).sql

# 2. Deactivate
wp plugin deactivate loungenie-portal --allow-root

# 3. Update (via SFTP/SCP or git)
git pull origin main  # If using git deployment

# 4. Reactivate
wp plugin activate loungenie-portal --allow-root

# 5. Verify
wp plugin verify-checksums loungenie-portal --allow-root

# 6. Clear cache
wp cache flush --allow-root
```

### Major Updates (1.7.0 → 1.8.0)

Requires additional testing:

```bash
# 1. Test on staging first
# 2. Document breaking changes in CHANGELOG.md
# 3. Notify users of deprecations
# 4. Follow standard update procedure
# 5. Extended smoke testing (30 minutes)
# 6. Monitor error logs for 7 days
```

---

## Health Check Dashboard

Create a simple monitoring page:

```php
<?php
// /var/www/html/wp-content/plugins/loungenie-portal/health-check.php

$status = array();

// Database
try {
    global $wpdb;
    $wpdb->get_results("SELECT 1");
    $status['database'] = 'OK';
} catch (Exception $e) {
    $status['database'] = 'FAIL: ' . $e->getMessage();
}

// Tables
$required_tables = ['lgp_companies', 'lgp_units', 'lgp_tickets', 'lgp_tickets_thread'];
foreach ($required_tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'");
    $status[$table] = $exists ? 'OK' : 'MISSING';
}

// Email (if configured)
if (class_exists('LGP_Email_Handler')) {
    $settings = get_option('lgp_email_settings', array());
    $status['email'] = !empty($settings['pop3_server']) ? 'CONFIGURED' : 'NOT_CONFIGURED';
}

// HubSpot (if configured)
if (class_exists('LGP_HubSpot')) {
    $settings = get_option('lgp_hubspot_settings', array());
    $status['hubspot'] = !empty($settings['api_key']) ? 'CONFIGURED' : 'NOT_CONFIGURED';
}

// Output
header('Content-Type: application/json');
echo json_encode($status);
?>
```

Visit: `/wp-content/plugins/loungenie-portal/health-check.php`

---

## Knowledge Base

### FAQ

**Q: How often should I backup?**
A: Weekly minimum for database. Daily for critical installations.

**Q: Can I disable certain features?**
A: Yes. Edit `loungenie-portal.php` and comment out integration hooks.

**Q: How do I migrate to a new server?**
A: Backup database + files, restore on new server, update domain in wp_options.

**Q: What if email sync fails?**
A: Check logs in debug.log. Fallback to POP3 if using Graph. Manual intervention may be needed.

**Q: Can I customize the portal UI?**
A: Yes. Edit CSS in `/assets/css/portal.css`. Avoid editing PHP unless you know what you're doing.

---

## Contact & Resources

**Documentation:**
- [README.md](README.md) - Overview
- [DEPLOYMENT.md](DEPLOYMENT.md) - Deployment guide
- [SETUP_GUIDE.md](SETUP_GUIDE.md) - Installation

**Support:**
- Check `wp-content/debug.log` for errors
- Review [docs/archive/COMPREHENSIVE_AUDIT_AND_PLAN.md](../docs/archive/COMPREHENSIVE_AUDIT_AND_PLAN.md) for architecture

**Reporting Issues:**
- Create GitHub issue with:
  - Error message from debug.log
  - WordPress version
  - PHP version
  - Steps to reproduce

---

## Maintenance Schedule

| Task | Frequency | Time | Owner |
|------|-----------|------|-------|
| Check error log | Daily | 5 min | Admin |
| Database backup | Weekly | 10 min | Automation |
| Verify user accounts | Weekly | 15 min | Admin |
| Performance review | Monthly | 30 min | Admin |
| Security audit | Quarterly | 2 hrs | Admin |
| Dependency updates | Semi-annual | 2 hrs | Tech Lead |
| Full audit | Annual | 8 hrs | Tech Lead |

---

## Next Steps

After Phase 7 completion:

✅ **All 7 phases complete**
- Critical issues resolved
- Code quality improved
- Repository cleaned
- Documentation organized
- Testing validated
- Deployment procedures documented
- Maintenance procedures established

**You are ready for production deployment and long-term operations.**

---

**Maintenance Guide Created:** December 22, 2025  
**Plugin Version:** 1.8.1  
**Next Scheduled Audit:** June 2026
