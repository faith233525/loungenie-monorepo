# AI Development Prompt - Unit & Color Aggregation Pattern
## System Guidance for All Future Development

**Document Version:** 1.0  
**Effective Date:** December 19, 2025  
**Applies To:** All future LounGenie Portal development  
**Category:** Architectural Constraints & Design Patterns

---

## Primary Directive

> **ARCHITECTURAL RULE: Units are aggregated at the company level. Do NOT track individual unit IDs in features, APIs, or UI components.**

All new code, features, and modifications must follow this aggregation pattern. This guidance overrides previous implementations that track individual unit IDs.

---

## Core Requirements for All Development

### 1. Data Model Constraint

```
ALLOWED:
  - Company.unit_count (total units in company)
  - Company.top_colors { color_name: count, ... }
  - Ticket.units_affected_range ("1", "2-5", "6-10", "10+")
  - Unit.color_tag (per-unit color, used for aggregation only)

PROHIBITED:
  - unit_ids[] arrays
  - Per-unit tracking in tickets or requests
  - Individual unit ID lists in API responses
  - Storing specific unit references in features
```

### 2. API Response Pattern

**All endpoints must return aggregated data:**

```json
{
  "company_id": 5,
  "company_name": "ABC Hotels Group",
  "unit_count": 15,
  "top_colors": {
    "yellow": 10,
    "orange": 5
  }
}
```

**NOT:**
```json
{
  "company_id": 5,
  "units": [
    { "id": 1, "color": "yellow" },
    { "id": 2, "color": "yellow" },
    { "id": 3, "color": "orange" }
  ]
}
```

### 3. Role-Based Access

```
SUPPORT ROLE:
  - Can view ALL companies
  - Can view aggregates for all companies
  - No per-unit access (aggregates only)

PARTNER ROLE:
  - Can view ONLY own company
  - Can view aggregates for own company
  - No access to other companies' data
```

### 4. Visual Design Constraints

```
✅ ALLOWED:
  - Icons (SVG, semantic symbols)
  - Color distribution bars
  - Count badges (e.g., "Yellow: 10")
  - Company cards with aggregate metrics

❌ PROHIBITED:
  - Emojis (no 🟨, 🏷️, etc.)
  - Individual unit listings
  - Per-unit detail views
  - Unit-level filter selections
```

---

## When Building Features

### Feature: Dashboard Widget

```
REQUIREMENT: Display company color distribution

CORRECT IMPLEMENTATION:
1. Fetch: GET /wp-json/lgp/v1/companies/{id}/colors
2. Response: { unit_count: 15, top_colors: { yellow: 10, orange: 5 } }
3. Render: Color bar chart with aggregated counts
4. NO individual unit details

INCORRECT IMPLEMENTATION:
1. Fetch: GET /wp-json/lgp/v1/companies/{id}/units
2. Response: Array of unit objects with IDs
3. Render: List of individual units
4. VIOLATES guidance
```

### Feature: Support Ticket Form

```
REQUIREMENT: User selects which units are affected

CORRECT IMPLEMENTATION:
  - Radio buttons: "1 Unit", "2-5 Units", "6-10 Units", "10+ Units"
  - Store: units_affected = "6-10"
  - NO unit_ids[] selection

INCORRECT IMPLEMENTATION:
  - Multi-select dropdown of all units
  - Store: unit_ids = [1, 2, 3, 4, 5, 6]
  - VIOLATES guidance
```

### Feature: Company Profile

```
REQUIREMENT: Show company information and units

CORRECT IMPLEMENTATION:
  - Company name, contact, address
  - Metrics: Total units (15)
  - Color breakdown: "Yellow: 10, Orange: 5"
  - NO individual unit listing

INCORRECT IMPLEMENTATION:
  - List of all 15 units with IDs
  - Per-unit edit buttons
  - VIOLATES guidance
```

### Feature: REST API Endpoint

```
CORRECT PATTERN:

  GET /wp-json/lgp/v1/companies/5/colors
  
  Response:
  {
    "company_id": 5,
    "unit_count": 15,
    "top_colors": {
      "yellow": 10,
      "orange": 5,
      "ice-blue": 0
    },
    "last_updated": "2025-12-19T10:30:00Z"
  }

INCORRECT PATTERN:

  GET /wp-json/lgp/v1/companies/5/units
  
  Response:
  [
    { "id": 1, "color": "yellow" },
    { "id": 2, "color": "yellow" },
    ...
  ]
```

---

## Code Review Checklist

### When Reviewing Code, Verify:

- [ ] No `unit_ids[]` arrays created
- [ ] No individual unit ID loops in new code
- [ ] API responses contain aggregates only
- [ ] Role-based checks (Support vs. Partner) implemented
- [ ] Color data stored as JSON in company table
- [ ] Cache invalidation on unit changes
- [ ] No per-unit detail views in UI
- [ ] Icons used, no emojis in output
- [ ] Tests verify aggregation, not individual IDs

### Red Flags 🚩

Reject or request changes if code contains:

