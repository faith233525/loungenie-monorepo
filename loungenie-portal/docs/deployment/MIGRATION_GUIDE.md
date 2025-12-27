# Migration Guide - Portal Enhancements v1.0

## Database Migration

This guide walks through migrating your existing database to support the new portal features.

### Prerequisites
- WordPress admin access
- SSH/Command-line access (optional)
- Database backup (REQUIRED)
- 30 minutes of maintenance window

---

## Step 1: Backup Database

### Using Command Line
```bash
# Create backup directory
mkdir -p ~/backups

# Backup database
mysqldump -u wordpress_user -p wordpress_db > ~/backups/backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup size
ls -lh ~/backups/backup_*.sql
```

### Using phpMyAdmin
1. Go to phpMyAdmin
2. Select your WordPress database
3. Click "Export"
4. Choose "SQL" format
5. Click "Go"

---

## Step 2: Add Required Columns

### For Units Map View

```sql
-- Add geographic coordinates
ALTER TABLE wp_lgp_units ADD COLUMN latitude DECIMAL(10, 8) DEFAULT NULL;
ALTER TABLE wp_lgp_units ADD COLUMN longitude DECIMAL(11, 8) DEFAULT NULL;
ALTER TABLE wp_lgp_units ADD COLUMN location VARCHAR(255) DEFAULT NULL;

-- Verify columns added
DESCRIBE wp_lgp_units;
```

### For Service Tickets

```sql
-- Ensure urgency and status columns exist
ALTER TABLE wp_lgp_tickets ADD COLUMN IF NOT EXISTS urgency VARCHAR(50) DEFAULT 'medium';
ALTER TABLE wp_lgp_tickets ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'open';

-- Verify
DESCRIBE wp_lgp_tickets;
```

### For Companies

```sql
-- Add contract status tracking
ALTER TABLE wp_lgp_companies ADD COLUMN IF NOT EXISTS contract_status VARCHAR(50) DEFAULT 'active';

-- Verify
DESCRIBE wp_lgp_companies;
```

---

## Step 3: Create Performance Indexes

```sql
-- Index for map performance
CREATE INDEX idx_unit_coordinates ON wp_lgp_units(latitude, longitude);

-- Indexes for ticket filtering
CREATE INDEX idx_ticket_unit_id ON wp_lgp_tickets(unit_id);
CREATE INDEX idx_ticket_status ON wp_lgp_tickets(status);
CREATE INDEX idx_ticket_urgency ON wp_lgp_tickets(urgency);

-- Indexes for help guides
CREATE INDEX idx_help_guides_type ON wp_lgp_help_guides(type);
CREATE INDEX idx_help_guides_created ON wp_lgp_help_guides(created_at);

-- Verify indexes
SHOW INDEXES FROM wp_lgp_units;
SHOW INDEXES FROM wp_lgp_tickets;
SHOW INDEXES FROM wp_lgp_help_guides;
```

---

## Step 4: Migrate Coordinate Data

### Geocoding Existing Addresses

If you have addresses but no coordinates, you can use the geocoding API:

```sql
-- Identify units without coordinates
SELECT id, name, address FROM wp_lgp_units WHERE latitude IS NULL OR longitude IS NULL;
```

#### Option A: Manual Geocoding
1. Go to https://nominatim.openstreetmap.org/
2. Search for each address
3. Note the latitude/longitude
4. Update in database

#### Option B: Automated Geocoding (PHP)

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'lgp_units';

// Get units without coordinates
$units = $wpdb->get_results("SELECT id, address FROM $table WHERE latitude IS NULL");

foreach ( $units as $unit ) {
    // Call geocoding service
    $response = wp_remote_get( 'https://nominatim.openstreetmap.org/search?q=' . urlencode( $unit->address ) . '&format=json' );
    
    if ( is_array( $response ) && 200 === $response['response']['code'] ) {
        $data = json_decode( $response['body'] );
        
        if ( ! empty( $data ) ) {
            $wpdb->update(
                $table,
                array(
                    'latitude'  => (float) $data[0]->lat,
                    'longitude' => (float) $data[0]->lon,
                ),
                array( 'id' => $unit->id )
            );
        }
    }
    
    // Rate limiting
    sleep( 1 );
}
?>
```

#### Option C: Bulk Update via CSV

1. Export units as CSV
2. Add latitude/longitude columns
3. Use geocoding tool (Google Sheets, etc.)
4. Import back to database

### Using WP-CLI

```bash
# Create a custom WP-CLI command for bulk geocoding
wp eval-file migrate-geocode.php

