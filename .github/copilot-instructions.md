# LounGenie Portal - AI Agent Instructions

This document guides AI coding agents (GitHub Copilot, Cursor, Cline, etc.) working on the LounGenie Portal WordPress plugin. It captures architectural decisions, conventions, and critical workflows.

---

## 🏗️ Architecture Overview

### Core Philosophy: WordPress as Backend Only

**CRITICAL**: This plugin uses WordPress **ONLY** for:
- Authentication (wp_login, user roles)
- Database ($wpdb, schema management)
- REST API infrastructure (rest_api_init)
- Transients/caching

**NEVER**:
- ❌ No themes (zero theme dependencies)
- ❌ No page builders (Elementor, Divi, etc.)
- ❌ No shortcodes
- ❌ No frontend frameworks (React, Vue, Angular)
- ❌ No global wp_enqueue_scripts
---

## 🚀 Enterprise Features
- ✅ Vanilla JavaScript (ES6+) in [assets/js/](../loungenie-portal/assets/js/)
### 1. Microsoft 365 SSO (Azure AD OAuth 2.0)

**Implementation:** `includes/class-lgp-microsoft-sso.php`

**Azure Setup:**
1. Create Azure AD app registration
2. Set redirect URI: `{site}/m365-sso-callback`
3. Add API permissions: `User.Read`, `email`, `profile`, `openid`
4. Generate client secret

**WordPress Config:** Settings → M365 SSO (Client ID, Secret, Tenant ID)

### 2. Email-to-Ticket (Graph API + POP3 Fallback)

**Two Pipelines:**
- **New:** Microsoft Graph (app-only, see `includes/class-lgp-graph-client.php`)
- **Legacy:** POP3 polling (fallback, see `includes/class-lgp-email-handler.php`)

**Feature Flag:**

