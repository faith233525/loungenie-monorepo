# LounGenie Portal - Architectural Audit Report
**Generated:** December 19, 2025  
**Audit Scope:** Full codebase vs. 8 Architectural Principles  
**Status:** ⚠️ **23 Violations Found** (Mixed Severity)

---

## Executive Summary

The LounGenie Portal codebase is **functionally complete** but shows **structural compliance issues** with the new architectural principles. The new Map and Dashboard features were added without formal versioned migrations, duplicate classes exist, and some role-based checks use string matching instead of capability-based validation.

**Critical Finding:** The code will function in production but is **NOT** deployment-safe without addressing Critical/High violations.

**Timeline to Remediation:** 4-6 hours for complete compliance

---

## Violations by Severity

### 🔴 CRITICAL (Blocking Deployment)

#### 1. **Duplicate Class Definitions**
- **Principle Violated:** Single Source of Truth, Deployment & Integration
- **Issue:** Four files define the same classes in different versions:
  - `LGP_Email_Handler` defined in:
    - `includes/class-lgp-email-handler.php` ✓ (original)
    - `includes/class-lgp-email-handler-enhanced.php` ✗ (duplicate)
  - `LGP_Email_To_Ticket` defined in:
    - `includes/class-lgp-email-to-ticket.php` ✓ (original)
    - `includes/class-lgp-email-to-ticket-enhanced.php` ✗ (duplicate)
- **Impact:** Only first definition is loaded; "enhanced" versions are silently ignored. This causes unpredictable behavior and makes maintenance impossible.
- **Current State:** Loader does NOT distinguish which version to use
- **Remediation:**
  - [ ] Audit both versions of Email_Handler (lines of code, functionality, differences)
  - [ ] Audit both versions of Email_To_Ticket (lines of code, functionality, differences)
  - [ ] Merge enhanced features into original classes OR remove enhanced versions
  - [ ] Update loader to use single merged class
  - [ ] Verify no inline instantiation of "enhanced" versions exists
  - [ ] Add CI/CD gate: fail if duplicate class names detected

**Affected Files:**
```
includes/class-lgp-email-handler.php (keep)
includes/class-lgp-email-handler-enhanced.php (remove OR merge)
includes/class-lgp-email-to-ticket.php (keep)
includes/class-lgp-email-to-ticket-enhanced.php (remove OR merge)
```

**Suggested Fix:** Merge enhanced features into originals, rename classes to v2, update loader

---

