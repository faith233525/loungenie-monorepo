# Phase 2 & 3 Implementation Plan
## Refactoring Targets & New Features

**Document Version:** 1.0  
**Date:** December 19, 2025  
**Status:** Planning (Ready for Sprint 2)

---

## Phase Overview

### Phase 1: Documentation ✅ COMPLETE
- ✅ Architectural Guidance: [UNIT_COLOR_AGGREGATION_GUIDE.md](./UNIT_COLOR_AGGREGATION_GUIDE.md)
- ✅ AI Development Prompt: [AI_DEVELOPMENT_PROMPT_UNIT_AGGREGATION.md](./AI_DEVELOPMENT_PROMPT_UNIT_AGGREGATION.md)
- ✅ Database Schema: [DATABASE_SCHEMA_AGGREGATION_GUIDE.md](./DATABASE_SCHEMA_AGGREGATION_GUIDE.md)

### Phase 2: Refactoring (THIS DOCUMENT)
**Timeline:** Sprint 2 (2-3 weeks)  
**Effort:** 16-20 developer-hours  
**Priority:** HIGH (Foundation for Phase 3)

### Phase 3: New Features
**Timeline:** Sprint 3 (3-4 weeks)  
**Effort:** 24-32 developer-hours  
**Priority:** MEDIUM (Enhancements after refactoring complete)

---

## Phase 2: Refactoring Targets

### Refactor 1: Support Ticket Form
**Current Location:** `/loungenie-portal/templates/components/support-ticket-form.php`  
**Status:** NEEDS REFACTORING ⚠️

#### Current Behavior (WRONG)
```html
<!-- Current form has these fields -->
<label>Number of Units Affected</label>
<select name="units_affected"> <!-- Radio buttons -->

<label>Affected Units (optional)</label>
<select name="unit_ids[]" multiple>  <!-- ❌ WRONG: Individual unit selection -->
    <option value="1">Unit 1</option>
    <option value="2">Unit 2</option>
    ...
</select>
```

#### Required Changes

**Change 1.1: Remove Individual Unit Selection**
```html
<!-- REMOVE this section entirely -->
<div class="lgp-form-group">
    <label for="lgp-units-list">Affected Units (optional)</label>
    <select name="unit_ids[]" multiple>
        <!-- REMOVE entire multi-select -->
    </select>
</div>
```

**Change 1.2: Update Units Affected Validation**
```php
// File: loungenie-portal/includes/class-lgp-support-ticket-handler.php
// Method: validate_submission()

// OLD (remove this):
if ( isset( $_POST['unit_ids'] ) && is_array( $_POST['unit_ids'] ) ) {
    $unit_ids = array_map( 'absint', $_POST['unit_ids'] );
}

// NEW (keep this only):
$units_affected = sanitize_text_field( $_POST['units_affected'] ?? '' );
if ( ! in_array( $units_affected, [ '1', '2-5', '6-10', '10+' ], true ) ) {
    $this->errors['units_affected'] = __( 'Please select a valid range', 'loungenie-portal' );
}
```

**Change 1.3: Update Form Handler**
```php
// File: loungenie-portal/includes/class-lgp-support-ticket-handler.php
// Method: handle_submission()

// OLD: Process unit_ids array
$unit_ids = [];
if ( isset( $_POST['unit_ids'] ) && is_array( $_POST['unit_ids'] ) ) {
    $unit_ids = array_map( 'absint', $_POST['unit_ids'] );
}

// NEW: Only process range
$units_affected = sanitize_text_field( $_POST['units_affected'] );

// Store in database
$ticket_data['units_affected'] = $units_affected;
// REMOVE: $ticket_data['unit_ids'] = $unit_ids;
```

**Change 1.4: Update Database Storage**
```php
// In class-lgp-support-ticket-handler.php create_ticket()

// OLD:
$meta = [
    '_units_affected' => $units_affected,
    '_affected_unit_ids' => json_encode( $unit_ids )  // ❌ REMOVE
];

// NEW:
$meta = [
    '_units_affected' => $units_affected
];
```

#### Testing Requirements
- [ ] Form loads without unit selection dropdown
- [ ] Form validates range selection required
- [ ] Form submits successfully with range only
- [ ] Database stores only range (no unit IDs)
- [ ] API doesn't expose unit_ids in response
- [ ] Mobile view still responsive

