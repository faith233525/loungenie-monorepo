# Unit & Color Tracking Architecture Guide
## Aggregated Model - Do Not Track Individual Units

**Document Version:** 1.0  
**Last Updated:** December 19, 2025  
**Author:** LounGenie Portal Development Team  
**Status:** Architectural Guidance Document

---

## Executive Summary

This document establishes the **authoritative architectural pattern** for unit and color tracking in the LounGenie Portal. The key principle is:

> **Units are NOT tracked individually by ID. Only aggregate counts per color per company are stored and displayed.**

This aggregation model simplifies data management, improves performance, and aligns with the portal's role-based access control (Support sees all, Partner sees own company only).

---

## Core Principles

### 1. **Aggregation Only - No Individual Unit IDs**

#### ❌ **INCORRECT Pattern (Do Not Use)**
```php
// WRONG: Tracking individual unit IDs
$ticket->unit_ids = [1, 2, 3, 4, 5];  // Per-unit tracking
$ticket->affected_unit_count = 5;

// WRONG: Storing individual unit references
$units_affected = [
    ['id' => 1, 'color' => 'yellow'],
    ['id' => 2, 'color' => 'yellow'],
    ['id' => 3, 'color' => 'orange']
];
```

#### ✅ **CORRECT Pattern (Use This)**
```php
// RIGHT: Company-level aggregation only
$company->unit_count = 15;  // Total units in company
$company->top_colors = [    // Aggregated color distribution
    'yellow' => 10,
    'orange' => 3,
    'ice-blue' => 2
];

// RIGHT: In tickets, reference the company aggregate, not individual units
$ticket->company_id = 5;
$ticket->units_affected_count = "6-10";  // Range, not individual IDs
```

---

### 2. **Company-Level Color Distribution**

**Data Storage Location:** `wp_lgp_companies` table  
**Field Name:** `top_colors`  
**Data Type:** JSON  
**Update Frequency:** Calculated on-demand or cached (recommended: 1-hour cache)

#### Database Schema
```sql
ALTER TABLE wp_lgp_companies ADD COLUMN top_colors JSON DEFAULT NULL AFTER contact_phone;

-- Example data:
{
  "yellow": 10,
  "orange": 5,
  "ice-blue": 2,
  "classic-blue": 1,
  "ducati-red": 0
}
```

#### PHP Example
```php
class LGP_Company_Colors {
    
    /**
     * Get aggregated color distribution for a company
     * 
     * @param int $company_id Company ID
     * @return array Color counts by color name
     */
    public static function get_company_colors( $company_id ) {
        global $wpdb;
        
        $colors_data = $wpdb->get_var( $wpdb->prepare(
            "SELECT top_colors FROM {$wpdb->prefix}lgp_companies WHERE id = %d",
            $company_id
        ) );
        
        if ( ! $colors_data ) {
            return self::calculate_company_colors( $company_id );
        }
        
        return json_decode( $colors_data, true ) ?? [];
    }
    
    /**
     * Calculate color distribution from units table
     * Aggregates ALL units for the company, no individual tracking
     * 
     * @param int $company_id Company ID
     * @return array Color counts
     */
    public static function calculate_company_colors( $company_id ) {
        global $wpdb;
        
        // Get color counts for this company ONLY
        // Do NOT return individual unit IDs
        $colors = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                COALESCE(color_tag, 'unknown') as color,
                COUNT(*) as count 
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
        
        // Cache this calculation for 1 hour
        wp_cache_set(
            'company_colors_' . $company_id,
            $color_counts,
            'loungenie_portal',
            3600
        );
        
        return $color_counts;
    }
    
    /**
     * Get total unit count for company
     * 
     * @param int $company_id Company ID
     * @return int Total unit count
     */
    public static function get_company_unit_count( $company_id ) {
        global $wpdb;
        
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}lgp_units WHERE company_id = %d",
            $company_id
        ) );
    }
}
```

---

### 3. **Role-Based Visibility**

#### Support Role Access
```
GET /wp-json/lgp/v1/companies
Response:
[
  {
    "id": 1,
    "name": "ABC Hotels Group",
    "unit_count": 15,
    "top_colors": {
      "yellow": 10,
      "orange": 5
    }
  },
  {
    "id": 2,
    "name": "Resort Chains Inc.",
    "unit_count": 8,
    "top_colors": {
      "ice-blue": 5,
      "classic-blue": 3
    }
  }
  // ... ALL companies visible
]
```