// Enable new Graph pipeline (loungenie-portal.php)
if ( ! self::use_new_email_pipeline() ) {
    LGP_Email_Handler::init(); // Legacy POP3
**Version:** 1.8.1 | **Updated:** Dec 2025 | **WordPress:** 5.8+ | **PHP:** 7.4+

### WordPress as Backend Framework ONLY
**Idempotency:** Uses `internetMessageId` to prevent duplicate ticket creation

### 3. HubSpot CRM Integration

**Auto-Sync:** Companies, tickets, attachments → HubSpot CRM

**Implementation:** `includes/class-lgp-hubspot.php`

**Setup:** Settings → HubSpot Integration (Private App Access Token)
// 1. Define constants (LGP_PLUGIN_DIR, LGP_ASSETS_URL)
// 3. Load class-lgp-loader.php for orchestration
## 🛠️ Development Workflow
```
### Local Setup (No WordPress Required)

**Offline Development:**
```bash
cd loungenie-portal/scripts
php offline-run.php seed       # Generate mock data
php offline-run.php test       # Run validation tests
php offline-run.php dashboard  # Render dashboard views
php offline-run.php validate   # Check data integrity
```

See `OFFLINE_DEVELOPMENT.md` for full guide.

### Code Standards (WPCS)

**Before Committing:**
```bash
composer run cs    # Check WordPress Coding Standards
composer run cbf   # Auto-fix safe violations
composer run test  # Run PHPUnit tests
```

**WPCS Strategy:** New code MUST comply; legacy violations tracked incrementally.

See `WPCS_STRATEGY.md` for details.

### Class Naming Convention
- **Prefix:** `LGP_`
- **File:** `class-lgp-*.php`
- **Namespace:** `LounGenie\Portal` (new classes only, see `class-lgp-auth.php`)

### Security Patterns

**Input:**
```php
$name = sanitize_text_field( $_POST['name'] );
$email = sanitize_email( $_POST['email'] );
$id = absint( $_GET['id'] );
```
```php
**Output:**
```php
echo esc_html( $name );
echo '<a href="' . esc_url( $url ) . '">';
echo '<input value="' . esc_attr( $value ) . '">';
```
    LGP_Security::init();      // CSP headers
**Database:**
```php
$results = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$table} WHERE company_id = %d",
    $company_id
) );
```
    
    // Phase 3: APIs & Features
### Frontend Isolation Pattern
__( 'Translatable string', 'loungenie-portal' );
_e( 'Echoed string', 'loungenie-portal' );
_x( 'Text', 'context', 'loungenie-portal' );
**Templates:** Semantic HTML in `templates/portal-shell.php`, `dashboard-support.php`, etc.
**Styles:** CSS tokens in `assets/css/design-tokens.css` (60-30-10 color rule)
---

## ❓ FAQ

**Q: Why no themes?**  
A: Zero external dependencies. Plugin is 100% self-contained, works with ANY WordPress theme.

**Q: Why Brain Monkey instead of Patchwork?**  
A: Patchwork causes "DefinedTooEarly" conflicts when WordPress functions defined in bootstrap. Brain Monkey aliases are safer.

**Q: Why company-level color aggregation?**  
A: Business requirement: Track total units by color per company, not individual unit details. Reduces DB complexity, enforces data privacy.

**Q: Why transactions for tickets?**  
A: Atomicity: If audit logging fails, ticket creation should rollback. Prevents data inconsistencies.

**Q: What if Microsoft Graph is down?**  
A: Email handler auto-fallbacks to POP3/wp_mail (see `includes/class-lgp-email-handler.php`).
public function get_data( $request ) {
    if ( ! is_user_logged_in() ) {
## 📚 Further Reading
    }
- **README.md** - Project overview
- **IMPLEMENTATION_SUMMARY.md** - Phase completion matrix
- **ENTERPRISE_FEATURES.md** - Microsoft 365 SSO, caching, security
- **FILTERING_GUIDE.md** - Analytics dashboard
- **WPCS_STRATEGY.md** - Coding standards approach
- **CONTRIBUTING.md** - Git workflow, commit conventions
- **OFFLINE_DEVELOPMENT.md** - Local testing without WordPress
    
---
    
**Last Updated:** December 2025  
**Test Pass Rate:** 90% (173/192 tests)  
**Production Status:** ✅ Ready for deployment
        $company_id = (int) get_user_meta( $user->ID, 'lgp_company_id', true );
For questions or clarifications, review the codebase structure in **FOLDER_STRUCTURE.md**.
    $results = $wpdb->get_results( "SELECT * FROM {$table} WHERE 1=1 {$where}" );
}
```

**See:** `api/dashboard.php`, `api/tickets.php`, `api/units.php` for real examples
}
### Security Checklist (Before Merging)
- [ ] Input sanitization: `sanitize_text_field()`, `sanitize_email()`, `absint()`
- [ ] Output escaping: `esc_html()`, `esc_attr()`, `esc_url()`
- [ ] Database queries: ALL use `$wpdb->prepare()` (NEVER raw SQL)
- [ ] Nonces verified: `wp_verify_nonce()` on forms
- [ ] File uploads: Max 10MB, MIME whitelist (see `class-lgp-file-validator.php`)
- [ ] CSP compliant: No inline scripts, use nonces from `LGP_Security::get_csp_nonce()`
    $where_clause = ''; // Support sees all
- API responses
- Company metadata
## 💾 Database Patterns
See [includes/class-lgp-company-colors.php](../loungenie-portal/includes/class-lgp-company-colors.php):
### Transaction Safety (CRITICAL for Data Integrity)
$colors = $wpdb->get_results( $wpdb->prepare(
**ALL critical operations MUST use transactions:**
    $company_id
global $wpdb;
$wpdb->query( 'START TRANSACTION' );
try {
```html
<!-- Portal shell sets data-role for CSS theming -->
<body data-role="<?php echo $is_support ? 'support' : 'partner'; ?>">
```

**Component Library:** `assets/css/portal-components.css` (cards, tables, buttons, badges)
Expected: **90%+ pass rate** (173/192 tests as of Dec 2025).
### Test Stack
- **PHPUnit 9.x**: Test runner
- **Brain Monkey**: WordPress function mocking (via `when()` and `expect()`)
**Production environment:** Shared WordPress hosting (NOT dedicated server)

**HARD RULES (see class-lgp-shared-hosting-rules.php):**
**Checklist Before Merging:**
1. ❌ NO WebSockets, persistent connections, polling loops
2. ✅ REST responses <300ms (p95), paginate (max 100 items), NEVER `SELECT *`
3. ✅ WP-Cron ONLY: `hourly`, `daily`, `weekly` schedules
4. ✅ Conditional assets: Only enqueue on `/portal/*` routes
5. ✅ File uploads: 10MB max, 6 MIME types (JPG, PNG, PDF, TXT, DOC, CSV)
6. ✅ CSP compliant: No `unsafe-inline`, whitelist CDNs
7. ✅ Rate limiting: 5 tickets/hour/user, 10 attachments/hour/user
8. ✅ Database: Index all FKs, use transients for computed values


---

## ❓ FAQ

**Q: Why no themes?**  
A: To maintain zero external dependencies. The plugin is 100% self-contained and works with ANY WordPress theme.

**Q: Why Brain Monkey instead of Patchwork?**  
A: Patchwork causes "DefinedTooEarly" conflicts when WordPress functions are defined in bootstrap.php. Brain Monkey aliases are safer.

**Q: Why company-level color aggregation?**  
A: Business requirement: Track total units by color per company, not individual unit-level details. Reduces database complexity and enforces data privacy.

**Q: Why transactions for tickets?**  
A: To ensure atomicity: If audit logging fails, ticket creation should roll back. Prevents data inconsistencies.

**Q: What if Microsoft Graph is down?**  
A: Email handler automatically falls back to POP3/wp_mail (see [includes/class-lgp-email-handler.php](../loungenie-portal/includes/class-lgp-email-handler.php)).

---

## 📚 Further Reading

- [README.md](../loungenie-portal/README.md) - Project overview
- [IMPLEMENTATION_SUMMARY.md](../loungenie-portal/IMPLEMENTATION_SUMMARY.md) - Phase completion matrix
- [ENTERPRISE_FEATURES.md](../loungenie-portal/ENTERPRISE_FEATURES.md) - Microsoft 365 SSO, caching, security
- [FILTERING_GUIDE.md](../loungenie-portal/FILTERING_GUIDE.md) - Analytics dashboard
- [WPCS_STRATEGY.md](../loungenie-portal/WPCS_STRATEGY.md) - Coding standards approach
- [CONTRIBUTING.md](../loungenie-portal/CONTRIBUTING.md) - Git workflow, commit conventions
- [OFFLINE_DEVELOPMENT.md](../loungenie-portal/OFFLINE_DEVELOPMENT.md) - Local testing without WordPress

---

**Last Updated**: December 2025  
**Test Pass Rate**: 90% (173/192 tests)  
**Production Status**: ✅ Ready for deployment

For questions or clarifications, review the codebase structure in [FOLDER_STRUCTURE.md](../loungenie-portal/FOLDER_STRUCTURE.md).
