# LounGenie Portal: Security & Production Hardening Guide

## Overview

This guide covers all security measures implemented in the production-ready email-to-ticket system.

---

## 1. Input Sanitization & Validation

### Email Parsing
- **Source**: `class-lgp-email-to-ticket-enhanced.php`, `class-lgp-email-handler-enhanced.php`
- **Measures**:
  - All email addresses validated with `is_email()`
  - Subject lines sanitized with `sanitize_text_field()`
  - Email bodies processed with `wp_kses_post()` to allow safe HTML
  - IMAP headers decoded safely with character encoding checks

### File Uploads
- **Source**: `class-lgp-attachment-handler.php`
- **Measures**:
  - Filename sanitization: `sanitize_file_name()`
  - MIME type validation against whitelist
  - File size limits enforced (10MB per file, max 5 per ticket)
  - Secure filename generation with random suffix to prevent collisions

### Database Queries
- **All queries use prepared statements**:
  ```php
  $wpdb->prepare("SELECT * FROM table WHERE id = %d", $id)
  $wpdb->insert($table, $data, $format)  // Explicit type casting
  ```

---

## 2. File Security

### Directory Structure
```
wp-content/uploads/lgp-attachments/
├── .htaccess              # Prevents script execution
├── index.php              # Prevents directory listing
└── company-domain/        # Company-specific folders
    ├── index.php
    └── attachments...
```

### .htaccess Protection
```apache
<FilesMatch "\.php$">
    Deny from all
</FilesMatch>

# Prevent access to sensitive files
<FilesMatch "^(config|wp-config|\.env)">
    Deny from all
</FilesMatch>
```

### File Access Control
- Attachments are **not directly accessible** via HTTP
- Access goes through portal's REST API with role checks
- URLs generated with HMAC token: `hash_hmac('sha256', attachment_id|ticket_id, wp_salt())`

---

## 3. Credential Encryption

### POP3 Password Storage
- **Class**: `class-lgp-email-handler-enhanced.php`
- **Method**: XOR encryption with `wp_salt('secure_auth')`
- **Implementation**:
  ```php
  // Encrypt
  $encrypted = encrypt_password($password);  // Base64 + XOR
  
  // Decrypt (only when connecting)
  $password = decrypt_password($encrypted);
  ```
- **Note**: For production with sensitive environments, consider:
  - AWS Secrets Manager integration
  - Vault.io integration
  - Separate credentials service

---

## 4. Database Security

### Table Structure
```sql
-- Deduplication table
CREATE TABLE lgp_email_dedup (
    id BIGINT UNSIGNED PRIMARY KEY,
    email_hash VARCHAR(64) UNIQUE,  -- SHA256 hash only
    ticket_id BIGINT UNSIGNED,
    source VARCHAR(50),
    expires_at DATETIME,
    KEY ticket_id (ticket_id),
    KEY expires_at (expires_at)
);

-- Tickets with JSON thread history
CREATE TABLE lgp_tickets (
    id BIGINT UNSIGNED PRIMARY KEY,
    service_request_id BIGINT UNSIGNED,
    status VARCHAR(50),
    thread_history LONGTEXT,  -- JSON, max 500KB
    email_reference VARCHAR(255),
    KEY service_request_id,
    KEY status
);

-- Attachments with path references
CREATE TABLE lgp_ticket_attachments (
    id BIGINT UNSIGNED PRIMARY KEY,
    ticket_id BIGINT UNSIGNED,
    file_path VARCHAR(500),        -- Full path, not accessible directly
    file_name VARCHAR(255),
    file_type VARCHAR(100),         -- MIME type
    file_size BIGINT(20),
    uploaded_by BIGINT UNSIGNED,
    created_at DATETIME,
    KEY ticket_id (ticket_id)
);
```

### Indexes for Security & Performance
- `email_hash` UNIQUE prevents duplicates
- `expires_at` allows cleanup of old dedup records
- `status` allows role-based filtering (Support sees all, Partner sees own only)

---

## 5. API Access Control

### Role-Based Permissions

**Support Team (`lgp_support`)**:
- View all tickets across all companies
- Create tickets
- Reply to tickets
- Access attachments
- View audit logs

**Partner Company (`lgp_partner`)**:
- View only their company's tickets
- Create service requests (converted to tickets)
- Reply to own tickets
- Download own attachments
- Cannot view other companies' data

### Endpoint Authorization
```php
// In REST routes
'permission_callback' => function() {
    if ( ! is_user_logged_in() ) {
        return false;
    }
    
    if ( current_user_can( 'lgp_view_tickets' ) ) {
        return true;  // Support Team
    }
    
    if ( current_user_can( 'lgp_view_own_tickets' ) ) {
        return true;  // Partner Company
    }
    
    return false;
}
```

