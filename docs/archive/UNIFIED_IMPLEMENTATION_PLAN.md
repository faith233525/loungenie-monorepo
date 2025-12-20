# UNIFIED IMPLEMENTATION PLAN
## Architectural Audit + Unit & Color Aggregation Merger

**Date:** December 19, 2025  
**Status:** Phase 2 Execution Ready  
**Priority:** HIGH - Production Deployment

---

## Executive Summary

This document unifies two concurrent development initiatives:

1. **Architectural Audit Remediation** - Phase 2 (role-based checks, transaction safety, test coverage)
2. **Unit & Color Aggregation Initiative** - Phase 2 (refactor units → color aggregates, API updates)

**Execution Strategy:** Sequential completion to avoid conflicts
- **First:** Complete Architectural Audit Phase 2 (foundation fixes)
- **Then:** Execute Unit & Color Aggregation Phase 2 (feature refactoring)

---

## Overlapping Areas Analysis

### Shared Components (Require Coordination)

| Component | Audit Phase 2 | Unit & Color Phase 2 | Conflict Risk |
|-----------|---------------|----------------------|---------------|
| **Dashboard API** | Add role checks | Add color aggregates | MEDIUM - Same endpoint |
| **Map API** | Add role checks | Verify no unit IDs exposed | LOW - Different concerns |
| **Units API** | Add role checks | Not modified (aggregates at company level) | LOW |
| **Database** | No schema changes | Add `top_colors` JSON column | LOW - Different tables |
| **Templates** | No changes | Remove unit_ids[] from ticket form | NONE |
| **Tests** | Expand coverage | Add aggregation tests | NONE - Complementary |

### Current State Assessment

**✅ Already Complete:**
- Unit & Color Phase 1 documentation (6 comprehensive guides)
- Database schema design for aggregation
- Code examples and migration scripts

**⚠️ In Progress:**
- Architectural Audit Phase 2 (role-based checks, transactions, tests)

**📅 Pending:**
- Unit & Color Phase 2 implementation
- Unified test suite execution
- Final deployment

---

## Phase 2A: Architectural Audit Completion (PRIORITY 1)

### Goal
Fix critical architectural issues before feature refactoring

### Tasks (16-20 hours)

#### Task 1: Role-Based Access Checks in APIs (6h)
**Files to Update:**
- `loungenie-portal/api/dashboard.php`
- `loungenie-portal/api/map.php`
- `loungenie-portal/api/units.php`
- `loungenie-portal/api/companies.php`
- `loungenie-portal/api/help-guides.php`

**Pattern to Apply:**
```php
public static function get_items( $request ) {
    // Check authentication
    if ( ! LGP_Auth::is_authenticated() ) {
        return new WP_Error( 'unauthorized', 'Authentication required', ['status' => 401] );
    }
    
    // Role-based filtering
    $is_support = LGP_Auth::is_support();
    $company_id = LGP_Auth::get_user_company_id();
    
    if ( $is_support ) {
        // Support: see all
        $where = "1=1";
    } else {
        // Partner: see only own company
        $where = $wpdb->prepare( "company_id = %d", $company_id );
    }
    
    $results = $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where}" );
    return rest_ensure_response( $results );
}
```

**Acceptance Criteria:**
- [ ] All API endpoints verify authentication
- [ ] Support role can access all companies
- [ ] Partner role restricted to own company
- [ ] Unauthorized access returns 401/403
- [ ] Tests verify role-based filtering

#### Task 2: Transaction Safety for Critical Updates (4h)
**Files to Update:**
- `loungenie-portal/api/tickets.php`
- `loungenie-portal/api/help-guides.php` (user progress)
- `loungenie-portal/includes/class-lgp-support-ticket-handler.php`

**Pattern to Apply:**
```php
public static function update_ticket_status( $ticket_id, $new_status ) {
    global $wpdb;
    
    $wpdb->query( 'START TRANSACTION' );
    
    try {
        // Update ticket
        $result = $wpdb->update(
            $wpdb->prefix . 'lgp_tickets',
            ['status' => $new_status, 'updated_at' => current_time( 'mysql' )],
            ['id' => $ticket_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if ( $result === false ) {
            throw new Exception( 'Ticket update failed' );
        }
        
        // Log audit trail
        LGP_Logger::log_event( 
            get_current_user_id(), 
            'ticket_status_update', 
            $ticket_id, 
            ['old_status' => $old_status, 'new_status' => $new_status]
        );
        
        $wpdb->query( 'COMMIT' );
        return true;
        
    } catch ( Exception $e ) {
        $wpdb->query( 'ROLLBACK' );
        LGP_Logger::log_error( 'ticket_update_failed', $e->getMessage() );
        return false;
    }
}
```

