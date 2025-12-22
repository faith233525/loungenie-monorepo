# LounGenie Portal - Critical Code Fixes

This document provides exact code fixes for all CRITICAL and HIGH priority issues identified in the comprehensive code audit.

---

## 🔴 CRITICAL FIX #1: Version Mismatch

**File:** `loungenie-portal/loungenie-portal.php`

**Change Line 8:**

```diff
- * @version   1.8.0
+ * @version   1.8.1
```

**Verification:**
- Header comment matches Plugin version
- Run: `head -20 loungenie-portal/loungenie-portal.php | grep -E "version|Version"`

---

## 🔴 CRITICAL FIX #2: Add Global $wpdb to API Methods

Apply this pattern to ALL API files that query the database:

**Files Affected:**
- `api/dashboard.php`
- `api/map.php`
- `api/gateways.php`
- `api/tickets.php`
- `api/companies.php`
- `api/units.php`
- `api/help-guides.php`

**Example Fix for `api/dashboard.php`:**

```php
// Find this method:
public function get_metrics($request)
{
    // Add this line immediately after opening brace:
    global $wpdb;
    
    // Rest of method continues...
}

// Find every method that uses $wpdb and add the global declaration
public function get_metrics($request) {
    global $wpdb; // ← ADD THIS LINE
    
    // Enhanced authentication check
    if (! is_user_logged_in()) {
        // ...
    }
    // ... rest of code
}

public function some_other_method() {
    global $wpdb; // ← ADD THIS LINE
    
    // ... method code
}
```

**Shell Command to Find Missing Declarations:**

```bash
cd /workspaces/Pool-Safe-Portal/loungenie-portal

# Find all methods that use $wpdb but don't declare it
for file in api/*.php; do
    echo "=== $file ==="
    grep -n "function.*(" "$file" | while read -r line; do
        line_num=$(echo "$line" | cut -d: -f1)
        echo "Method at line $line_num:"
        sed -n "${line_num},$((line_num+30))p" "$file" | \
            grep -E "(function|global \$wpdb|\$wpdb-)" | head -5
    done
done
```

---

## 🔴 CRITICAL FIX #3: Add require_once Guards for All Classes

**File:** `includes/class-lgp-loader.php`

**Replace the entire `init()` method** with this corrected version:

```php
public static function init()
{
    // Phase 1: Foundation
    self::require_class('class-lgp-database.php', 'LGP_Database');
    self::require_class('class-lgp-security.php', 'LGP_Security');
    self::require_class('class-lgp-capabilities.php', 'LGP_Capabilities');
    self::require_class('class-lgp-company-colors.php', 'LGP_Company_Colors');

    // Phase 2: Core
    self::require_class('class-lgp-auth.php', 'LGP_Auth');
    self::require_class('class-lgp-router.php', 'LGP_Router');
    self::require_class('class-lgp-assets.php', 'LGP_Assets');

    // Phase 3: APIs & Logging
    self::require_class('class-lgp-logger.php', 'LGP_Logger');
    self::require_class('class-lgp-notifications.php', 'LGP_Notifications');

    // Phase 4: Features
    if (! self::use_new_email_pipeline()) {
        self::require_class('class-lgp-email-handler.php', 'LGP_Email_Handler');
    }
    
    self::require_class('class-lgp-microsoft-sso.php', 'LGP_Microsoft_SSO');
    self::require_class('class-lgp-hubspot.php', 'LGP_HubSpot');
    self::require_class('class-lgp-outlook.php', 'LGP_Outlook');
    self::require_class('class-lgp-system-health.php', 'LGP_System_Health');
    self::require_class('class-lgp-csv-partner-import.php', 'LGP_CSV_Partner_Import');

    // Admin Tools
    if (is_admin() || current_user_can('manage_options')) {
        self::require_class('class-lgp-role-switcher.php', 'LGP_Role_Switcher');
    }

    // Register REST APIs
    self::register_rest_apis();
}

/**
 * Safely require and initialize a class file
 */
private static function require_class($file, $class_name) {
    if (file_exists(LGP_PLUGIN_DIR . 'includes/' . $file)) {
        require_once LGP_PLUGIN_DIR . 'includes/' . $file;
        
        if (class_exists($class_name)) {
            $class_name::init();
        } else {
            error_log("LGP: Failed to load class {$class_name} from {$file}");
        }
    } else {
        error_log("LGP: Class file not found: includes/{$file}");
    }
}

/**
 * Register all REST API endpoints with guards
 */
private static function register_rest_apis()
{
    // API classes (already in /api/ folder, auto-loaded if needed)
    $api_endpoints = array(
        'companies'     => 'LGP_Companies_API',
        'units'         => 'LGP_Units_API',
        'tickets'       => 'LGP_Tickets_API',
        'gateways'      => 'LGP_Gateways_API',
        'attachments'   => 'LGP_Attachments_API',
        'help-guides'   => 'LGP_Help_Guides_API',
        'dashboard'     => 'LGP_Dashboard_API',
        'map'           => 'LGP_Map_API',
    );

    foreach ($api_endpoints as $endpoint_file => $class_name) {
        $file_path = LGP_PLUGIN_DIR . 'api/' . $endpoint_file . '.php';
        
        if (file_exists($file_path)) {
            require_once $file_path;
            
            if (class_exists($class_name)) {
                $class_name::init();
            } else {
                error_log("LGP: Failed to load API class {$class_name}");
            }
        }
    }

    // Service Notes and Audit Log self-register when their files are loaded
}
```