# Inside migrate-geocode.php:
<?php
// See Option B above
?>
```

---

## Step 5: Migrate Ticket Data

### Update Ticket Statuses

```sql
-- Map old status values to new ones
UPDATE wp_lgp_tickets SET status = 'open' WHERE status IN ('new', 'unassigned', 'pending');
UPDATE wp_lgp_tickets SET status = 'in_progress' WHERE status IN ('assigned', 'working', 'in-progress');
UPDATE wp_lgp_tickets SET status = 'resolved' WHERE status IN ('completed', 'fixed', 'resolved');
UPDATE wp_lgp_tickets SET status = 'closed' WHERE status IN ('archived', 'closed', 'rejected');

-- Verify update
SELECT DISTINCT status FROM wp_lgp_tickets;
```

### Set Default Urgency Levels

```sql
-- Set based on ticket age (example)
UPDATE wp_lgp_tickets SET urgency = 'critical' 
WHERE status = 'open' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);

UPDATE wp_lgp_tickets SET urgency = 'high' 
WHERE status = 'open' AND urgency IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 3 DAY);

UPDATE wp_lgp_tickets SET urgency = 'medium' 
WHERE status = 'open' AND urgency IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY);

UPDATE wp_lgp_tickets SET urgency = 'low' 
WHERE urgency IS NULL;

-- Verify
SELECT DISTINCT urgency FROM wp_lgp_tickets;
```

---

## Step 6: Validate Help Guides

### Verify Structure

```sql
-- Check help guides table exists
SHOW COLUMNS FROM wp_lgp_help_guides;

-- Verify critical columns
SELECT id, title, type, tags, target_companies FROM wp_lgp_help_guides LIMIT 1;
```

### Ensure Valid JSON

```sql
-- Fix any malformed JSON in tags
UPDATE wp_lgp_help_guides 
SET tags = '[]' 
WHERE tags IS NULL OR tags = '';

-- Fix any malformed JSON in target_companies
UPDATE wp_lgp_help_guides 
SET target_companies = '[]' 
WHERE target_companies IS NULL OR target_companies = '';
```

---

## Step 7: Verify Data Integrity

### Run Validation Queries

```sql
-- Check row counts
SELECT 'units' as table_name, COUNT(*) as count FROM wp_lgp_units
UNION
SELECT 'tickets' as table_name, COUNT(*) FROM wp_lgp_tickets
UNION
SELECT 'help_guides' as table_name, COUNT(*) FROM wp_lgp_help_guides
UNION
SELECT 'companies' as table_name, COUNT(*) FROM wp_lgp_companies;

-- Check for missing required data
SELECT 'Missing coordinates' as issue, COUNT(*) as count 
FROM wp_lgp_units WHERE latitude IS NULL OR longitude IS NULL
UNION
SELECT 'Missing ticket status', COUNT(*) 
FROM wp_lgp_tickets WHERE status IS NULL
UNION
SELECT 'Missing ticket urgency', COUNT(*) 
FROM wp_lgp_tickets WHERE urgency IS NULL;

-- Check for invalid JSON
SELECT id, tags FROM wp_lgp_help_guides WHERE tags NOT LIKE '%[%]%';
SELECT id, target_companies FROM wp_lgp_help_guides WHERE target_companies NOT LIKE '%[%]%';
```

---

## Step 8: Deploy Code

### File Structure
```
loungenie-portal/
├── templates/
│   └── map-view.php (NEW)
├── assets/
│   ├── css/
│   │   ├── map-view.css (NEW)
│   │   └── variables.css (NEW)
│   └── js/
│       └── map-view.js (NEW)
├── api/
│   ├── units.php (UPDATED)
│   └── knowledge-center.php (UPDATED; legacy alias help-guides.php)
├── includes/
│   └── class-lgp-knowledge-guide.php (UPDATED; class `LGP_Help_Guide`)
├── tests/
│   └── MapViewTest.php (NEW)
├── FEATURES.md (NEW)
├── ENHANCEMENTS_SUMMARY.md (NEW)
└── loungenie-portal.php (may need minor updates)
```

### Using Git
```bash
# Pull new code
git pull origin main

# Or manually upload files via SFTP
```

### Verify Deployment
```bash
# Check file permissions
ls -la loungenie-portal/templates/map-view.php
ls -la loungenie-portal/assets/css/map-view.css

# Verify no PHP syntax errors
php -l loungenie-portal/templates/map-view.php
php -l loungenie-portal/assets/js/map-view.js  # This will fail (JS), which is OK
```

---

## Step 9: Clear Caches

```bash
# WordPress object cache
wp cache flush

# WordPress transients
wp transient delete-all

# Plugin cache (if applicable)
# (depends on your caching plugin)

