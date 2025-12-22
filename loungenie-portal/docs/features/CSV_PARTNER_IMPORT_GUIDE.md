# CSV Partner Import - Complete Guide

## Overview

The CSV Partner Import feature enables Administrators and Support users to bulk upload partner company data via CSV files. This feature is WordPress.org compliant, shared-hosting safe, and includes comprehensive validation.

## Features

✅ **Role-Based Access**
- Admin: Full CSV upload access
- Support: CSV upload allowed (cannot modify system settings)
- Partner: No access to CSV import

✅ **Security & Validation**
- Nonce verification on all requests
- File type validation (.csv only)
- File size limit: 2MB maximum
- MIME type checking
- Email format validation
- Sanitized inputs, escaped outputs
- No shell execution or eval

✅ **Import Capabilities**
- Batch processing (50 rows per batch)
- Dry-run preview mode
- Row-by-row error reporting
- Create new companies
- Update existing companies (matched by email)
- Audit logging

✅ **WordPress.org Compliance**
- No Composer dependencies
- No external libraries
- Shared hosting safe
- WP-Cron compatible
- Proper i18n support

---

## Usage

### Accessing the Import Page

**For Admins:**
1. WordPress Admin → LounGenie → CSV Import

**For Support Users:**
1. WordPress Admin → LounGenie → CSV Import
2. Support users see informational notice about their permissions

### CSV Format Requirements

#### Required Columns (case-insensitive)

| Column | Description | Example |
|--------|-------------|---------|
| `company_name` | Company name | "Acme Corporation" |
| `company_email` | Company email address | "contact@acme.com" |
| `status` | active or inactive | "active" |
| `primary_contact_name` | Primary contact full name | "John Smith" |
| `primary_contact_title` | Primary contact job title | "Operations Manager" |
| `primary_contact_email` | Primary contact email | "john.smith@acme.com" |
| `primary_contact_phone` | Primary contact phone | "555-0100" |

#### Optional Columns (case-insensitive)

| Column | Description | Example |
|--------|-------------|---------|
| `secondary_contact_name` | Secondary contact full name | "Jane Doe" |
| `secondary_contact_title` | Secondary contact job title | "Assistant Manager" |
| `secondary_contact_email` | Secondary contact email | "jane.doe@acme.com" |
| `secondary_contact_phone` | Secondary contact phone | "555-0101" |

**Notes:**
- Column names are case-insensitive
- Whitespace is automatically trimmed
- Secondary contact fields are optional
- If any secondary contact field is provided, Support users must provide all secondary fields
- Admins can provide partial secondary contact information

### Sample CSV Template

Download the sample template from the admin page or use this format:

```csv
company_name,company_email,status,primary_contact_name,primary_contact_title,primary_contact_email,primary_contact_phone,secondary_contact_name,secondary_contact_title,secondary_contact_email,secondary_contact_phone
"Acme Corporation","contact@acme.com","active","John Smith","Operations Manager","john.smith@acme.com","555-0100","Jane Doe","Assistant Manager","jane.doe@acme.com","555-0101"
"Tech Solutions Inc","info@techsolutions.com","active","Mike Johnson","Director","mike.j@techsolutions.com","555-0200","","","",""
```

### Upload Process

#### Step 1: Prepare CSV File
1. Create CSV with required columns
2. Validate data completeness
3. Ensure file size < 2MB

#### Step 2: Upload
1. Click "Choose CSV File" button
2. Select your CSV file
3. Optionally enable "Dry Run" for preview
4. Click "Upload and Process"

#### Step 3: Review Results
- **Total Rows:** Number of rows processed
- **Success Count:** Successfully imported companies
- **Error Count:** Failed rows with reasons

---

## Validation Rules

### Company Data

✓ **Company Name:** Required, cannot be empty  
✓ **Company Email:** Required, valid email format  
✓ **Status:** Required, must be "active" or "inactive"

### Primary Contact (Required)

✓ **Name:** Required, cannot be empty  
✓ **Title:** Required, cannot be empty  
✓ **Email:** Required, valid email format  
✓ **Phone:** Required, cannot be empty

### Secondary Contact (Optional)

✓ **Support Users:** If any secondary field provided, all must be complete  
✓ **Admins:** Can provide partial secondary contact information  
✓ **Email:** If provided, must be valid email format

### File Validation

✓ **File Size:** Maximum 2MB  
✓ **File Type:** .csv extension only  
✓ **MIME Type:** text/csv, text/plain, or application/csv  
✓ **Required Columns:** All required columns must be present  
✓ **Empty File:** Must contain at least one data row

---

## Import Behavior

### Creating New Companies

When a company email does not exist in the database:
- New company record created
- Primary contact information saved
- Secondary contact saved (if provided)
- Status set to provided value
- `created_at` timestamp recorded

### Updating Existing Companies

When a company email already exists:
- Existing company record updated
- Contact information updated
- Status updated if changed
- `updated_at` timestamp recorded
- **Important:** Match is based on `company_email` field