#### File Changes Summary
```
loungenie-portal/templates/components/support-ticket-form.php
  - REMOVE: Lines 246-266 (Affected Units multi-select)
  - UPDATE: Lines 173-175 (Legend text if needed)

loungenie-portal/includes/class-lgp-support-ticket-handler.php
  - REMOVE: Lines 140-143 (unit_ids processing)
  - REMOVE: Lines references to unit_ids in validation
  - REMOVE: Meta key '_affected_unit_ids' from storage
  - UPDATE: Validation message (remove unit selection validation)
```

---

### Refactor 2: Company Profile Template
**Current Location:** `/loungenie-portal/templates/company-profile.php`  
**Status:** NEEDS REFACTORING ⚠️

#### Current Behavior (PARTIALLY WRONG)
```html
<!-- Shows individual unit listing -->
<table>
    <tr>
        <td>#1</td>
        <td>123 Main St</td>
        <td>Smart Lock Pro</td>
        <td>Active</td>
        <td>Yellow</td>
    </tr>
    <!-- Lists all units individually -->
</table>
```

#### Required Changes

**Change 2.1: Replace Units Table with Color Distribution**
```php
// OLD: Fetch and display individual units
$units = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM $units_table WHERE company_id = %d ORDER BY id DESC",
    $company_id
) );

// NEW: Get aggregated colors
$colors = LGP_Company_Colors::get_company_colors( $company_id );
$unit_count = LGP_Company_Colors::get_company_unit_count( $company_id );
```

**Change 2.2: Update HTML Markup**
```html
<!-- OLD: Unit listing table -->
<h3>Units</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Address</th>
            <th>Lock Type</th>
            <th>Status</th>
            <th>Color</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $units as $unit ) : ?>
            <tr>
                <td>#<?php echo esc_html( $unit->id ); ?></td>
                <!-- ... -->
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- NEW: Color distribution -->
<h3>Unit Overview</h3>
<div class="lgp-metrics">
    <div class="lgp-metric">
        <span class="lgp-metric-label">Total Units</span>
        <span class="lgp-metric-value"><?php echo esc_html( $unit_count ); ?></span>
    </div>
</div>

<h3>Color Distribution</h3>
<div class="lgp-color-distribution">
    <ul>
        <?php foreach ( $colors as $color => $count ) : ?>
            <li>
                <span class="lgp-icon" style="background: <?php echo esc_attr( lgp_get_color_hex( $color ) ); ?>;"></span>
                <span><?php echo esc_html( $color ); ?>: <?php echo esc_html( $count ); ?> units</span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
```

#### File Changes Summary
```
loungenie-portal/templates/company-profile.php
  - REMOVE: Lines 50-58 (Unit table fetch & display)
  - UPDATE: Lines 216-240 (Replace units table with color display)
  - ADD: Color display section after metrics
```

#### Testing Requirements
- [ ] Color distribution displays correctly
- [ ] Total unit count matches database
- [ ] All colors display with proper icons
- [ ] No individual unit IDs exposed
- [ ] Responsive on mobile

---

### Refactor 3: Dashboard API Endpoints
**Current Location:** `/loungenie-portal/api/dashboard.php`  
**Status:** PARTIALLY CORRECT (verify and enhance)

#### Current Code Analysis
```php
// Current code fetches metrics including unit counts
$total_units = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$units_table} WHERE {$where_units}" );

// This is correct, but verify it returns aggregates only
```

#### Required Verification

**Check 3.1: Verify No Individual Unit IDs Exposed**
```php
// Current dashboard.php - VERIFY this is NOT returning per-unit data:
$response = [
    'total_companies' => $total_companies,
    'total_units'     => $total_units,
    'open_tickets'    => $open_tickets
];

// ✅ CORRECT: Returns aggregates only
// ❌ WOULD BE WRONG: 'units' => [{ id: 1, color: 'yellow' }, ...]
```

**Check 3.2: Add Color Aggregates to Dashboard Response**
```php
// ENHANCEMENT: Add color distribution to dashboard
$response = [
    'total_companies' => $total_companies,
    'total_units'     => $total_units,
    'open_tickets'    => $open_tickets,
    'top_colors'      => LGP_Company_Colors::get_all_company_colors()  // NEW
];
```

**Check 3.3: Add Company-Level Colors Endpoint**
```php
// NEW ENDPOINT: GET /wp-json/lgp/v1/companies/{id}/colors
register_rest_route( 'lgp/v1', '/companies/(?P<id>\d+)/colors', [
    'methods'             => 'GET',
    'callback'            => 'lgp_api_company_colors',
    'permission_callback' => 'lgp_api_check_auth'
] );

function lgp_api_company_colors( $request ) {
    $company_id = (int) $request['id'];
    
    // Check role
    if ( ! LGP_Auth::is_support() && LGP_Auth::get_user_company_id() !== $company_id ) {
        return new WP_Error( 'forbidden', 'Access denied', [ 'status' => 403 ] );
    }
    
    return rest_ensure_response( [
        'company_id' => $company_id,
        'unit_count' => LGP_Company_Colors::get_company_unit_count( $company_id ),
        'top_colors' => LGP_Company_Colors::get_company_colors( $company_id )
    ] );
}
```

