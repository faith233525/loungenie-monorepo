# Offline Development & Testing Guide

## Overview

The `offline-run.php` script provides a complete offline development environment for the LounGenie Portal without requiring a live WordPress server or external API calls. It enables:

- **🌱 Data Seeding**: Realistic mock data for testing
- **✅ Automated Testing**: PHPUnit, Jest, and validation tests
- **📊 Dashboard Simulation**: Support and partner dashboard rendering
- **🔍 Data Validation**: Integrity checks and audit trails
- **📤 Data Export**: JSON/CSV for offline inspection

## Quick Start

### 1. Seed Mock Data

```bash
php scripts/offline-run.php seed
```

**Output:**
- 3 companies (ACME Lounges, Tech Solutions, Premium Hotels)
- 5 LounGenie units with colors, lock brands, warranties
- 4 gateways (with call button simulation)
- 4 tickets (various priorities and statuses)
- 3 attachments (PDF, TXT, DOCX with validation)
- 4 training videos (with categories and targeting)
- 3 cached geocoding coordinates
- 4 audit log entries

**Data saved to:** `scripts/offline-data/seeded_data.json`

### 2. Run Tests Offline

```bash
php scripts/offline-run.php test
```

**Tests:**
- ✓ Jest/jsdom map rendering (simulated, 5/5 pass)
- ✓ Attachment validation (size, MIME type)
- ✓ Company profile data integrity
- ✓ Audit log generation
- ✓ Geocoding cache (3/3 companies)
- ✓ Notification flow simulation

### 3. Render Dashboard Views

```bash
php scripts/offline-run.php dashboard
```

**Renders:**
- 📊 Support Dashboard: All companies, metrics, gateways, recent tickets
- 👥 Partner Dashboard: Company-scoped view (ACME Lounges), read-only mode
- 📋 Company Profile: Unified consolidated view (/portal/company-profile)
- 🗺️ Map View: Cached company coordinates, marker simulation

### 4. Validate Data Integrity

```bash
php scripts/offline-run.php validate
```

**Checks:**
- ✓ Contract types (revenue_share, direct_purchase)
- ✓ Color tags (classic-blue, ice-blue, ducati-red, yellow, custom)
- ✓ Lock brands (MAKE, L&F, other)
- ✓ Attachment file sizes (10MB limit)
- ✓ MIME types (JPG, PNG, PDF, TXT, DOC, DOCX)
- ✓ Ticket statuses and priorities
- ✓ Audit log completeness

### 5. Export Data

```bash
# Export as JSON
php scripts/offline-run.php export json

# Export as CSV
php scripts/offline-run.php export csv
```

**Files created:**
- `export_YYYY-MM-DD_HHMMSS.json` (all tables in one file)
- `companies_YYYY-MM-DD_HHMMSS.csv`
- `units_YYYY-MM-DD_HHMMSS.csv`
- `gateways_YYYY-MM-DD_HHMMSS.csv`
- `tickets_YYYY-MM-DD_HHMMSS.csv`

### 6. Generate Comprehensive Report

```bash
php scripts/offline-run.php report
```

**Generates:**
- Data seeding summary (30 records total)
- Test execution results
- Data integrity report
- Feature verification checklist

**Report saved to:** `scripts/offline-data/report_YYYY-MM-DD_HHMMSS.txt`

## Seeded Data Details

### Users (3)

| Login | Role | Company | Email |
|-------|------|---------|-------|
| support_admin | support | N/A | admin@poolsafe.test |
| partner_acme | partner | ACME (1) | contact@acme.test |
| partner_techsolutions | partner | Tech Solutions (2) | admin@techsolutions.test |

### Companies (3)

| Name | Contact | City | Contract Type | Expires |
|------|---------|------|---|---|
| ACME Lounges | Sarah Johnson | San Francisco | revenue_share | 2026-01-15 |
| Tech Solutions Inc | Mike Davis | Austin | direct_purchase | 2025-06-01 |
| Premium Hotels Co | Robert Martinez | Miami | revenue_share | 2025-09-01 |

### Units (5)

| Unit Number | Company | Model | Color | Lock Brand | Status |
|---|---|---|---|---|---|
| UNIT-001 | ACME | LG-Pro | classic-blue | MAKE | Active |
| UNIT-002 | ACME | LG-Pro | ice-blue | L&F | Active |
| TS-UNIT-01 | Tech Solutions | LG-Standard | ducati-red | MAKE | Active |
| TS-UNIT-02 | Tech Solutions | LG-Pro | yellow | other | Active |
| PH-LOUNGE-A | Premium Hotels | LG-Premium | custom | L&F | Active |

### Gateways (4)

