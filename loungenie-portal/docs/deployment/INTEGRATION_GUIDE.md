# Email-to-Ticket System: Integration Guide

**Version**: 1.8.0  
**Status**: Production-Ready  
**Last Updated**: 2024-01-15

---

## Quick Start (30 minutes)

### Step 1: Update Plugin Loader (5 minutes)

Edit [loungenie-portal.php](loungenie-portal.php) and add these requires in the main plugin initialization:

```php
// At the top, after existing includes:
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-deduplication.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-attachment-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-user-creator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-email-to-ticket-enhanced.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-email-handler-enhanced.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-email-notifications.php';

// Initialize classes on plugins_loaded hook
add_action( 'plugins_loaded', function() {
    // Initialize in order (deduplication first, then others)
    LGP_Deduplication::instance();
    LGP_Attachment_Handler::instance();
    LGP_User_Creator::instance();
    LGP_Email_Notifications::instance();
    
    // Email handlers start their cron hooks
    LGP_Email_To_Ticket_Enhanced::instance();
    LGP_Email_Handler_Enhanced::instance();
}, 5 );
```

### Step 2: Execute Database Migrations (5 minutes)

Using phpMyAdmin or command line, run these SQL statements:

```sql
-- Create deduplication table
CREATE TABLE IF NOT EXISTS wp_lgp_email_dedup (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email_hash VARCHAR(64) NOT NULL UNIQUE,
    ticket_id BIGINT UNSIGNED,
    company_id BIGINT UNSIGNED,
    source VARCHAR(50) DEFAULT 'unknown',
    processed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    KEY idx_company (company_id),
    KEY idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create incoming emails queue table (for debugging)
CREATE TABLE IF NOT EXISTS wp_lgp_incoming_emails (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email_from VARCHAR(255),
    email_subject VARCHAR(255),
    email_body LONGTEXT,
    source VARCHAR(50),
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed TINYINT(1) DEFAULT 0,
    ticket_id BIGINT UNSIGNED,
    KEY idx_processed (processed),
    KEY idx_received (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enhance tickets table (if not already done)
ALTER TABLE wp_lgp_tickets 
ADD COLUMN IF NOT EXISTS thread_history LONGTEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS email_reference VARCHAR(255) DEFAULT NULL;

-- Add indexes for performance
ALTER TABLE wp_lgp_email_dedup ADD KEY IF NOT EXISTS idx_source (source);
ALTER TABLE wp_lgp_tickets ADD KEY IF NOT EXISTS idx_email_reference (email_reference);
```

### Step 3: Configure POP3 Settings (5 minutes)

In WordPress admin, go to Settings > LounGenie Portal > Email Settings and fill in:

```
POP3 Server:        mail.yourdomain.com
POP3 Port:          995 (secure) or 110 (standard)
POP3 Username:      support@company.com
POP3 Password:      [will be encrypted]
Enable POP3:        ☑ Checked
Process Interval:   15 minutes
Delete After Read:  ☑ Checked (recommended)
```

**Alternative**: If no admin UI exists, add settings directly in plugin options:

```php
// In WordPress admin, run in code snippets plugin or do manually:
update_option( 'lgp_pop3_settings', array(
    'server'       => 'mail.yourdomain.com',
    'port'         => 995,
    'username'     => 'support@company.com',
    'password'     => 'encrypted_will_be_set',
    'enabled'      => true,
    'delete_after' => true,
) );
```

### Step 4: Map Company Domains (5 minutes)

For each company in WordPress admin, set their contact email to match the email domain:

**Example Configuration**:
- **Pool Safe Inc**: contact_email = `support@poolsafeinc.com`
- **LounGenie**: contact_email = `support@loungenie.com`

When email arrives from `jane@poolsafeinc.com`, the system will:
1. Extract domain: `@poolsafeinc.com`
2. Query companies: `WHERE contact_email LIKE '%@poolsafeinc.com'`
3. Assign to Pool Safe Inc (company_id = 5)

**PHP Code** (if adding programmatically):

```php
// Set company contact email
update_field( 'contact_email', 'support@poolsafeinc.com', 'company_5' );

// Or via wpdb:
$wpdb->update(
    'wp_lgp_companies',
    array( 'contact_email' => 'support@poolsafeinc.com' ),
    array( 'id' => 5 ),
    array( '%s' ),
    array( '%d' )
);
```

### Step 5: Set File Permissions (5 minutes)

