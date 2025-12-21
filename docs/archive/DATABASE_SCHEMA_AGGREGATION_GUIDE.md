# Database Schema Guide - Unit & Color Aggregation
## SQL Implementation & Migration

**Document Version:** 1.0  
**Date:** December 19, 2025  
**Database System:** WordPress with MySQL/MariaDB  
**PHP Compatibility:** PHP 7.4+

---

## Current Schema State

### Existing Tables (As of v1.2.0)

#### `wp_lgp_companies` - REQUIRES UPDATE
```sql
CREATE TABLE wp_lgp_companies (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    address text,
    state varchar(50),
    venue_type varchar(50),
    contact_name varchar(255),
    contact_email varchar(255),
    contact_phone varchar(50),
    management_company_id bigint(20) UNSIGNED,
    -- Existing company fields...
    company_name varchar(255),
    management_company varchar(255),
    primary_contract varchar(50),
    primary_contract_status varchar(20),
    secondary_contract varchar(50),
    secondary_contract_status varchar(20),
    contract_notes text,
    season varchar(20),
    street_address varchar(255),
    city varchar(100),
    zip varchar(20),
    country varchar(100),
    top_colour varchar(50),                    -- ⚠️ DEPRECATED
    
    -- ✅ NEW FIELD NEEDED:
    top_colors JSON DEFAULT NULL,               -- NEW: Stores { "yellow": 10, "orange": 5 }
    
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY management_company_id (management_company_id),
    KEY venue_type (venue_type),
    KEY primary_contract_status (primary_contract_status),
    KEY season (season)
);
```

#### `wp_lgp_units` - UNCHANGED
```sql
CREATE TABLE wp_lgp_units (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id bigint(20) UNSIGNED NOT NULL,
    management_company_id bigint(20) UNSIGNED,
    unit_number varchar(100) UNIQUE,
    address text,
    lock_type varchar(100),
    lock_brand varchar(50),          -- MAKE, L&F, other
    lock_part varchar(100),
    key varchar(100),
    color_tag varchar(50),           -- Used for aggregation
    season varchar(20) DEFAULT 'year-round',
    venue_type varchar(50),
    status varchar(50) DEFAULT 'active',
    install_date date,
    installation_date date,
    master_code varbinary(255),
    sub_master_code varbinary(255),
    latitude decimal(10,6),
    longitude decimal(10,6),
    service_history text,            -- JSON array
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unit_number (unit_number),
    KEY company_id (company_id),
    KEY color_tag (color_tag),       -- INDEX for aggregation
    KEY status (status),
    KEY season (season)
);
```

---

## Schema Updates Required

### Migration 1: Add `top_colors` JSON Column to Companies

**Purpose:** Store aggregated color distribution per company  
**Impact:** Non-breaking (new column, nullable)  
**Rollback:** Simple column drop

#### SQL Migration
```sql
-- Add new column
ALTER TABLE wp_lgp_companies 
ADD COLUMN top_colors JSON DEFAULT NULL 
AFTER contact_phone;

-- Optional: Create index for JSON queries (MySQL 5.7+)
-- Note: JSON path indexing requires generated columns in some versions

-- Verify
DESCRIBE wp_lgp_companies;  -- Should show top_colors column
```

