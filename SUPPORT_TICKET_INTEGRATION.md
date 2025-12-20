# Support Ticket Form - Integration Quick Start

## File Structure
```
loungenie-portal/
├── includes/
│   └── class-lgp-support-ticket-handler.php    # Backend form processor
├── templates/
│   └── support-ticket-form.php                 # HTML form template
└── assets/
    ├── js/
    │   └── support-ticket-form.js              # Form validation & upload
    └── css/
        └── support-ticket-form.css             # Form styling
```

## Integration Steps

### 1. Register and Enqueue Assets
Add to `class-lgp-assets.php`:

```php
// Register form scripts
wp_register_script(
    'lgp-support-ticket-form',
    LGP_ASSETS_URL . 'js/support-ticket-form.js',
    array(),
    LGP_VERSION,
    true
);

// Register form styles
wp_register_style(
    'lgp-support-ticket-form',
    LGP_ASSETS_URL . 'css/support-ticket-form.css',
    array(),
    LGP_VERSION
);

// Enqueue on support page
if ( is_page( 'support' ) ) {
    wp_enqueue_script( 'lgp-support-ticket-form' );
    wp_enqueue_style( 'lgp-support-ticket-form' );
}
```

### 2. Add Page Template
Create a new page template or use existing shortcode:

```php
// In page template or shortcode handler
add_shortcode( 'lgp_support_ticket', function() {
    ob_start();
    include LGP_PLUGIN_DIR . '/templates/support-ticket-form.php';
    return ob_get_clean();
});
```

### 3. Load the Handler Class
Add to plugin main file:

```php
// Load support ticket handler
require_once LGP_PLUGIN_DIR . '/includes/class-lgp-support-ticket-handler.php';

// Initialize handler
LGP_Support_Ticket_Handler::init();
```

### 4. Create Upload Directory
Run once during plugin activation:

```php
public static function create_upload_dirs() {
    $upload_dir = wp_upload_dir();
    $ticket_dir = $upload_dir['basedir'] . '/lgp-tickets';
    
    if ( ! is_dir( $ticket_dir ) ) {
        wp_mkdir_p( $ticket_dir );
        
        // Add .htaccess for security
        $htaccess = $ticket_dir . '/.htaccess';
        file_put_contents( $htaccess, 'deny from all' );
    }
}

// Call on plugin activation
register_activation_hook( __FILE__, array( 'LGP_Plugin', 'create_upload_dirs' ) );
```

## Dependencies

### Required WordPress Functions
- `wp_ajax_` hooks
- `wp_verify_nonce()`
- `wp_send_json_success()`
- `wp_send_json_error()`
- `wp_localize_script()`
- `wp_get_current_user()`
- `wp_mail()`
- `wp_upload_dir()`
- `wp_mkdir_p()`
- `wp_insert_post()`
- `update_post_meta()`
- `get_user_meta()`

### Required Custom Classes
- `LGP_Auth::get_user_company_id()`
- `LGP_Database::get_companies()`
- `LGP_Database::get_company_units()`

## Configuration

### File Upload Settings
Edit in `class-lgp-support-ticket-handler.php`:

```php
// Maximum file size (bytes)
$max_size = 10 * 1024 * 1024; // 10MB

// Maximum number of files
$max_files = 5;

// Allowed MIME types
$allowed_mimes = array(
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    // ... add more as needed
);
```

### Email Configuration
Configure sender and recipients:

```php
// In send_confirmation_email()
$to = $ticket_data['email']; // Requester email
$from = get_option( 'admin_email' ); // Sender

// In notify_support_team()
$to = get_option( 'admin_email' ); // Support team email
// Or add custom support email setting
$to = get_option( 'lgp_support_email', get_option( 'admin_email' ) );
```

### Categories and Urgency
Edit in `templates/support-ticket-form.php`:

```php
<option value="maintenance"><?php _e( 'Maintenance Issue', 'loungenie-portal' ); ?></option>
// Add more categories as needed

<option value="low"><?php _e( 'Low', 'loungenie-portal' ); ?></option>
<option value="medium"><?php _e( 'Medium', 'loungenie-portal' ); ?></option>
<option value="high"><?php _e( 'High', 'loungenie-portal' ); ?></option>
<option value="critical"><?php _e( 'Critical', 'loungenie-portal' ); ?></option>
```

## Database Schema Setup

Create table if not exists (optional - uses custom post type as fallback):

