# Production Deployment Checklist

This comprehensive checklist ensures a smooth and secure deployment of the PoolSafe Portal to production environment.

## Pre-Deployment Phase

### 1. Code Review and Testing

- [ ] PR #2 (copilot/implement-portal-routing) merged into main branch
- [ ] GitHub Actions workflow completed successfully
- [ ] All unit tests passed (PHP and JavaScript)
- [ ] CodeQL security scan completed with 0 critical/high vulnerabilities
- [ ] REST API tests passed
- [ ] Manual testing completed in staging environment
- [ ] Stakeholder review and approval received
- [ ] Code review completed and approved

### 2. Environment Preparation

- [ ] Production WordPress site accessible
- [ ] WordPress version 5.8+ confirmed
- [ ] PHP version 7.4+ confirmed
- [ ] MySQL/MariaDB version confirmed
- [ ] HTTPS/SSL certificate installed and valid
- [ ] Server resources adequate (CPU, RAM, disk space)
- [ ] Backup system in place and tested
- [ ] Monitoring tools configured

### 3. Database Preparation

- [ ] Database backup completed
- [ ] Database credentials secured
- [ ] Database user has proper permissions
- [ ] Database collation set correctly (utf8mb4_unicode_ci recommended)
- [ ] Maximum upload size sufficient (check php.ini)
- [ ] Execution time limits appropriate (check php.ini)

### 4. Azure AD Configuration

- [ ] Production Azure AD app registered
- [ ] Client ID obtained and documented
- [ ] Tenant ID obtained and documented
- [ ] Client Secret created and securely stored
- [ ] Production redirect URI configured:
  ```
  https://your-production-domain.com/wp-admin/admin-ajax.php?action=psp_support_callback
  ```
- [ ] API permissions granted (User.Read, email, openid, profile)
- [ ] Admin consent granted for permissions
- [ ] Authentication settings configured correctly

### 5. Security Preparation

- [ ] Secure password policy defined
- [ ] Client secrets stored in secure vault/manager
- [ ] Database credentials encrypted
- [ ] WordPress security keys and salts regenerated
- [ ] Firewall rules reviewed and updated
- [ ] Rate limiting configured
- [ ] Security monitoring enabled

### 6. Documentation Review

- [ ] README.md reviewed and updated
- [ ] AZURE_AD_SETUP.md available
- [ ] WORDPRESS_SSO_SETUP.md available
- [ ] SAMPLE_DATA_IMPORT.md available
- [ ] GITHUB_ACTIONS_VERIFICATION.md available
- [ ] STAKEHOLDER_REVIEW_GUIDE.md available
- [ ] Contact information for support updated

## Deployment Phase

### 7. Download Deployment Artifact

- [ ] Access GitHub Actions workflow run
- [ ] Navigate to Artifacts section
- [ ] Download deployment ZIP file
  - `loungenie-portal-deployment.zip` or
  - `poolsafe-portal-deployment.zip`
- [ ] Verify ZIP file integrity (size, contents)
- [ ] Extract and review contents locally

### 8. Backup Current State

- [ ] Full WordPress backup completed
- [ ] Database dump created and stored securely
- [ ] Plugin files backed up (if updating)
- [ ] Theme files backed up
- [ ] .htaccess backed up
- [ ] wp-config.php backed up
- [ ] Backup stored in secure off-site location
- [ ] Backup restore procedure tested

### 9. Upload Plugin to Production

**Method 1: WordPress Admin (Recommended)**
- [ ] Log in to WordPress Admin
- [ ] Navigate to Plugins → Add New
- [ ] Click "Upload Plugin"
- [ ] Choose the deployment ZIP file
- [ ] Click "Install Now"
- [ ] Wait for upload and installation to complete

**Method 2: FTP/SFTP**
- [ ] Connect to server via FTP/SFTP
- [ ] Navigate to `/wp-content/plugins/`
- [ ] Upload extracted plugin folder
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Verify all files uploaded successfully