#### File Changes Summary
```
loungenie-portal/api/dashboard.php
  - VERIFY: Response structure has no per-unit arrays
  - ADD: Color aggregates to main dashboard response
  - ADD: New endpoint for company-level colors
```

---

### Refactor 4: Map API Endpoint
**Current Location:** `/loungenie-portal/api/map.php`  
**Status:** REVIEW REQUIRED

#### Current Code (Lines 29-48)
```php
public function get_units( $request ) {
    // REVIEW: Does this return individual unit IDs?
    $sql = "SELECT u.id, u.company_id, u.unit_number, u.status, u.season, u.latitude, u.longitude...";
    
    // This might be OK for map display (geolocation needs unit coords)
    // But verify the response filtering
}
```

#### Decision: KEEP WITH RESTRICTIONS
- ✅ ALLOWED: Return geolocation data (latitude/longitude) for map pins
- ❌ NOT ALLOWED: Return detailed unit information in list
- ✅ PREFERRED: Group units by company/color on frontend

#### Implementation Notes
```php
// Current approach: OK
// Returns units with geolocation for map display
// But ensure response structure is:
[
    'id': 1,
    'company_id': 5,
    'latitude': 40.7128,
    'longitude': -74.0060,
    'color_tag': 'yellow',
    'status': 'active'
    // NOT: 'unit_ids': [1, 2, 3], or other per-unit arrays
]
```

**Action Item:** Code review of map.php endpoint (no refactoring needed if structure is correct)

---

### Refactor 5: Units View Template
**Current Location:** `/loungenie-portal/templates/units-view.php`  
**Status:** REVIEW REQUIRED (might be OK)

#### Current Code Review Needed
```php
// Line 34-48: Fetches units with company info
$units = $wpdb->get_results(
    "SELECT u.*, c.name as company_name FROM {$units_table} u ..."
);

// Question: Is this for viewing individual units, or for filtering?
// If individual unit details are needed, this is OK
// If it's just for company-level filtering, might need refactoring
```

**Decision Matrix:**

| Scenario | Action |
|----------|--------|
| Template displays individual unit details | Keep as-is (OK) |
| Template lists units for bulk operations | Refactor to show companies instead |
| Template has per-unit selection | Refactor to remove selection |

#### Review Checklist
- [ ] View purpose is clear (detail vs. list)
- [ ] Selection methods reviewed
- [ ] Form fields checked for unit_ids[]
- [ ] Filters don't expose individual IDs

---

## Phase 2: Refactoring Summary

### Files to Modify (Priority Order)

| File | Type | Effort | Priority | Status |
|------|------|--------|----------|--------|
| support-ticket-form.php | Template | 3h | HIGH | ❌ Needs Refactor |
| class-lgp-support-ticket-handler.php | PHP Class | 4h | HIGH | ❌ Needs Refactor |
| company-profile.php | Template | 3h | HIGH | ❌ Needs Refactor |
| dashboard.php | API | 2h | MEDIUM | ⚠️ Verify & Enhance |
| units-view.php | Template | 2h | MEDIUM | ⚠️ Code Review |
| map.php | API | 1h | MEDIUM | ⚠️ Code Review |

**Total Phase 2 Effort:** 16-20 hours

### Testing for Phase 2

#### Unit Tests
- [ ] Support ticket form validation (no unit_ids)
- [ ] Company profile color aggregation
- [ ] Dashboard color API response
- [ ] Role-based access (Support vs. Partner)

#### Integration Tests
- [ ] Form submission end-to-end
- [ ] Company profile data display
- [ ] API endpoint role validation

#### Manual/QA Tests
- [ ] Support dashboard renders correctly
- [ ] Partner portal shows only own data
- [ ] Mobile responsiveness maintained
- [ ] No console errors

---

## Phase 3: New Features (Preview)

### Feature 1: Color Distribution Dashboard Widget
**Location:** New file  
**Purpose:** Visual display of company color distribution  
**Effort:** 6-8h

**Deliverables:**
- [ ] PHP widget class
- [ ] HTML template
- [ ] CSS styling (responsive)
- [ ] JavaScript (if interactive)
- [ ] Unit tests