---

## 6. Email Source Validation

### Supported Email Channels
```php
$support_emails = array(
    'support@loungenie.com',
    'tickets@loungenie.com',
    'help@poolsafe.com',
);
```
- **Filterable**: `apply_filters('lgp_support_email_addresses', $support_emails)`
- Add more addresses via settings or filter
- Prevents arbitrary email addresses from creating tickets

### Company Domain Matching
- Emails from `jane.doe@poolsafeinc.com` → automatically matched to "Pool Safe Inc" company
- Domain lookup via `lgp_companies.contact_email`
- User auto-created with `lgp_partner` role, linked to company via `_lgp_company_id` meta

---

## 7. Deduplication Security

### Hash Generation (Email Fingerprint)
```php
$hash = hash('sha256', 
    strtolower($email) . '|' . 
    strtolower($subject) . '|' . 
    (timestamp / 60) * 60  // Round to minute
);
```

### Why This Matters
- **Prevents duplicate tickets** if email processed via hook + POP3
- **Time window**: 1 hour (configurable)
- **Hash only**: Original email not stored in dedup table
- **Automatic cleanup**: Expired records removed daily

---

## 8. Memory Safety (Shared Hosting)

### Chunked File Reading
```php
const CHUNK_SIZE = 1024 * 1024; // 1MB

while ( !feof($source) ) {
    $chunk = fread($source, CHUNK_SIZE);
    fwrite($destination, $chunk);
}
```
- Reads large files in 1MB chunks
- Prevents "Allowed memory size exhausted" errors
- Safe on servers with 128MB+ PHP memory limit

### Cron Batching
```php
const BATCH_SIZE = 10;  // Process max 10 emails per cron run
for ( $i = 1; $i <= $email_count && $processed < BATCH_SIZE; $i++ ) {
    process_single_email($connection, $i);
}
```
- Prevents timeout on shared hosts (30-60s limits)
- Processes incrementally, distributes load

### Email Processing Lock
```php
// Prevent parallel cron execution
$lock = get_transient('lgp_email_processing_lock');
if ( $lock ) {
    return;  // Already running, skip
}
set_transient('lgp_email_processing_lock', time(), 300);  // 5 min lock
```

---

## 9. Attachment Security

### Disk Space Management
- Max 5 attachments per ticket
- Max 10MB per file
- Company-specific folders (`poolsafe-com/`, `loungenie-com/`)
- Direct .php execution prevented via .htaccess

### Download Access Control
```php
// REST endpoint checks:
1. User must be logged in
2. User must have 'lgp_download_attachment' capability
3. If Partner: only allow own company's attachments
4. Validate HMAC token

$token = hash_hmac('sha256', "$id|$ticket_id", wp_salt());
// Token expires with page load (can be made shorter)
```

---

## 10. Audit Logging

### Events Logged
- User creation from email
- Ticket creation (source: email/hook/pop3)
- Email send failures
- Notification delivery
- Password changes
- Login/logout

### Logger Integration
```php
if ( class_exists( 'LGP_Logger' ) ) {
    LGP_Logger::log_event(
        $user_id,
        'ticket_created_from_email',
        array(
            'ticket_id'  => $ticket_id,
            'company_id' => $company_id,
            'source'     => 'pop3',
            'from'       => $sender_email,
        )
    );
}
```

### Audit Table
```sql
CREATE TABLE lgp_audit_log (
    id BIGINT UNSIGNED PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    action VARCHAR(100),
    company_id BIGINT UNSIGNED,
    meta LONGTEXT,  -- JSON
    created_at DATETIME,
    KEY user_id,
    KEY action,
    KEY created_at
);
```

---

## 11. Configuration Checklist

### Before Production Deployment

- [ ] **POP3 Settings Configured**
  - Server: mail.yourdomain.com
  - Port: 110 (or 995 for SSL)
  - Username: tickets@yourdomain.com
  - Password: [strong password, will be encrypted]

- [ ] **Support Email Addresses Registered**
  - Add all support channels via filter
  - Example: `support@`, `tickets@`, `help@`

- [ ] **Company Domains Mapped**
  - Each company has `contact_email` set to a company domain
  - Users from that domain auto-assigned to company

- [ ] **Email Templates Customized**
  - Update confirmation emails
  - Update notification templates
  - Set "From" address