```bash
# Navigate to WordPress root
cd /var/www/html

# Create attachment directory with proper permissions
mkdir -p wp-content/uploads/lgp-attachments

# Set permissions (755 = read/write for owner, read for others)
chmod 755 wp-content/uploads/lgp-attachments

# Create company-specific folders
mkdir -p wp-content/uploads/lgp-attachments/poolsafeinc-com
mkdir -p wp-content/uploads/lgp-attachments/loungenie-com

# Verify .htaccess is in place (created by plugin)
# Should contain:
# <FilesMatch "\.php$">
#     Deny from all
# </FilesMatch>

# Check if it exists:
cat wp-content/uploads/lgp-attachments/.htaccess
```

---

## Verification Checklist (10 minutes)

After integration, verify everything works:

### ✓ Database Tables Exist
```php
// In WordPress admin, run in code snippets:
$tables = $GLOBALS['wpdb']->get_results(
    "SHOW TABLES LIKE 'wp_lgp_email_dedup'"
);
echo count( $tables ) ? 'OK' : 'MISSING';
```

### ✓ Classes Instantiate
```php
// Verify no PHP errors in error logs
wp_remote_get( admin_url( 'admin-ajax.php' ) );
// Check: /wp-content/debug.log should have no fatal errors
```

### ✓ Cron Jobs Registered
```php
// In WordPress admin, run in code snippets:
$crons = get_option( 'cron' );
foreach ( $crons as $timestamp => $events ) {
    foreach ( $events as $hook => $data ) {
        if ( strpos( $hook, 'lgp_' ) === 0 ) {
            echo $hook . ': OK<br>';
        }
    }
}
```

### ✓ POP3 Connection Test
```php
// In WordPress admin, run in code snippets:
$handler = LGP_Email_Handler_Enhanced::instance();
$result = $handler->test_pop3_connection();
echo $result['success'] ? 'Connection OK' : 'Connection Failed: ' . $result['error'];
```

### ✓ Attachment Directory Writable
```php
$dir = wp_upload_dir();
$attachment_dir = $dir['basedir'] . '/lgp-attachments';
if ( is_writable( $attachment_dir ) ) {
    echo 'Directory writable: OK';
} else {
    echo 'Directory not writable: FAILED';
}
```

---

## Testing Workflow (30 minutes)

Once integrated, follow these steps to validate:

### Test 1: Hook-Based Email Intake (5 minutes)

Send an email to your WordPress site's admin email:

```
From: jane@poolsafeinc.com
To: admin@yoursite.com
Subject: [URGENT] System Down
Body: The gateway is returning 500 errors
Attach: log.zip (2MB)
```

**Expected Result**:
- Ticket created in portal
- Jane's user auto-created as `lgp_partner`
- Attachment saved to `/uploads/lgp-attachments/poolsafeinc-com/`
- Support Team receives notification
- Jane receives confirmation email

**Debug**:
```php
// Check ticket was created:
$tickets = get_posts( array(
    'post_type' => 'lgp_ticket',
    'numberposts' => 1,
) );
var_dump( $tickets[0]->post_content );
```

### Test 2: POP3 Email Intake (10 minutes)

Configure your POP3 server to receive emails, then:

1. **Trigger cron manually** (if not running):
```php
do_action( 'lgp_process_emails_cron' );
```

2. **Check processed emails**:
```php
$wpdb->get_results(
    "SELECT * FROM wp_lgp_incoming_emails WHERE processed = 1 LIMIT 5"
);
```

3. **Verify no duplicates**:
```php
$wpdb->get_results(
    "SELECT email_hash, COUNT(*) as count FROM wp_lgp_email_dedup GROUP BY email_hash HAVING count > 1"
);
// Should return empty (no duplicates)
```

### Test 3: Deduplication (5 minutes)

Send the same email twice (within 1 hour):

```
From: jane@poolsafeinc.com
To: support@company.com
Subject: [CRITICAL] Connection Issue
Body: POP3 connection failing
```

**Expected Result**:
- First email: Creates ticket #100
- Second email: Skipped, returns ticket #100
- No duplicate ticket created
- Dedup table shows both sources ('hook' and 'pop3')

**Debug**:
```php
// Check dedup table:
$wpdb->get_results(
    "SELECT * FROM wp_lgp_email_dedup WHERE ticket_id = 100"
);
// Should show hash with multiple sources
```

### Test 4: User Auto-Creation (5 minutes)

Send email from a NEW email address:

```
From: newuser@poolsafeinc.com
To: support@company.com
Subject: First Contact
Body: Setting up new account
```

**Expected Result**:
- New WordPress user created: `newuser.poolsafeinc`
- Assigned `lgp_partner` role
- Company ID linked: 5 (Pool Safe Inc)
- Welcome email sent with password reset link
- Can login to portal immediately

