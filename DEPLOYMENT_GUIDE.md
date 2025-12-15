# PoolSafe Portal v3.3.0 - Production Ready

## ✅ Deployment Status: READY

### What's New in v3.3.0

**Unified Portal System**
- Single `[poolsafe_portal]` shortcode for login and portal
- Automatic login/portal switching based on authentication state
- Company-based authentication with multiple contacts per company
- Microsoft 365 SSO for support users
- Admin interface for company management
- No page reload required for login/logout

**Features**
- ✅ Company authentication (username/password, bcrypt hashed)
- ✅ Multiple contacts per company (primary + secondary + more)
- ✅ Simple passwords allowed (admin controlled, securely hashed)
- ✅ Microsoft 365 OAuth2 SSO for support users
- ✅ REST API endpoints: dashboard stats, tickets, services, companies
- ✅ AJAX login without page reload
- ✅ WordPress theme color integration
- ✅ Fully responsive design
- ✅ ARIA-compliant UI components
- ✅ Modal system with focus management
- ✅ Minified assets with cache-busting (3.3.0)
- ✅ Performance optimizations: pagination, caching, lazy loading
- ✅ Security: CSP headers, nonce verification, capability checks
- ✅ Session management (7-day sessions, automatic cleanup)

### Quick Start

**1. Install Plugin**
```bash
# Upload final-deployment/wp-poolsafe-portal to wp-content/plugins/
# OR use the pre-built ZIP
```

**2. Activate Plugin**
- Go to WordPress Admin → Plugins
- Activate "PoolSafe Portal"
- Database tables will be created automatically

**3. Add Portal Page**
- Create new page: "Portal"
- Add shortcode: `[poolsafe_portal]`
- Publish

**4. Configure Microsoft 365 SSO (Optional for Support Users)**
- Go to WordPress Admin → Companies → M365 Settings
- Add Azure AD credentials:
  - Client ID
  - Client Secret
  - Tenant ID
- Save settings

**5. Create First Company**
- Go to WordPress Admin → Companies → Add New
- Fill in:
  - Company Name
  - Username (simple, no @ required)
  - Password (any password, no complexity rules)
  - Primary Contact details
- Click "Create Company"

**6. Test Login**
- Visit your portal page
- See login form with two tabs:
  - **Partner Login**: Company username/password
  - **Support Login**: Microsoft 365 SSO
- After login, portal displays automatically

### Database Tables Created

```sql
wp_psp_companies          # Company accounts
wp_psp_company_contacts   # Multiple contacts per company
wp_psp_sessions           # Secure session tokens (SHA-256)
wp_psp_login_log          # Login attempt tracking
wp_psp_tickets            # Support tickets
wp_psp_service_records    # Service scheduling
wp_psp_partners           # Legacy partner data (migrated)
```

### REST API Endpoints

**Authentication**
- `POST /psp/v1/auth/company/login` - Company login
- `POST /psp/v1/auth/company/logout` - Logout
- `GET /psp/v1/auth/validate` - Session validation
- `GET /psp/v1/auth/support/login-url` - Get M365 SSO URL

**Portal Data**
- `GET /psp/v1/dashboard/stats` - Dashboard statistics
- `GET /psp/v1/tickets` - List tickets (paginated)
- `POST /psp/v1/tickets` - Create ticket
- `GET /psp/v1/services` - List services (paginated)
- `POST /psp/v1/services` - Schedule service
- `GET /psp/v1/companies` - List companies (support only)

**Admin**
- `GET /psp/v1/admin/companies` - List all companies
- `POST /psp/v1/admin/companies` - Create company
- `PUT /psp/v1/admin/companies/{id}` - Update company
- `POST /psp/v1/admin/companies/{id}/password` - Reset password
- `POST /psp/v1/admin/companies/{id}/contacts` - Add contact

### Performance Features

**Caching**
- Dashboard stats cached for 5 minutes (wp_cache)
- REST API responses cached for 60 seconds
- Asset versioning with 3.3.0 (browser cache busting)
- Minified JS/CSS for faster loading

**Pagination**
- Default: 20 items per page
- Maximum: 100 items per page
- Applied to: tickets, services, companies lists

**Lazy Loading**
- Tab content loaded on demand
- Dashboard stats loaded separately
- Reduces initial page load time

### Security Checklist

- [x] Passwords hashed with bcrypt (PASSWORD_DEFAULT)
- [x] Session tokens hashed with SHA-256
- [x] AJAX requests verified with check_ajax_referer()
- [x] REST requests verified with wp_verify_nonce()
- [x] CSP headers configured (Content-Security-Policy)
- [x] SQL queries use $wpdb->prepare()
- [x] User input sanitized (sanitize_text_field, sanitize_email)
- [x] Output escaped (esc_html, esc_attr, esc_url)
- [x] Capability checks (psp_manage_companies, psp_access_portal)
- [x] Rate limiting via sleep(1) on failed login (timing attack prevention)
- [x] Secure cookies (httponly, secure flag on HTTPS)
- [x] Session expiry (7 days, automatic cleanup via WP-Cron)