**Method 3: SSH/Command Line**
- [ ] SSH into server
- [ ] Navigate to `/wp-content/plugins/`
- [ ] Upload ZIP via SCP/SFTP
- [ ] Extract: `unzip poolsafe-portal-deployment.zip`
- [ ] Set permissions: `chmod -R 755 poolsafe-portal`

### 10. Plugin Activation

- [ ] Navigate to Plugins in WordPress Admin
- [ ] Locate "PoolSafe Portal" plugin
- [ ] Click "Activate"
- [ ] Verify activation success message
- [ ] Check for any PHP errors or warnings
- [ ] Verify database tables created:
  - `wp_psp_companies`
  - `wp_psp_company_contacts`
  - `wp_psp_sessions`
  - `wp_psp_login_log`
  - `wp_psp_tickets`
  - `wp_psp_ticket_replies`
  - `wp_psp_service_records`
  - `wp_psp_partners`

### 11. Configure WordPress SSO Settings

- [ ] Navigate to Companies → M365 Settings
- [ ] Enter Azure AD Client ID
- [ ] Enter Azure AD Tenant ID
- [ ] Enter Azure AD Client Secret
- [ ] Verify OAuth callback URL displayed correctly
- [ ] Click "Save Changes"
- [ ] Verify success message
- [ ] Confirm settings saved in database

### 12. Create Portal Page

- [ ] Navigate to Pages → Add New
- [ ] Title: "Portal" (or your preferred title)
- [ ] Add shortcode: `[poolsafe_portal]`
- [ ] Set permalink/slug: `/portal` (or your preferred URL)
- [ ] Publish page
- [ ] Note the page URL for testing

### 13. Import Production Data (Skip Sample Data)

**⚠️ IMPORTANT**: Do NOT import sample-data.sql in production

Instead:
- [ ] Import real company data (if migrating from another system)
- [ ] Or prepare to create companies manually
- [ ] Verify data import completed successfully
- [ ] Check data integrity

## Post-Deployment Phase

### 14. Initial Testing

**Test 1: WordPress Integration**
- [ ] Plugin appears in admin menu
- [ ] Menu items accessible (Companies, M365 Settings, Login Activity)
- [ ] No PHP errors in debug log
- [ ] No JavaScript errors in browser console

**Test 2: Portal Page**
- [ ] Navigate to portal page URL
- [ ] Page loads without errors
- [ ] Login form displayed correctly
- [ ] "Sign in with Microsoft" button visible
- [ ] Page styling correct (no theme conflicts)

**Test 3: Company Authentication**
- [ ] Create a test company account
- [ ] Log in with company credentials
- [ ] Verify dashboard loads
- [ ] Verify company data displayed
- [ ] Test logout functionality
- [ ] Test password requirements

**Test 4: Microsoft 365 SSO**
- [ ] Click "Sign in with Microsoft" button
- [ ] Redirects to Microsoft login page
- [ ] Enter M365 credentials
- [ ] Redirects back to portal successfully
- [ ] User created in WordPress with Support role
- [ ] Can access support dashboard
- [ ] Can view all companies data

**Test 5: REST API Endpoints**
```bash
# Test authentication
curl -X POST https://your-domain.com/wp-json/psp/v1/auth/company/login \
  -H "Content-Type: application/json" \
  -d '{"username":"testcompany","password":"testpassword"}'

# Test dashboard stats (with session token)
curl https://your-domain.com/wp-json/psp/v1/dashboard/stats \
  -H "Authorization: Bearer YOUR_SESSION_TOKEN"
```
- [ ] Authentication endpoint works
- [ ] Dashboard endpoint returns data
- [ ] Tickets endpoint works
- [ ] Services endpoint works
- [ ] Proper error handling for invalid requests

### 15. Functional Testing

**Companies Management**
- [ ] Create new company
- [ ] Add primary contact
- [ ] Add secondary contact
- [ ] Add additional contacts
- [ ] Update company information
- [ ] Reset company password
- [ ] Deactivate company
- [ ] Reactivate company