**Acceptance Criteria:**
- [ ] Ticket updates are atomic
- [ ] User progress updates use transactions
- [ ] Failed transactions rollback completely
- [ ] Audit log entries created for all updates
- [ ] Concurrency conflicts handled gracefully

#### Task 3: Expand Test Coverage (6h)
**New Test Files to Create:**
- `tests/test-dashboard-api-roles.php`
- `tests/test-map-api-roles.php`
- `tests/test-ticket-concurrency.php`
- `tests/test-user-progress-transactions.php`

**Test Template:**
```php
class Test_Dashboard_API_Roles extends WP_UnitTestCase {
    
    public function test_support_sees_all_companies() {
        // Create multiple companies
        $company1 = $this->factory->post->create(['post_type' => 'lgp_company']);
        $company2 = $this->factory->post->create(['post_type' => 'lgp_company']);
        
        // Set current user as Support
        $support_user = $this->factory->user->create(['role' => 'lgp_support']);
        wp_set_current_user( $support_user );
        
        // Call API
        $request = new WP_REST_Request( 'GET', '/lgp/v1/dashboard' );
        $response = rest_do_request( $request );
        
        // Verify
        $this->assertEquals( 200, $response->get_status() );
        $data = $response->get_data();
        $this->assertGreaterThanOrEqual( 2, $data['total_companies'] );
    }
    
    public function test_partner_sees_only_own_company() {
        // Create multiple companies
        $company1 = $this->factory->post->create(['post_type' => 'lgp_company']);
        $company2 = $this->factory->post->create(['post_type' => 'lgp_company']);
        
        // Set current user as Partner for company1
        $partner_user = $this->factory->user->create(['role' => 'lgp_partner']);
        wp_set_current_user( $partner_user );
        update_user_meta( $partner_user, 'lgp_company_id', $company1 );
        
        // Call API
        $request = new WP_REST_Request( 'GET', '/lgp/v1/dashboard' );
        $response = rest_do_request( $request );
        
        // Verify
        $this->assertEquals( 200, $response->get_status() );
        $data = $response->get_data();
        $this->assertEquals( 1, $data['total_companies'] );
        $this->assertEquals( $company1, $data['company_id'] );
    }
    
    public function test_unauthorized_user_denied() {
        // No user logged in
        wp_set_current_user( 0 );
        
        // Call API
        $request = new WP_REST_Request( 'GET', '/lgp/v1/dashboard' );
        $response = rest_do_request( $request );
        
        // Verify
        $this->assertEquals( 401, $response->get_status() );
    }
}
```

**Coverage Target:** 85%+ for modified files

---

## Phase 2B: Unit & Color Aggregation Implementation (PRIORITY 2)

### Goal
Refactor unit tracking to company-level color aggregates

### Tasks (16-20 hours)

#### Task 1: Database Migration for `top_colors` Column (2h)
**File:** `loungenie-portal/includes/class-lgp-migrations.php`

**Add Migration:**
```php
/**
 * Migration v1.5.0 - Add top_colors to companies
 */
public static function migrate_v1_5_0() {
    global $wpdb;
    
    $companies_table = $wpdb->prefix . 'lgp_companies';
    
    // Check if column exists
    $column_exists = $wpdb->get_results(
        $wpdb->prepare(
            "SHOW COLUMNS FROM {$companies_table} LIKE %s",
            'top_colors'
        )
    );
    
    if ( empty( $column_exists ) ) {
        // Add JSON column
        $wpdb->query(
            "ALTER TABLE {$companies_table} 
             ADD COLUMN top_colors JSON DEFAULT NULL 
             AFTER contact_phone"
        );
        
        // Populate initial values
        self::populate_initial_colors();
        
        LGP_Logger::log_event(
            0,
            'migration_v1_5_0',
            0,
            ['action' => 'Added top_colors column']
        );
    }
}

private static function populate_initial_colors() {
    global $wpdb;
    
    $companies = $wpdb->get_results(
        "SELECT id FROM {$wpdb->prefix}lgp_companies"
    );
    
    foreach ( $companies as $company ) {
        $colors = $wpdb->get_results( $wpdb->prepare(
            "SELECT COALESCE(color_tag, 'unknown') as color, COUNT(*) as count 
             FROM {$wpdb->prefix}lgp_units 
             WHERE company_id = %d 
             GROUP BY color_tag",
            $company->id
        ), OBJECT_K );
        
        $color_counts = [];
        foreach ( $colors as $color => $data ) {
            $color_counts[ $color ] = (int) $data->count;
        }
        
        $wpdb->update(
            $wpdb->prefix . 'lgp_companies',
            ['top_colors' => json_encode( $color_counts )],
            ['id' => $company->id],
            ['%s'],
            ['%d']
        );
    }
}
```