---

## 🔴 CRITICAL FIX #4: Remove `in_array()` Role Checks

**Files Affected:**
- `api/dashboard.php` (line 67 area)
- `api/map.php` (line 122 area)
- Any other API file with `in_array()`

**Before:**
```php
$user = wp_get_current_user();
if (in_array('lgp_support', $user->roles)) {
    // Show support dashboard
}
```

**After - Pattern A (RECOMMENDED):**
```php
// Use the LGP_Auth helper methods
if (! LGP_Auth::is_support()) {
    return new WP_Error(
        'forbidden',
        __('This resource is only available to support users.', 'loungenie-portal'),
        array('status' => 403)
    );
}

// Support-only code follows...
```

**After - Pattern B (If you need user object):**
```php
$user = wp_get_current_user();

if (! isset($user->ID) || $user->ID === 0) {
    return new WP_Error(
        'unauthorized',
        __('You must be logged in.', 'loungenie-portal'),
        array('status' => 401)
    );
}

if (! isset($user->roles) || ! is_array($user->roles)) {
    return new WP_Error(
        'invalid_user',
        __('Invalid user object.', 'loungenie-portal'),
        array('status' => 400)
    );
}

// Use user_can() instead of in_array()
if (! user_can($user, 'lgp_view_support_dashboard')) {
    return new WP_Error(
        'forbidden',
        __('Insufficient permissions.', 'loungenie-portal'),
        array('status' => 403)
    );
}

// Proceed with processing...
```

**Shell script to find all instances:**

```bash
cd /workspaces/Pool-Safe-Portal/loungenie-portal

# Find all in_array checks with role strings
grep -rn "in_array.*lgp_" api/ includes/ --include="*.php"

# Results should be replaced with LGP_Auth methods
```

---

## 🟠 HIGH PRIORITY FIX #1: Remove Redundant function_exists()

**File:** `api/tickets.php` lines ~304-305

**Before:**
```php
if (function_exists('error_log')) {
    error_log('LGP ticket create error: ' . $e->getMessage());
}
```

**After - Option A (Simple):**
```php
error_log('LGP ticket create error: ' . $e->getMessage());
```

**After - Option B (Better - Use Logger):**
```php
if (class_exists('LGP_Logger')) {
    LGP_Logger::log_event(
        get_current_user_id(),
        'ticket_create_error',
        0,
        array('error' => $e->getMessage())
    );
} else {
    error_log('LGP ticket create error: ' . $e->getMessage());
}
```

---

## 🟠 HIGH PRIORITY FIX #2: Fix JavaScript lgpData Undefined

**File:** `assets/js/portal.js` lines ~184+

**Before:**
```javascript
function loadPageData(endpoint, page) {
    const url = lgpData.restUrl + endpoint + '?page=' + page;
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-WP-Nonce': lgpData.nonce
        }
    })
}
```