#### Partner Role Access
```
GET /wp-json/lgp/v1/companies?own_only=true
Response:
[
  {
    "id": 1,
    "name": "ABC Hotels Group",
    "unit_count": 15,
    "top_colors": {
      "yellow": 10,
      "orange": 5
    }
  }
  // ... ONLY own company visible
]
```

#### Implementation
```php
class LGP_Companies_API {
    
    public static function get_companies( $request ) {
        global $wpdb;
        
        $is_support = LGP_Auth::is_support();
        $company_id = LGP_Auth::get_user_company_id();
        
        // Build WHERE clause based on role
        if ( $is_support ) {
            // Support: see all companies
            $where = "1=1";
        } else {
            // Partner: see only own company
            $where = $wpdb->prepare( "id = %d", $company_id );
        }
        
        $companies = $wpdb->get_results(
            "SELECT id, name, contact_name, contact_email, top_colors 
            FROM {$wpdb->prefix}lgp_companies 
            WHERE {$where} 
            ORDER BY name ASC"
        );
        
        // Add calculated metrics
        $response = [];
        foreach ( $companies as $company ) {
            $response[] = [
                'id'           => $company->id,
                'name'         => $company->name,
                'unit_count'   => LGP_Company_Colors::get_company_unit_count( $company->id ),
                'top_colors'   => json_decode( $company->top_colors ?? '{}', true ),
                'contact_name' => $company->contact_name,
                'contact_email' => $company->contact_email
            ];
        }
        
        return rest_ensure_response( $response );
    }
}
```

---

## Visual Elements: Icons Only

Per the design guidance, **no emojis are used**. Only semantic icons.

### Color Display Pattern

#### Dashboard Cards
```html
<!-- Company Color Distribution Card -->
<div class="lgp-card">
  <h3>Color Distribution</h3>
  <ul class="lgp-color-list">
    <li>
      <span class="lgp-icon">
        <svg viewBox="0 0 16 16" width="16" height="16">
          <!-- Yellow box icon -->
          <rect x="2" y="2" width="12" height="12" fill="#FDD835"/>
        </svg>
      </span>
      <span class="lgp-label">Yellow: 10 units</span>
    </li>
    <li>
      <span class="lgp-icon">
        <svg viewBox="0 0 16 16" width="16" height="16">
          <!-- Orange box icon -->
          <rect x="2" y="2" width="12" height="12" fill="#FB8C00"/>
        </svg>
      </span>
      <span class="lgp-label">Orange: 5 units</span>
    </li>
  </ul>
</div>
```

#### CSS
```css
.lgp-color-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.lgp-color-list li {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px solid #e5e5e5;
}

.lgp-color-list li:last-child {
  border-bottom: none;
}

.lgp-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.lgp-label {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}
```

---

## Existing System Analysis

### Current State (As of v1.2.0)

**Per-Unit Tracking Locations:**
1. `wp_lgp_units.color_tag` - Individual unit color (column-level tracking)
2. Support ticket form: `unit_ids[]` - Selected individual units (form-level tracking)
3. Dashboard cache: `top_colors` query aggregates but stores per-color counts (already correct!)

**What's Already Correct:**
- Cache query in `class-lgp-cache.php` already uses GROUP BY color
- No individual unit IDs exposed in API responses
- Role-based filtering in templates

**What Needs Refactoring:**
1. **Support Ticket Form** - Remove per-unit ID selection, keep range selection only
2. **API Endpoints** - Ensure `/units` API doesn't expose individual unit IDs unnecessarily
3. **Company Profile Template** - Display color aggregates, not individual units
4. **Dashboard** - Add aggregated color distribution widget

---

## Implementation Phases

### Phase 1: Documentation & Guidance (✅ CURRENT)

**Deliverables:**
- ✅ This architectural guidance document
- ✅ Database schema recommendations
- ✅ Code examples (PHP, JavaScript)
- ✅ API response examples
- ✅ Role-based access patterns

**Owner:** Architecture Team  
**Timeline:** Complete  

### Phase 2: Refactor Existing Code

**Tasks:**
1. Update support ticket form to remove `unit_ids[]` selection
   - Keep "Number of Units Affected" (range selector)
   - Remove "Affected Units" (individual unit selector)
   
2. Update company profile template
   - Change from listing individual units to color distribution
   - Add aggregated color counts
   
3. Refactor dashboard endpoints
   - Remove per-unit data from API responses
   - Ensure top_colors is calculated and cached
   
4. Update tests
   - Remove tests checking for individual unit IDs
   - Add tests for color aggregation