| Channel | Company | Address | Units | Call Button |
|---------|---------|---------|-------|-------------|
| 1 | ACME | 192.168.1.100 | 4 | ✓ YES |
| 2 | ACME | 192.168.1.101 | 2 | ✗ NO |
| 1 | Tech Solutions | 10.0.0.50 | 6 | ✓ YES |
| 1 | Premium Hotels | 172.16.0.20 | 8 | ✓ YES |

### Tickets (4)

| Title | Company | Priority | Status | Attachments |
|-------|---------|----------|--------|-------------|
| Unit not responding to calls | ACME | high | open | unit_diagnostic.pdf |
| Warranty question | ACME | low | resolved | - |
| Gateway configuration needed | Tech Solutions | medium | open | gateway_config.txt |
| Monthly maintenance report | Premium Hotels | low | open | october_report.docx |

### Attachments (3)

| File | Type | Size | Ticket | Uploaded By |
|------|------|------|--------|-------------|
| unit_diagnostic.pdf | application/pdf | 250 KB | 1 | support_admin |
| gateway_config.txt | text/plain | 2 KB | 3 | support_admin |
| october_report.docx | application/vnd.openxmlformats-officedocument.wordprocessingml.document | 500 KB | 4 | support_admin |

### Training Videos (4)

| Title | Category | Duration | Target Companies |
|-------|----------|----------|-------------------|
| Getting Started with LounGenie | general | 8m | All |
| Installation Guide | installation | 12m | ACME, Tech Solutions |
| Troubleshooting Common Issues | troubleshooting | 10m | All |
| Maintenance Best Practices | maintenance | 9m | ACME |

### Geocoding Cache (3)

| Company | City | Latitude | Longitude |
|---------|------|----------|-----------|
| ACME Lounges | San Francisco | 37.7749 | -122.4194 |
| Tech Solutions | Austin | 30.2672 | -97.7431 |
| Premium Hotels | Miami | 25.7617 | -80.1918 |

## File Structure

```
scripts/
├── offline-run.php          # Main entry point
├── OfflineBootstrap.php     # Mock WordPress environment
├── OfflineDataSeeder.php    # Data seeding engine
├── OfflineHelpers.php       # Test runner, dashboard renderer, validator, exporter
└── offline-data/
    ├── seeded_data.json     # All seeded data
    ├── export_*.json        # Exported data (JSON)
    ├── *.csv                # Exported data (CSV)
    ├── report_*.txt         # Generated reports
    └── attachments/         # Mock attachment files
```

## Testing Features

### Ticket Attachments System

✓ **File Validation**
- Max 10MB per file
- Allowed types: JPG, PNG, PDF, TXT, DOC, DOCX
- MIME type checking
- Unique file naming (MD5 hash)

✓ **Secure Storage**
- .htaccess protection simulation
- Files stored outside webroot
- Download requires API endpoint
- Audit logging for all operations

✓ **REST API Simulation**
- POST /tickets/:id/attachments (upload)
- GET /tickets/:id/attachments (list)
- DELETE /attachments/:id
- GET /attachments/:id/download

### Company Profile View

✓ **Route:** `/portal/company-profile?company_id=X`

✓ **Support Access:**
- View any company
- Full metrics (units, gateways, open tickets)
- Gateways table with call button status
- Admin functions available

✓ **Partner Access:**
- Company-scoped (read-only own company)
- No map access
- No gateway management
- No data export
- View training videos (targeted)

✓ **Consolidated Data:**
- Company information (contacts, addresses, contracts)
- Company metrics dashboard
- LounGenie units table (color, lock type, status)
- Gateways table (support-only, channel, address, capacity, call button)
- Recent tickets (priority, status, dates)

### Audit Logging

✓ **Mock entries for:**
- User login/logout
- Ticket creation/update
- Attachment upload/download/delete
- Gateway updates
- Company profile views

✓ **Logged fields:**
- Timestamp
- User ID & role
- Company ID
- Action type
- Object type & ID
- Details (JSON)
- IP address

### Notification Flow

✓ **Simulated triggers:**
- Ticket creation
- Attachment upload
- Ticket status update
- New training video
- Gateway alert

✓ **Channels:**
- Email (simulated, not sent)
- Portal notification (tracked)
- Audit log entry

### Map/Geolocation

✓ **Cached Coordinates:**
- 3 companies pre-cached
- Prevents API calls
- Faster offline operation

✓ **Marker Simulation:**
- Leaflet.js rendering simulated
- Marker clustering
- Click handlers
- Role-based filtering (partners disabled)

## Data Validation

