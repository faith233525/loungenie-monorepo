# LounGenie Portal v1.8.1 - Quick Reference Card

**Status:** ✅ PRODUCTION READY | **Version:** 1.8.1 | **Date:** December 22, 2025

---

## 🚀 Quick Start Deployment

```bash
# 1. Upload plugin to WordPress
cp -r loungenie-portal /var/www/html/wp-content/plugins/

# 2. Activate in WordPress Admin
# Plugins → LounGenie Portal → Activate

# 3. Access portal
# Visit: https://yoursite.com/portal
```

## 📋 Essential Documentation

| Document | Purpose | Link |
|----------|---------|------|
| **Deployment Guide** | How to deploy to production | [DEPLOYMENT.md](loungenie-portal/DEPLOYMENT.md) |
| **Maintenance Guide** | Daily/monthly/annual operations | [MAINTENANCE.md](loungenie-portal/MAINTENANCE.md) |
| **Setup Guide** | Installation & configuration | [loungenie-portal/SETUP_GUIDE.md](loungenie-portal/SETUP_GUIDE.md) |
| **Test Report** | Validation test results | [TEST_VALIDATION_REPORT_2025.md](loungenie-portal/TEST_VALIDATION_REPORT_2025.md) |
| **Features** | Complete feature list | [loungenie-portal/FEATURES.md](loungenie-portal/FEATURES.md) |
| **Enterprise** | Advanced features (SSO, HubSpot, Graph API) | [loungenie-portal/ENTERPRISE_FEATURES.md](loungenie-portal/ENTERPRISE_FEATURES.md) |

## ✅ Pre-Deployment Checklist

- [ ] Version numbers updated (VERSION file, plugin header)
- [ ] `composer run cs` passes (WPCS compliance)
- [ ] `composer run test` passes (PHPUnit)
- [ ] Git status clean: `git status`
- [ ] All documentation reviewed
- [ ] Security settings verified

## 🔧 Key Configuration (Optional)

**Microsoft 365 SSO:** Admin → Settings → M365 SSO (Client ID, Secret, Tenant)  
**HubSpot Integration:** Admin → Settings → HubSpot (API Key)  
**Email Pipeline:** Admin → Settings → Email (Graph API or POP3)

## 📊 Test Results

| Category | Result | Details |
|----------|--------|---------|
| Data Seeding | 30/30 ✅ | All mock data created |
| Validation | 8/8 ✅ | Attachments, companies, audits |
| JavaScript | 5/5 ✅ | Map/marker rendering |
| PHP Syntax | 0 errors ✅ | All files compile |
| **Overall** | **100% ✅** | **Production Ready** |

## 🔗 User Roles

| Role | Access |
|------|--------|
| `lgp_support` | Full access to all companies/units |
| `lgp_partner` | Limited to their own company |

Create users in WordPress, assign role, link to company via:
```php
update_user_meta($user_id, 'lgp_company_id', $company_id);
```

## 🆘 Common Issues

| Issue | Solution |
|-------|----------|
| Plugin won't activate | Check `wp-content/debug.log` for errors |
| Database tables missing | Reactivate plugin to trigger creation |
| Portal route 404 | Flush rewrite rules: `wp rewrite flush --hard` |
| Email sync failing | Check Graph API config or POP3 credentials |
| Slow dashboard | Enable Redis caching via plugin |

## 📅 Maintenance Schedule

| Task | When | Time |
|------|------|------|
| Check error logs | Daily | 5 min |
| Database backup | Weekly | 10 min |
| Performance review | Monthly | 30 min |
| Security audit | Quarterly | 2 hrs |
| Full audit | Annual (June) | 8 hrs |

See [MAINTENANCE.md](loungenie-portal/MAINTENANCE.md) for details.

## 🎯 What Was Improved

✅ **Phase 1:** Validated all core functionality (0 issues found)  
✅ **Phase 2:** Suppressed IDE false positives with @phpstan annotations  
✅ **Phase 3:** Removed vendor bloat (1,800+ files removed from git)  
✅ **Phase 4:** Archived 30+ internal documents (root cleaned)  
✅ **Phase 5:** Tested all functions (43/43 tests passing)  
✅ **Phase 6:** Created deployment procedures  
✅ **Phase 7:** Established maintenance schedule  

## 📞 Support Resources

- **Documentation:** [loungenie-portal/README.md](loungenie-portal/README.md)
- **Setup Help:** [loungenie-portal/SETUP_GUIDE.md](loungenie-portal/SETUP_GUIDE.md)
- **Architecture:** [docs/archive/COMPREHENSIVE_AUDIT_AND_PLAN.md](docs/archive/COMPREHENSIVE_AUDIT_AND_PLAN.md)
- **Troubleshooting:** [MAINTENANCE.md - Troubleshooting Section](loungenie-portal/MAINTENANCE.md#troubleshooting-guide)

## 💾 Backup Before Deploying

```bash
# Database
mysqldump wordpress > backup-$(date +%Y%m%d).sql

# Plugin files
tar -czf loungenie-portal-backup-$(date +%Y%m%d).tar.gz \
  /var/www/html/wp-content/plugins/loungenie-portal
```

## 🚨 Emergency Rollback

If critical issues occur:
```bash
# Deactivate via WordPress Admin, or:
rm -rf /var/www/html/wp-content/plugins/loungenie-portal
# Restore from backup
tar -xzf loungenie-portal-backup-YYYYMMDD.tar.gz -C /
```

## 📈 Performance Targets

- Dashboard load: <2.5s
- API response: <300ms
- Map rendering: <1s
- File upload: <5s (per 10 MB)

## 🔐 Security Checklist

- [ ] HTTPS enforced
- [ ] CSP headers enabled (check DevTools)
- [ ] Rate limiting active (5 tickets/hr, 10 attachments/hr)
- [ ] File upload whitelist: JPG, PNG, PDF, TXT, DOC, CSV
- [ ] Max upload size: 10 MB
- [ ] Database queries use prepared statements
- [ ] Input sanitized, output escaped

## 📚 Repository Structure

```
loungenie-portal/
├── loungenie-portal.php          # Main plugin file
├── README.md                     # Plugin documentation
├── DEPLOYMENT.md                 # 📍 Start here for deployment
├── MAINTENANCE.md                # 📍 Start here for operations
├── SETUP_GUIDE.md                # Installation guide
├── ENTERPRISE_FEATURES.md        # Advanced features
├── includes/                     # Core plugin classes
├── templates/                    # HTML templates
├── assets/                       # CSS & JavaScript
├── api/                          # REST API endpoints
└── tests/                        # Unit tests

docs/
├── INDEX.md                      # Documentation index
└── archive/                      # Historical documents
```

---

**Plugin Version:** 1.8.1  
**Compatibility:** WordPress 5.8+, PHP 7.4+  
**Status:** ✅ Production Ready  
**Last Updated:** December 22, 2025  
**Next Audit:** June 2026