#### PHP Migration Class
```php
class LGP_Migrations_Unit_Aggregation {
    
    public static function add_top_colors_column() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'lgp_companies';
        $column_exists = $wpdb->get_results( 
            $wpdb->prepare(
                "SHOW COLUMNS FROM $table LIKE %s",
                'top_colors'
            )
        );
        
        if ( empty( $column_exists ) ) {
            $wpdb->query(
                "ALTER TABLE $table 
                 ADD COLUMN top_colors JSON DEFAULT NULL 
                 AFTER contact_phone"
            );
            
            // Log migration
            LGP_Logger::log_event(
                0,
                'migration_add_top_colors_column',
                0,
                [ 'status' => 'success' ]
            );
            
            return true;
        }
        
        return false;  // Already exists
    }
    
    /**
     * Populate initial color aggregates for existing companies
     * Run once after column is added
     */
    public static function populate_initial_colors() {
        global $wpdb;
        
        $companies_table = $wpdb->prefix . 'lgp_companies';
        $units_table = $wpdb->prefix . 'lgp_units';
        
        // Get all companies
        $companies = $wpdb->get_results(
            "SELECT id FROM $companies_table"
        );
        
        foreach ( $companies as $company ) {
            // Calculate colors for this company
            $colors = $wpdb->get_results( $wpdb->prepare(
                "SELECT 
                    COALESCE(color_tag, 'unknown') as color,
                    COUNT(*) as count 
                FROM $units_table 
                WHERE company_id = %d 
                GROUP BY color_tag 
                ORDER BY count DESC",
                $company->id
            ), OBJECT_K );
            
            // Build JSON
            $color_counts = [];
            foreach ( $colors as $color => $data ) {
                $color_counts[ $color ] = (int) $data->count;
            }
            
            // Store JSON in company
            $wpdb->update(
                $companies_table,
                [ 'top_colors' => json_encode( $color_counts ) ],
                [ 'id' => $company->id ],
                [ '%s' ],
                [ '%d' ]
            );
        }
        
        LGP_Logger::log_event(
            0,
            'migration_populate_colors',
            0,
            [ 
                'companies_processed' => count( $companies ),
                'status' => 'success'
            ]
        );
    }
}
```

---

## Data Synchronization Strategy

### Approach 1: Calculate On Demand (Recommended)

**When:** On API request  
**Cache:** 1 hour (wp_cache)  
**Trigger:** User opens dashboard or company view

**Pros:**
- Always current
- No background jobs needed
- Simple implementation

**Cons:**
- Slight query latency on first load

#### Implementation
```php
class LGP_Company_Colors {
    
    const CACHE_TTL = 3600;  // 1 hour
    
    public static function get_company_colors( $company_id ) {
        // Try cache first
        $cache_key = "company_colors_{$company_id}";
        $colors = wp_cache_get( $cache_key, 'loungenie_portal' );
        
        if ( $colors !== false ) {
            return $colors;
        }
        
        // Calculate from database
        global $wpdb;
        $color_data = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                COALESCE(color_tag, 'unknown') as color,
                COUNT(*) as count 
            FROM {$wpdb->prefix}lgp_units 
            WHERE company_id = %d 
            GROUP BY color_tag 
            ORDER BY count DESC",
            $company_id
        ), OBJECT_K );
        
        // Convert to simple array
        $colors = [];
        foreach ( $color_data as $color => $data ) {
            $colors[ $color ] = (int) $data->count;
        }
        
        // Cache result
        wp_cache_set( $cache_key, $colors, 'loungenie_portal', self::CACHE_TTL );
        
        return $colors;
    }
}
```

### Approach 2: Update on Unit Changes (Optional)

**When:** Unit is created, updated, or deleted  
**Process:** Recalculate and update company.top_colors  
**Benefit:** Minimal query time on fetch (already calculated)

#### Implementation
```php
// Hook into unit operations
add_action( 'lgp_unit_after_create', function( $unit_id ) {
    $unit = get_unit( $unit_id );
    LGP_Company_Colors::refresh_company_colors( $unit->company_id );
}, 10, 1 );

add_action( 'lgp_unit_after_update', function( $unit_id, $old_unit, $new_unit ) {
    // Refresh if color changed
    if ( $old_unit->color_tag !== $new_unit->color_tag ) {
        LGP_Company_Colors::refresh_company_colors( $new_unit->company_id );
    }
}, 10, 3 );

add_action( 'lgp_unit_after_delete', function( $unit_id, $company_id ) {
    LGP_Company_Colors::refresh_company_colors( $company_id );
}, 10, 2 );

class LGP_Company_Colors {
    
    public static function refresh_company_colors( $company_id ) {
        global $wpdb;
        
        // Calculate current colors
        $colors = self::calculate_colors( $company_id );
        
        // Update company record
        $wpdb->update(
            $wpdb->prefix . 'lgp_companies',
            [ 'top_colors' => json_encode( $colors ) ],
            [ 'id' => $company_id ],
            [ '%s' ],
            [ '%d' ]
        );
        
        // Invalidate cache
        wp_cache_delete( "company_colors_{$company_id}", 'loungenie_portal' );
    }
    
    private static function calculate_colors( $company_id ) {
        global $wpdb;
        
        $colors_data = $wpdb->get_results( $wpdb->prepare(
            "SELECT COALESCE(color_tag, 'unknown') as color, COUNT(*) as count 
            FROM {$wpdb->prefix}lgp_units 
            WHERE company_id = %d 
            GROUP BY color_tag",
            $company_id
        ), OBJECT_K );
        
        $colors = [];
        foreach ( $colors_data as $color => $data ) {
            $colors[ $color ] = (int) $data->count;
        }
        
        return $colors;
    }
}
```