- [ ] **File Permissions Set**
  ```bash
  chmod 755 wp-content/uploads/lgp-attachments
  chmod 644 wp-content/uploads/lgp-attachments/.htaccess
  chmod 644 wp-content/uploads/lgp-attachments/index.php
  ```

- [ ] **WordPress Nonces Enabled**
  - All AJAX and form submissions include nonces
  - Verified via `wp_verify_nonce()`

- [ ] **SSL/TLS for Admin**
  - Define FORCE_SSL_ADMIN in wp-config.php
  - All password transmission over HTTPS

---

## 12. Monitoring & Maintenance

### Daily Tasks
```bash
# Check cron is running
curl -s 'http://yoursite.com/wp-cron.php?doing_wp_cron' >/dev/null 2>&1

# Monitor log file
tail -f /path/to/wp-content/debug.log | grep "LGP"
```

### Weekly Tasks
- Check dedup table size (should stay < 10K records)
- Review failed email logs
- Verify attachments are being saved to correct folders
- Check disk space usage

### Monthly Tasks
- Clean expired dedup records
- Audit user creation logs
- Review failed authentication attempts
- Update email templates as needed

---

## 13. Troubleshooting Security Issues

### Issue: Duplicate Tickets Despite Deduplication

**Cause**: Email hash collision or settings changed
**Solution**:
```php
// Check dedup table
SELECT COUNT(*) FROM wp_lgp_email_dedup WHERE expires_at > NOW();

// If needed, manually clean
DELETE FROM wp_lgp_email_dedup WHERE expires_at < DATE_SUB(NOW(), INTERVAL 2 HOUR);
```

### Issue: Attachments Not Saving

**Causes**:
1. Directory not writable: `chmod 755 wp-content/uploads/lgp-attachments`
2. File size too large: Check against 10MB limit
3. MIME type not allowed: Whitelist may need update

**Solution**:
```php
// Test attachment handler
$result = LGP_Attachment_Handler::save_attachment(
    $temp_file,
    'test.pdf',
    $ticket_id,
    $company_id
);
var_dump($result);  // Check for false or WP_Error
```

### Issue: Emails Not Being Processed

**Causes**:
1. POP3 credentials wrong
2. Cron not running
3. Memory limit too low
4. Email encoding issue

**Solution**:
```php
// Test POP3 manually
$connection = imap_open('{mail.example.com:110/pop3}INBOX', 'user@example.com', 'password');
if ( !$connection ) {
    echo imap_last_error();
}

// Check cron
wp cron test  // WP-CLI
```

---

## 14. Compliance

### GDPR Compliance
- User data (email, name) collected from incoming email only
- Users can request deletion via portal
- Audit logs retained 90 days minimum
- Data retention policy documented

### Data Security
- Passwords never logged
- POP3 credentials encrypted at rest
- Attachments scanned for malware (optional integration)
- SSL/TLS for all external connections

### Backup & Recovery
- Database includes email_dedup, thread_history, attachments metadata
- Attachments themselves in wp-content/uploads (separate backup)
- Daily backups recommended
- Test recovery procedure monthly

---

## 15. Production Deployment Checklist

```
SECURITY PHASE
[ ] SSL certificate installed and valid
[ ] FORCE_SSL_ADMIN enabled
[ ] .htaccess files in place
[ ] wp-config.php secured (wp-cli recommended)
[ ] Database password changed from default
[ ] WordPress user "admin" renamed

EMAIL SECURITY PHASE
[ ] POP3 account created with strong password
[ ] POP3 password encrypted and tested
[ ] Support email addresses registered
[ ] Email templates customized
[ ] From/Reply-To addresses set correctly

ATTACHMENT SECURITY PHASE
[ ] Directory permissions set (755)
[ ] .htaccess blocking PHP execution
[ ] MIME type whitelist reviewed
[ ] File size limits tested
[ ] Company folder structure created

TESTING PHASE
[ ] Send test email to support address
[ ] Verify ticket created and user auto-created
[ ] Verify notification sent to Support Team
[ ] Verify Partner Company notification
[ ] Test attachment upload and download
[ ] Test deduplication (send same email twice)
[ ] Verify audit logs record events
[ ] Check all files are owned by web server user

MONITORING PHASE
[ ] Log monitoring set up
[ ] Error email alerts configured
[ ] Cron monitoring enabled (WP Control or similar)
[ ] Disk space monitoring enabled
[ ] Database backup scheduled
[ ] Notify support team of new system
```

---

## Questions & Support

For security concerns or clarifications, contact your development team with:
1. Specific security scenario or vulnerability
2. Steps to reproduce
3. Error logs or debug output
4. WordPress/PHP version information
