# Quick Reference: 7 Critical Fixes You Need to Know

**Time to Read:** 3 minutes  
**Time to Implement:** 2.5 hours  
**Files to Modify:** 12  

---

## THE 7 MUST-KNOWS

### 1️⃣ Version Number (1 minute)
**File:** loungenie-portal.php line 8  
**Change:** `@version 1.8.0` → `@version 1.8.1`

### 2️⃣ Add Global $wpdb (20 minutes)  
**Files:** api/companies.php, units.php, tickets.php, dashboard.php, map.php, support.php, attachments.php, help-guides.php  
**Add at start of each method:** `global $wpdb;`

### 3️⃣ Class Guards (30 minutes)
**File:** includes/class-lgp-loader.php  
**Add:** `if ( class_exists( 'ClassName' ) )` before each init() call

### 4️⃣ Replace in_array (20 minutes)
**Files:** api/dashboard.php, map.php, help-guides.php  
**Replace:** `in_array( $role, ['lgp_support'] )` with `LGP_Auth::is_support()`

### 5️⃣ Remove function_exists Check (2 minutes)
**File:** api/tickets.php line 304-305  
**Delete:** `if ( function_exists( 'error_log' ) )` wrapper

### 6️⃣ JavaScript Safety (10 minutes)
**Files:** includes/class-lgp-assets.php, assets/js/portal.js  
**Add:** Safety check for `lgpData` object existence

### 7️⃣ Null Checks (10 minutes)
**Files:** api/map.php, dashboard.php  
**Add:** Check if query results exist before using them

---

## VERIFICATION COMMANDS

```bash
cd loungenie-portal

# After each fix group:
php -l loungenie-portal.php              # Check syntax
composer run test                         # Run tests (expect 192/192)
composer run cs                           # Check standards
```

---

## IMPLEMENTATION TIMELINE

| Time | Task | Files |
|------|------|-------|
| 0:00-0:01 | Fix #1 | loungenie-portal.php |
| 0:01-0:25 | Fix #2 | api/*.php (8 files) |
| 0:25-1:00 | Fix #3 | class-lgp-loader.php |
| 1:00-1:20 | Fix #4 | api/dashboard.php, map.php, help-guides.php |
| 1:20-1:25 | Fix #5 | api/tickets.php |
| 1:25-1:40 | Fix #6 | class-lgp-assets.php, portal.js |
| 1:40-2:00 | Fix #7 | api/map.php, dashboard.php |
| 2:00-2:15 | Test | Run full suite |

**Total: 2.5 hours**

---

**For full details, open:** [loungenie-portal/CRITICAL_CODE_FIXES.md](loungenie-portal/CRITICAL_CODE_FIXES.md)