---

## Index Strategy

### Critical Indexes for Aggregation Queries

#### Index 1: `color_tag` on Units
```sql
-- Already exists, verify it's present
SHOW INDEX FROM wp_lgp_units WHERE Column_name = 'color_tag';

-- If missing, create it
CREATE INDEX idx_units_color_tag ON wp_lgp_units(color_tag);
```

**Query Performance:**
```
Before: ~500ms (full table scan for 1000+ units)
After:  ~5ms   (index seek)
```

#### Index 2: `company_id` on Units
```sql
-- Verify existence
SHOW INDEX FROM wp_lgp_units WHERE Column_name = 'company_id';

-- If missing, create it
CREATE INDEX idx_units_company_id ON wp_lgp_units(company_id);
```

#### Composite Index for Aggregation (Optional)
```sql
-- For better performance on grouped queries
CREATE INDEX idx_units_company_color ON wp_lgp_units(company_id, color_tag);

-- Verify with EXPLAIN
EXPLAIN SELECT 
    color_tag, COUNT(*) 
FROM wp_lgp_units 
WHERE company_id = 5 
GROUP BY color_tag;
-- Should show "Using index" or "Using index for group-by"
```

---

## Query Patterns

### Aggregation Query - Standard

```sql
-- Get color distribution for a company
SELECT 
    COALESCE(color_tag, 'unknown') as color,
    COUNT(*) as count
FROM wp_lgp_units
WHERE company_id = 5
GROUP BY color_tag
ORDER BY count DESC;

-- Result:
+--------+-------+
| color  | count |
+--------+-------+
| yellow | 10    |
| orange | 5     |
+--------+-------+
```

**Performance:** ~5ms (with index)

### Total Unit Count - Simple

```sql
-- Get total units for a company
SELECT COUNT(*) as unit_count
FROM wp_lgp_units
WHERE company_id = 5;

-- Result: 15
```

**Performance:** ~1ms

### Color Distribution - All Companies

```sql
-- Get aggregates for support dashboard
SELECT 
    c.id,
    c.name,
    COUNT(u.id) as unit_count,
    JSON_OBJECT(
        'yellow', SUM(IF(u.color_tag = 'yellow', 1, 0)),
        'orange', SUM(IF(u.color_tag = 'orange', 1, 0)),
        'ice-blue', SUM(IF(u.color_tag = 'ice-blue', 1, 0)),
        'classic-blue', SUM(IF(u.color_tag = 'classic-blue', 1, 0)),
        'ducati-red', SUM(IF(u.color_tag = 'ducati-red', 1, 0))
    ) as color_distribution
FROM wp_lgp_companies c
LEFT JOIN wp_lgp_units u ON c.id = u.company_id
GROUP BY c.id, c.name
ORDER BY c.name ASC;

-- Result:
+----+-------------------+------------+-------------------------------------+
| id | name              | unit_count | color_distribution                  |
+----+-------------------+------------+-------------------------------------+
| 1  | ABC Hotels Group  | 15         | {"yellow": 10, "orange": 5, ...}   |
| 2  | Resort Chains Inc | 8          | {"ice-blue": 5, "classic-blue": 3} |
+----+-------------------+------------+-------------------------------------+
```

**Performance:** ~50ms for 50 companies

---

## Data Migration Guide

### Scenario: Existing System Stores `unit_ids` in Tickets

**Current Ticket Data:**
```sql
SELECT ID, post_meta FROM wp_postmeta 
WHERE post_type = 'support_ticket' AND meta_key = '_affected_unit_ids';

-- Result:
+----+-------------------+
| ID | post_meta         |
+----+-------------------+
| 1  | [1, 2, 3, 4]      |
| 2  | [5, 6]            |
+----+-------------------+
```