```php
// ❌ REJECT: Individual unit arrays
$unit_ids = array_map( 'absint', $_POST['unit_ids'] );
$ticket->unit_ids = $unit_ids;

// ❌ REJECT: Looping through individual units
foreach ( $selected_units as $unit_id ) {
    // Processing individual units
}

// ❌ REJECT: Unit-level filtering in UI
<select name="unit_ids[]" multiple>
    <option value="1">Unit 1</option>
    <option value="2">Unit 2</option>
</select>

// ❌ REJECT: Exposing unit IDs in API
return [ 'units' => $wpdb->get_results( "SELECT id, color FROM units" ) ];
```

---

## Database Queries - Preferred Patterns

### ✅ Correct Pattern: Aggregate by Color

```php
$colors = $wpdb->get_results( "
    SELECT 
        color_tag,
        COUNT(*) as count
    FROM wp_lgp_units
    WHERE company_id = %d
    GROUP BY color_tag
    ORDER BY count DESC
", ARRAY_A );

// Result:
// [
//   ['color_tag' => 'yellow', 'count' => 10],
//   ['color_tag' => 'orange', 'count' => 5]
// ]
```

### ✅ Correct Pattern: Count Total

```php
$unit_count = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM wp_lgp_units WHERE company_id = %d",
    $company_id
) );

// Result: 15 (integer)
```

### ❌ Incorrect Pattern: Select Individual Units

```php
$units = $wpdb->get_results( "
    SELECT id, color_tag FROM wp_lgp_units WHERE company_id = %d
" );

// WRONG: Returns individual unit IDs
```

---

## Caching Strategy

### Cache Color Aggregates

```php
class LGP_Company_Colors {
    
    public static function get_company_colors( $company_id ) {
        // 1. Check cache first (1-hour TTL)
        $cached = wp_cache_get( "company_colors_{$company_id}" );
        if ( $cached !== false ) {
            return $cached;
        }
        
        // 2. Calculate from database
        global $wpdb;
        $colors = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT color_tag, COUNT(*) as count 
                 FROM {$wpdb->prefix}lgp_units 
                 WHERE company_id = %d 
                 GROUP BY color_tag",
                $company_id
            ),
            ARRAY_A
        );
        
        // 3. Store in cache
        wp_cache_set( 
            "company_colors_{$company_id}", 
            $colors, 
            'loungenie_portal', 
            3600  // 1 hour
        );
        
        return $colors;
    }
    
    /**
     * Invalidate cache when units are modified
     */
    public static function invalidate_company_cache( $company_id ) {
        wp_cache_delete( "company_colors_{$company_id}" );
    }
}
```

### Hook Into Unit Changes

```php
add_action( 'lgp_unit_created', function( $unit_id, $company_id ) {
    LGP_Company_Colors::invalidate_company_cache( $company_id );
}, 10, 2 );

add_action( 'lgp_unit_updated', function( $unit_id, $company_id ) {
    LGP_Company_Colors::invalidate_company_cache( $company_id );
}, 10, 2 );

add_action( 'lgp_unit_deleted', function( $unit_id, $company_id ) {
    LGP_Company_Colors::invalidate_company_cache( $company_id );
}, 10, 2 );
```

---

## Test Requirements

### All New Features Must Test

```php
class Test_Unit_Color_Aggregation extends WP_UnitTestCase {
    
    public function test_get_company_colors_aggregates() {
        // Create test company with mixed color units
        $company = create_test_company();
        create_test_unit( $company->id, 'yellow' );
        create_test_unit( $company->id, 'yellow' );
        create_test_unit( $company->id, 'orange' );
        
        // Get colors
        $colors = LGP_Company_Colors::get_company_colors( $company->id );
        
        // Verify aggregation
        $this->assertEquals( [ 'yellow' => 2, 'orange' => 1 ], $colors );
    }
    
    public function test_support_sees_all_companies() {
        // Create multiple companies
        $company1 = create_test_company();
        $company2 = create_test_company();
        
        // Set user as Support
        wp_set_current_user( create_test_user( 'support' ) );
        
        // Fetch companies
        $response = lgp_get_companies_api();
        
        // Verify all companies visible
        $this->assertCount( 2, $response );
    }
    
    public function test_partner_sees_only_own_company() {
        // Create multiple companies
        $company1 = create_test_company();
        $company2 = create_test_company();
        
        // Set user as Partner for company1
        $partner = create_test_user( 'partner' );
        set_user_company( $partner, $company1->id );
        wp_set_current_user( $partner );
        
        // Fetch companies
        $response = lgp_get_companies_api();
        
        // Verify only own company visible
        $this->assertCount( 1, $response );
        $this->assertEquals( $company1->id, $response[0]->id );
    }
    
    public function test_color_json_format() {
        $colors = [ 'yellow' => 10, 'orange' => 5 ];
        $json = json_encode( $colors );
        
        // Store and retrieve
        $company = create_test_company();
        $company->top_colors = $json;
        $company->save();
        
        // Retrieve and verify
        $retrieved = json_decode( $company->top_colors, true );
        $this->assertEquals( $colors, $retrieved );
    }
}
```

---

## Examples of Correct Usage