**Debug**:
```php
// Check user creation:
$user = get_user_by( 'email', 'newuser@poolsafeinc.com' );
echo $user->ID . ' - ' . implode( ', ', $user->roles );
// Should output: 42 - lgp_partner
```

### Test 5: Access Control (5 minutes)

Login as different users and verify:

**Support Team User**:
- ✓ Sees all tickets (all companies)
- ✓ Can reply to any ticket
- ✓ Can download all attachments
- ✓ Sees analytics dashboard

**Partner Company User** (jane from Pool Safe Inc):
- ✓ Sees only Pool Safe Inc tickets
- ✓ Cannot see other companies' tickets
- ✓ Can reply to own tickets only
- ✓ Can download own attachments only
- ✗ Cannot see system analytics

**Debug**:
```php
// Check user company assignment:
$company_id = get_user_meta( get_current_user_id(), '_lgp_company_id', true );
echo 'Company ID: ' . $company_id;

// Check role:
$user = wp_get_current_user();
echo 'Roles: ' . implode( ', ', $user->roles );
```

---

## Common Issues & Solutions

### Issue: Tickets Not Being Created from Emails

**Symptoms**:
- Emails received but no tickets appear
- No errors in debug.log

**Solutions**:

1. **Check email routing**:
```php
// Verify support email list
$emails = apply_filters( 'lgp_support_emails', array() );
var_dump( $emails );
// Should contain 'support@company.com', etc.
```

2. **Check dedup isn't blocking**:
```php
// Check dedup table for stale entries
$wpdb->get_results(
    "SELECT * FROM wp_lgp_email_dedup WHERE expires_at < NOW()"
);
// Delete stale entries:
// DELETE FROM wp_lgp_email_dedup WHERE expires_at < NOW();
```

3. **Check company mapping**:
```php
// Verify company email domain is set
$company = get_post( 5 ); // Pool Safe Inc
echo get_field( 'contact_email', $company->ID );
// Should output: support@poolsafeinc.com
```

4. **Check user creation**:
```php
// Force user creation
$creator = LGP_User_Creator::instance();
$result = $creator->get_or_create_user( 'jane@poolsafeinc.com', 5 );
if ( is_wp_error( $result ) ) {
    echo 'Error: ' . $result->get_error_message();
}
```

### Issue: POP3 Connection Failing

**Symptoms**:
- "POP3 connection failed" in logs
- Cron never processes emails

**Solutions**:

1. **Verify IMAP PHP extension**:
```php
echo extension_loaded( 'imap' ) ? 'OK' : 'NOT INSTALLED';
// If not: sudo apt-get install php-imap && sudo systemctl restart apache2
```

2. **Test POP3 credentials**:
```php
$settings = get_option( 'lgp_pop3_settings' );
$server = "{".$settings['server'].":".$settings['port']."/pop3/ssl}INBOX";
$connection = imap_open( $server, $settings['username'], $settings['password'] );
echo $connection ? 'Connected' : imap_last_error();
```

3. **Check firewall**:
```bash
# Test connectivity to POP3 server
telnet mail.yourdomain.com 995
# Should connect (may need to Ctrl+C to exit)
```

4. **Verify port is correct**:
- Port 110: Standard POP3 (unencrypted)
- Port 995: POP3 over SSL/TLS (recommended)

### Issue: Duplicate Tickets Created

**Symptoms**:
- Same email creates 2+ tickets
- Both hook and POP3 processing tickets

**Solutions**:

1. **Verify dedup table exists**:
```php
$wpdb->query( "SHOW TABLES LIKE 'wp_lgp_email_dedup'" );
// Should return 1
```

2. **Check dedup window**:
```php
// Dedup is 1 hour - if same email arrives after 1 hour, it will create a duplicate
// This is by design (prevents accidental re-opening of old tickets)
```

3. **Verify hash generation**:
```php
$dedup = LGP_Deduplication::instance();
$hash = $dedup->generate_hash( 'jane@poolsafeinc.com', '[URGENT] Down', '2024-01-15 14:00' );
var_dump( $hash );
// Should be 64-char SHA256
```

### Issue: Attachments Not Saving

**Symptoms**:
- Tickets created but no attachments
- "Attachment too large" errors

**Solutions**:

1. **Check directory permissions**:
```bash
ls -la /var/www/html/wp-content/uploads/lgp-attachments/
# Should show: drwxr-xr-x (755)
```

2. **Check disk space**:
```bash
df -h /var/www/html/wp-content/uploads/
# Need at least 1GB free for attachments
```

3. **Check file size limits**:
```php
// In class-lgp-attachment-handler.php, max is 10MB
// If attachment is larger, it will fail
echo filesize( '/path/to/large-file.zip' ); // in bytes
// Divide by 1048576 for MB
```