# Browser cache (user's responsibility)
# Clear in browser: Ctrl+Shift+Delete (Windows) or Cmd+Shift+Delete (Mac)
```

---

## Step 10: Test New Features

### Manual Testing Checklist

- [ ] Navigate to map view (`?view=map`)
- [ ] Map displays with markers
- [ ] Filters work (urgency, status, type)
- [ ] Click marker to see popup
- [ ] Click "View Details" to see modal
- [ ] Modal displays tickets for unit
- [ ] Knowledge Center API responds: `GET /lgp/v1/knowledge-center?type=maintenance` (legacy alias `/lgp/v1/help-guides` ok)
- [ ] Type filtering works
- [ ] Tag filtering works
- [ ] Partner users see only their company's data

### Test Database Queries

```php
<?php
// Test in theme's functions.php temporarily
global $wpdb;

// Test units with coordinates
$units = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}lgp_units WHERE latitude IS NOT NULL LIMIT 5");
error_log( 'Units with coords: ' . count( $units ) );

// Test tickets
$tickets = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}lgp_tickets WHERE urgency IS NOT NULL LIMIT 5");
error_log( 'Tickets with urgency: ' . count( $tickets ) );

// Test help guides
$guides = LGP_Help_Guide::get_all( [ 'type' => 'maintenance' ] );
error_log( 'Maintenance guides: ' . count( $guides ) );
?>
```

---

## Rollback Procedure

If something goes wrong:

### Quick Rollback

```bash
# Revert database
mysql -u wordpress_user -p wordpress_db < ~/backups/backup_TIMESTAMP.sql

# Revert code
git revert HEAD

# Clear caches
wp cache flush
```

### Verify Rollback

```bash
# Check database is restored
SELECT COUNT(*) FROM wp_lgp_units;
SELECT COUNT(*) FROM wp_lgp_tickets;

# Verify old code is back
grep "map-view" loungenie-portal.php  # Should NOT find matches
```

---

## Common Issues & Solutions

### Issue: "Coordinates not showing on map"

**Solution**: Verify coordinates exist in database:
```sql
SELECT COUNT(*) as total,
       COUNT(latitude) as with_lat,
       COUNT(longitude) as with_lon
FROM wp_lgp_units;
```

### Issue: "Filters not working"

**Solution**: Check help guides table structure:
```sql
DESCRIBE wp_lgp_help_guides;
```

Ensure `type` and `tags` columns exist with proper JSON format.

### Issue: "Map loads but no JavaScript"

**Solution**: Check browser console (F12):
```javascript
console.log( lgpMapData );  // Should show nonce and ajaxUrl
```

If undefined, ensure script was enqueued with proper nonce.

### Issue: "Database migration timeout"

**Solution**: For large datasets, run in batches:
```php
<?php
for ( $i = 0; $i < 1000; $i += 100 ) {
    $results = $wpdb->get_results( 
        $wpdb->prepare( 
            "SELECT * FROM {$wpdb->prefix}lgp_units LIMIT %d, 100", 
            $i 
        ) 
    );
    // Process batch
    usleep( 100000 );  // 0.1 second delay
}
?>
```

---

## Performance Optimization

After migration, optimize performance:

```sql
-- Optimize all tables
OPTIMIZE TABLE wp_lgp_units;
OPTIMIZE TABLE wp_lgp_tickets;
OPTIMIZE TABLE wp_lgp_help_guides;
OPTIMIZE TABLE wp_lgp_companies;

-- Check table statistics
ANALYZE TABLE wp_lgp_units;
ANALYZE TABLE wp_lgp_tickets;
ANALYZE TABLE wp_lgp_help_guides;
```

---

## Post-Migration Verification

### 7-Day Checklist

- [ ] Day 1: Monitor error logs
- [ ] Day 2: Check performance metrics
- [ ] Day 3: Verify all features working
- [ ] Day 4: Review user feedback
- [ ] Day 5: Check database growth
- [ ] Day 6: Optimize slow queries
- [ ] Day 7: Final validation and sign-off

### Monitor These Logs

```bash
# PHP errors
tail -f /var/log/php_errors.log

# WordPress debug log
tail -f /var/www/html/wp-content/debug.log

# Database slow query log
tail -f /var/log/mysql/slow.log

# Web server errors
tail -f /var/log/apache2/error.log  # or nginx/error.log
```

---

## Questions & Support

For migration issues:
1. Check `ENHANCEMENTS_SUMMARY.md` for feature details
2. Review `FEATURES.md` for quick reference
3. Check code comments in source files
4. Review test cases in `tests/MapViewTest.php`

---

**Version**: 1.0  
**Last Updated**: 2024-01-15
