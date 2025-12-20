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

**Instead**:
- ✅ Isolated UI at `/portal/*` route (see [includes/class-lgp-router.php](../loungenie-portal/includes/class-lgp-router.php))
- ✅ Semantic HTML in [templates/](../loungenie-portal/templates/)
- ✅ Vanilla JavaScript (ES6+) in [assets/js/](../loungenie-portal/assets/js/)
- ✅ Conditional asset loading (only on /portal/*)

---

## 🎯 Governing Principles (NEVER VIOLATE)

### 1. Role-Based Access Control (RBAC)
**Two roles only**: `lgp_support` (sees all companies) and `lgp_partner` (sees own company only).

**In every API endpoint**:
```php
// Check authentication first
if ( ! is_user_logged_in() ) {
    return new WP_Error( 'unauthorized', 'Authentication required', array( 'status' => 401 ) );
}

// Then check role
$user = wp_get_current_user();
$is_support = in_array( 'lgp_support', $user->roles, true );
$is_partner = in_array( 'lgp_partner', $user->roles, true );

if ( ! $is_support && ! $is_partner ) {
    return new WP_Error( 'forbidden', 'Insufficient permissions', array( 'status' => 403 ) );
}

// Apply company-level data filtering for partners
if ( $is_partner ) {
    $company_id = (int) get_user_meta( $user->ID, 'lgp_company_id', true );
    $where_clause = $wpdb->prepare( "AND company_id = %d", $company_id );
} else {
    $where_clause = ''; // Support sees all
}
```

### 2. Color Aggregation Principle
**CRITICAL ARCHITECTURAL RULE**: Units are aggregated at **company level by color**. Individual unit IDs are **NEVER** tracked or exposed in:
- API responses
- UI forms
- Company metadata

See [includes/class-lgp-company-colors.php](../loungenie-portal/includes/class-lgp-company-colors.php):
```php
// ✅ CORRECT: Company-level color aggregation
$colors = $wpdb->get_results( $wpdb->prepare(
    "SELECT color, COUNT(*) as qty FROM {$wpdb->prefix}lgp_units WHERE company_id = %d GROUP BY color",
    $company_id
) );

// ❌ WRONG: Exposing individual unit IDs
$units = $wpdb->get_results( "SELECT id, serial_number FROM lgp_units" ); // NEVER DO THIS
```

### 3. Transaction Safety
**ALL** critical database operations (tickets, service requests, attachments) **MUST** use transactions:
```php
global $wpdb;
$wpdb->query( 'START TRANSACTION' );
try {
    // 1. Insert ticket
    $wpdb->insert( $wpdb->prefix . 'lgp_tickets', $data );
    $ticket_id = $wpdb->insert_id;
    
    // 2. Log audit event
    LGP_Logger::log_event( $user_id, 'ticket_create', $company_id, ['ticket_id' => $ticket_id] );
    
    // 3. Commit
    $wpdb->query( 'COMMIT' );
    
    // 4. Fire action AFTER successful commit
    do_action( 'lgp_ticket_created', $ticket_id );
} catch ( Exception $e ) {
    $wpdb->query( 'ROLLBACK' );
    LGP_Logger::log( 'error', 'ticket_create_failed', ['error' => $e->getMessage()], $user_id, $company_id );
    return new WP_Error( 'transaction_failed', $e->getMessage(), array( 'status' => 500 ) );
}
```

### 4. Audit Logging
**EVERY** action that modifies data **MUST** log to `lgp_audit_log` table:
```php
LGP_Logger::log_event(
    $user_id,           // int: User who performed action
    'ticket_update',    // string: Action type
    $company_id,        // int|null: Company context
    ['ticket_id' => 5]  // array: Metadata
);
```

Schema: `user_id`, `action`, `company_id`, `meta` (JSON), `created_at`.

---

## 🚫 Shared Hosting Constraints

**HARD RULES** (see [includes/class-lgp-shared-hosting-rules.php](../loungenie-portal/includes/class-lgp-shared-hosting-rules.php)):

1. **Request-Bound Only**: No WebSockets, persistent connections, polling loops, or background listeners.
2. **REST Performance**: <300ms response time (p95); paginate all list endpoints (max 100 items/page); never `SELECT *`.
3. **WP-Cron Only**: Use `hourly`, `daily`, `weekly` schedules. No real-time ingestion or streaming telemetry.
4. **Asset Discipline**: Conditional enqueue (only on `/portal/*`); per-view bundles; minified CSS/JS.
5. **File Upload Limits**: 10MB max; 6 MIME types only (JPEG, PNG, PDF, plain text, ZIP, CSV); randomized filenames.
6. **CSP Compliance**: No `unsafe-inline` scripts/styles; whitelist CDNs only.
7. **Rate Limiting**: 5 tickets/hour per user; 10 attachments/hour per user; 100 list requests/min per IP.
8. **Database Realism**: Index all foreign keys; avoid JSON blobs for hot paths; use transients for computed values.
9. **DO NOT ADD**: Real-time listeners, WebSockets, server-side AI, background queues, message brokers.

**Before merging ANY code**:
- [ ] Does this run during page load or REST request only?
- [ ] Is REST response time <300ms (measured)?
- [ ] Are all foreign keys indexed?
- [ ] Does it use WP-Cron only?
- [ ] Are assets conditionally enqueued (/portal/* only)?
- [ ] Does it respect file upload limits?
- [ ] CSP-compliant (no unsafe-inline)?
- [ ] Rate limited (if applicable)?

---

## 🧪 Testing Patterns

### Running Tests
```bash
cd loungenie-portal
php vendor/bin/phpunit --no-coverage
```

Expected: **90%+ pass rate** (173/192 tests as of Dec 2025).

### Test Stack
- **PHPUnit 9.x**: Test runner
- **Brain Monkey**: WordPress function mocking (via `when()` and `expect()`)
- **Patchwork**: Not used (causes "DefinedTooEarly" conflicts; use Brain Monkey aliases instead)

### Critical Test Pattern: Brain Monkey Function Aliasing

**DO THIS** (in [tests/Util/WPTestCase.php](../loungenie-portal/tests/Util/WPTestCase.php)):
```php
protected function defineTestFunctions() {
    \Brain\Monkey\Functions\when('sanitize_text_field')->alias(fn($s) => strip_tags((string)$s));
    \Brain\Monkey\Functions\when('sanitize_email')->alias(fn($s) => filter_var($s, FILTER_SANITIZE_EMAIL));
    \Brain\Monkey\Functions\when('esc_html')->alias(fn($s) => htmlspecialchars((string)$s, ENT_QUOTES));
    \Brain\Monkey\Functions\when('wp_json_encode')->alias(fn($v) => json_encode($v));
    \Brain\Monkey\Functions\when('current_time')->alias(fn($t, $gmt=false) => date('Y-m-d H:i:s'));
    // ... (see full list in WPTestCase.php)
}
```

**DO NOT** define WordPress functions directly in `bootstrap.php` (causes Patchwork conflicts).

### REST API Dispatcher Pattern

For testing API endpoints, use the dispatcher in [tests/bootstrap.php](../loungenie-portal/tests/bootstrap.php):
```php
function rest_do_request( $request ) {
    $route = $request->get_route();
    
    if ( $route === '/loungenie/v1/dashboard/metrics' ) {
        return _rest_dispatch_dashboard( $request );
    } elseif ( preg_match( '#^/loungenie/v1/map/units#', $route ) ) {
        return _rest_dispatch_map_units( $request );
    } elseif ( preg_match( '#^/loungenie/v1/tickets#', $route ) ) {
        return _rest_dispatch_tickets( $request );
    }
    
    return new WP_REST_Response( ['error' => 'Route not found'], 404 );
}
```

### In-Memory Database Stub

Tests use [WP_Database_Stub](../loungenie-portal/tests/bootstrap.php) (named class, not anonymous) with:
- Auto-increment ID tracking per table
- In-memory storage (arrays)
- Basic query methods (insert, update, delete, get_var, get_results, get_row)

**ALWAYS** reset `global $wpdb` in `setUp()`:
```php
protected function setUp(): void {
    parent::setUp();
    Monkey\setUp();
    
    global $wpdb;
    $wpdb = new WP_Database_Stub(); // Fresh instance per test
}
```

---

## 🔌 Key Integrations

### 1. Microsoft Graph (Email)
**Priority**: Graph API first, POP3/wp_mail fallback.

See [includes/class-lgp-email-handler.php](../loungenie-portal/includes/class-lgp-email-handler.php):
- **Inbound**: Delta sync (`https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages/delta`)
- **Outbound**: `POST /me/sendMail` with `wp_mail()` fallback
- **Idempotency**: De-duplicate via `internetMessageId`
- **Locks**: Transient lock (`lgp_graph_sync_lock`) prevents concurrent syncs
- **Logging**: All operations logged with `LGP_Logger::log()`

**OAuth2 Scopes Required**: `Mail.Read`, `Mail.ReadWrite`, `Mail.Send` (app-only).

### 2. HubSpot CRM
See [includes/class-lgp-hubspot.php](../loungenie-portal/includes/class-lgp-hubspot.php):
- Sync companies to HubSpot contacts
- Uses WP-Cron for batch updates (daily)

### 3. Multi-Layer Caching
**Order**: Redis → Memcached → APCu → Transients (fallback).

See [ENTERPRISE_FEATURES.md](../loungenie-portal/ENTERPRISE_FEATURES.md):
```php
// Try Redis first
if ( class_exists( 'Redis' ) ) {
    $redis = new Redis();
    if ( $redis->connect( '127.0.0.1', 6379 ) ) {
        $value = $redis->get( $key );
    }
}

// Fallback to transients
if ( ! $value ) {
    $value = get_transient( $key );
}
```

### 4. Microsoft 365 SSO
Azure AD OAuth 2.0 flow (see [includes/class-lgp-microsoft-sso.php](../loungenie-portal/includes/class-lgp-microsoft-sso.php)):
- **Endpoints**: `https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize`
- **Scopes**: `openid`, `profile`, `email`
- **Token validation**: With Microsoft Graph API
- **HTTPS-only**: No token persistence

---

## 📝 Code Conventions

### PHP Namespace
All new classes use `namespace LounGenie\Portal;` (see [includes/class-lgp-auth.php](../loungenie-portal/includes/class-lgp-auth.php)).

### Class Naming
- Prefix: `LGP_`
- File: `class-lgp-*.php`
- Example: `class-lgp-email-handler.php` → `class LGP_Email_Handler`

### Security
- **Input sanitization**: `sanitize_text_field()`, `sanitize_email()`, `sanitize_textarea_field()`
- **Output escaping**: `esc_html()`, `esc_attr()`, `esc_url()`
- **Database**: Always use `$wpdb->prepare()` for queries
- **Nonces**: `wp_verify_nonce()` on forms
- **Permissions**: `current_user_can()`, `is_user_logged_in()`

### i18n
- Translatable strings: `__( 'text', 'loungenie-portal' )`
- Echoed strings: `_e( 'text', 'loungenie-portal' )`
- Contextual: `_x( 'text', 'context', 'loungenie-portal' )`

### Logging Method Signature
**CORRECT** (5 parameters):
```php
LGP_Logger::log( 'error', 'ticket_create_failed', ['error' => $msg], $user_id, $company_id );
```

**INCORRECT** (method does not exist):
```php
LGP_Logger::log_error( 'ticket_create_failed', $msg ); // ❌ NO log_error() method
```

### Constants
Define WordPress constants in [tests/bootstrap.php](../loungenie-portal/tests/bootstrap.php):
```php
if (!defined('OBJECT')) { define('OBJECT', 'OBJECT'); }
if (!defined('OBJECT_K')) { define('OBJECT_K', 'OBJECT_K'); }
if (!defined('ARRAY_A')) { define('ARRAY_A', 'ARRAY_A'); }
if (!defined('ARRAY_N')) { define('ARRAY_N', 'ARRAY_N'); }
```

---

## 🎨 Design System

### 60-30-10 Color Rule
- **60% Atmosphere** (light backgrounds): `#E9F8F9`, `#FFFFFF`, `#F5FBFC`, `#D8E9EC`
- **30% Structure** (text/borders): `#0F172A`, `#454F5E`, `#7A8699`, `#94A3B8`
- **10% Action** (buttons/highlights): `#3AA6B9` (Teal), `#25D0EE` (Cyan)

### Role-Specific Theming
- **Partner**: Teal (`#3AA6B9`) - Professional & Trustworthy
- **Support**: Cyan (`#25D0EE`) - Energetic & Responsive

### CSS Variables
Use `--lgp-*` custom properties in [assets/css/design-tokens.css](../loungenie-portal/assets/css/design-tokens.css).

### Component Library
See [assets/css/portal-components.css](../loungenie-portal/assets/css/portal-components.css):
- `.lgp-card` (elevated cards)
- `.lgp-table` (responsive tables with hover)
- `.lgp-btn-primary`, `.lgp-btn-secondary` (role-themed buttons)
- `.lgp-stat-card` (dashboard metric cards)

---

## 📂 Critical File Map

| File | Purpose |
|------|---------|
| [loungenie-portal.php](../loungenie-portal/loungenie-portal.php) | Plugin entry point; loads all classes |
| [includes/class-lgp-loader.php](../loungenie-portal/includes/class-lgp-loader.php) | Centralized component initialization |
| [includes/class-lgp-router.php](../loungenie-portal/includes/class-lgp-router.php) | Routes `/portal/*` to templates |
| [includes/class-lgp-auth.php](../loungenie-portal/includes/class-lgp-auth.php) | Role-based access control, login redirects, audit logging |
| [includes/class-lgp-database.php](../loungenie-portal/includes/class-lgp-database.php) | Schema management (5 tables) |
| [includes/class-lgp-migrations.php](../loungenie-portal/includes/class-lgp-migrations.php) | Versioned schema upgrades |
| [includes/class-lgp-logger.php](../loungenie-portal/includes/class-lgp-logger.php) | Audit logging helper (`log_event()`) |
| [includes/class-lgp-email-handler.php](../loungenie-portal/includes/class-lgp-email-handler.php) | Microsoft Graph email sync |
| [includes/class-lgp-email-to-ticket.php](../loungenie-portal/includes/class-lgp-email-to-ticket.php) | Convert emails to tickets |
| [includes/class-lgp-company-colors.php](../loungenie-portal/includes/class-lgp-company-colors.php) | Enforces color aggregation principle |
| [api/dashboard.php](../loungenie-portal/api/dashboard.php) | Dashboard metrics endpoint (`/loungenie/v1/dashboard/metrics`) |
| [api/map.php](../loungenie-portal/api/map.php) | Map units endpoint with geolocation |
| [api/tickets.php](../loungenie-portal/api/tickets.php) | Ticket CRUD with transactions |
| [tests/bootstrap.php](../loungenie-portal/tests/bootstrap.php) | PHPUnit environment setup, WP_Database_Stub, REST dispatcher |
| [tests/Util/WPTestCase.php](../loungenie-portal/tests/Util/WPTestCase.php) | Base test case with Brain Monkey aliases |

---

## 🚀 Deployment Checklist

Before merging to production:

1. ✅ **Tests**: `php vendor/bin/phpunit --no-coverage` (90%+ pass rate)
2. ✅ **WPCS**: `composer run cs` (no new violations)
3. ✅ **Auto-fix**: `composer run cbf` (apply safe formatting)
4. ✅ **Security**: All inputs sanitized, outputs escaped, queries prepared
5. ✅ **Transactions**: Critical operations use START TRANSACTION / COMMIT / ROLLBACK
6. ✅ **Audit Logging**: All data modifications logged with `LGP_Logger::log_event()`
7. ✅ **Role Checks**: All API endpoints enforce RBAC (Support vs Partner)
8. ✅ **Company Scoping**: Partners see only their company's data
9. ✅ **Shared Hosting Rules**: No WebSockets, <300ms response time, WP-Cron only
10. ✅ **Color Aggregation**: No individual unit ID exposure

---

## 🔍 Common Debugging Patterns

### Enable Debug Logging
In `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Logs written to `wp-content/debug.log`.

### Inspect Audit Log
```sql
SELECT * FROM wp_lgp_audit_log 
WHERE action = 'ticket_create' 
ORDER BY created_at DESC 
LIMIT 50;
```

### Check Graph Sync Lock
```bash
wp transient get lgp_graph_sync_lock
```

If stuck, delete:
```bash
wp transient delete lgp_graph_sync_lock
```

### Verify Role Assignments
```bash
wp user meta get <user_id> wp_capabilities
wp user meta get <user_id> lgp_company_id
```

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