### Dry Run Mode

Enable "Dry Run" to:
- Preview import without saving data
- Validate all rows
- See what would be imported/updated
- Identify errors before actual import
- No database changes are made

---

## Security Features

### Authentication & Authorization

✅ **Nonce Verification:** All requests verify WordPress nonces  
✅ **Capability Check:** `manage_options` OR `lgp_manage_companies`  
✅ **REST API Authentication:** X-WP-Nonce header required

### Input Validation

✅ **Sanitization:** All text fields sanitized with `sanitize_text_field()`  
✅ **Email Validation:** Email fields validated with `is_email()` and `sanitize_email()`  
✅ **Type Checking:** Status values validated against whitelist  
✅ **SQL Safety:** All queries use `$wpdb->prepare()`

### File Security

✅ **Extension Check:** Only .csv files allowed  
✅ **MIME Verification:** File content verified with `finfo_file()`  
✅ **Size Limit:** 2MB maximum enforced  
✅ **Temporary Files:** Processed in PHP temp directory, auto-cleaned

### Output Security

✅ **Escaped Output:** All user data escaped in templates  
✅ **XSS Prevention:** JavaScript escapes all dynamic content  
✅ **Error Messages:** Generic messages, no sensitive data exposure

---

## Performance & Hosting

### Shared Hosting Safe

✅ **No Shell Execution:** Pure PHP processing, no `exec()`, `shell_exec()`, or `system()`  
✅ **Memory Efficient:** Streams CSV row-by-row, not entire file  
✅ **Batch Processing:** 50 rows per batch to avoid timeouts  
✅ **No Background Workers:** All processing in HTTP request (no daemons)

### Resource Limits

| Resource | Limit | Reason |
|----------|-------|--------|
| File Size | 2MB | Prevent memory exhaustion |
| Batch Size | 50 rows | Avoid PHP timeout on shared hosting |
| Execution Time | ~30s | Standard PHP timeout compatibility |
| Memory | Standard PHP limits | No memory_limit overrides |

### WordPress.org Compliance

✅ **No Composer Runtime:** No vendor dependencies  
✅ **No External Libs:** Pure WordPress/PHP  
✅ **Standard APIs:** Uses WordPress HTTP, database, filesystem APIs  
✅ **Proper i18n:** All strings translatable with `__()`, `_e()`, `_x()`  
✅ **Clean Deactivation:** No data left behind  
✅ **No Telemetry:** No external API calls during import

---

## Error Handling

### Row-Level Errors

Each failed row displays:
- **Row Number:** CSV line number
- **Company Name:** Attempted company name
- **Error Message:** Specific validation failure

### Common Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| "Missing required columns" | CSV header missing required columns | Add all required columns to CSV |
| "Company name is required" | Empty company_name field | Provide company name |
| "Invalid company email format" | Malformed email address | Fix email format |
| "Status must be 'active' or 'inactive'" | Invalid status value | Use "active" or "inactive" |
| "Primary contact email is required" | Missing primary contact email | Provide primary contact email |
| "If secondary contact provided, all fields required" | Partial secondary contact (Support only) | Complete all secondary fields or remove |
| "Database insert failed" | Database error | Check database logs |
| "File too large" | File > 2MB | Split into smaller files |

### File-Level Errors

| Error | Cause | Solution |
|-------|-------|----------|
| "Invalid security token" | Nonce verification failed | Refresh page and try again |
| "No file uploaded" | File input empty | Select a CSV file |
| "Only CSV files allowed" | Wrong file extension | Use .csv file |
| "Invalid file type" | Wrong MIME type | Ensure file is valid CSV |
| "CSV file is empty" | No header row | Add header row to CSV |
| "No data rows" | No data after header | Add at least one data row |

---

## REST API Reference

### Upload Endpoint

**URL:** `POST /wp-json/lgp/v1/csv-import/partners`

**Headers:**
```
Content-Type: multipart/form-data
X-WP-Nonce: {wp_rest_nonce}
```

**Body:**
```
csv_file: (binary file data)
dry_run: 1 (optional, for preview)
```

**Response (Success):**
```json
{
  "total": 5,
  "success": 4,
  "errors": 1,
  "dry_run": false,
  "imported": [
    {
      "name": "Acme Corporation",
      "email": "contact@acme.com",
      "status": "active",
      "primary_contact": "John Smith",
      "action": "created"
    }
  ],
  "failed": [
    {
      "line": 3,
      "company": "Invalid Co",
      "error": "Invalid company email format"
    }
  ]
}
```

**Response (Error):**
```json
{
  "error": "Missing required columns: company_name, company_email"
}
```

### Preview Endpoint

**URL:** `POST /wp-json/lgp/v1/csv-import/preview`

Same as upload endpoint but always runs in dry-run mode.

---

## Audit Logging

All imports are logged via `LGP_Logger` if available:

**Event Type:** `csv_partner_import`

**Logged Data:**
- User ID
- Company ID (if updated)
- Company name
- Action (created/updated)
- Dry run status