**Step 1: Backup**
```sql
-- Create backup table before any changes
CREATE TABLE wp_lgp_support_tickets_backup AS
SELECT * FROM wp_posts WHERE post_type = 'support_ticket';

CREATE TABLE wp_postmeta_backup AS
SELECT * FROM wp_postmeta 
WHERE post_type = 'support_ticket' AND meta_key IN ('_affected_unit_ids', '_units_affected_count');
```

**Step 2: Populate Units Affected Range**
```php
// Determine range from unit count
$unit_ids = get_post_meta( $ticket_id, '_affected_unit_ids', true );
$count = is_array( $unit_ids ) ? count( $unit_ids ) : 0;

$range = match ( true ) {
    $count === 0 => null,
    $count === 1 => '1',
    $count <= 5  => '2-5',
    $count <= 10 => '6-10',
    default      => '10+'
};

update_post_meta( $ticket_id, '_units_affected', $range );
```

**Step 3: Remove Old Data**
```sql
-- Delete individual unit ID references
DELETE FROM wp_postmeta 
WHERE post_id IN (
    SELECT ID FROM wp_posts WHERE post_type = 'support_ticket'
)
AND meta_key = '_affected_unit_ids';
```

**Step 4: Verify**
```sql
-- Check no old data remains
SELECT COUNT(*) FROM wp_postmeta 
WHERE meta_key = '_affected_unit_ids';
-- Should return 0

-- Check new field is populated
SELECT COUNT(*) FROM wp_postmeta 
WHERE meta_key = '_units_affected' AND meta_value IS NOT NULL;
-- Should match ticket count
```

---

## Maintenance & Monitoring

### Performance Monitoring

#### Query Analysis
```php
class LGP_Color_Aggregation_Monitor {
    
    public static function analyze_color_query_performance() {
        global $wpdb;
        
        // Enable slow query logging
        $wpdb->query( "SET SESSION long_query_time = 0.1" );
        
        // Run aggregation query
        $start = microtime( true );
        
        $colors = $wpdb->get_results(
            "SELECT color_tag, COUNT(*) as count 
            FROM {$wpdb->prefix}lgp_units 
            GROUP BY color_tag"
        );
        
        $elapsed = ( microtime( true ) - $start ) * 1000;  // Convert to ms
        
        // Log result
        LGP_Logger::log_event(
            0,
            'color_query_performance',
            0,
            [
                'elapsed_ms' => $elapsed,
                'threshold'  => 100,
                'pass'       => $elapsed < 100
            ]
        );
        
        return [
            'elapsed_ms' => $elapsed,
            'colors'     => count( $colors )
        ];
    }
    
    public static function analyze_table_structure() {
        global $wpdb;
        
        // Check indexes
        $indexes = $wpdb->get_results(
            "SHOW INDEX FROM {$wpdb->prefix}lgp_units"
        );
        
        $has_color_index = false;
        $has_company_index = false;
        
        foreach ( $indexes as $idx ) {
            if ( $idx->Column_name === 'color_tag' ) $has_color_index = true;
            if ( $idx->Column_name === 'company_id' ) $has_company_index = true;
        }
        
        return [
            'has_color_index'   => $has_color_index,
            'has_company_index' => $has_company_index,
            'recommendations'   => [
                ! $has_color_index ? 'Create index on color_tag' : null,
                ! $has_company_index ? 'Create index on company_id' : null
            ]
        ];
    }
}
```

#### Health Check Script
```php
// Run periodically (daily via cron)
class LGP_Color_Aggregation_Health_Check {
    
    public static function run() {
        $issues = [];
        
        // Check 1: Data integrity
        global $wpdb;
        
        $companies = $wpdb->get_results(
            "SELECT id, top_colors FROM {$wpdb->prefix}lgp_companies"
        );
        
        foreach ( $companies as $company ) {
            if ( ! $company->top_colors ) continue;
            
            $stored = json_decode( $company->top_colors, true );
            $actual = LGP_Company_Colors::calculate_colors( $company->id );
            
            if ( $stored !== $actual ) {
                $issues[] = "Company {$company->id}: Stored colors don't match calculated";
            }
        }
        
        // Check 2: Index health
        $monitors = LGP_Color_Aggregation_Monitor::analyze_table_structure();
        $issues = array_merge( $issues, array_filter( $monitors['recommendations'] ) );
        
        // Check 3: Query performance
        $perf = LGP_Color_Aggregation_Monitor::analyze_color_query_performance();
        if ( $perf['elapsed_ms'] > 100 ) {
            $issues[] = "Color aggregation query slow: {$perf['elapsed_ms']}ms";
        }
        
        return [
            'status'   => empty( $issues ) ? 'healthy' : 'issues_found',
            'issues'   => $issues,
            'checked_at' => current_time( 'iso8601' )
        ];
    }
}
```