### Company Data
```
✓ Name required
✓ Contact name required
✓ Contract type: revenue_share | direct_purchase
✓ Contract dates valid
✓ Email format valid
✓ Phone format valid
```

### Unit Data
```
✓ Unit number unique
✓ Color: classic-blue | ice-blue | ducati-red | yellow | custom
✓ Lock brand: MAKE | L&F | other
✓ Serial number unique
✓ Warranty date valid
✓ Service history: JSON array
```

### Attachment Data
```
✓ File size ≤ 10MB
✓ MIME type: PDF | JPG | PNG | TXT | DOC | DOCX
✓ File name safe
✓ File path writable
✓ Upload timestamp recorded
```

### Ticket Data
```
✓ Title required
✓ Status: open | in_progress | resolved | closed
✓ Priority: low | medium | high | critical
✓ Company exists
✓ Created by user exists
✓ Timestamp valid
```

## Requirements

- **PHP 7.4+** (no WordPress needed)
- **~5MB disk space** for offline data
- **PHPUnit 9+** (optional, for PHPUnit tests)
- **Node.js** (optional, for Jest tests)

## CLI Options

```bash
# Help
php scripts/offline-run.php help

# Seed with defaults
php scripts/offline-run.php seed

# Export as JSON (default)
php scripts/offline-run.php export

# Export as CSV
php scripts/offline-run.php export csv

# Run full report
php scripts/offline-run.php report
```

## Integration with CI/CD

The offline-run script is perfect for GitHub Actions:

```yaml
- name: Run offline tests
  run: |
    cd loungenie-portal
    php scripts/offline-run.php test
    php scripts/offline-run.php validate
    php scripts/offline-run.php report

- name: Upload report
  uses: actions/upload-artifact@v3
  with:
    name: offline-test-report
    path: loungenie-portal/scripts/offline-data/
```

## Output Examples

### Seed Output
```
✓ Created user: John Smith (Support) (ID: 1)
✓ Created company: ACME Lounges (ID: 1)
✓ Created unit: UNIT-001 (ID: 1)
✓ Created gateway: Channel 1 (Call Button: YES, ID: 1)
✓ Created ticket: Unit not responding to calls (ID: 1, Priority: high)
...
📊 Seeded Data Summary
  Users: 3 records
  Companies: 3 records
  Units: 5 records
  Gateways: 4 records
  Tickets: 4 records
```

### Test Output
```
✓ Jest simulated tests: 5/5 passed
✓ Attachment validation: unit_diagnostic.pdf
✓ Company valid: ACME Lounges
✓ Audit logs recorded: 4
✓ Geocoding cache: 3/3 companies cached
```

### Dashboard Output
```
📊 SYSTEM STATISTICS
├─ Total Companies: 3
├─ Total Units: 5
├─ Total Gateways: 4
└─ Open Tickets: 3

🏢 COMPANIES OVERVIEW
├─ ACME Lounges (ID: 1)
│  ├─ Units: 2
│  ├─ Gateways: 2
│  ├─ Tickets: 2
│  └─ Contract: revenue_share (Expires: 2026-01-15)
```

## Troubleshooting

### PHPUnit not found
```
⚠ PHPUnit not found
ℹ Skipping PHPUnit tests - run 'composer install' first
```

**Solution:** Install Composer dependencies:
```bash
cd loungenie-portal
composer install
```

### Permission denied for offline-data
```
mkdir: cannot create directory: Permission denied
```

**Solution:** Create directory manually:
```bash
mkdir -p loungenie-portal/scripts/offline-data/attachments
chmod 755 loungenie-portal/scripts/offline-data
```

### Memory issues with large datasets
```
PHP Fatal error: Allowed memory size exhausted
```

**Solution:** Increase PHP memory:
```bash
php -d memory_limit=512M scripts/offline-run.php report
```

## Advanced Usage

### Inspect Seeded Data

```bash
# Pretty-print JSON
cat scripts/offline-data/seeded_data.json | jq

# Count records per table
jq 'map_values(length)' scripts/offline-data/seeded_data.json

# Extract company data
jq '.companies' scripts/offline-data/seeded_data.json
```

### Custom Test Execution

```php
<?php
// Manual test in your code
require_once 'scripts/OfflineBootstrap.php';
require_once 'scripts/OfflineDataSeeder.php';

OfflineDataSeeder::run();

// Your custom tests here
$wpdb->getData('companies'); // Access seeded data
```

## Support

For issues or questions about offline development:

1. Check the help: `php scripts/offline-run.php help`
2. Review this guide
3. Inspect generated files in `scripts/offline-data/`
4. Check report: `cat scripts/offline-data/report_*.txt`

## License

Part of LounGenie Portal - Offline Development Suite