4. **Check MIME whitelist**:
```php
// Allowed types in class:
$allowed_types = array(
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    // ... etc
);
// If your file type isn't listed, add it to the whitelist
```

### Issue: Cron Not Running

**Symptoms**:
- Emails pile up but never processed
- Cron events registered but not executing

**Solutions**:

1. **Check WordPress cron is enabled**:
```php
echo defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'DISABLED' : 'ENABLED';
// If disabled, add to wp-config.php:
// define( 'DISABLE_WP_CRON', false );
```

2. **Set up system cron** (recommended for production):
```bash
# Add to crontab (crontab -e):
*/15 * * * * curl -s https://yoursite.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1

# Verify it's working:
tail -f /var/log/syslog | grep CRON
```

3. **Manually trigger cron**:
```php
// In WordPress admin code snippets:
do_action( 'lgp_process_emails_cron' );
echo 'Cron triggered';
```

4. **Check for cron lock**:
```php
// If processing is stuck:
delete_transient( 'lgp_email_processing_lock' );
echo 'Lock cleared';
```

---

## Maintenance Tasks

### Daily Maintenance
```php
// Check for errors in debug log:
tail -f /var/www/html/wp-content/debug.log | grep lgp_

// Monitor cron execution:
do_action( 'lgp_process_emails_cron' );

// Check disk space:
df -h /var/www/html/wp-content/uploads/
```

### Weekly Maintenance
```php
// Clean up old dedup records (auto-cleanup runs, but verify):
$wpdb->query( "DELETE FROM wp_lgp_email_dedup WHERE expires_at < NOW()" );

// Check for failed email processing:
$wpdb->get_results(
    "SELECT * FROM wp_lgp_incoming_emails WHERE processed = 0 AND received_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
);

// Monitor attachment storage:
du -sh /var/www/html/wp-content/uploads/lgp-attachments/
```

### Monthly Maintenance
```php
// Archive old tickets (optional):
$wpdb->query(
    "INSERT INTO wp_lgp_tickets_archive
     SELECT * FROM wp_lgp_tickets
     WHERE updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
);

// Review audit logs:
$wpdb->get_results(
    "SELECT action, COUNT(*) as count FROM wp_lgp_audit_log
     GROUP BY action
     ORDER BY count DESC
     LIMIT 10"
);

// Check storage growth:
// Expected: ~1-2MB per 100 tickets with attachments
// Alert if > 10GB in uploads/lgp-attachments/
```

---

## Performance Monitoring

### Check Email Processing Speed
```php
// In class-lgp-email-handler-enhanced.php, logging shows processing time
// Look for: "Processed 10 emails in 3.2 seconds"

// Target: < 5 seconds for 10 emails
// If > 10 seconds: Check for slow database queries
```

### Monitor Memory Usage
```php
// In debug.log, PHP should use < 10MB for email processing
// If > 50MB: Check for memory leaks in attachment processing

// Verify chunked reading is working:
// Search debug.log for "Copying file in 1MB chunks"
```

### Check Database Indexes
```php
// Verify indexes exist:
$wpdb->get_results( "SHOW INDEX FROM wp_lgp_email_dedup" );
$wpdb->get_results( "SHOW INDEX FROM wp_lgp_tickets" );

// Should show:
// - email_hash (UNIQUE)
// - company_id
// - ticket_id
// - expires_at
```

---

## Deployment Checklist

Before going live to production:

- [ ] Database migrations executed
- [ ] Plugin loader updated (requires added)
- [ ] Classes initialized on plugins_loaded
- [ ] POP3 settings configured and tested
- [ ] Company domains mapped
- [ ] File permissions set (755)
- [ ] .htaccess verified in attachment directory
- [ ] All 5 test cases passed
- [ ] Debug logging enabled (WP_DEBUG = true)
- [ ] System cron configured (not WP-Cron)
- [ ] Backup of database taken
- [ ] Monitoring alerts configured
- [ ] Staff trained on new system
- [ ] Communication sent to partner companies
- [ ] Rollback plan documented
- [ ] Go/No-Go meeting completed

---

## Support & Documentation

- **Security Guide**: [PRODUCTION_EMAIL_SECURITY.md](PRODUCTION_EMAIL_SECURITY.md)
- **Deployment Manual**: [PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md)
- **Testing Guide**: [COMPREHENSIVE_TESTING_GUIDE.md](COMPREHENSIVE_TESTING_GUIDE.md)
- **Architecture Diagram**: [ARCHITECTURE.md](ARCHITECTURE.md)

For additional support, contact: support@loungenie.com

---

**Last Updated**: 2024-01-15
