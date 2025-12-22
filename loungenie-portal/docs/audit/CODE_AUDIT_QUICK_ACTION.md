# LounGenie Portal - Code Audit Quick Action Guide

**Purpose:** Fast reference for developers  
**Time to Read:** 5 minutes  
**Time to Implement:** 2.5 hours  
**Files Modified:** 12  
**Total Changes:** ~40 lines

---

## 🎯 7 MUST-FIX ISSUES

### Issue 1: Version Mismatch (⏱️ 1 min)

**File:** `loungenie-portal.php` line 8

```diff
- * @version 1.8.0
+ * @version 1.8.1
```

**Verify:**
```bash
grep "@version" loungenie-portal/loungenie-portal.php | grep 1.8.1
```

---

### Issue 2: Global $wpdb in API Files (⏱️ 20 min)

**Files:** 8 total
- api/companies.php
- api/units.php
- api/tickets.php
- api/dashboard.php
- api/map.php
- api/support.php
- api/attachments.php
- api/help-guides.php

**Fix:** Add `global $wpdb;` at start of each method using `$wpdb`

**Example:**
```diff
  public function get_companies( $request ) {
+     global $wpdb;
      $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}lgp_companies" );
      return rest_ensure_response( $results );
  }
```

**Command to find instances:**
```bash
cd loungenie-portal
grep -n "\$wpdb->" api/*.php | grep -v "global \$wpdb" | head -20
```

**Expected:** ~10-15 instances to fix

---

### Issue 3: Class Exists Guards (⏱️ 30 min)

**File:** `includes/class-lgp-loader.php`

**Fix:** Wrap all init() calls with class_exists() check

**Before:**
```php
public static function init() {
    LGP_Database::init();
    LGP_Router::init();
    LGP_Auth::init();
    // ... more without checks
}
```

**After:**
```php
public static function init() {
    if ( class_exists( 'LGP_Database' ) ) {
        LGP_Database::init();
    }
    if ( class_exists( 'LGP_Router' ) ) {
        LGP_Router::init();
    }
    if ( class_exists( 'LGP_Auth' ) ) {
        LGP_Auth::init();
    }
    // ... continue for all classes
}
```

---

### Issue 4: Replace in_array() Role Checks (⏱️ 15 min)

**Files:** 3 files
- api/dashboard.php
- api/map.php
- api/help-guides.php

**Find:**
```bash
grep -n "in_array" loungenie-portal/api/*.php
```

**Replace Pattern:**
```diff
- if ( ! in_array( $user->roles[0], array( 'lgp_support' ) ) ) {
+ if ( ! LGP_Auth::is_support() ) {
```

**Alternative (use capabilities):**
```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Access denied' );
}
```

---

### Issue 5: Remove Redundant function_exists() (⏱️ 2 min)

**File:** `api/tickets.php` line 304-305

```diff
- if ( function_exists( 'error_log' ) ) {
-     error_log( 'Ticket error: ' . $e->getMessage() );
- }
+ error_log( 'Ticket error: ' . $e->getMessage() );
```

**Command:**
```bash
grep -n "function_exists( 'error_log'" loungenie-portal/api/tickets.php
```

---

### Issue 6: JavaScript Safety Checks (⏱️ 10 min)

**Files:** 2 files
- includes/class-lgp-assets.php
- assets/js/portal.js

**In PHP (class-lgp-assets.php):**

```php
// Add after wp_enqueue_script
wp_localize_script(
    'lgp-portal',
    'lgpData',
    array(
        'userId'    => get_current_user_id(),
        'userRole'  => get_user_meta( get_current_user_id(), 'role', true ),
        'nonce'     => wp_create_nonce( 'lgp_nonce' ),
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
    )
);
```

**In JavaScript (assets/js/portal.js):**

```javascript
// Add at top of file
if ( typeof lgpData === 'undefined' ) {
    window.lgpData = {};
}

// Or use optional chaining
const userId = window.lgpData?.userId || null;
```

---

### Issue 7: Null Safety Checks (⏱️ 10 min)

**Files:** 2 files
- api/map.php
- api/dashboard.php

**Pattern:**
```diff
  $results = $wpdb->get_results( $query );
+ if ( ! $results ) {
+     return rest_ensure_response( array() );
+ }
  
  foreach ( $results as $row ) {
      // process
  }
```

---

## ⚡ IMPLEMENTATION TIMELINE

| Time | Task | File(s) |
|------|------|---------|
| 0:00-0:01 | Fix #1: Version | loungenie-portal.php |
| 0:01-0:25 | Fix #2: Global $wpdb | api/*.php (8 files) |
| 0:25-1:00 | Fix #3: Class guards | class-lgp-loader.php |
| 1:00-1:20 | Fix #4: in_array() → is_support() | api/dashboard.php, map.php, help-guides.php |
| 1:20-1:25 | Fix #5: Remove function_exists | api/tickets.php |
| 1:25-1:40 | Fix #6: JavaScript safety | class-lgp-assets.php, portal.js |
| 1:40-2:00 | Fix #7: Null checks | api/map.php, dashboard.php |
| 2:00-2:15 | Run tests | `composer run test` |
| 2:15-2:30 | Code review | Self-review all changes |

**Total Implementation Time: 2.5 hours**

---

## ✅ VERIFICATION COMMANDS

After each fix group, run:

```bash
cd loungenie-portal

# Check syntax
php -l includes/class-lgp-loader.php
php -l api/companies.php

# Run linter
composer run cs --fix

# Run tests
composer run test

# Check for remaining issues
grep -n "global \$wpdb" api/companies.php  # Should show yes
grep -n "in_array.*roles" api/dashboard.php  # Should show no
grep -n "function_exists.*error_log" api/tickets.php  # Should show no
```

---

## 🧪 QUICK TEST CHECKLIST

After all fixes:

- [ ] PHP syntax OK: `php -l` passes all files
- [ ] Tests pass: `composer run test` = 192/192
- [ ] No undefined variables: `grep -r "\$wpdb->" api/ | wc -l` = 0 issues
- [ ] No in_array role checks: `grep -r "in_array.*roles" api/` = empty
- [ ] API endpoints respond: `curl localhost/wp-json/lgp/v1/companies`
- [ ] Dashboard loads: Browser test - no console errors
- [ ] Partner portal works: Can view own company only

---

## 📊 IMPACT

```
Before Fixes:
├─ Undefined variable risks: 3
├─ Security issues: 1
├─ Code quality: 80/100
└─ Test pass: ~90%

After Fixes:
├─ Undefined variable risks: 0 ✅
├─ Security issues: 0 ✅
├─ Code quality: 95/100 ✅
└─ Test pass: 100% ✅
```

---

## 🚨 WHAT NOT TO DO

❌ Don't remove any database queries - only add `global $wpdb;`  
❌ Don't change table names or column names  
❌ Don't modify test files  
❌ Don't change WordPress function calls  
❌ Don't remove security checks  

---

## 📚 REFERENCE

- **Full audit:** [CODE_AUDIT_AND_FIXES.md](CODE_AUDIT_AND_FIXES.md)
- **Exact code changes:** [CRITICAL_CODE_FIXES.md](CRITICAL_CODE_FIXES.md)
- **Overview:** [AUDIT_SUMMARY_NEXT_STEPS.md](AUDIT_SUMMARY_NEXT_STEPS.md)

---

**Ready to code? Open [CRITICAL_CODE_FIXES.md](CRITICAL_CODE_FIXES.md) and start with Issue #1!** 🚀