**Tickets System**
- [ ] Create new ticket as company
- [ ] View ticket list
- [ ] View ticket details
- [ ] Reply to ticket
- [ ] Update ticket status
- [ ] Filter tickets by status
- [ ] Filter tickets by priority
- [ ] Search tickets

**Services System**
- [ ] Create service request
- [ ] View service history
- [ ] View scheduled services
- [ ] Update service status
- [ ] Filter services
- [ ] Export services to CSV

**Dashboard Analytics**
- [ ] Dashboard loads for company users
- [ ] Dashboard loads for support users
- [ ] Statistics display correctly
- [ ] Top 5 analytics work (if applicable)
- [ ] Charts/graphs render correctly

**Admin Interface (Support Users)**
- [ ] Access company management
- [ ] View login activity logs
- [ ] Manage M365 settings
- [ ] View system status
- [ ] Access admin functions

### 16. Performance Testing

- [ ] Dashboard load time < 2 seconds
- [ ] Page navigation < 1 second
- [ ] Search/filter response < 500ms
- [ ] API endpoint response < 1 second
- [ ] CSV export completes in reasonable time
- [ ] No memory leaks
- [ ] Caching working correctly

### 17. Security Testing

**Authentication**
- [ ] Brute force protection working
- [ ] Rate limiting functional
- [ ] Session timeout works (7 days)
- [ ] Logout clears session completely
- [ ] HTTPS enforced
- [ ] Secure cookies set

**Authorization**
- [ ] Company users see only their data
- [ ] Support users see all data
- [ ] No privilege escalation possible
- [ ] URL manipulation doesn't bypass security
- [ ] Direct object reference protected

**Data Protection**
- [ ] Passwords properly hashed (bcrypt)
- [ ] Session tokens hashed (SHA-256)
- [ ] SQL injection protected (prepared statements)
- [ ] XSS protected (output escaping)
- [ ] CSRF protected (nonce verification)
- [ ] No sensitive data in URLs or logs

### 18. Browser Compatibility

- [ ] Chrome (latest 2 versions)
- [ ] Firefox (latest 2 versions)
- [ ] Safari (latest 2 versions)
- [ ] Edge (latest 2 versions)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

### 19. Responsive Design

- [ ] Desktop view (1920px)
- [ ] Laptop view (1366px)
- [ ] Tablet view (768px)
- [ ] Mobile view (375px)
- [ ] Orientation changes work
- [ ] Touch interactions work

### 20. Integration Testing

**HubSpot Integration** (if applicable)
- [ ] Company sync to HubSpot works
- [ ] Ticket sync to HubSpot works
- [ ] Bidirectional sync functional
- [ ] Error handling works

**Outlook Integration** (if applicable)
- [ ] Email notifications sent
- [ ] Email templates correct
- [ ] Reply handling works
- [ ] Attachment support works

**Microsoft Graph API**
- [ ] User profile retrieval works
- [ ] Token refresh works
- [ ] Error handling appropriate

## Production Monitoring

### 21. Set Up Monitoring

**Error Monitoring**
- [ ] WordPress debug log configured
- [ ] PHP error log monitored
- [ ] JavaScript error tracking (e.g., Sentry)
- [ ] 404 error monitoring
- [ ] API error rate monitoring

**Performance Monitoring**
- [ ] Page load time monitoring
- [ ] API response time monitoring
- [ ] Database query performance
- [ ] Server resource usage (CPU, RAM)
- [ ] Cache hit rate

**Security Monitoring**
- [ ] Failed login attempts
- [ ] Suspicious activity detection
- [ ] SQL injection attempts
- [ ] XSS attack attempts
- [ ] Unusual traffic patterns

**Uptime Monitoring**
- [ ] External uptime monitoring configured
- [ ] Alert notifications set up
- [ ] Status page configured
- [ ] Incident response plan in place

### 22. Configure Alerting

**Critical Alerts** (Immediate notification)
- [ ] Site down
- [ ] Database connection failure
- [ ] High error rate (> 5%)
- [ ] Security breach detected

