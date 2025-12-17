# Database Performance Optimization Guide

This guide provides database index recommendations and performance best practices for the LounGenie Portal custom tables on shared hosting environments.

## Current Index Coverage

The portal's database schema includes strategic indexes on commonly filtered and joined columns. Below is the current index coverage per table.

### lgp_companies
```sql
PRIMARY KEY (id)
KEY management_company_id (management_company_id)
KEY venue_type (venue_type)
```

**Status:** ✅ Well-indexed  
**Reasoning:** Primary key for lookups, foreign key indexed, common filter (venue_type) indexed.

### lgp_units
```sql
PRIMARY KEY (id)
KEY company_id (company_id)
KEY management_company_id (management_company_id)
KEY status (status)
KEY color_tag (color_tag)
KEY season (season)
KEY venue_type (venue_type)
KEY lock_brand (lock_brand)
```

**Status:** ✅ Well-indexed  
**Reasoning:** All common filters (status, season, color_tag, venue_type, lock_brand) are indexed. Foreign keys (company_id, management_company_id) are indexed for joins.

### lgp_service_requests
```sql
PRIMARY KEY (id)
KEY company_id (company_id)
KEY unit_id (unit_id)
KEY status (status)
KEY request_type (request_type)
```

**Status:** ✅ Well-indexed  
**Reasoning:** Foreign keys and common filters (status, request_type) are indexed.

### lgp_tickets
```sql
PRIMARY KEY (id)
KEY service_request_id (service_request_id)
KEY status (status)
```

**Status:** ✅ Well-indexed  
**Reasoning:** Foreign key and status filter are indexed.

### lgp_gateways
```sql
PRIMARY KEY (id)
KEY company_id (company_id)
KEY call_button (call_button)
KEY channel_number (channel_number)
```

**Status:** ✅ Well-indexed  
**Reasoning:** Foreign key and common filters are indexed.

### lgp_training_videos
```sql
PRIMARY KEY (id)
KEY category (category)
KEY created_by (created_by)
```

**Status:** ✅ Well-indexed  
**Reasoning:** Category filter and author tracking are indexed.

### lgp_ticket_attachments
```sql
PRIMARY KEY (id)
KEY ticket_id (ticket_id)
KEY uploaded_by (uploaded_by)
```

**Status:** ✅ Well-indexed  
**Reasoning:** Foreign keys are indexed for quick lookups.

### lgp_service_notes
```sql
PRIMARY KEY (id)
KEY company_id (company_id)
KEY unit_id (unit_id)
KEY user_id (user_id)
KEY service_date (service_date)
KEY service_type (service_type)
```

**Status:** ✅ Well-indexed  
**Reasoning:** All foreign keys and common filters (service_date, service_type) are indexed.

### lgp_audit_log
```sql
PRIMARY KEY (id)
KEY user_id (user_id)
KEY action (action)
KEY company_id (company_id)
KEY created_at (created_at)
```

**Status:** ✅ Well-indexed  
**Reasoning:** All common filters for audit queries are indexed.

## Optional Composite Indexes

For frequently used query patterns, consider adding composite indexes. These should be added only if performance profiling shows slow queries.

### Recommended Composite Indexes (if needed)

#### lgp_service_requests
```sql
-- For queries filtering by company and status together
ALTER TABLE wp_lgp_service_requests
ADD KEY company_status_idx (company_id, status);

-- For queries filtering by company and request type together
ALTER TABLE wp_lgp_service_requests
ADD KEY company_type_idx (company_id, request_type);
```

**When to add:** If support dashboard or partner views show slow queries when listing requests by company with status/type filters.

#### lgp_tickets
```sql
-- For queries joining tickets with service requests and filtering by status
ALTER TABLE wp_lgp_tickets
ADD KEY request_status_idx (service_request_id, status);

-- For queries ordering by creation date with status filter
ALTER TABLE wp_lgp_tickets
ADD KEY status_created_idx (status, created_at);
```

**When to add:** If ticket list views or dashboard queries are slow when filtering by status and ordering by date.

#### lgp_service_notes
```sql
-- For queries filtering by company and date range together
ALTER TABLE wp_lgp_service_notes
ADD KEY company_date_idx (company_id, service_date);
```

**When to add:** If service history reports filtered by company and date range are slow.

#### lgp_audit_log
```sql
-- For queries filtering by action and date range together
ALTER TABLE wp_lgp_audit_log
ADD KEY action_date_idx (action, created_at);

-- For queries filtering by company and date range together
ALTER TABLE wp_lgp_audit_log
ADD KEY company_date_idx (company_id, created_at);
```

**When to add:** If audit reports filtered by action or company over specific date ranges are slow.

## Query Optimization Best Practices

### 1. Use EXPLAIN to analyze slow queries

Before adding indexes, profile your queries:

```sql
EXPLAIN SELECT * FROM wp_lgp_service_requests 
WHERE company_id = 123 AND status = 'pending';
```

Look for:
- `type: ALL` (full table scan - bad)
- `type: index` or `type: ref` (using index - good)
- `rows: high number` (scanning many rows - consider indexing)

### 2. Avoid SELECT *