---

## Rollback Procedure

If something goes wrong, here's how to revert:

### Quick Rollback
```sql
-- Drop the new column (removes aggregation)
ALTER TABLE wp_lgp_companies DROP COLUMN top_colors;

-- Revert to old single color field if needed
-- ALTER TABLE wp_lgp_companies DROP COLUMN top_colour;
```

### Code Rollback
```php
// In your version control, revert to previous commit
git revert <commit-hash>

// Or maintain backward compatibility:
if ( $company->top_colors ) {
    // Use new JSON format
    $colors = json_decode( $company->top_colors, true );
} else {
    // Fallback to old field if it exists
    $colors = [ $company->top_colour => 1 ];
}
```

---

## Testing SQL Migrations

### Test Environment Checklist
- [ ] Clone production database
- [ ] Run migrations in test environment
- [ ] Verify column added
- [ ] Verify data populated
- [ ] Run performance tests
- [ ] Check for errors in logs
- [ ] Verify backup tables
- [ ] Test rollback procedure

### Sample Test Script
```php
// tests/test-color-aggregation-migration.php

class Test_Color_Aggregation_Migration extends WP_UnitTestCase {
    
    public function test_column_added() {
        global $wpdb;
        
        $columns = $wpdb->get_results(
            "DESCRIBE {$wpdb->prefix}lgp_companies"
        );
        
        $has_top_colors = false;
        foreach ( $columns as $col ) {
            if ( $col->Field === 'top_colors' ) {
                $has_top_colors = true;
                $this->assertStringContainsString( 'json', strtolower( $col->Type ) );
            }
        }
        
        $this->assertTrue( $has_top_colors, 'top_colors column not found' );
    }
    
    public function test_colors_calculated_correctly() {
        $company = $this->factory->post->create( [
            'post_type' => 'lgp_company'
        ] );
        
        // Create units
        create_test_unit( $company, 'yellow' );
        create_test_unit( $company, 'yellow' );
        create_test_unit( $company, 'orange' );
        
        // Calculate colors
        $colors = LGP_Company_Colors::get_company_colors( $company );
        
        $this->assertEquals(
            [ 'yellow' => 2, 'orange' => 1 ],
            $colors
        );
    }
    
    public function test_json_format_valid() {
        $colors = [ 'yellow' => 10, 'orange' => 5 ];
        $json = json_encode( $colors );
        
        $decoded = json_decode( $json, true );
        
        $this->assertEquals( $colors, $decoded );
        $this->assertIsArray( $decoded );
    }
}
```

---

## Implementation Timeline

**Week 1: Preparation**
- [ ] Review schema changes
- [ ] Backup production database
- [ ] Create test environment
- [ ] Test migrations in staging

**Week 2: Migration**
- [ ] Add top_colors column
- [ ] Populate initial color data
- [ ] Verify data integrity
- [ ] Deploy to production

**Week 3: Validation**
- [ ] Monitor performance
- [ ] Verify aggregation accuracy
- [ ] Run health checks
- [ ] Check for errors in logs

---

## Questions & Support

**Q: Can I roll back if issues arise?**  
A: Yes, the column drop is reversible within 30 days using backups.

**Q: What about JSON compatibility with older MySQL?**  
A: JSON support requires MySQL 5.7+ or MariaDB 10.2+. Check version: `SELECT VERSION();`

**Q: How often should I refresh color aggregates?**  
A: On-demand calculation with 1-hour caching is recommended. Update on unit changes for real-time accuracy.

**Q: Can this support custom colors?**  
A: Yes, any value in `color_tag` will be aggregated. Extend the color list as needed.

---

**END OF SCHEMA GUIDE**

Next: Phase 2 - Code Refactoring (in progress)