**After:**
```javascript
function loadPageData(endpoint, page) {
    // Defensive check for undefined globals
    if (typeof lgpData === 'undefined' || !lgpData.restUrl) {
        console.error('LGP: lgpData not available - check wp_localize_script()');
        return;
    }
    
    const url = lgpData.restUrl + endpoint + '?page=' + page;
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-WP-Nonce': lgpData.nonce || ''
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Page data loaded:', data);
        // Process data...
    })
    .catch(error => {
        console.error('Error loading page data:', error);
    });
}
```

**Also ensure in PHP** (`includes/class-lgp-assets.php`):

```php
// After enqueueing portal.js, add localization:
wp_enqueue_script('lgp-portal', LGP_ASSETS_URL . 'js/portal.js', array(), LGP_VERSION);

// IMPORTANT: Localize script AFTER enqueueing
wp_localize_script(
    'lgp-portal',
    'lgpData',
    array(
        'restUrl' => rest_url('lgp/v1/'),
        'nonce'   => wp_create_nonce('wp_rest'),
        'userId'  => get_current_user_id(),
        'userRole' => ! empty(wp_get_current_user()->roles) 
            ? $wp_get_current_user()->roles[0] 
            : 'guest',
    )
);
```

---

## 🟠 HIGH PRIORITY FIX #3: Add null/empty Checks for Database Results

**File:** `api/map.php` lines ~95-110

**Before:**
```php
$units = $wpdb->get_results(
    "SELECT ... FROM {$units_table} u
     LEFT JOIN {$companies_table} c ON c.id = u.company_id
     WHERE u.latitude IS NOT NULL AND u.longitude IS NOT NULL"
);

foreach ($units as $unit) {
    // Process unit
}
```

**After:**
```php
$units = $wpdb->get_results(
    "SELECT 
        u.id, 
        CONCAT('Unit ', COALESCE(u.unit_number, u.id)) AS name,
        COALESCE(u.venue_type, u.lock_type, 'Unknown') AS type,
        CONCAT_WS(', ', c.name, u.address) AS location,
        u.latitude, 
        u.longitude,
        u.status,
        u.season
     FROM {$units_table} u
     LEFT JOIN {$companies_table} c ON c.id = u.company_id
     WHERE u.latitude IS NOT NULL AND u.longitude IS NOT NULL 
     AND u.latitude != 0 AND u.longitude != 0"
);

// Check for database errors
if ($wpdb->last_error) {
    error_log('LGP Map Query Error: ' . $wpdb->last_error);
    return new WP_Error(
        'db_error',
        __('Unable to retrieve units from database', 'loungenie-portal'),
        array('status' => 500)
    );
}

// Check if results are valid
if (!is_array($units)) {
    return array('units' => array(), 'total' => 0);
}

// Now safe to iterate
$processed_units = array();
foreach ($units as $unit) {
    // Validate coordinates
    if (!isset($unit->latitude, $unit->longitude)) {
        continue;
    }
    
    $lat = floatval($unit->latitude);
    $lng = floatval($unit->longitude);
    
    if ($lat === 0.0 || $lng === 0.0) {
        continue; // Skip invalid coordinates
    }
    
    $processed_units[] = array(
        'id'        => (int) $unit->id,
        'name'      => sanitize_text_field($unit->name ?? 'Unknown'),
        'type'      => sanitize_text_field($unit->type ?? 'Unknown'),
        'location'  => sanitize_text_field($unit->location ?? 'Unknown'),
        'latitude'  => $lat,
        'longitude' => $lng,
        'status'    => sanitize_text_field($unit->status ?? 'unknown'),
        'season'    => sanitize_text_field($unit->season ?? 'unknown'),
    );
}

return array(
    'units' => $processed_units,
    'total' => count($processed_units),
);
```

---

## 🟠 HIGH PRIORITY FIX #4: Fix Return Type Inconsistency

**File:** `includes/class-lgp-auth.php`

**Before:**
```php
public static function get_user_company_id() {
    if (! is_user_logged_in()) {
        return false; // ← Boolean
    }
    
    $company_id = (int) get_user_meta(
        get_current_user_id(),
        'lgp_company_id',
        true
    );
    
    return $company_id; // ← Integer
}
```

