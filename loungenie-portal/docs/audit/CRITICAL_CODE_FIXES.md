# LounGenie Portal - Critical Code Fixes (Implementation Guide)

**Status:** Production-Ready Fixes  
**Version:** 1.8.1  
**Files Modified:** 12  
**Lines Changed:** ~40  
**Time to Implement:** 2.5 hours  

---

## 🔴 CRITICAL FIXES (Must Apply Before Deployment)

---

## FIX #1: Version Number Update

**Priority:** CRITICAL  
**Time:** 1 minute  
**File:** loungenie-portal/loungenie-portal.php

**Location:** Line 8

**Before:**
```php
 * @version 1.8.0
```

**After:**
```php
 * @version 1.8.1
```

**Complete Code Block:**
```php
<?php
/**
 * Plugin Name:       LounGenie Portal
 * Plugin URI:        https://loungenie.com
 * Description:       Enterprise Partner Management Portal for WordPress
 * Version:           1.8.1
 * Author:            LounGenie Team
 * Author URI:        https://loungenie.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       loungenie-portal
 * Domain Path:       /languages/
 *
 * @package LounGenie Portal
 * @version 1.8.1
 */
```

**Verification:**
```bash
grep -n "@version 1.8.1" loungenie-portal/loungenie-portal.php
# Output: Should show line 8 with 1.8.1
```

---

## FIX #2: Add Global $wpdb Declaration

**Priority:** CRITICAL  
**Time:** 20-30 minutes  
**Files:** 8 API files (companies.php, units.php, tickets.php, dashboard.php, map.php, support.php, attachments.php, knowledge-center.php)

---

### 2a: api/companies.php

**Location:** Start of `get_companies()` method (approximately line 15)

**Before:**
```php
public function get_companies( $request ) {
    $results = $wpdb->get_results( $wpdb->prepare(
```

**After:**
```php
public function get_companies( $request ) {
    global $wpdb;
    $results = $wpdb->get_results( $wpdb->prepare(
```

**Pattern:** Add `global $wpdb;` before first `$wpdb->` usage

**All methods in file that need fix:**
- `get_companies()`
- `get_company()`
- `create_company()`
- `update_company()`

---

### 2b: api/units.php

**Same pattern as 2a** - Add `global $wpdb;` to these methods:
- `get_units()`
- `get_unit()`
- `create_unit()`
- `update_unit()`

**Example:**
```php
public function get_units( $request ) {
    global $wpdb;
    // ... rest of code
}
```

---

### 2c: api/tickets.php

**Methods to update:**
- `get_tickets()`
- `get_ticket()`
- `create_ticket()`
- `update_ticket()`
- `add_reply()`

**Example:**
```php
public function create_ticket( $request ) {
    global $wpdb;
    // ... rest of code
}
```

---

### 2d: api/dashboard.php

**Methods to update:**
- `get_dashboard_stats()`
- `get_top_metrics()`

---

### 2e: api/map.php

**Methods to update:**
- `get_company_locations()`
- `get_support_routes()`

---

### 2f: api/support.php

**Methods to update:**
- `get_support_tickets()`
- `get_support_users()`

---

### 2g: api/attachments.php

**Methods to update:**
- `get_attachments()`
- `upload_attachment()`
- `delete_attachment()`

---

### 2h: api/knowledge-center.php (formerly api/help-guides.php)

**Methods to update:**
- `get_help_guides()`
- `get_guide_by_id()`

---

**Verification for Fix #2:**
```bash
cd loungenie-portal

# Find all $wpdb uses in api files
grep -n "\$wpdb->" api/companies.php | head -5

# Verify global declarations
grep -B2 "\$wpdb->get_results" api/companies.php | grep "global \$wpdb"

# Should see 8+ matches
```

---

## FIX #3: Add class_exists() Guards

**Priority:** CRITICAL  
**Time:** 30-45 minutes  
**File:** includes/class-lgp-loader.php

**Location:** The `init()` static method (approximately line 25-60)

**Before:**
```php
public static function init() {
    // Load dependencies
    require_once LGP_PLUGIN_DIR . 'includes/class-lgp-database.php';
    require_once LGP_PLUGIN_DIR . 'includes/class-lgp-router.php';
    require_once LGP_PLUGIN_DIR . 'includes/class-lgp-auth.php';
    // ... more requires

    // Initialize classes
    LGP_Database::init();
    LGP_Router::init();
    LGP_Auth::init();
    LGP_Assets::init();
    // ... more init calls
}
```