**Estimated Effort:** 16-20 hours  
**Timeline:** Phase 2 (Sprint 2)

### Phase 3: Build UI Components

**New Features:**
1. Color distribution dashboard widget
2. Company unit metrics card
3. Role-based company list with aggregates
4. Color-based filtering (company dashboard)
5. Historical unit count trends (optional)

**Estimated Effort:** 24-32 hours  
**Timeline:** Phase 3 (Sprint 3)

---

## Code Examples

### REST API Endpoint (Correct Pattern)

```php
// Register endpoint
add_action( 'rest_api_init', function() {
    register_rest_route( 'lgp/v1', '/companies/(?P<id>\d+)/colors', array(
        'methods'             => 'GET',
        'callback'            => 'lgp_get_company_colors',
        'permission_callback' => function() {
            return LGP_Auth::is_support() || LGP_Auth::is_partner();
        }
    ) );
} );

function lgp_get_company_colors( $request ) {
    $company_id = (int) $request['id'];
    
    // Check permission: Support sees all, Partner sees own only
    if ( ! LGP_Auth::is_support() && LGP_Auth::get_user_company_id() !== $company_id ) {
        return new WP_Error( 'forbidden', 'Access denied', array( 'status' => 403 ) );
    }
    
    // Get aggregated colors
    $colors = LGP_Company_Colors::get_company_colors( $company_id );
    $unit_count = LGP_Company_Colors::get_company_unit_count( $company_id );
    
    // Response: Aggregates only, no individual unit IDs
    return rest_ensure_response( [
        'company_id' => $company_id,
        'unit_count' => $unit_count,
        'top_colors' => $colors,
        'timestamp'  => current_time( 'iso8601' )
    ] );
}
```

### JavaScript - Display Color Distribution

```javascript
async function displayColorDistribution(companyId) {
    const response = await fetch(`/wp-json/lgp/v1/companies/${companyId}/colors`);
    const data = await response.json();
    
    const container = document.getElementById('color-distribution');
    
    // Clear existing
    container.innerHTML = '';
    
    // Create color list
    const list = document.createElement('ul');
    list.className = 'lgp-color-list';
    
    // Sort colors by count descending
    const sorted = Object.entries(data.top_colors)
        .sort((a, b) => b[1] - a[1]);
    
    // Display each color with count
    sorted.forEach(([color, count]) => {
        const li = document.createElement('li');
        li.innerHTML = `
            <span class="lgp-icon" style="background-color: ${getColorHex(color)};">
                ▌
            </span>
            <span class="lgp-label">${color}: ${count} units</span>
        `;
        list.appendChild(li);
    });
    
    container.appendChild(list);
    
    // Display total
    const total = document.createElement('div');
    total.className = 'lgp-total-units';
    total.textContent = `Total: ${data.unit_count} units`;
    container.appendChild(total);
}

function getColorHex(colorName) {
    const colorMap = {
        'yellow': '#FDD835',
        'orange': '#FB8C00',
        'ice-blue': '#0277BD',
        'classic-blue': '#1565C0',
        'ducati-red': '#C62828'
    };
    return colorMap[colorName.toLowerCase()] || '#999999';
}
```

### Database Query Pattern (Correct)

```php
// ✅ CORRECT: Aggregate only
$query = "
    SELECT 
        color_tag,
        COUNT(*) as unit_count
    FROM wp_lgp_units
    WHERE company_id = %d
    GROUP BY color_tag
    ORDER BY unit_count DESC
";

$results = $wpdb->get_results( $wpdb->prepare( $query, $company_id ) );
// Result: [
//   {'color_tag': 'yellow', 'unit_count': 10},
//   {'color_tag': 'orange', 'unit_count': 5}
// ]

// ❌ WRONG: Per-unit tracking
$query = "
    SELECT id, color_tag
    FROM wp_lgp_units
    WHERE company_id = %d
";

$units = $wpdb->get_results( $wpdb->prepare( $query, $company_id ) );
// Do NOT use this pattern - returns individual unit IDs
```

---

## Dashboard Integration Example

### Support Dashboard Component