**Register in Migrations Array:**
```php
$migrations = array(
    '1.0.0' => array( __CLASS__, 'migrate_v1_0_0' ),
    '1.1.0' => array( __CLASS__, 'migrate_v1_1_0' ),
    '1.2.0' => array( __CLASS__, 'migrate_v1_2_0' ),
    '1.5.0' => array( __CLASS__, 'migrate_v1_5_0' ), // NEW
);
```

#### Task 2: Create Company Colors Utility Class (3h)
**File:** `loungenie-portal/includes/class-lgp-company-colors.php` (NEW)

```php
<?php
/**
 * Company Colors Aggregation Utility
 * Handles calculation and caching of company-level color distribution
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LGP_Company_Colors {
    
    const CACHE_TTL = 3600; // 1 hour
    
    /**
     * Initialize hooks
     */
    public static function init() {
        // Invalidate cache on unit changes
        add_action( 'lgp_unit_created', [__CLASS__, 'invalidate_cache'], 10, 2 );
        add_action( 'lgp_unit_updated', [__CLASS__, 'invalidate_cache'], 10, 2 );
        add_action( 'lgp_unit_deleted', [__CLASS__, 'invalidate_cache'], 10, 2 );
    }
    
    /**
     * Get color distribution for a company
     *
     * @param int $company_id Company ID
     * @return array Color counts ['yellow' => 10, 'orange' => 5, ...]
     */
    public static function get_company_colors( $company_id ) {
        // Try cache first
        $cache_key = "company_colors_{$company_id}";
        $colors = wp_cache_get( $cache_key, 'loungenie_portal' );
        
        if ( $colors !== false ) {
            return $colors;
        }
        
        // Calculate from database
        $colors = self::calculate_colors( $company_id );
        
        // Cache result
        wp_cache_set( $cache_key, $colors, 'loungenie_portal', self::CACHE_TTL );
        
        return $colors;
    }
    
    /**
     * Get total unit count for company
     *
     * @param int $company_id Company ID
     * @return int Total units
     */
    public static function get_company_unit_count( $company_id ) {
        global $wpdb;
        
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}lgp_units WHERE company_id = %d",
            $company_id
        ) );
    }
    
    /**
     * Calculate color distribution
     *
     * @param int $company_id Company ID
     * @return array Color counts
     */
    private static function calculate_colors( $company_id ) {
        global $wpdb;
        
        $colors = $wpdb->get_results( $wpdb->prepare(
            "SELECT COALESCE(color_tag, 'unknown') as color, COUNT(*) as count 
             FROM {$wpdb->prefix}lgp_units 
             WHERE company_id = %d 
             GROUP BY color_tag 
             ORDER BY count DESC",
            $company_id
        ), OBJECT_K );
        
        $color_counts = [];
        foreach ( $colors as $color => $data ) {
            $color_counts[ $color ] = (int) $data->count;
        }
        
        return $color_counts;
    }
    
    /**
     * Invalidate cache when units change
     *
     * @param int $unit_id Unit ID
     * @param int $company_id Company ID
     */
    public static function invalidate_cache( $unit_id, $company_id ) {
        wp_cache_delete( "company_colors_{$company_id}", 'loungenie_portal' );
        
        // Optionally: Update company.top_colors field
        self::refresh_company_colors( $company_id );
    }
    
    /**
     * Refresh company colors in database
     *
     * @param int $company_id Company ID
     */
    public static function refresh_company_colors( $company_id ) {
        global $wpdb;
        
        $colors = self::calculate_colors( $company_id );
        
        $wpdb->update(
            $wpdb->prefix . 'lgp_companies',
            ['top_colors' => json_encode( $colors )],
            ['id' => $company_id],
            ['%s'],
            ['%d']
        );
    }
}
```