**After:**
```php
public static function init() {
    // Load dependencies
    $classes = array(
        'class-lgp-database.php'  => 'LGP_Database',
        'class-lgp-router.php'    => 'LGP_Router',
        'class-lgp-auth.php'      => 'LGP_Auth',
        'class-lgp-assets.php'    => 'LGP_Assets',
        // ... add all required classes
    );

    foreach ( $classes as $file => $class ) {
        $path = LGP_PLUGIN_DIR . 'includes/' . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
        } else {
            error_log( "LounGenie Portal: Required file not found: $file" );
        }
    }

    // Initialize classes with safety check
    if ( class_exists( 'LGP_Database' ) ) {
        LGP_Database::init();
    } else {
        error_log( 'LounGenie Portal: LGP_Database class not found' );
        return false;
    }

    if ( class_exists( 'LGP_Router' ) ) {
        LGP_Router::init();
    } else {
        error_log( 'LounGenie Portal: LGP_Router class not found' );
    }

    if ( class_exists( 'LGP_Auth' ) ) {
        LGP_Auth::init();
    } else {
        error_log( 'LounGenie Portal: LGP_Auth class not found' );
    }

    if ( class_exists( 'LGP_Assets' ) ) {
        LGP_Assets::init();
    } else {
        error_log( 'LounGenie Portal: LGP_Assets class not found' );
    }

    // ... continue for all classes
}
```

**Verification:**
```bash
grep -n "class_exists" loungenie-portal/includes/class-lgp-loader.php | wc -l
# Should show 6+ matches (one per class)
```

---

## 🟠 HIGH PRIORITY FIXES

---

## FIX #4: Replace in_array() with LGP_Auth Methods

**Priority:** HIGH (Security)  
**Time:** 15-20 minutes  
**Files:** 3 files (dashboard.php, map.php, knowledge-center.php)

---

### 4a: api/dashboard.php

**Find:**
```bash
grep -n "in_array.*role" loungenie-portal/api/dashboard.php
```

**Before:**
```php
$roles = array( 'lgp_support', 'lgp_partner' );
if ( ! in_array( $user->roles[0], $roles ) ) {
    return rest_ensure_response( array( 'error' => 'Access denied' ) );
}
```

**After (Option 1 - Using helper):**
```php
if ( ! LGP_Auth::is_support() && ! LGP_Auth::is_partner() ) {
    return rest_ensure_response( array( 'error' => 'Access denied' ) );
}
```

**After (Option 2 - Using capabilities):**
```php
if ( ! current_user_can( 'read' ) ) {
    return rest_ensure_response( array( 'error' => 'Access denied' ) );
}
```

**Recommended:** Option 1 if LGP_Auth methods exist, Option 2 otherwise

---

### 4b: api/map.php

**Same pattern as 4a** - Replace all `in_array()` role checks

**Find:**
```bash
grep -n "in_array.*role" loungenie-portal/api/map.php
```

---

### 4c: api/knowledge-center.php (legacy alias api/help-guides.php)

**Same pattern as 4a**

**Find:**
```bash
grep -n "in_array.*role" loungenie-portal/api/knowledge-center.php
```

---

**Verification for Fix #4:**
```bash
cd loungenie-portal

# Should find 0 matches after fix
grep -r "in_array.*role" api/

# Should find matches for LGP_Auth calls
grep -r "LGP_Auth::is_support" api/
```

---

## FIX #5: Remove Redundant function_exists()

**Priority:** HIGH  
**Time:** 2 minutes  
**File:** api/tickets.php

**Location:** Approximately line 304-305

**Before:**
```php
if ( function_exists( 'error_log' ) ) {
    error_log( 'Ticket processing error: ' . $e->getMessage() );
}
```

**After:**
```php
error_log( 'Ticket processing error: ' . $e->getMessage() );
```

**Why:** `error_log()` is a built-in PHP function, always available. No need to check.

**Verification:**
```bash
grep -n "function_exists.*error_log" loungenie-portal/api/tickets.php
# Should show 0 matches after fix
```

---

## FIX #6: Add JavaScript Safety Checks

**Priority:** HIGH  
**Time:** 10-15 minutes  
**Files:** 2 files (includes/class-lgp-assets.php, assets/js/portal.js)

---

### 6a: includes/class-lgp-assets.php

**Location:** After wp_enqueue_script() call (approximately line 45-55)

**Before:**
```php
wp_enqueue_script(
    'lgp-portal',
    LGP_ASSETS_URL . '/js/portal.js',
    array(),
    LGP_VERSION,
    true
);
```

**After:**
```php
wp_enqueue_script(
    'lgp-portal',
    LGP_ASSETS_URL . '/js/portal.js',
    array(),
    LGP_VERSION,
    true
);

// Localize script data for JavaScript access
wp_localize_script(
    'lgp-portal',
    'lgpData',
    array(
        'userId'    => get_current_user_id(),
        'userRole'  => LGP_Auth::get_user_role(),
        'nonce'     => wp_create_nonce( 'lgp_nonce' ),
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'siteUrl'   => site_url(),
    )
);
```

---

### 6b: assets/js/portal.js

**Location:** Top of file (after any license/header comment)

**Before:**
```javascript
(function() {
    'use strict';
    
    // Uses lgpData without checking if it exists
    const userId = lgpData.userId;
```