```html
<!-- Support View: All Companies with Color Breakdown -->
<div class="lgp-dashboard">
  <h2>Company Overview</h2>
  
  <div class="lgp-companies-grid">
    <!-- Card for each company -->
    <div class="lgp-card" data-company-id="1">
      <h3>ABC Hotels Group</h3>
      <div class="lgp-metrics">
        <div class="lgp-metric">
          <span class="lgp-metric-label">Total Units</span>
          <span class="lgp-metric-value">15</span>
        </div>
      </div>
      
      <div class="lgp-color-distribution">
        <h4>Color Distribution</h4>
        <div class="lgp-color-bars">
          <div class="lgp-color-bar" style="width: 67%;">
            <span class="lgp-color-icon" style="background: #FDD835;"></span>
            <span class="lgp-color-label">Yellow (10)</span>
          </div>
          <div class="lgp-color-bar" style="width: 33%;">
            <span class="lgp-color-icon" style="background: #FB8C00;"></span>
            <span class="lgp-color-label">Orange (5)</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- More company cards... -->
  </div>
</div>
```

### Partner Dashboard Component (Limited)

```html
<!-- Partner View: Own Company Only -->
<div class="lgp-dashboard">
  <h2>My Company</h2>
  
  <div class="lgp-card">
    <h3>ABC Hotels Group</h3>
    <div class="lgp-metrics">
      <div class="lgp-metric">
        <span class="lgp-metric-label">Total Units</span>
        <span class="lgp-metric-value">15</span>
      </div>
    </div>
    
    <div class="lgp-color-distribution">
      <h4>Color Distribution</h4>
      <!-- Same as above, but ONLY for own company -->
    </div>
  </div>
</div>
```

---

## Testing Checklist

### Unit Tests
- [ ] `test_get_company_colors()` - Verify aggregation query
- [ ] `test_color_count_totals()` - Verify sums match unit count
- [ ] `test_role_based_visibility_support()` - Support sees all
- [ ] `test_role_based_visibility_partner()` - Partner sees own only
- [ ] `test_cache_invalidation()` - Colors update when units change
- [ ] `test_color_json_format()` - Verify JSON structure

### Integration Tests
- [ ] Support user fetches all companies
- [ ] Partner user fetches only own company
- [ ] API response structure matches specification
- [ ] Color counts match database totals
- [ ] Dashboard renders with aggregated data

### Manual Testing
- [ ] [ ] Support dashboard shows all companies with colors
- [ ] [ ] Partner portal shows only own company colors
- [ ] [ ] Color totals add up to unit count
- [ ] [ ] No individual unit IDs exposed in UI or API
- [ ] [ ] Icons display correctly (no emojis)
- [ ] [ ] Mobile responsiveness maintained

---

## Migration Path (If Current Data Has Individual IDs)

### Scenario: Support Tickets Currently Store `unit_ids[]`

**Current Data:**
```json
{
  "ticket_id": 123,
  "company_id": 5,
  "unit_ids": [1, 2, 3, 4],
  "units_affected": "2-5"
}
```

**New Pattern:**
```json
{
  "ticket_id": 123,
  "company_id": 5,
  "units_affected": "2-5"
}
```

**Migration SQL:**
```sql
-- Remove unit_ids if stored in postmeta
DELETE FROM wp_postmeta 
WHERE post_id IN (
    SELECT ID FROM wp_posts WHERE post_type = 'support_ticket'
)
AND meta_key = '_affected_unit_ids';
```

---

## FAQ

**Q: Why not track individual unit IDs?**  
A: Aggregation reduces data complexity, improves query performance, and aligns with the company-level access control model. Partners don't need individual unit visibility.

**Q: What if someone needs to know which specific units are affected?**  
A: Store the color range that's affected (e.g., "yellow units 5-10"). The company can map this to specific units if needed in their own system.

**Q: How do we handle unit-specific maintenance history?**  
A: This is stored per-unit in the units table (`service_history` JSON field). Tickets reference companies, not units.

**Q: Can we show color trends over time?**  
A: Yes! Cache color snapshots daily. Build a historical table if needed: `wp_lgp_company_color_history`.

**Q: What about the map view?**  
A: Show units with geolocation but group by company color. Don't expose individual unit IDs on the map.

---

## References

- [Class LGP_Company_Colors - To Be Created]
- [Support Ticket Form Refactor - Phase 2]
- [Dashboard Widgets - Phase 3]
- [Design System Guide](./DESIGN_SYSTEM_GUIDE.md)
- [FILTERING_GUIDE.md](./loungenie-portal/FILTERING_GUIDE.md)

---

## Document Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-12-19 | Architecture Team | Initial architectural guidance |

---

**Next Steps:**
1. ✅ Review and approve this guidance
2. 📋 Phase 2: Refactor existing code (16-20 hours)
3. 🎨 Phase 3: Build UI components (24-32 hours)