Instead of:
```php
$wpdb->get_results("SELECT * FROM {$table}");
```

Use:
```php
$wpdb->get_results("SELECT id, name, status FROM {$table}");
```

**Why:** Reduces memory usage and network transfer, especially for tables with TEXT/LONGTEXT columns.

### 3. Use prepared statements for user input

Always use `$wpdb->prepare()`:
```php
$wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} WHERE company_id = %d AND status = %s",
        $company_id,
        $status
    )
);
```

### 4. Paginate large result sets

For list views, use LIMIT and OFFSET:
```php
$per_page = 20;
$page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$offset = ($page - 1) * $per_page;

$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);
```

### 5. Cache expensive queries

Use transients for data that doesn't change frequently:
```php
$cache_key = 'lgp_stats_' . $company_id;
$stats = get_transient($cache_key);

if (false === $stats) {
    $stats = $wpdb->get_results(/* expensive query */);
    set_transient($cache_key, $stats, 15 * MINUTE_IN_SECONDS);
}
```

### 6. Avoid N+1 queries

Bad (N+1 problem):
```php
foreach ($tickets as $ticket) {
    $company = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM companies WHERE id = %d", $ticket->company_id)
    );
}
```

Good (single JOIN query):
```php
$tickets = $wpdb->get_results("
    SELECT t.*, c.name as company_name
    FROM tickets t
    LEFT JOIN companies c ON t.company_id = c.id
");
```

## Performance Monitoring Tools

### 1. Query Monitor Plugin

Install Query Monitor for development:
```bash
wp plugin install query-monitor --activate
```

Features:
- Shows all database queries per page load
- Identifies slow queries (>0.05s)
- Highlights duplicate queries
- Shows query execution order

### 2. MySQL Slow Query Log

If you have access to MySQL configuration, enable slow query logging:

```ini
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 1
```

### 3. New Relic / Application Performance Monitoring

For production monitoring, consider APM tools:
- New Relic (has WordPress plugin)
- Scout APM
- Blackfire

## Shared Hosting Limitations

### Typical MySQL Limits

Most shared hosts impose these limits:
- Max execution time: 30-60 seconds
- Max connections: 15-30 concurrent
- Max query size: 16MB-32MB
- InnoDB buffer pool: Limited (shared)

### Optimization Strategies

1. **Keep queries simple:** Complex JOINs across 4+ tables may timeout
2. **Batch operations:** Process large datasets in chunks of 100-500 rows
3. **Use indexes wisely:** Too many indexes slow down INSERT/UPDATE
4. **Clean up old data:** Archive or delete old audit logs periodically
5. **Avoid full table scans:** Always filter by indexed columns

## Table Maintenance

### Optimize tables monthly

```sql
OPTIMIZE TABLE wp_lgp_tickets;
OPTIMIZE TABLE wp_lgp_service_requests;
OPTIMIZE TABLE wp_lgp_audit_log;
```

Or via WP-CLI:
```bash
wp db optimize
```

### Check table sizes

Monitor growth of tables:
```sql
SELECT 
    table_name AS "Table",
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
AND table_name LIKE 'wp_lgp_%'
ORDER BY (data_length + index_length) DESC;
```

### Archive old data

For audit logs and service notes, consider archiving records older than 2 years:
```php
// Archive audit logs older than 2 years
$wpdb->query(
    "DELETE FROM {$wpdb->prefix}lgp_audit_log 
     WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR)
     LIMIT 1000"
);
```

Run this periodically via WP-Cron or manually.

## Benchmarking Your Queries

### Test query performance

```php
$start = microtime(true);
$results = $wpdb->get_results(/* your query */);
$time = microtime(true) - $start;

if ($time > 0.1) {
    error_log("Slow query: " . $time . "s");
}
```

### Expected performance targets

- Simple SELECT by PRIMARY KEY: < 0.001s
- SELECT with indexed WHERE clause: < 0.01s
- Complex JOIN queries: < 0.05s
- Paginated list views: < 0.1s
- Dashboard aggregations: < 0.2s

## When to Add Indexes

✅ **Add indexes when:**
- Query EXPLAIN shows full table scan (type: ALL)
- Slow query log shows repeated slow queries
- Page load times > 2s due to specific queries
- Dashboard views take > 0.5s to render

❌ **Avoid indexes when:**
- Table has < 1000 rows (marginal benefit)
- Column has low cardinality (few unique values)
- Column is rarely used in WHERE/JOIN clauses
- Table has frequent INSERT/UPDATE operations

## Summary

The portal's current database schema is **well-optimized** for shared hosting with appropriate indexes on all foreign keys and commonly filtered columns. No immediate index changes are required.

**Action items:**
1. ✅ Current indexes are sufficient for typical workloads
2. Monitor query performance using Query Monitor plugin
3. Add composite indexes only if profiling shows specific slow queries
4. Optimize tables monthly
5. Archive old audit logs (2+ years) quarterly

**Performance tips:**
- Use transient caching for expensive queries (15-min TTL)
- Paginate list views (20-50 items per page)
- Avoid SELECT * in production queries
- Profile before optimizing

---

**Last Updated:** December 17, 2025  
**Portal Version:** 1.6.0+
