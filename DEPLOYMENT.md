# LounGenie Portal - Production Deployment Guide

## Pre-Deployment Checklist

### Security ✓
- [x] OWASP Top 10 compliance verified
- [x] SQL injection prevention (prepared statements)
- [x] XSS prevention (output escaping)
- [x] CSRF protection (nonces)
- [x] Input validation & sanitization
- [x] Rate limiting on API endpoints (tickets 5/hr, attachments 10/hr)
- [x] Server-side MIME validation for uploads
- [x] File upload count limits (max 5 per request)
- [x] Attachment retention/cleanup (90-day cron)
- [x] Audit logging enabled

### Performance ✓
- [x] All CSS & JS minified
- [x] Database queries optimized
- [x] Caching implemented
- [x] Lazy loading support
- [x] Shared hosting optimized
- [x] Payload reduced ~70%

### Accessibility ✓
- [x] WCAG 2.1 Level AA compliance
- [x] Semantic HTML structure
- [x] ARIA labels on forms
- [x] Keyboard navigation support
- [x] Color contrast ratios (4.5:1 minimum)

### Code Quality ✓
- [x] PSR-12 standards
- [x] Single responsibility principle
- [x] Well-documented code
- [x] No debug code in production
- [x] Error handling implemented

## Installation Steps

### 1. Download Plugin
```bash
git clone https://github.com/faith233525/Pool-Safe-Portal.git
cd plugins/loungenie-portal
```

### 2. Upload to WordPress
1. Go to WordPress Admin → Plugins → Add New
2. Click "Upload Plugin"
3. Select `loungenie-portal-PRODUCTION-READY.zip`
4. Click "Install Now"
5. Click "Activate Plugin"

### 3. Initial Configuration
1. Go to Settings → LounGenie Portal
2. Configure general settings:
   - Partner company name
   - Support email
   - Portal URL
3. Set user roles & capabilities
4. Enable audit logging

### 4. Database Setup
1. Run activation hook (automatic on activation)
2. Creates required tables:
   - `wp_lgp_companies`
   - `wp_lgp_units`
   - `wp_lgp_tickets`
   - `wp_lgp_service_requests`
   - `wp_lgp_audit_log`
3. Verify database permissions

### 5. Create Content
1. Create Portal page: `/portal`
2. Create Partner login page: `/partner-login`
3. Create Support login page: `/support-login`
4. Assign shortcodes as needed

## Testing Checklist

### Functionality
- [ ] User registration works
- [ ] Login/logout functions
- [ ] Role-based access control working
- [ ] API endpoints responding correctly
- [ ] Database queries executing
- [ ] Email notifications sending

### Performance
- [ ] Page load time < 2 seconds
- [ ] CSS/JS minified
- [ ] No console errors
- [ ] Responsive on mobile/tablet

### Security
- [ ] HTTPS enabled and enforced (HSTS headers)
- [ ] Nonces working on all forms
- [ ] Rate limiting active (verify logs for limit triggers)
- [ ] Audit log recording authentication and CRUD events
- [ ] File cleanup cron running (check transients daily)
- [ ] No sensitive data in logs or error messages

## Deployment Environment

### Recommended Hosting
- **Preferred**: WP Engine, Kinsta, SiteGround
- **Supported**: Any shared hosting with PHP 7.4+
- **Minimum**: 256MB RAM per PHP process

### Server Configuration
```bash
# PHP Version: 7.4+
PHP_VERSION=7.4

# Memory Limit
memory_limit=256M

# Upload Limit
upload_max_filesize=50M
post_max_size=50M

# Execution Time
max_execution_time=60

# Database
MySQL 5.7+ or MariaDB 10.3+
```

## Maintenance

### Regular Tasks
- Monitor audit logs weekly
- Review security updates
- Test backup/restore monthly
- Update WordPress & PHP
- Clear cache periodically

### Database Optimization
```sql
-- Run monthly
OPTIMIZE TABLE wp_lgp_tickets;
OPTIMIZE TABLE wp_lgp_service_requests;
OPTIMIZE TABLE wp_lgp_audit_log;
```

## Troubleshooting

### White Screen
1. Enable debug logging: `define('WP_DEBUG', true);`
2. Check PHP error logs
3. Verify PHP version compatibility

### Database Errors
1. Verify database connection
2. Check user permissions
3. Run activation hook again

### Missing Assets
1. Verify plugin URL correct
2. Check file permissions (644)
3. Clear browser cache
4. Clear WordPress cache

## Support

- GitHub Issues: https://github.com/faith233525/Pool-Safe-Portal/issues
- Email: support@poolsafe.com

## Version History

### v1.9.1 (Current)
- Initial production release
- 51 classes, 11 API endpoints
- WCAG 2.1 AA compliant
- Enterprise-grade security