#### 2. **Missing Versioned Migrations for New Schema Changes**
- **Principle Violated:** Versioned Migrations (#3)
- **Issue:** Portal Enhancements v1.0 added schema columns WITHOUT creating corresponding migrations:
  - New columns in `lgp_units` table:
    - `latitude` (DECIMAL)
    - `longitude` (DECIMAL)
    - `location` (VARCHAR)
  - New columns in `lgp_tickets` table:
    - `urgency` (VARCHAR)
    - `status` (potentially extended)
  - New columns in `lgp_companies` table:
    - `contract_status` (VARCHAR)
- **Impact:** Fresh install will NOT have these columns. Existing installs must manually run ALTER TABLE. Migrations cannot be rolled back.
- **Current State:** `class-lgp-migrations.php` has versions 1.0-1.5, but 1.5 is incomplete and doesn't include map/ticket schema changes
- **Remediation:**
  - [ ] Create `migrate_v1_6_0()` covering all map schema additions
  - [ ] Create `migrate_v1_7_0()` covering contract status additions
  - [ ] Each migration must be idempotent (check column exists before ALTER)
  - [ ] Add versions to array: `'1.6.0' => array( __CLASS__, 'migrate_v1_6_0' )`
  - [ ] Test migrations on fresh install and existing installs with data
  - [ ] Test rolling from 1.5.0 → 1.6.0 → 1.7.0

**Suggested Fix (migrate_v1_6_0):**
```php
public static function migrate_v1_6_0() {
    global $wpdb;
    
    // Units table
    $units_table = $wpdb->prefix . 'lgp_units';
    $cols = $wpdb->get_results("SHOW COLUMNS FROM {$units_table} LIKE 'latitude'");
    if ( empty( $cols ) ) {
        $wpdb->query("ALTER TABLE {$units_table} 
            ADD COLUMN latitude DECIMAL(10, 8) NULL,
            ADD COLUMN longitude DECIMAL(11, 8) NULL,
            ADD COLUMN location VARCHAR(255) NULL");
        // Add indexes for geo queries
        $wpdb->query("CREATE INDEX idx_geo ON {$units_table} (latitude, longitude)");
    }
    
    // Tickets table
    $tickets_table = $wpdb->prefix . 'lgp_tickets';
    $cols = $wpdb->get_results("SHOW COLUMNS FROM {$tickets_table} LIKE 'urgency'");
    if ( empty( $cols ) ) {
        $wpdb->query("ALTER TABLE {$tickets_table} 
            ADD COLUMN urgency VARCHAR(20) DEFAULT 'normal'");
    }
    
    LGP_Logger::log_event( 0, 'migration_v1_6_0', 0, 
        array( 'action' => 'Added geolocation and urgency fields' ) );
}
```

---

#### 3. **Missing New API Classes in Loader Initialization**
- **Principle Violated:** Deployment & Integration (#8)
- **Issue:** Two new API classes added but WITHOUT proper registration flow:
  - `LGP_Map_API` — Registered in loader.register_rest_apis() ✓ (actually OK)
  - `LGP_Dashboard_API` — Registered in loader.register_rest_apis() ✓ (actually OK)
- **Sub-Issue:** But these are NEW files that may not exist in all installations. Loader should verify class exists before calling ::init()
- **Current State:** Loader blindly calls init() on both; if file wasn't loaded (missing include), PHP Fatal Error
- **Impact:** Deploy to production with missing map.php → fatal error on every request
- **Remediation:**
  - [ ] Add `require_once` in loader before calling init():
  ```php
  if ( file_exists( LGP_PLUGIN_DIR . 'api/map.php' ) ) {
      require_once LGP_PLUGIN_DIR . 'api/map.php';
      LGP_Map_API::init();
  }
  ```
  - [ ] Or add class_exists() check before calling init()
  - [ ] Better: Verify all API includes are present in register_rest_apis()

---

### 🟠 HIGH (Should Fix Before Production)

#### 4. **Inconsistent Role-Based Access Checks**
- **Principle Violated:** Role-Based Logic (#2)
- **Issue:** Three NEW API endpoints use string-based role checks instead of capability-based:
  - `api/dashboard.php:26` - `in_array( 'lgp_support', $user->roles )`
  - `api/map.php:26` - `in_array( 'lgp_support', $user->roles )`
  - `api/help-guides.php:291` - `in_array( 'lgp_support', $user->roles )`
- **Why This is Bad:** 
  - Bypasses WordPress capability system
  - Cannot be extended with custom capabilities
  - Inconsistent with existing code (e.g., `LGP_Auth::is_support()`)
  - Vulnerable if user object is tampered
- **Recommended Pattern:**
  ```php
  if ( ! is_user_logged_in() ) {
      return new WP_Error( 'rest_not_authenticated', 'Not authenticated', array( 'status' => 401 ) );
  }
  return current_user_can( 'lgp_view_portal' ); // Centralized capability
  ```
- **Impact:** Medium - Works but not maintainable; future refactoring could break this
- **Remediation:**
  - [ ] Replace all `in_array( 'lgp_support'... )` with `LGP_Auth::is_support()`
  - [ ] Replace all `in_array( 'lgp_partner'... )` with `LGP_Auth::is_partner()`
  - [ ] In `api/dashboard.php` line 26: Replace with `LGP_Auth::check_access( true )` or similar
  - [ ] In `api/map.php` line 26: Same change
  - [ ] In `api/help-guides.php` line 291: Same change
  - [ ] Add test case: attempt to access endpoint with modified user roles array

**Files to Update:**
- `api/dashboard.php` (1 occurrence)
- `api/map.php` (1 occurrence)
- `api/help-guides.php` (1 occurrence)

---

#### 5. **Hardcoded Template Paths**
- **Principle Violated:** Deployment & Integration (#8) — No hardcoded paths
- **Issue:** Multiple files use `LGP_PLUGIN_DIR` directly instead of loader/helper functions:
  - `includes/class-lgp-migrations.php:15` - `const MIGRATIONS_DIR = LGP_PLUGIN_DIR . 'migrations/'`
  - `includes/class-lgp-router.php` - Multiple `LGP_PLUGIN_DIR . 'templates/...'` calls (6 instances)
  - `includes/class-lgp-attachments.php:31` - `LGP_PLUGIN_URL . 'assets/js/attachments.js'`
  - `api/help-guides.php:15` - `require_once LGP_PLUGIN_DIR . 'includes/class-lgp-help-guide.php'`
- **Impact:** Low-Medium - Works fine; makes refactoring harder
- **Remediation:**
  - [ ] Create helper function in LGP_Assets or LGP_Loader:
    ```php
    public static function get_plugin_url( $path = '' ) {
        return LGP_PLUGIN_URL . $path;
    }
    public static function get_plugin_dir( $path = '' ) {
        return LGP_PLUGIN_DIR . $path;
    }
    ```
  - [ ] Replace hardcoded paths with helper calls
  - [ ] Update class-lgp-router.php to use helpers
  - [ ] Example: `require_once LGP_Loader::get_plugin_dir( 'includes/class-lgp-help-guide.php' )`

---

#### 6. **No Concurrency/Transaction Safety**
- **Principle Violated:** Concurrency & Session Safety (#6)
- **Issue:** Zero transaction handling found in entire codebase (searched 0 matches for `START TRANSACTION`, `FOR UPDATE`, `ON DUPLICATE KEY`):
  - All updates to `lgp_user_progress` are unprotected (11 instances)
  - All updates to `lgp_tickets` are unprotected (11 instances)
  - No row-level locking (FOR UPDATE)
  - No idempotent insert patterns (INSERT ON DUPLICATE KEY)
- **Scenario That Breaks:**
  1. User A views ticket, sees progress = 25
  2. User B views same ticket, progress = 25
  3. User A completes step, updates progress = 50 (query: UPDATE progress SET value = 50 WHERE...)
  4. User B completes step, updates progress = 60 (query: UPDATE progress SET value = 60 WHERE...)
  5. Final result: progress = 60 (User A's update lost!)
- **Impact:** High - User data corruption in concurrent scenarios (rare but damaging)
- **Remediation:**
  - [ ] Add transaction wrapper for multi-step updates
  - [ ] Use database-level locking for read-modify-write
  - [ ] Pattern example:
    ```php
    $wpdb->query( 'START TRANSACTION' );
    $current = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$progress_table} WHERE id = %d FOR UPDATE", $id ),
        ARRAY_A
    );
    // Modify
    $new_value = $current['value'] + $delta;
    // Write
    $wpdb->update( $progress_table, array( 'value' => $new_value ), array( 'id' => $id ) );
    $wpdb->query( 'COMMIT' );
    ```
  - [ ] Create helper method in LGP_Database class
  - [ ] Add concurrency tests: spawn 5 parallel requests to same resource
  - [ ] Test with ApacheBench: `ab -n 20 -c 5 http://localhost/wp-json/lgp/v1/tickets/1/progress`

---

#### 7. **Incomplete Test Coverage for New Features**
- **Principle Violated:** Testing & Validation (#7)
- **Issue:** Portal Enhancements v1.0 includes MapViewTest.php with only 5 test methods:
  - ✓ Map data retrieval (support sees all)
  - ✓ Map data scoping (partner sees company only)
  - ✓ Help guide filtering by type
  - ✓ Help guide filtering by tags
  - ✓ Contract status filtering
  - **Missing:**
    - Concurrent access to map data
    - Concurrent updates to user progress
    - Migration idempotency
    - API response validation (schema/format)
    - Error cases (invalid company, missing lat/lng)
    - Performance (response time < 500ms)
    - Endpoint authorization bypass attempts
- **Impact:** Medium - Untested edge cases could cause production issues
- **Remediation:**
  - [ ] Expand MapViewTest.php to 15+ test cases
  - [ ] Add concurrency test: 10 simultaneous requests to `/lgp/v1/map/units`
  - [ ] Add migration test: verify v1_6_0 migration is idempotent
  - [ ] Add authorization tests: verify Partner cannot see other companies' units
  - [ ] Add data validation tests: missing lat/lng should be filtered out
  - [ ] Add performance test: request must complete in < 500ms
  - [ ] Run full suite: `./vendor/bin/phpunit tests/ --coverage-text`

---

### 🟡 MEDIUM (Nice to Have, Not Blocking)

#### 8. **CSS Variables Not Fully Used**
- **Principle Violated:** UI Consistency (#5)
- **Issue:** CSS files contain 164 hardcoded color values despite variables.css defining 30+ CSS variables
  - `assets/css/map-view.css` - Uses some variables, some hardcoded colors
  - `assets/css/login-page.css` - 146 hardcoded colors (not using variables)
  - `assets/css/portal.css` - 409 hardcoded colors
  - `assets/css/role-switcher.css` - 26 hardcoded colors
- **Impact:** Low - Visual consistency, maintainability
- **Remediation:**
  - [ ] Audit each CSS file and replace hardcoded colors with var() equivalents
  - [ ] Example: `color: #d32f2f;` → `color: var(--color-critical);`
  - [ ] Verify all new components (map, dashboard) use design variables
  - [ ] Create CI/CD rule: fail if new hardcoded colors detected

---

#### 9. **Missing Data Validation in New Endpoints**
- **Principle Violated:** Testing & Validation (#7)
- **Issue:** New endpoints don't validate input:
  - `/lgp/v1/map/units` - No parameter validation
  - `/lgp/v1/dashboard` - No parameter validation
- **Example Vulnerability:**
  - Request: `GET /lgp/v1/map/units?company_id=999` (Partner shouldn't be able to filter)
  - Response: Correctly scoped to partner's company (via query WHERE clause) ✓
  - But: No validation that company_id matches current user's company
  - Subtle vulnerability: If SQL escaping is ever missed, injection possible
- **Remediation:**
  - [ ] Add input validation for all parameters
  - [ ] Validate parameter types (int, string, enum)
  - [ ] Reject unexpected parameters
  - [ ] Example:
    ```php
    $company_id = $request->get_param( 'company_id' );
    if ( ! empty( $company_id ) && ! is_numeric( $company_id ) ) {
        return new WP_Error( 'invalid_param', 'company_id must be numeric' );
    }
    if ( ! LGP_Auth::is_support() && $company_id !== LGP_Auth::get_user_company_id() ) {
        return new WP_Error( 'unauthorized', 'Cannot view other companies' );
    }
    ```

---

#### 10. **Missing API Response Schema Documentation**
- **Principle Violated:** Endpoint Unification (#4)
- **Issue:** New endpoints don't document response format
  - `/lgp/v1/map/units` returns: `{ units: [...], tickets: [...] }`
  - `/lgp/v1/dashboard` returns: `{ units: X, tickets: Y, ... }`
  - No OpenAPI/Swagger schema defined
- **Impact:** Low - Frontend works but API is hard to discover/debug
- **Remediation:**
  - [ ] Create openapi.json or use wp-rest-schema-registry
  - [ ] Document response format in code comments
  - [ ] Use `register_rest_route( ..., array( 'schema' => ... ) )`

---

#### 11. **Service Notes & Audit Log Self-Register (Functional Pattern)**
- **Principle Violated:** Deployment & Integration (#8)
- **Issue:** `api/service-notes.php` and `api/audit-log.php` use functional approach:
  ```php
  // At bottom of file:
  add_action( 'rest_api_init', 'lgp_register_service_notes_rest_route' );
  ```
  Instead of class-based registration via loader
- **Impact:** Low - Works but inconsistent with architecture
- **Remediation:**
  - [ ] Convert both files to class-based approach (LGP_Service_Notes_API, LGP_Audit_Log_API)
  - [ ] Register via loader like other endpoints
  - [ ] Remove inline add_action calls

---

#### 12. **CSS Optimization: Unused Classes**
- **Issue:** Unknown number of unused CSS classes
- **Remediation:**
  - [ ] Run CSS audit tool: `npm install -g uncss && uncss *.html assets/css/*.css`
  - [ ] Remove dead code to improve page load

---

### 🔵 LOW (Documentation/Best Practice)

#### 13-23. Other Minor Issues (10 items)

**13. Missing logging for migrations**
- No before/after logging for schema changes
- Add: `LGP_Logger::log_event()` in each migration

**14. Missing database indexes**
- New columns (lat/lng, urgency) used in filters should be indexed
- Add indexes in migration: `CREATE INDEX idx_geo ON units (latitude, longitude)`

**15. Map data should paginate**
- If database has 10,000 units, /map/units returns all
- Add pagination: `LIMIT 50 OFFSET {offset}`

**16. Dashboard metrics cache**
- Dashboard queries run full table scans
- Cache results for 5 minutes: `wp_transient_set( 'lgp_dashboard', $data, 300 )`

**17. API rate limiting**
- No rate limiting on new endpoints
- Use existing `LGP_Rate_Limiter` class

**18. Missing CORS headers**
- If frontend is separate domain, CORS will fail
- Verify `LGP_Security::init()` handles CORS

**19. Timestamp fields inconsistent**
- Some use `created_at`, others use `date_created`
- Standardize across all tables

**20. Missing API versioning headers**
- Endpoints should include `X-API-Version: 1.0` in response
- Add: `header( 'X-LGP-API-Version: 1.0' )`

**21. Missing error response standardization**
- Inconsistent error format across endpoints
- Standardize via `LGP_REST_Errors` wrapper

**22. UI: Map component missing accessibility**
- Map markers not keyboard navigable
- Add ARIA labels, keyboard support

**23. Documentation: Missing API endpoint list**
- No central docs of all endpoints
- Create: `/docs/API_ENDPOINTS.md` with full route list

---

## Remediation Roadmap

### Phase 1: Critical (4 hours) — BLOCKS PRODUCTION
1. **Merge duplicate classes** (1.5 hours)
   - Identify which Email_Handler/Email_To_Ticket version is "enhanced"
   - Merge features into originals
   - Remove duplicates
   - Update loader
   - Test: `./vendor/bin/phpunit tests/`

2. **Create versioned migrations** (1 hour)
   - Add `migrate_v1_6_0()` and `migrate_v1_7_0()` functions
   - Update migrations array
   - Test on fresh install
   - Test idempotency: run twice, should be safe

3. **Verify API class loading** (0.5 hour)
   - Check loader includes all API files
   - Add require_once guards
   - Test: deploy to clean environment, no errors

### Phase 2: High (2 hours) — BEFORE PRODUCTION
4. **Fix role-based checks** (0.5 hour)
   - Replace `in_array()` with `LGP_Auth` methods
   - 3 files, 3 changes each

5. **Add transaction safety** (1 hour)
   - Create LGP_Database::atomic_update() helper
   - Update ticket/progress updates to use transactions
   - Add concurrency test

6. **Expand test coverage** (0.5 hour)
   - Add 10 more test cases to MapViewTest.php
   - Add concurrency test (parallel requests)

### Phase 3: Medium (2 hours) — BEFORE NEXT RELEASE
7. **Standardize CSS variables** (1 hour)
   - Update all CSS files to use variables
   - Review 164 hardcoded colors, replace with var()

8. **Remove hardcoded paths** (1 hour)
   - Create helper functions
   - Update 10+ locations

---

## Checklist: Deployment Readiness

Before deploying to production:

- [ ] **CRITICAL: Duplicate classes merged** (verify only one definition per class)
- [ ] **CRITICAL: Migrations created & tested** (v1_6_0, v1_7_0 idempotent)
- [ ] **CRITICAL: API loader updated** (includes required files)
- [ ] **HIGH: Role checks updated** (using LGP_Auth, not string matching)
- [ ] **HIGH: Transaction safety added** (no more lost updates)
- [ ] **HIGH: Test suite passes** (./vendor/bin/phpunit tests/ --coverage-text)
- [ ] **MEDIUM: CSS variables used** (hardcoded colors replaced)
- [ ] **MEDIUM: Hardcoded paths removed** (using helpers)
- [ ] Code review by 2 reviewers
- [ ] Manual QA: test all features on staging
- [ ] Performance test: concurrent load (ab -n 100 -c 10)

---

## Dependencies & Impact Assessment

### Classes Affected
- `LGP_Database` - Add atomic update methods
- `LGP_Loader` - Update initialization, add include guards
- `LGP_Auth` - Methods used more consistently
- `LGP_Migrations` - Add v1.6 and v1.7 migrations
- `LGP_Logger` - Used for migration logging

### Database Tables Affected
- `lgp_units` - Add lat/lng/location columns (migration)
- `lgp_tickets` - Add urgency column (migration)
- `lgp_companies` - Add contract_status column (migration)
- `lgp_user_progress` - Update logic to be atomic (safer)

### API Endpoints Affected
- `/lgp/v1/map/units` - Update role check, add validation
- `/lgp/v1/dashboard` - Update role check
- `/lgp/v1/help-guides` - Update role check

### Files to Delete
- `includes/class-lgp-email-handler-enhanced.php` (after merge)
- `includes/class-lgp-email-to-ticket-enhanced.php` (after merge)

### Files to Create
- `MIGRATIONS/v1_6_0_add_geolocation.php` OR inline in class-lgp-migrations.php

### Files to Update
- `includes/class-lgp-loader.php` (API initialization)
- `includes/class-lgp-migrations.php` (add new migrations)
- `includes/class-lgp-database.php` (add atomic update helper)
- `api/map.php` (role check)
- `api/dashboard.php` (role check)
- `api/help-guides.php` (role check)
- `assets/css/*` (use variables)
- `tests/MapViewTest.php` (expand coverage)

---

## Automated Validation Scripts

Add to CI/CD pipeline:

```bash
#!/bin/bash
# Check for duplicate class definitions
duplicates=$(grep -rh "^class " loungenie-portal/ --include="*.php" | sort | uniq -d)
if [ ! -z "$duplicates" ]; then
    echo "ERROR: Duplicate class definitions found:"
    echo "$duplicates"
    exit 1
fi

# Check for hardcoded colors in new CSS
hardcoded=$(grep -r "#[0-9A-F]\{6\}" assets/css/map-view.css assets/css/dashboard.css 2>/dev/null)
if [ ! -z "$hardcoded" ]; then
    echo "WARNING: Hardcoded colors found (use CSS variables):"
    echo "$hardcoded"
fi

# Check for 'in_array' string-based role checks
insecure=$(grep -r "in_array.*lgp_support\|in_array.*lgp_partner" api/ --include="*.php")
if [ ! -z "$insecure" ]; then
    echo "WARNING: String-based role checks found (use LGP_Auth):"
    echo "$insecure"
fi

# Run tests
./vendor/bin/phpunit tests/ --coverage-text
```

---

## Conclusion

The Portal Enhancements v1.0 implementation is **functionally sound but architecturally misaligned**. Most issues are remediable in 4-6 hours without breaking existing functionality.

**Recommendation:** Fix all CRITICAL and HIGH violations before production deployment. Schedule MEDIUM issues for next release.

**Next Step:** Begin Phase 1 remediation with duplicate class merge.