**Query Audit Logs:**
```sql
SELECT * FROM wp_lgp_audit_log 
WHERE action = 'csv_partner_import' 
ORDER BY created_at DESC;
```

---

## Troubleshooting

### Import Not Working

**Check:**
1. User has Admin or Support role
2. User has `lgp_manage_companies` capability
3. JavaScript enabled in browser
4. Browser console for errors
5. WordPress debug log for PHP errors

**Debug Mode:**
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### File Upload Fails

**Check:**
1. PHP `upload_max_filesize` setting (must be > 2MB)
2. PHP `post_max_size` setting (must be > 2MB)
3. WordPress `wp_max_upload_size()` value
4. Server disk space available
5. PHP temp directory writable

### Partial Import (Some Rows Fail)

**Solution:**
1. Review error details table
2. Fix failed rows in CSV
3. Re-upload only failed rows
4. Use dry-run to validate fixes

### Slow Performance

**Causes:**
- Large CSV files (>1000 rows)
- Shared hosting resource limits
- Database locks

**Solutions:**
- Split CSV into smaller batches (500 rows each)
- Import during off-peak hours
- Increase PHP `max_execution_time` if possible

---

## Development & Customization

### Extending Validation

Add custom validation in `validate_row()` method:

```php
// In class-lgp-csv-partner-import.php
private static function validate_row( $row ) {
    // Existing validation...
    
    // Custom validation
    if ( ! empty( $row['company_name'] ) ) {
        if ( strlen( $row['company_name'] ) < 3 ) {
            return new WP_Error( 'name_too_short', __( 'Company name must be at least 3 characters', 'loungenie-portal' ) );
        }
    }
    
    return true;
}
```

### Custom Column Mapping

Modify column constants:

```php
// Add custom required columns
const REQUIRED_COLUMNS = array(
    'company_name',
    'company_email',
    'status',
    'industry', // Custom field
    // ...
);
```

### Hooks & Filters

Future enhancement opportunities:

```php
// Before import (validation override)
apply_filters( 'lgp_csv_import_validate_row', $is_valid, $row, $line_number );

// After successful import
do_action( 'lgp_csv_partner_imported', $company_id, $row_data, $action );

// Modify batch size
apply_filters( 'lgp_csv_import_batch_size', 50 );
```

---

## Testing

### Manual Testing

1. **Valid Import:**
   - Use sample-partner-import.csv
   - Upload without dry-run
   - Verify all 5 companies created

2. **Dry Run:**
   - Enable dry-run checkbox
   - Upload sample CSV
   - Verify no database changes

3. **Error Handling:**
   - Create CSV with missing email
   - Upload and verify error message
   - Fix CSV and re-upload

4. **Update Existing:**
   - Import sample CSV twice
   - Second import should update, not duplicate

5. **Role Permissions:**
   - Test as Admin (full access)
   - Test as Support (limited)
   - Test as Partner (no access)

### Automated Testing

```php
// Example PHPUnit test
public function test_csv_validation() {
    $row = array(
        'company_name' => 'Test Co',
        'company_email' => 'invalid-email',
        'status' => 'active',
        // ...
    );
    
    $result = LGP_CSV_Partner_Import::validate_row( $row );
    
    $this->assertWPError( $result );
    $this->assertEquals( 'invalid_email', $result->get_error_code() );
}
```

---

## FAQ

**Q: Can Partners upload CSV files?**  
A: No, only Admins and Support users have CSV import access.

**Q: What happens if I upload the same CSV twice?**  
A: Existing companies (matched by email) will be updated. No duplicates created.

**Q: Can I import without secondary contact?**  
A: Yes, secondary contact is optional. Leave those columns empty.

**Q: Is there a row limit?**  
A: File size is limited to 2MB (~2000-5000 rows depending on data). Split larger files.

**Q: Can I undo an import?**  
A: No automated undo. Use dry-run to preview before importing.

**Q: Are passwords created for companies?**  
A: No, CSV import does NOT create WordPress users. It only creates company records.

**Q: Can Support users override validation?**  
A: No, only Admins can override certain validations (e.g., partial secondary contact).

**Q: Is WP-CLI supported?**  
A: Not yet. Use the admin interface or REST API.

---

## Changelog

### Version 1.0.0 (December 2025)
- ✅ Initial release
- ✅ Admin and Support role support
- ✅ Dry-run preview mode
- ✅ Row-by-row error reporting
- ✅ Sample CSV template download
- ✅ Audit logging integration
- ✅ WordPress.org compliance verified
- ✅ Shared hosting compatibility tested

---

## Support

For issues or questions:

1. Check WordPress debug log
2. Review error messages in import results
3. Validate CSV format against requirements
4. Test with sample CSV template
5. Contact development team

---

**Plugin:** LounGenie Portal v1.8.1  
**Feature:** CSV Partner Import  
**Last Updated:** December 22, 2025  
**License:** GPLv2 or later