```sql
CREATE TABLE IF NOT EXISTS wp_lgp_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    user_id INT,
    requester_name VARCHAR(255) NOT NULL,
    requester_email VARCHAR(255) NOT NULL,
    requester_phone VARCHAR(20),
    category VARCHAR(50) NOT NULL,
    urgency VARCHAR(20) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    units_affected VARCHAR(255),
    ticket_reference VARCHAR(50) UNIQUE NOT NULL,
    status VARCHAR(20) DEFAULT 'open',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX (company_id),
    INDEX (user_id),
    INDEX (ticket_reference),
    INDEX (status),
    INDEX (created_at)
);
```

## Localization

### Supported Languages
Add translations for these strings:
- "Submit a Support Ticket"
- "Your Information"
- "First Name"
- "Last Name"
- "Email Address"
- "Phone Number"
- "Property Information"
- "Issue Details"
- "Category"
- "Urgency Level"
- "Subject"
- "Detailed Description"
- "Attachments"
- "Consent & Preferences"
- "Submit Ticket"
- And all validation messages

### Translation Files
Create in `languages/`:
```
loungenie-portal-es_ES.po
loungenie-portal-es_ES.mo
loungenie-portal-fr_FR.po
loungenie-portal-fr_FR.mo
```

## API Endpoints

### Submit Ticket
**Endpoint:** `wp-admin/admin-ajax.php`  
**Action:** `lgp_submit_support_ticket`  
**Method:** POST  
**Nonce:** `lgp_ticket_nonce`

**Request:**
```javascript
{
    action: 'lgp_submit_support_ticket',
    lgp_ticket_nonce: 'nonce_value',
    first_name: 'John',
    last_name: 'Doe',
    email: 'john@example.com',
    phone: '(555) 123-4567',
    company_id: 1,
    units_affected: '3',
    unit_ids: [1, 2, 3],
    category: 'maintenance',
    urgency: 'high',
    subject: 'Pool filter not working',
    description: 'The pool filter...',
    consent_contact: '1',
    consent_privacy: '1',
    attachments: [File, File] // FormData
}
```

**Response Success:**
```json
{
    "success": true,
    "data": {
        "ticket_id": "TKT-20240115143045123",
        "message": "Your support ticket has been submitted successfully."
    }
}
```

**Response Error:**
```json
{
    "success": false,
    "data": {
        "message": "Error message describing the issue"
    }
}
```

### Get Company Units
**Endpoint:** `wp-admin/admin-ajax.php`  
**Action:** `lgp_get_company_units`  
**Method:** POST

**Request:**
```javascript
{
    action: 'lgp_get_company_units',
    company_id: 1,
    nonce: 'nonce_value'
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "units": [
            {"id": 1, "name": "Unit 101"},
            {"id": 2, "name": "Unit 102"}
        ]
    }
}
```

## Error Handling

### Common Error Messages

| Error | Cause | Fix |
|-------|-------|-----|
| "Security verification failed" | Invalid nonce | Refresh page and retry |
| "Required field missing" | Incomplete form | Fill all required fields |
| "Invalid email address" | Bad email format | Check email format |
| "Failed to create ticket" | Database error | Check server logs |
| "File exceeds maximum size" | File too large | Reduce file size |
| "Only 5 files allowed" | Too many files | Remove extra files |

### Debug Mode
Enable debug logging:

```php
// In class-lgp-support-ticket-handler.php
define( 'LGP_TICKET_DEBUG', true );

// Then check logs
error_log( 'Ticket debug: ' . print_r( $data, true ) );
```

## Performance Tips

### Optimization
1. **Lazy Load Form:** Only load scripts on support page
2. **Compress Uploads:** Reduce image size before upload
3. **Database Indexing:** Index company_id, user_id, ticket_reference
4. **Caching:** Cache company/unit dropdowns with 1-hour TTL

### Monitoring
1. Track form submission success rate
2. Monitor average response time
3. Alert on submission failures
4. Review file upload patterns

## Testing

### Unit Tests Example
```php
class Test_Support_Ticket_Handler extends WP_UnitTestCase {
    
    public function test_validate_email() {
        $_POST['email'] = 'invalid-email';
        $validation = LGP_Support_Ticket_Handler::validate_submission();
        $this->assertFalse( $validation['valid'] );
    }
    
    public function test_create_ticket() {
        $ticket_data = array(
            'first_name' => 'John',
            'email' => 'john@example.com',
            // ... other fields
        );
        $ticket_id = LGP_Support_Ticket_Handler::create_ticket( $ticket_data );
        $this->assertNotFalse( $ticket_id );
    }
}
```

## Support & Resources

- **Documentation:** See SUPPORT_TICKET_FORM_GUIDE.md
- **Issues:** Check troubleshooting section
- **Examples:** Review templates/ and includes/ directories
- **API:** Use REST API endpoints when available

## Version
Current Version: 1.0.0  
Last Updated: January 2024