### Browser Compatibility

**Tested Browsers**
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

**JavaScript Features Used**
- ES6+ (async/await, arrow functions, template literals)
- Fetch API
- DOM manipulation

**CSS Features Used**
- CSS Custom Properties (--psp-*)
- Flexbox
- Grid
- Media queries (mobile-first responsive)

### Files Modified in v3.3.0

**New Files**
- `includes/class-psp-company-auth.php` (230 lines)
- `includes/class-psp-support-auth.php` (120 lines)
- `includes/class-psp-db-schema.php` (80 lines)
- `includes/class-psp-portal.php` (288 lines)
- `includes/class-psp-company-admin.php` (445 lines)
- `js/portal.js` (600 lines)
- `js/portal.min.js` (minified + source map)
- `css/portal.css` (730 lines)
- `css/portal.min.css` (minified)

**Updated Files**
- `wp-poolsafe-portal.php` - Version 3.3.0, new auth initialization
- `includes/class-psp-rest.php` - Added dashboard/stats, enhanced tickets/services
- `includes/class-psp-activator.php` - DB schema creation on activation

**Deployment Directories**
- `final-deployment/wp-poolsafe-portal/` - Ready to deploy
- `production-clean/wp-poolsafe-portal/` - Production copy

### Troubleshooting

**Login issues?**
1. Check database tables exist (wp_psp_companies, wp_psp_sessions)
2. Verify username (no @ symbol for company login)
3. Check browser console for JavaScript errors
4. Clear browser cookies and try again

**M365 SSO not working?**
1. Verify Azure AD app registration
2. Check redirect URI: `https://yoursite.com/wp-admin/admin-ajax.php?action=psp_support_callback`
3. Confirm Client ID, Secret, Tenant ID in M365 Settings
4. Check user has valid Microsoft 365 account

**Portal not loading?**
1. Verify shortcode is `[poolsafe_portal]` (no extra spaces)
2. Check CSS/JS files loaded (inspect page source)
3. Look for PHP errors in debug.log
4. Verify REST API accessible: `/wp-json/psp/v1/health`

**Performance issues?**
1. Enable object caching (Redis, Memcached)
2. Use caching plugin (W3 Total Cache, WP Rocket)
3. Check database indexes (run EXPLAIN on slow queries)
4. Increase pagination limits if needed

### Support & Maintenance

**Cron Jobs**
- `psp_clean_sessions` - Runs daily, removes expired sessions

**WordPress Admin**
- Companies menu: Manage company accounts
- Login Activity: View authentication logs
- M365 Settings: Configure Microsoft SSO

**Logs Location**
- WordPress debug.log (if WP_DEBUG enabled)
- Database: wp_psp_login_log table

### Migration from Previous Versions

**From v3.2.x to v3.3.0**
- Automatic database migration on activation
- Existing wp_psp_partners data preserved
- Company-partner mapping via company name matching
- No manual steps required

**New Installations**
- Fresh database tables created
- No migration needed

### Production Deployment

**Option 1: Direct Upload**
```bash
# Upload final-deployment/wp-poolsafe-portal to wp-content/plugins/
wp plugin activate poolsafe-portal
```

**Option 2: ZIP Installation**
```bash
# Create ZIP
cd final-deployment
zip -r poolsafe-portal-3.3.0.zip wp-poolsafe-portal/
# Upload via WordPress Admin → Plugins → Add New → Upload Plugin
```

**Post-Deployment**
1. ✅ Activate plugin
2. ✅ Verify tables created (phpMyAdmin or wp-cli)
3. ✅ Create portal page with shortcode
4. ✅ Configure M365 settings (if using SSO)
5. ✅ Create test company account
6. ✅ Test login flow
7. ✅ Test ticket creation
8. ✅ Test service scheduling
9. ✅ Verify responsive design on mobile
10. ✅ Check browser console for errors

### Version History

**v3.3.0** (2025-12-10)
- Unified portal shortcode
- Company-based authentication
- Microsoft 365 SSO
- REST API enhancements
- Performance optimizations
- Minified assets

**v3.2.5** (Previous)
- WordPress user integration
- Improved security headers
- Enhanced ticket system

**v3.0.0** (Major)
- WordPress standards compliance
- Custom database tables
- REST API namespace

---

## Ready to Deploy ✅

All files are in `final-deployment/wp-poolsafe-portal/` directory.
Plugin is production-ready and fully tested.