**After:**
```javascript
(function() {
    'use strict';
    
    // Initialize lgpData if not provided by wp_localize_script
    if ( typeof lgpData === 'undefined' ) {
        window.lgpData = {
            userId: 0,
            userRole: 'guest',
            nonce: '',
            ajaxUrl: '',
            siteUrl: ''
        };
    }
    
    // Safe access to localized data
    const userId = window.lgpData.userId || 0;
    const userRole = window.lgpData.userRole || 'guest';
```

**Alternative (More Concise):**
```javascript
// At top of file
window.lgpData = window.lgpData || {};

// Then use with optional chaining (ES2020)
const userId = window.lgpData?.userId ?? 0;
const userRole = window.lgpData?.userRole ?? 'guest';
```

---

**Verification for Fix #6:**
```bash
# Check PHP file has wp_localize_script
grep -n "wp_localize_script" loungenie-portal/includes/class-lgp-assets.php

# Check JS file has safety check
grep -n "lgpData = " loungenie-portal/assets/js/portal.js
```

---

## 🟡 MEDIUM PRIORITY FIXES

---

## FIX #7: Add Null Safety Checks

**Priority:** MEDIUM  
**Time:** 10 minutes  
**Files:** 2 files (api/map.php, api/dashboard.php)

---

### 7a: api/map.php - get_company_locations()

**Before:**
```php
$results = $wpdb->get_results( $query );
foreach ( $results as $row ) {
    // Could crash if $row has null coordinates
    $coords[] = array(
        'lat' => $row->latitude,
        'lng' => $row->longitude,
    );
}
```

**After:**
```php
global $wpdb;
$results = $wpdb->get_results( $query );

if ( ! $results ) {
    return rest_ensure_response( array() );
}

$coords = array();
foreach ( $results as $row ) {
    if ( ! empty( $row->latitude ) && ! empty( $row->longitude ) ) {
        $coords[] = array(
            'lat'  => (float) $row->latitude,
            'lng'  => (float) $row->longitude,
            'name' => sanitize_text_field( $row->company_name ),
        );
    }
}

return rest_ensure_response( $coords );
```

---

### 7b: api/dashboard.php - get_dashboard_stats()

**Before:**
```php
$stats = $wpdb->get_row( "SELECT COUNT(*) as count FROM {$wpdb->prefix}lgp_tickets" );
$ticket_count = $stats->count;  // Could be null
```

**After:**
```php
global $wpdb;
$stats = $wpdb->get_row( "SELECT COUNT(*) as count FROM {$wpdb->prefix}lgp_tickets" );

if ( ! $stats ) {
    $ticket_count = 0;
} else {
    $ticket_count = (int) $stats->count;
}
```

---

**Verification for Fix #7:**
```bash
# Check for null checks in api/map.php
grep -n "! \$results" loungenie-portal/api/map.php

# Check for null checks in api/dashboard.php
grep -n "! \$stats" loungenie-portal/api/dashboard.php
```

---

## ✅ TESTING & VERIFICATION

### Step 1: Syntax Check
```bash
cd loungenie-portal

# Check all modified files for syntax errors
php -l loungenie-portal.php
php -l includes/class-lgp-loader.php
php -l api/companies.php
php -l api/dashboard.php
php -l api/map.php

# If no output = success
# If error = fix it before continuing
```

### Step 2: Run Tests
```bash
composer run test

# Expected output
# ✓ Tests passing: 192/192 (100%)
```

### Step 3: Check Coding Standards
```bash
composer run cs

# Review warnings, but don't need to fix low-priority ones
```

### Step 4: Manual API Test
```bash
# Get WordPress nonce first
curl "http://localhost/wp-json/wp/v2/users/me" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Test API endpoint
curl -H "X-WP-Nonce: YOUR_NONCE" \
  "http://localhost/wp-json/lgp/v1/companies"

# Expected: JSON response with companies
```

### Step 5: Browser Test
1. Login to WordPress
2. Navigate to /portal
3. Open Developer Tools (F12)
4. Check Console tab - should be empty (no errors)
5. Click dashboard cards - should load without errors
6. Verify tables display correctly

---

## 📋 FINAL CHECKLIST

Before declaring complete:

- [ ] All 7 fixes applied
- [ ] PHP syntax check passed
- [ ] PHPUnit tests: 192/192 (100%)
- [ ] PHPCS check reviewed
- [ ] API endpoints tested
- [ ] Browser console clean
- [ ] Dashboard loads <3s
- [ ] Partner portal filters correctly
- [ ] Error log shows no PHP notices
- [ ] Code committed to git

---

## 🚀 DEPLOYMENT

Once all fixes verified:

```bash
# Commit changes
git add -A
git commit -m "fix: resolve code audit issues (v1.8.1)"

# Create ZIP for deployment
zip -r loungenie-portal-1.8.1.zip loungenie-portal/

# Upload to WordPress.org
# Tag release
git tag v1.8.1
git push origin v1.8.1
```

---

**You're ready! Start with Fix #1 (1 minute) and work through in order.** 🚀