### Example 1: Dashboard API Endpoint

```php
add_action( 'rest_api_init', function() {
    register_rest_route( 'lgp/v1', '/dashboard/company-summary', [
        'methods'             => 'GET',
        'callback'            => 'lgp_dashboard_summary',
        'permission_callback' => fn() => current_user_can( 'lgp_access_portal' )
    ] );
} );

function lgp_dashboard_summary( $request ) {
    global $wpdb;
    
    $is_support = LGP_Auth::is_support();
    $company_id = LGP_Auth::get_user_company_id();
    
    if ( $is_support ) {
        // Support sees all
        $companies = $wpdb->get_results(
            "SELECT id, name, top_colors FROM {$wpdb->prefix}lgp_companies ORDER BY name ASC"
        );
    } else {
        // Partner sees only own
        $companies = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name, top_colors FROM {$wpdb->prefix}lgp_companies WHERE id = %d",
            $company_id
        ) );
    }
    
    // Format response with aggregates
    $response = [];
    foreach ( $companies as $company ) {
        $response[] = [
            'id'           => $company->id,
            'name'         => $company->name,
            'unit_count'   => LGP_Company_Colors::get_company_unit_count( $company->id ),
            'top_colors'   => json_decode( $company->top_colors ?? '{}', true ),
        ];
    }
    
    return rest_ensure_response( $response );
}
```

### Example 2: Company Profile Template

```php
<?php
// templates/company-profile.php
global $wpdb;

$company_id = get_query_var( 'company_id' );
$company = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}lgp_companies WHERE id = %d",
    $company_id
) );

if ( ! $company ) {
    echo 'Company not found';
    return;
}

// Get aggregated metrics
$unit_count = LGP_Company_Colors::get_company_unit_count( $company->id );
$colors = LGP_Company_Colors::get_company_colors( $company->id );
?>

<div class="lgp-company-profile">
    <h1><?php echo esc_html( $company->name ); ?></h1>
    
    <div class="lgp-metrics">
        <div class="lgp-metric">
            <span class="lgp-label">Total Units</span>
            <span class="lgp-value"><?php echo esc_html( $unit_count ); ?></span>
        </div>
    </div>
    
    <div class="lgp-color-distribution">
        <h3>Color Distribution</h3>
        <ul>
            <?php foreach ( $colors as $color => $count ) : ?>
                <li>
                    <span class="lgp-icon" style="background: <?php echo esc_attr( lgp_get_color_hex( $color ) ); ?>;"></span>
                    <span><?php echo esc_html( $color ); ?>: <?php echo esc_html( $count ); ?> units</span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
```

---

## Frequently Asked Questions for Developers

**Q: Can I create a feature that shows individual unit details?**  
A: No. All unit-level details must be accessed through company aggregates or internal unit properties, not exposed via ticket selections or user-facing lists.

**Q: What if a ticket needs to reference specific units affected?**  
A: Store a range (1, 2-5, 6-10, 10+) or color description. The company can map this internally.

**Q: How do I filter by unit color?**  
A: Filter by company aggregate colors, not individual unit IDs. Example: `top_colors.yellow > 5`

**Q: Can the map view show individual units?**  
A: The map can display unit locations but must aggregate by company/color. Don't expose individual unit IDs to the UI.

**Q: What about historical unit data?**  
A: Create a separate history table (`wp_lgp_company_color_history`) that snapshots daily. Never expose individual historical unit records.

---

## Migration Guide for Existing Code

### If You Find Existing Unit ID Tracking

**Step 1: Identify**
```php
// Search for these patterns
$_POST['unit_ids']
$ticket->unit_ids
$affected_units = [1, 2, 3]
```

**Step 2: Replace with Aggregation**
```php
// OLD
$_POST['units_affected_ids']

// NEW
$_POST['units_affected_range']  // "1", "2-5", "6-10", "10+"
```

**Step 3: Update Queries**
```php
// OLD
WHERE id IN (1, 2, 3)

// NEW
WHERE company_id = 5  // Then aggregate by color
```

**Step 4: Test**
- [ ] No individual unit IDs in output
- [ ] Aggregates match database totals
- [ ] Role-based access still works

---

## Document Ownership & Updates

**Owner:** LounGenie Portal Architecture Team  
**Contact:** [Your Team Contact]  
**Review Cycle:** Quarterly  
**Last Updated:** December 19, 2025

**To Update This Prompt:**
1. Review with architecture team
2. Document change rationale
3. Communicate to development team
4. Update version number and date

---

## Implementation Schedule

**Phase 1: Documentation & Guidance** ✅ Complete  
- Architectural guidance doc created
- This prompt document created
- Examples and code patterns documented

**Phase 2: Code Refactoring** (Sprint 2)
- Support ticket form updated
- Company profile template refactored
- Dashboard endpoints verified
- Tests updated

**Phase 3: New Features** (Sprint 3)
- Color distribution dashboard
- Company metrics widget
- Role-based company list
- Color-based filtering

---

**END OF PROMPT DOCUMENT**

All development on LounGenie Portal must adhere to this guidance starting immediately.