**Register in Loader:**
```php
// In class-lgp-loader.php init() method
LGP_Company_Colors::init();
```

#### Task 3: Update Dashboard API with Color Aggregates (2h)
**File:** `loungenie-portal/api/dashboard.php`

**Add to Response:**
```php
public static function get_dashboard_data( $request ) {
    global $wpdb;
    
    // Existing auth check (from Phase 2A)
    if ( ! LGP_Auth::is_authenticated() ) {
        return new WP_Error( 'unauthorized', 'Authentication required', ['status' => 401] );
    }
    
    $is_support = LGP_Auth::is_support();
    $company_id = LGP_Auth::get_user_company_id();
    
    $where_companies = $is_support ? '1=1' : $wpdb->prepare( 'id = %d', $company_id );
    
    // Get companies with color aggregates
    $companies = $wpdb->get_results(
        "SELECT id, name, top_colors FROM {$wpdb->prefix}lgp_companies WHERE {$where_companies}"
    );
    
    $response_companies = [];
    foreach ( $companies as $company ) {
        $response_companies[] = [
            'id'           => $company->id,
            'name'         => $company->name,
            'unit_count'   => LGP_Company_Colors::get_company_unit_count( $company->id ),
            'top_colors'   => json_decode( $company->top_colors ?? '{}', true )
        ];
    }
    
    return rest_ensure_response([
        'companies'       => $response_companies,
        'total_companies' => count( $response_companies ),
        'total_units'     => array_sum( array_column( $response_companies, 'unit_count' ) )
    ]);
}
```

#### Task 4: Refactor Support Ticket Form (3h)
**File:** `loungenie-portal/templates/components/support-ticket-form.php`

**Remove Unit Selection (Lines 246-266):**
```php
// DELETE THIS ENTIRE SECTION:
<?php if ( ! empty( $units ) ) : ?>
    <!-- Specific Units Selection -->
    <div class="lgp-form-group">
        <label for="lgp-units-list" class="lgp-label">
            <?php esc_html_e( 'Affected Units (optional)', 'loungenie-portal' ); ?>
        </label>
        <select 
            id="lgp-units-list"
            name="unit_ids[]"
            multiple
            class="lgp-input"
            aria-describedby="lgp-units-list-hint">
            <?php foreach ( $units as $unit ) : ?>
                <option value="<?php echo esc_attr( $unit->id ); ?>">
                    <?php echo esc_html( $unit->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small id="lgp-units-list-hint" class="lgp-form-hint">
            <?php esc_html_e( 'Hold Ctrl/Cmd to select multiple units', 'loungenie-portal' ); ?>
        </small>
    </div>
<?php endif; ?>
```

**Keep Only Range Selection** (already exists, just verify):
```php
<!-- Number of Units Affected -->
<div class="lgp-form-group">
    <label for="lgp-units-affected" class="lgp-label">
        <?php esc_html_e( 'Number of Units Affected', 'loungenie-portal' ); ?>
        <span class="lgp-required">*</span>
    </label>
    <div class="lgp-radio-group">
        <label class="lgp-radio-label">
            <input type="radio" name="units_affected" value="1" class="lgp-radio-input" required />
            <span><?php esc_html_e( '1 Unit', 'loungenie-portal' ); ?></span>
        </label>
        <label class="lgp-radio-label">
            <input type="radio" name="units_affected" value="2-5" class="lgp-radio-input" />
            <span><?php esc_html_e( '2-5 Units', 'loungenie-portal' ); ?></span>
        </label>
        <label class="lgp-radio-label">
            <input type="radio" name="units_affected" value="6-10" class="lgp-radio-input" />
            <span><?php esc_html_e( '6-10 Units', 'loungenie-portal' ); ?></span>
        </label>
        <label class="lgp-radio-label">
            <input type="radio" name="units_affected" value="10+" class="lgp-radio-input" />
            <span><?php esc_html_e( '10+ Units', 'loungenie-portal' ); ?></span>
        </label>
    </div>
    <div id="lgp-units-affected-error" class="lgp-form-error" role="alert"></div>
</div>
```