**After:**
```php
/**
 * Get logged-in user's company ID
 *
 * @return int Company ID, or 0 if not assigned
 */
public static function get_user_company_id() {
    if (! is_user_logged_in()) {
        return 0; // ← Always integer
    }
    
    $user_id = get_current_user_id();
    
    $company_id = get_user_meta(
        $user_id,
        'lgp_company_id',
        true
    );
    
    // Ensure integer, return 0 if not set
    return (int) ($company_id ?? 0);
}

// Usage (updated for consistency):
$company_id = LGP_Auth::get_user_company_id();

// Now always a safe integer check
if ($company_id > 0) {
    // User has a company
} else {
    // User has no company assigned
}
```

---

## 🟢 JAVASCRIPT FIX: Add Optional Chaining Safety

**File:** `assets/js/map-view.js` (if it exists) or similar

**Before:**
```javascript
markers.forEach(marker => {
    const lat = marker.latitude;
    const lng = marker.longitude;
    const name = marker.name;
    const popup = `<strong>${name}</strong>`;
});
```

**After:**
```javascript
( function() {
    'use strict';
    
    // Validate markers array
    if (!window.lgpCompanyMap || !Array.isArray(window.lgpCompanyMap.markers)) {
        console.warn('LGP: No markers data available');
        return;
    }
    
    const markers = window.lgpCompanyMap.markers;
    
    markers.forEach(marker => {
        // Use optional chaining for safety
        const lat = marker?.latitude;
        const lng = marker?.longitude;
        const name = marker?.name || 'Unknown';
        
        // Validate coordinates are numbers
        if (typeof lat !== 'number' || typeof lng !== 'number') {
            console.warn('LGP: Invalid marker coordinates', marker);
            return; // Skip this marker
        }
        
        // Build popup safely
        const popup = `<strong>${escapeHtml(name)}</strong>`;
        
        // Add to map...
    });
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
} )();
```

---

## ✅ Verification Commands

After applying fixes, run these commands to verify:

```bash
cd /workspaces/Pool-Safe-Portal/loungenie-portal

# 1. Check for syntax errors
php -l loungenie-portal.php
php -l includes/class-lgp-loader.php
php -l api/dashboard.php
php -l api/map.php

# 2. Search for remaining issues
echo "=== Remaining in_array() checks ==="
grep -rn "in_array.*lgp_" api/ includes/ --include="*.php" || echo "✓ None found"

echo -e "\n=== Remaining undefined function_exists('error_log') ==="
grep -rn "function_exists.*error_log" . --include="*.php" || echo "✓ None found"

echo -e "\n=== Global \$wpdb declarations ==="
grep -c "global \$wpdb" api/*.php | grep -v ":0" || echo "✓ All declared"

# 3. Run tests if available
if [ -f phpunit.xml ]; then
    composer run test
fi
```

---

## 🚀 Implementation Order

Apply fixes in this order:

1. **Critical Fix #1:** Version mismatch (5 min)
2. **Critical Fix #2:** Add global $wpdb (15 min - multiple files)
3. **Critical Fix #3:** Class require_once guards (20 min)
4. **Critical Fix #4:** Remove in_array() checks (15 min)
5. **High Fix #1:** Remove function_exists() (5 min)
6. **High Fix #2:** JavaScript lgpData safety (10 min)
7. **High Fix #3:** Database null checks (20 min)
8. **High Fix #4:** Return type consistency (15 min)
9. **Test:** Run full test suite (30 min)
10. **Deploy:** Create ZIP and submit to WordPress.org

**Total Time:** ~2.5 hours for all critical and high fixes

---

## 🧪 Testing Checklist After Fixes

- [ ] Plugin activates without errors
- [ ] Plugin deactivates cleanly
- [ ] Support user can login and access /portal
- [ ] Partner user can login and access /portal
- [ ] Dashboard loads without JS errors
- [ ] Map view renders correctly
- [ ] All API endpoints return 200 OK
- [ ] No PHP errors in error logs
- [ ] No JavaScript console errors
- [ ] CSV import (if enabled) works

---

**Ready to deploy after these fixes are applied!**
