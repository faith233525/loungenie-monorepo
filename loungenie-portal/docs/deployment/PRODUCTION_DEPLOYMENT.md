# LounGenie Portal: Email-to-Ticket Production Deployment Guide

**Version**: 1.8.0  
**Last Updated**: December 2024  
**Environment**: WordPress + MySQL on Shared Hosting  

---

## Table of Contents

1. [Pre-Deployment Setup](#pre-deployment-setup)
2. [Database Migrations](#database-migrations)
3. [Configuration](#configuration)
4. [Class Integration](#class-integration)
5. [Testing & Validation](#testing--validation)
6. [Monitoring & Maintenance](#monitoring--maintenance)
7. [Troubleshooting](#troubleshooting)

---

## Pre-Deployment Setup

### 1. Server Requirements

**Minimum**:
- PHP 7.4+ (WordPress requirement)
- MySQL 5.7+ (InnoDB engine with JSON support)
- 128MB PHP memory limit
- 200MB available disk space

**Recommended for Production**:
- PHP 8.0+
- MySQL 8.0+
- 256MB PHP memory limit
- 500MB+ available disk space
- IMAP extension enabled (`php-imap` package)
- SSL/TLS certificate (mandatory for production)

### 2. Check Server Capabilities

```bash
# SSH into server, test PHP
php -v
php -r "phpinfo();" | grep imap  # Should show imap support

# Check PHP memory limit
php -r "echo ini_get('memory_limit');"  # Should be >= 128M

# Check available disk space
df -h /var/www/html/wp-content/uploads/

# Check MySQL JSON support
mysql -u root -p -e "SELECT JSON_VALID('{\"test\":1}');"
```

### 3. Backup Current Setup

```bash
# Backup WordPress directory
tar -czf backup-www-$(date +%Y%m%d).tar.gz /var/www/html/

# Backup database
mysqldump -u wordpress -p poolsafe_portal > backup-db-$(date +%Y%m%d).sql

# Store backups safely
scp backup-www-*.tar.gz user@backup-server:/backups/
scp backup-db-*.sql user@backup-server:/backups/
```

---

## Database Migrations

### 1. Create New Tables

```php
<?php
// Add to loungenie-portal.php OR create migration file

require_once dirname( __FILE__ ) . '/includes/class-lgp-deduplication.php';
require_once dirname( __FILE__ ) . '/includes/class-lgp-attachment-handler.php';

// Initialize new tables on plugin activation
function lgp_activate_enhanced() {
    LGP_Deduplication::ensure_table_exists();
    LGP_Attachment_Handler::ensure_attachment_base_exists();
    LGP_Attachment_Handler::add_security_files();
}

register_activation_hook( __FILE__, 'lgp_activate_enhanced' );
?>
```

### 2. Manual Database Setup (Backup Method)

If activation hook doesn't work, run manually:

```sql
-- Deduplication table
CREATE TABLE IF NOT EXISTS wp_lgp_email_dedup (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    email_hash varchar(64) NOT NULL UNIQUE,
    ticket_id bigint(20) UNSIGNED,
    company_id bigint(20) UNSIGNED,
    source varchar(50) NOT NULL DEFAULT 'hook',
    processed_at datetime DEFAULT CURRENT_TIMESTAMP,
    expires_at datetime,
    PRIMARY KEY (id),
    UNIQUE KEY email_hash (email_hash),
    KEY ticket_id (ticket_id),
    KEY company_id (company_id),
    KEY expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Incoming emails table (optional, for debugging)
CREATE TABLE IF NOT EXISTS wp_lgp_incoming_emails (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    to varchar(255) NOT NULL,
    subject varchar(500),
    message longtext,
    headers longtext,
    source varchar(50) DEFAULT 'hook',
    status varchar(20) DEFAULT 'pending',
    ticket_id bigint(20) UNSIGNED,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY status (status),
    KEY ticket_id (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify attachment table exists (should already exist)
-- ALTER TABLE wp_lgp_ticket_attachments ADD COLUMN IF NOT EXISTS mime_type varchar(100);
-- ALTER TABLE wp_lgp_ticket_attachments ADD KEY IF NOT EXISTS ticket_id (ticket_id);
```

### 3. Add Indexes for Performance

```sql
-- Optimize ticket queries
ALTER TABLE wp_lgp_service_requests ADD INDEX idx_company_status (company_id, status);
ALTER TABLE wp_lgp_service_requests ADD INDEX idx_priority_created (priority, created_at);

-- Optimize dedup queries
ALTER TABLE wp_lgp_email_dedup ADD INDEX idx_expires (expires_at);

-- Optimize attachment queries
ALTER TABLE wp_lgp_ticket_attachments ADD INDEX idx_ticket_created (ticket_id, created_at);
```

---

## Configuration

### 1. Email Settings (WordPress Admin)

Create a settings page or add to `wp-config.php`:

```php
<?php
// wp-config.php

// POP3 Email Configuration (Encrypted)
define( 'LGP_POP3_SERVER', 'mail.poolsafe.com' );
define( 'LGP_POP3_PORT', 110 );
define( 'LGP_POP3_USERNAME', 'tickets@poolsafe.com' );
define( 'LGP_POP3_PASSWORD', 'your_strong_password_here' );
define( 'LGP_POP3_SSL', false );

// Support Email Addresses
define( 'LGP_SUPPORT_EMAILS', array(
    'support@poolsafe.com',
    'tickets@poolsafe.com',
    'help@poolsafe.com',
) );

// Email Notifications
define( 'LGP_FROM_EMAIL', 'support@poolsafe.com' );
define( 'LGP_FROM_NAME', 'PoolSafe Support' );
?>
```

### 2. Load Configuration

```php
<?php
// In class-lgp-email-handler-enhanced.php

private static function get_settings() {
    $settings = get_option( 'lgp_email_settings' );
    
    if ( !$settings ) {
        $settings = array(
            'pop3_server'   => defined( 'LGP_POP3_SERVER' ) ? LGP_POP3_SERVER : '',
            'pop3_port'     => defined( 'LGP_POP3_PORT' ) ? LGP_POP3_PORT : 110,
            'pop3_username' => defined( 'LGP_POP3_USERNAME' ) ? LGP_POP3_USERNAME : '',
            'pop3_password' => defined( 'LGP_POP3_PASSWORD' ) ? LGP_POP3_PASSWORD : '',
            'pop3_ssl'      => defined( 'LGP_POP3_SSL' ) ? LGP_POP3_SSL : false,
        );
    }
    
    return $settings;
}
?>
```

### 3. Company Domain Mapping

Set company contact emails with their domain:

```sql
UPDATE wp_lgp_companies 
SET contact_email = 'contact@poolsafeinc.com'  -- Domain for auto-mapping
WHERE name = 'Pool Safe Inc.';
```

Emails from `anyone@poolsafeinc.com` will auto-map to this company.

### 4. File Permissions

```bash
# Set directory permissions (accessible to web server)
chmod 755 /var/www/html/wp-content/uploads/lgp-attachments

# Create company folders
mkdir -p /var/www/html/wp-content/uploads/lgp-attachments/poolsafeinc-com
mkdir -p /var/www/html/wp-content/uploads/lgp-attachments/loungenie-com

# Set ownership
chown -R www-data:www-data /var/www/html/wp-content/uploads/lgp-attachments
chmod 755 /var/www/html/wp-content/uploads/lgp-attachments/*/

# Verify .htaccess is in place
ls -la /var/www/html/wp-content/uploads/lgp-attachments/.htaccess
```

---

## Class Integration

### 1. Update Plugin Loader

Edit `loungenie-portal.php`:

```php
<?php
// loungenie-portal.php (main plugin file)

// Include enhanced classes
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-deduplication.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-attachment-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-user-creator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-email-to-ticket-enhanced.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-email-handler-enhanced.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-email-notifications.php';

// Initialize email system
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'LGP_Deduplication' ) ) {
        LGP_Deduplication::init();
    }
    if ( class_exists( 'LGP_Attachment_Handler' ) ) {
        LGP_Attachment_Handler::init();
    }
    if ( class_exists( 'LGP_Email_To_Ticket' ) ) {
        LGP_Email_To_Ticket::init();
    }
    if ( class_exists( 'LGP_Email_Handler' ) ) {
        LGP_Email_Handler::init();
    }
    if ( class_exists( 'LGP_Email_Notifications' ) ) {
        LGP_Email_Notifications::init();
    }
}, 15 );  // After other plugins_loaded hooks

?>
```

### 2. Register Cron Hooks (if custom)

```php
<?php
// Add to main plugin file or init hook

// Ensure cron schedule is registered
add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['lgp_fifteen_minutes'] = array(
        'interval' => 15 * 60,
        'display'  => __( 'Every 15 Minutes' ),
    );
    return $schedules;
} );

// Schedule if not exists
if ( ! wp_next_scheduled( 'lgp_process_emails_cron' ) ) {
    wp_schedule_event( time(), 'lgp_fifteen_minutes', 'lgp_process_emails_cron' );
}

?>
```

### 3. Disable Old Email Classes (if exists)

```php
<?php
// Prevent old versions from running

// In loungenie-portal.php, before loading new classes:
if ( class_exists( 'LGP_Email_To_Ticket' ) && !method_exists( 'LGP_Email_To_Ticket', 'process_incoming_email' ) ) {
    // Old version exists, need to update
    error_log( 'LGP: Old email-to-ticket class detected, please update plugin' );
    
    // Could add a notice to admin here
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>LounGenie Portal: Email system needs to be updated. Please backup and re-upload plugin files.</p></div>';
    });
}

?>
```

---

## Testing & Validation

### 1. Test Deduplication System

```php
<?php
// Paste in WordPress debug plugin or temporary admin function

// Generate test hash
$hash1 = LGP_Deduplication::generate_hash(
    'jane.doe@poolsafeinc.com',
    '[URGENT] Test Ticket',
    time()
);

// Same email/subject a minute later should be similar
$hash2 = LGP_Deduplication::generate_hash(
    'jane.doe@poolsafeinc.com',
    '[URGENT] Test Ticket',
    time() + 60
);

// These should match (rounded to minute)
echo "Hash 1: $hash1\n";
echo "Hash 2: $hash2\n";
echo "Match: " . ( $hash1 === $hash2 ? "YES" : "NO" ) . "\n";

// Register and check
LGP_Deduplication::register_processed_email( $hash1, 123, 1, 'test' );
$record = LGP_Deduplication::get_dedup_record( $hash1 );

echo "Dedup Record: " . ( $record ? "Found (ticket #{$record->ticket_id})" : "Not found" ) . "\n";

?>
```

### 2. Test User Creation

```php
<?php
// Test auto-user creation

$user_id = LGP_User_Creator::get_or_create_user(
    'test.user@poolsafeinc.com',
    1,  // company ID
    'Test User'
);

if ( is_wp_error( $user_id ) ) {
    echo "ERROR: " . $user_id->get_error_message();
} else {
    $user = get_user_by( 'id', $user_id );
    echo "Created user: {$user->user_login} ({$user->user_email})";
    echo "Role: " . implode( ', ', $user->roles );
    echo "Company: " . get_user_meta( $user_id, '_lgp_company_id', true );
}

?>
```

### 3. Test Attachment Handler

```php
<?php
// Create test file
$test_file = wp_tempnam();
file_put_contents( $test_file, 'This is a test attachment' );

// Save via handler
$result = LGP_Attachment_Handler::save_attachment(
    $test_file,
    'test-file.txt',
    123,  // ticket ID
    1,    // company ID
    1     // uploaded_by user ID
);

if ( is_array( $result ) ) {
    echo "Attachment saved:\n";
    echo "  ID: " . $result['id'] . "\n";
    echo "  Path: " . $result['path'] . "\n";
    echo "  Size: " . $result['size'] . " bytes\n";
} else {
    echo "Failed to save attachment";
}

// Test directory structure
$company_dir = LGP_Attachment_Handler::get_company_directory( 1 );
echo "Company directory: $company_dir\n";
echo "Exists: " . ( is_dir( $company_dir ) ? "YES" : "NO" ) . "\n";
echo "Has index.php: " . ( file_exists( "$company_dir/index.php" ) ? "YES" : "NO" ) . "\n";

?>
```

### 4. Test POP3 Connection

```php
<?php
// Test email settings
$settings = LGP_Email_Handler::get_settings();

if ( !$settings['pop3_server'] ) {
    echo "ERROR: POP3 settings not configured\n";
} else {
    echo "POP3 Server: " . $settings['pop3_server'] . "\n";
    echo "Port: " . $settings['pop3_port'] . "\n";
    echo "Username: " . $settings['pop3_username'] . "\n";
    
    // Try connection (don't expose password)
    $mailbox = '{' . $settings['pop3_server'] . ':' . $settings['pop3_port'] . '/pop3}INBOX';
    @$connection = imap_open( $mailbox, $settings['pop3_username'], 'test' );
    
    if ( !$connection ) {
        echo "Connection Status: FAILED\n";
        echo "Error: " . imap_last_error() . "\n";
    } else {
        $count = imap_num_msg( $connection );
        echo "Connection Status: SUCCESS\n";
        echo "Emails in inbox: $count\n";
        imap_close( $connection );
    }
}

?>
```

### 5. Manual Email Test

```bash
#!/bin/bash

# Send test email to support address
echo "Subject: [TEST] Deduplication Test" > email.txt
echo "" >> email.txt
echo "This is a test email for deduplication." >> email.txt

# Using mail command (adjust as needed)
mail -s "[TEST] Deduplication Test" support@poolsafe.com < email.txt

# Check if processed (give POP3 cron 15 minutes)
# Then check for ticket in database:
# SELECT * FROM wp_lgp_tickets ORDER BY created_at DESC LIMIT 1;
```

---

## Monitoring & Maintenance

### 1. Monitor Email Processing

**Check logs daily:**
```bash
tail -f /var/www/html/wp-content/debug.log | grep "LGP"

# Specific patterns to watch for:
grep "Created ticket" /var/www/html/wp-content/debug.log
grep "Email duplicate" /var/www/html/wp-content/debug.log
grep "Failed to" /var/www/html/wp-content/debug.log
```

**Enable debugging (if not already):**
```php
// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
```

### 2. Cron Health Check

```php
<?php
// Check if cron is actually running

global $wpdb;

// Get last email processed
$last_email = $wpdb->get_row(
    "SELECT * FROM {$wpdb->prefix}lgp_tickets 
     WHERE email_reference IS NOT NULL 
     ORDER BY created_at DESC LIMIT 1"
);

if ( $last_email ) {
    $time_ago = strtotime( 'now' ) - strtotime( $last_email->created_at );
    echo "Last email processed: " . round( $time_ago / 60 ) . " minutes ago\n";
    
    if ( $time_ago > 1800 ) {  // 30 minutes
        echo "WARNING: No emails processed in last 30 minutes\n";
        echo "Check: WordPress cron is running\n";
    }
} else {
    echo "No emails processed yet\n";
}

// Force cron run manually
if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
    echo "Cron is currently running\n";
}

?>
```

### 3. Disk Space Monitor

```bash
#!/bin/bash
# monitor-disk.sh

USAGE=$(du -sh /var/www/html/wp-content/uploads/lgp-attachments/ | cut -f1)
INODE=$(df -i /var/www/html/wp-content/uploads/ | tail -1 | awk '{print $5}')

echo "Attachment Storage: $USAGE"
echo "Inode Usage: $INODE"

if [ "$INODE" -gt 90 ]; then
    echo "WARNING: Inode usage high, files may be fragmented"
fi

# Clean old attachments (optional, based on your retention policy)
# find /var/www/html/wp-content/uploads/lgp-attachments -type f -mtime +365 -delete
```

### 4. Database Cleanup

```php
<?php
// Run monthly to clean expired dedup records

LGP_Deduplication::cleanup_expired();

// Also clean old incoming emails (if using temp table)
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->prefix}lgp_incoming_emails 
     WHERE status = 'processed' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
);

echo "Cleanup completed\n";

?>
```

### 5. Admin Dashboard Widget

Add to WordPress admin to show stats:

```php
<?php
// Add this to main plugin file

add_action( 'wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'lgp_email_stats',
        'LounGenie Email Statistics',
        function() {
            $stats = LGP_Deduplication::get_stats();
            echo "<p>Deduplicated emails: " . $stats['total_deduplicated'] . "</p>";
            echo "<p>Active hashes: " . $stats['active_hashes'] . "</p>";
            
            if ( !empty( $stats['by_source'] ) ) {
                echo "<p>By source:<ul>";
                foreach ( $stats['by_source'] as $row ) {
                    echo "<li>{$row['source']}: {$row['count']}</li>";
                }
                echo "</ul></p>";
            }
        }
    );
});

?>
```

---

## Troubleshooting

### Problem: Emails Not Being Processed

**Symptoms**: Emails arrive at support@poolsafe.com but no tickets created

**Debugging Steps**:

```php
<?php
// 1. Check POP3 connection
$settings = LGP_Email_Handler::get_settings();
echo "POP3 Server: " . $settings['pop3_server'] . "\n";
echo "POP3 Port: " . $settings['pop3_port'] . "\n";

// 2. Test manual cron
if ( function_exists( 'do_action' ) ) {
    do_action( 'lgp_process_emails_cron' );
    echo "Manual cron triggered\n";
}

// 3. Check logs
tail -f /var/www/html/wp-content/debug.log | grep "LGP.*process"

// 4. Check cron lock
$lock = get_transient( 'lgp_email_processing_lock' );
echo "Cron lock: " . ( $lock ? "LOCKED" : "FREE" ) . "\n";

// 5. Check incoming emails table
global $wpdb;
$pending = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}lgp_incoming_emails WHERE status = 'pending'"
);
echo "Pending emails in queue: $pending\n";

?>
```

### Problem: Duplicate Tickets Being Created

**Symptoms**: Same email creates multiple tickets

**Solution**:

```php
<?php
// Check dedup table
global $wpdb;
$dedups = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}lgp_email_dedup ORDER BY processed_at DESC LIMIT 10"
);

echo "Recent dedup records:\n";
foreach ( $dedups as $dedup ) {
    echo "Hash: {$dedup->email_hash}\n";
    echo "  Ticket: #{$dedup->ticket_id}\n";
    echo "  Expires: {$dedup->expires_at}\n";
    echo "  Source: {$dedup->source}\n";
}

// If needed, manually register email
$new_hash = LGP_Deduplication::generate_hash(
    'sender@example.com',
    'Subject Line',
    time()
);

LGP_Deduplication::register_processed_email(
    $new_hash,
    $ticket_id,
    $company_id,
    'manual'
);

?>
```

### Problem: Attachments Not Saving

**Symptoms**: Emails received but no attachments attached to tickets

**Solution**:

```bash
# 1. Check directory permissions
ls -la /var/www/html/wp-content/uploads/lgp-attachments/

# Should show:
# drwxr-xr-x www-data www-data .
# drwxr-xr-x www-data www-data company-domain/

# 2. Fix permissions
chmod 755 /var/www/html/wp-content/uploads/lgp-attachments
chmod 755 /var/www/html/wp-content/uploads/lgp-attachments/*
chown -R www-data:www-data /var/www/html/wp-content/uploads/lgp-attachments

# 3. Check .htaccess
cat /var/www/html/wp-content/uploads/lgp-attachments/.htaccess
# Should contain FilesMatch rules

# 4. Check PHP error logs
tail -f /var/log/php-fpm/error.log | grep "lgp-attachments"
```

### Problem: Memory Limit Exceeded

**Symptoms**: "Allowed memory size exhausted" in emails processing

**Solution**:

```php
<?php
// Increase PHP memory limit for cron
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MEMORY_CRON_LIMIT', '512M' );  // Specifically for cron
```

Or in php.ini:
```ini
memory_limit = 256M
```

### Problem: Users Not Auto-Created

**Symptoms**: Emails from new partner addresses, but no WP user created

**Solution**:

```php
<?php
// Check user creation directly
$user_id = LGP_User_Creator::get_or_create_user(
    'test@newcompany.com',
    1,  // company_id
    'Test User'
);

if ( is_wp_error( $user_id ) ) {
    echo "Error: " . $user_id->get_error_message();
    // Likely: username already exists or email invalid
} else {
    $user = get_user_by( 'id', $user_id );
    echo "User created: {$user->user_login}\n";
    echo "Email: {$user->user_email}\n";
    echo "Role: " . implode( ', ', $user->roles ) . "\n";
}

// Check user count
$partners = count_users();
echo "Total users: " . $partners['total_users'] . "\n";

?>
```

---

## Rollback Plan

If issues arise with new email system:

```bash
#!/bin/bash
# Rollback to previous version

# 1. Restore WordPress files from backup
tar -xzf backup-www-$(date +%Y%m%d-%H%M%S).tar.gz -C /var/www/html --strip-components=2

# 2. Restore database (be careful!)
# Backup current
mysqldump -u wordpress -p poolsafe_portal > backup-db-rollback.sql

# Restore from backup
mysql -u wordpress -p poolsafe_portal < backup-db-original.sql

# 3. Clear all caches
wp cache flush --allow-root

# 4. Test
curl -H "X-Debug: 1" https://yoursite.com/wp-json/lg p/v1/tickets

# 5. Notify users
echo "Email system temporarily disabled while we investigate"

```

---

## Post-Deployment Checklist

```
[ ] Database tables created successfully
[ ] File permissions set correctly (755 on dirs, 644 on files)
[ ] .htaccess blocking PHP execution in attachments
[ ] POP3 credentials encrypted and tested
[ ] First test email processed successfully
[ ] User auto-created from test email
[ ] Notifications sent to Support Team
[ ] Ticket visible in Partner Company portal
[ ] Attachments saved to correct company folder
[ ] Deduplication working (send duplicate, verify one ticket)
[ ] Logs showing no errors
[ ] Cron running every 15 minutes
[ ] Admin dashboard widget showing stats
[ ] Backups verified
[ ] Documentation updated
[ ] Support team trained on new system
[ ] Monitoring alerts configured
```

For questions or issues, enable `WP_DEBUG` and review `/wp-content/debug.log` for detailed error messages.