#### Task 5: Update Ticket Handler (2h)
**File:** `loungenie-portal/includes/class-lgp-support-ticket-handler.php`

**Remove unit_ids Processing (Lines 140-143):**
```php
// DELETE:
if ( isset( $_POST['unit_ids'] ) && is_array( $_POST['unit_ids'] ) ) {
    $unit_ids = array_map( 'absint', $_POST['unit_ids'] );
}
```

**Keep Only:**
```php
$units_affected = sanitize_text_field( $_POST['units_affected'] ?? '' );
```

**Remove from Metadata Storage:**
```php
// DELETE from create_ticket() method:
'_affected_unit_ids' => json_encode( $unit_ids )

// KEEP:
'_units_affected' => $units_affected
```

#### Task 6: Update Company Profile Template (4h)
**File:** `loungenie-portal/templates/company-profile.php`

**Replace Units Table (Lines 216-240) with Color Distribution:**
```php
// Get color aggregates
$colors = LGP_Company_Colors::get_company_colors( $company->id );
$unit_count = LGP_Company_Colors::get_company_unit_count( $company->id );
?>

<div class="lgp-card">
    <h3><?php esc_html_e( 'Unit Overview', 'loungenie-portal' ); ?></h3>
    
    <div class="lgp-metrics">
        <div class="lgp-metric">
            <span class="lgp-metric-label"><?php esc_html_e( 'Total Units', 'loungenie-portal' ); ?></span>
            <span class="lgp-metric-value"><?php echo esc_html( $unit_count ); ?></span>
        </div>
    </div>
    
    <?php if ( ! empty( $colors ) ) : ?>
        <h4><?php esc_html_e( 'Color Distribution', 'loungenie-portal' ); ?></h4>
        <div class="lgp-color-distribution">
            <ul class="lgp-color-list">
                <?php foreach ( $colors as $color => $count ) : ?>
                    <li>
                        <span class="lgp-color-icon" style="background-color: <?php echo esc_attr( lgp_get_color_hex( $color ) ); ?>;">
                            <svg width="16" height="16" viewBox="0 0 16 16">
                                <rect x="2" y="2" width="12" height="12" fill="currentColor"/>
                            </svg>
                        </span>
                        <span class="lgp-color-label">
                            <?php echo esc_html( ucfirst( $color ) ); ?>: 
                            <?php echo esc_html( $count ); ?> 
                            <?php esc_html_e( 'units', 'loungenie-portal' ); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
```

---

## Phase 2C: Unified Testing (PRIORITY 3)

### Comprehensive Test Suite

#### Test Categories

1. **Role-Based Access Tests** (Phase 2A)
   - Dashboard API: Support vs. Partner
   - Map API: Support vs. Partner
   - Units API: Support vs. Partner
   - Help Guides API: Support vs. Partner
   - Unauthorized access scenarios

2. **Transaction Safety Tests** (Phase 2A)
   - Ticket status update atomicity
   - User progress update rollback
   - Concurrent ticket updates
   - Failed transaction cleanup

3. **Color Aggregation Tests** (Phase 2B)
   - Calculate colors for company
   - Cache invalidation on unit changes
   - JSON storage/retrieval
   - Dashboard API color response
   - Company profile color display

4. **Integration Tests**
   - Full ticket submission (no unit_ids)
   - Dashboard load with colors
   - Company profile render
   - Migration execution

#### Test Execution Command
```bash
cd /workspaces/Pool-Safe-Portal/loungenie-portal
vendor/bin/phpunit --coverage-text --coverage-html coverage/
```

#### Coverage Target
- **Overall:** 85%+
- **Modified Files:** 90%+
- **Critical Paths:** 100%

---

## Conflict Resolution Strategy

### Database Changes
- **Phase 2A:** No schema changes
- **Phase 2B:** Add `top_colors` column (v1.5.0 migration)
- **Resolution:** Sequential execution, no conflicts

### API Endpoint Changes
- **Dashboard API:**
  - Phase 2A: Add role checks
  - Phase 2B: Add color aggregates
  - **Resolution:** Merge both changes in single update