**Acceptance Criteria:**
- [ ] Displays color bars with percentages
- [ ] Shows top colors first
- [ ] Icons only (no emojis)
- [ ] Responsive on mobile
- [ ] Accessible (WCAG 2.1 AA)

### Feature 2: Company Metrics Card
**Location:** New component  
**Purpose:** Quick overview of company (units, colors, tickets)  
**Effort:** 4-6h

**Deliverables:**
- [ ] PHP component class
- [ ] HTML template
- [ ] CSS styling
- [ ] Role-based variants (Support/Partner)

### Feature 3: Role-Based Company List
**Location:** Update dashboard  
**Purpose:** List all companies (Support) or own company (Partner)  
**Effort:** 4-6h

**Deliverables:**
- [ ] Updated dashboard template
- [ ] Company card components
- [ ] Filter/search functionality
- [ ] Color preview per company

### Feature 4: Color-Based Filtering
**Location:** Existing dashboards  
**Purpose:** Filter companies/units by top color  
**Effort:** 6-8h

**Deliverables:**
- [ ] Filter UI component
- [ ] JavaScript filtering logic
- [ ] API integration
- [ ] Mobile-friendly filters

### Feature 5: Historical Color Tracking (Optional)
**Location:** New table + reports  
**Purpose:** Track color distribution changes over time  
**Effort:** 8-12h

**Deliverables:**
- [ ] Database: wp_lgp_company_color_history table
- [ ] Daily snapshot script
- [ ] Trend reports
- [ ] API endpoint

**Total Phase 3 Effort:** 24-32 hours

---

## Implementation Schedule

### Week 1-2: Phase 2 Refactoring
```
Mon-Wed: Refactor support ticket form
  - Update form template
  - Update handler class
  - Write unit tests

Thu-Fri: Refactor company profile
  - Update template
  - Add color display
  - Write tests

Mon: Refactor dashboard APIs
  - Verify response structure
  - Add color endpoint
  - Write tests

Tue-Wed: Review remaining templates
  - Units view check
  - Map API review
  - Integration testing

Thu-Fri: QA & Bug fixes
  - All tests passing
  - Manual testing complete
  - Documentation updated
```

### Week 3-4: Phase 3 New Features
```
Mon-Tue: Color distribution widget
  - PHP class
  - HTML/CSS
  - Tests

Wed: Company metrics card
  - Component development
  - Integration

Thu-Fri: Role-based company list
  - Dashboard updates
  - Testing

Week 4 Mon-Tue: Color filtering
  - Filter UI
  - JavaScript logic
  - API integration

Wed-Thu: Optional features & Polish
  - Historical tracking (if time)
  - Performance optimization
  - Accessibility review

Fri: Deployment prep
  - Migration testing
  - Performance validation
  - Documentation
```

---

## Risk Assessment

### Phase 2 Risks

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|-----------|
| Breaking existing ticket submissions | Medium | High | Comprehensive testing, staging environment |
| Data migration issues | Low | Medium | Backup strategy, rollback plan |
| Performance degradation | Low | Medium | Index verification, query optimization |
| Incomplete removal of unit_ids refs | Medium | Medium | Code search automation, PR review |

### Phase 3 Risks

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|-----------|
| Over-scoping features | High | High | Clear acceptance criteria, scope review |
| UI/UX issues | Medium | Medium | Designer review, user testing |
| Performance with large datasets | Low | Medium | Caching strategy, query optimization |

---

## Success Criteria

### Phase 2 Complete When:
- ✅ All refactoring tasks completed
- ✅ All unit tests passing
- ✅ All integration tests passing
- ✅ Manual QA checklist complete
- ✅ No per-unit ID arrays in code
- ✅ Documentation updated

### Phase 3 Complete When:
- ✅ All new features implemented
- ✅ All features tested
- ✅ UI/UX approved
- ✅ Accessibility verified
- ✅ Performance validated
- ✅ Deployment complete

---

## Next Steps

1. **Review This Plan**
   - [ ] Stakeholder review
   - [ ] Scope approval
   - [ ] Timeline confirmation

2. **Prepare for Phase 2**
   - [ ] Create feature branches
   - [ ] Set up staging environment
   - [ ] Brief development team

3. **Start Phase 2**
   - [ ] Begin refactoring with priority HIGH items
   - [ ] Daily standups
   - [ ] Continuous testing

---

**Document Owner:** Architecture Team  
**Last Updated:** December 19, 2025  
**Next Review:** Start of Phase 2