**Warning Alerts** (Within 1 hour)
- [ ] Slow performance (> 3 seconds)
- [ ] High CPU usage (> 80%)
- [ ] High memory usage (> 80%)
- [ ] Unusual traffic spike

**Info Alerts** (Daily summary)
- [ ] Daily traffic report
- [ ] Error summary
- [ ] Performance summary
- [ ] Security event summary

### 23. Documentation Updates

- [ ] Update README with production URL
- [ ] Document production credentials (securely)
- [ ] Create production runbook
- [ ] Document backup/restore procedures
- [ ] Create incident response procedures
- [ ] Update change log
- [ ] Document known issues
- [ ] Create troubleshooting guide

## Post-Deployment Activities

### 24. User Training

- [ ] Schedule training sessions
- [ ] Create user guides
- [ ] Record video tutorials
- [ ] Set up support channel
- [ ] Create FAQ document
- [ ] Provide test accounts for training

### 25. Communication

- [ ] Announce launch to stakeholders
- [ ] Notify users of new portal
- [ ] Send login instructions
- [ ] Provide support contact information
- [ ] Share documentation links
- [ ] Schedule Q&A session

### 26. Continuous Improvement

**Week 1**
- [ ] Monitor error logs daily
- [ ] Review performance metrics
- [ ] Collect user feedback
- [ ] Address critical issues immediately

**Week 2-4**
- [ ] Analyze usage patterns
- [ ] Identify pain points
- [ ] Prioritize enhancements
- [ ] Plan next iteration

**Monthly**
- [ ] Review security logs
- [ ] Update dependencies
- [ ] Rotate client secrets (if needed)
- [ ] Review performance optimizations
- [ ] User satisfaction survey

### 27. Maintenance Schedule

**Daily**
- [ ] Monitor error logs
- [ ] Check uptime status
- [ ] Review security alerts

**Weekly**
- [ ] Review performance reports
- [ ] Check for plugin updates
- [ ] Database backup verification
- [ ] Security scan

**Monthly**
- [ ] Full security audit
- [ ] Performance optimization review
- [ ] User feedback analysis
- [ ] Documentation updates
- [ ] Dependency updates

**Quarterly**
- [ ] Major version updates
- [ ] Feature enhancements
- [ ] User training refresher
- [ ] Disaster recovery drill

**Annually**
- [ ] Comprehensive security review
- [ ] Infrastructure assessment
- [ ] Compliance audit
- [ ] Major architectural changes

## Emergency Procedures

### Rollback Plan

If deployment fails or critical issues found:

**Step 1: Assess Severity**
- Critical: Immediate rollback required
- High: Rollback within 1 hour
- Medium: Fix forward if possible
- Low: Schedule fix for next release

**Step 2: Execute Rollback**
1. [ ] Notify stakeholders
2. [ ] Deactivate new plugin
3. [ ] Restore previous plugin version (if applicable)
4. [ ] Restore database from backup
5. [ ] Verify rollback successful
6. [ ] Test critical functionality
7. [ ] Update status page

**Step 3: Post-Mortem**
- [ ] Document what went wrong
- [ ] Identify root cause
- [ ] Create action items
- [ ] Update deployment checklist
- [ ] Schedule fix deployment

## Sign-Off

**Deployment Completed By**: _________________ Date: __________

**Technical Review By**: _________________ Date: __________

**Security Review By**: _________________ Date: __________

**Management Approval**: _________________ Date: __________

**Deployment Status**: ☐ Successful ☐ Partial ☐ Failed ☐ Rolled Back

**Notes**:
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________

## Additional Resources

- [Azure AD Setup Guide](./AZURE_AD_SETUP.md)
- [WordPress SSO Setup Guide](./WORDPRESS_SSO_SETUP.md)
- [GitHub Actions Verification](./GITHUB_ACTIONS_VERIFICATION.md)
- [Stakeholder Review Guide](./STAKEHOLDER_REVIEW_GUIDE.md)
- [Sample Data Import Guide](./SAMPLE_DATA_IMPORT.md)
- [Plugin README](./README.md)
- [Deployment Guide](./DEPLOYMENT_GUIDE.md)