- **Map API:**
  - Phase 2A: Add role checks
  - Phase 2B: Verify structure (no changes needed)
  - **Resolution:** Only Phase 2A changes needed

### Template Changes
- **Support Ticket Form:**
  - Phase 2A: No changes
  - Phase 2B: Remove unit_ids[] selection
  - **Resolution:** No conflict

- **Company Profile:**
  - Phase 2A: No changes
  - Phase 2B: Replace units table with colors
  - **Resolution:** No conflict

---

## Deployment Checklist

### Pre-Deployment
- [ ] All Phase 2A tasks complete
- [ ] All Phase 2B tasks complete
- [ ] Test suite passes (85%+ coverage)
- [ ] No duplicate classes/functions/endpoints
- [ ] Migrations tested in staging
- [ ] Rollback plan documented

### Staging Validation
- [ ] Deploy to staging environment
- [ ] Run migration v1.5.0
- [ ] Verify role-based access (Support/Partner)
- [ ] Test ticket submission (no unit_ids)
- [ ] Verify dashboard shows colors
- [ ] Check company profile displays
- [ ] Performance benchmarks meet targets (< 100ms queries)

### Production Deployment
- [ ] Database backup completed
- [ ] Maintenance mode enabled
- [ ] Deploy code to production
- [ ] Run migrations
- [ ] Smoke tests passed
- [ ] Maintenance mode disabled
- [ ] Monitor error logs (24h)

### Post-Deployment
- [ ] Verify no errors in logs
- [ ] Check performance metrics
- [ ] Validate user access patterns
- [ ] Generate deployment report

---

## Timeline

| Phase | Tasks | Duration | Owner | Status |
|-------|-------|----------|-------|--------|
| **2A: Audit** | Role checks, transactions, tests | 16-20h | Dev Team | Ready |
| **2B: Aggregation** | Migration, refactoring, colors | 16-20h | Dev Team | Pending |
| **2C: Testing** | Comprehensive test suite | 8-12h | QA | Pending |
| **Deployment** | Staging → Production | 4-8h | DevOps | Pending |
| **Total** | | **44-60h** | | **~2 weeks** |

---

## Success Metrics

### Phase 2A Complete When:
- [ ] All APIs have role-based checks
- [ ] Critical updates use transactions
- [ ] Test coverage ≥ 85%
- [ ] No security vulnerabilities
- [ ] All tests passing

### Phase 2B Complete When:
- [ ] `top_colors` column added and populated
- [ ] Support ticket form has no unit_ids[]
- [ ] Dashboard API returns color aggregates
- [ ] Company profile displays colors
- [ ] No individual unit ID tracking
- [ ] All tests passing

### Deployment Complete When:
- [ ] Production deployment successful
- [ ] No errors in logs (24h monitoring)
- [ ] Performance metrics normal
- [ ] User access functioning correctly
- [ ] Final report generated

---

## Change Log Template

### Phase 2A Changes
- **API Files Modified:** (list)
- **Classes Updated:** (list)
- **Tests Added:** (list)
- **Database Changes:** None
- **Migrations:** None

### Phase 2B Changes
- **API Files Modified:** (list)
- **Classes Created:** class-lgp-company-colors.php
- **Templates Updated:** (list)
- **Tests Added:** (list)
- **Database Changes:** Add top_colors JSON column
- **Migrations:** v1.5.0

---

## Risk Mitigation

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Breaking API changes | Low | High | Comprehensive testing, backward compat |
| Migration failure | Low | High | Staging validation, rollback plan |
| Performance degradation | Medium | Medium | Caching strategy, index optimization |
| Data loss | Very Low | Critical | Database backups, transaction safety |
| Concurrent updates | Medium | Medium | Transaction locks, optimistic locking |

---

## Next Steps

1. **Approve This Plan** - Stakeholder review and sign-off
2. **Start Phase 2A** - Assign tasks, create branches
3. **Complete Phase 2A** - Execute, test, merge
4. **Start Phase 2B** - Sequential execution after 2A
5. **Complete Phase 2B** - Execute, test, merge
6. **Unified Testing** - Comprehensive test suite
7. **Deploy to Staging** - Validate complete system
8. **Deploy to Production** - Final deployment

---

**Document Owner:** Technical Architecture Team  
**Last Updated:** December 19, 2025  
**Status:** Ready for Execution
