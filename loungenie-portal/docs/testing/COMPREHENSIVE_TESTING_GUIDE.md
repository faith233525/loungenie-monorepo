# LounGenie Portal: Email-to-Ticket Testing Checklist

**Version**: 1.8.0  
**Test Date**: ___________  
**Tester**: ___________  
**Environment**: [ ] Development [ ] Staging [ ] Production  

---

## Test Suite Overview

This checklist covers:
- ✅ Basic email-to-ticket functionality
- ✅ Multi-path email intake (hook + POP3)
- ✅ Deduplication & duplicate prevention
- ✅ Automatic user creation & role assignment
- ✅ Attachment handling & security
- ✅ Notification delivery
- ✅ Role-based access (Support vs Partner)
- ✅ Shared hosting optimization
- ✅ Error recovery & edge cases

---

## Section 1: Email Intake Paths

### 1.1 Test Hook-Based Email Interception

**Objective**: Verify emails sent via `wp_mail()` are intercepted and processed

**Prerequisites**:
- [ ] Plugin activated
- [ ] POP3 settings configured
- [ ] Test company exists with domain mapping

**Steps**:

```php
// Step 1: Trigger outgoing email to support address
$to = 'support@loungenie.com';
$subject = '[TEST] Hook Interception - ' . date('Y-m-d H:i:s');
$message = "This email was sent via wp_mail() hook.\n\nTest content.";
wp_mail( $to, $subject, $message );
```

**Expected Results**:
- [ ] Email is intercepted by filter
- [ ] Ticket created with `source=hook`
- [ ] Thread history contains email content
- [ ] Log shows "Created ticket via hook"

**Result**: _____ PASS / FAIL

---

### 1.2 Test POP3 Email Polling

**Objective**: Verify emails received via POP3 are fetched and processed

**Prerequisites**:
- [ ] POP3 server configured and accessible
- [ ] POP3 inbox contains test emails
- [ ] Company domains mapped in database

**Steps**:

```bash
# Step 1: Send test email from partner domain
echo "Subject: [TEST] POP3 Polling - $(date '+%Y-%m-%d %H:%M:%S')" > test-email.txt
echo "" >> test-email.txt
echo "This email was sent via POP3." >> test-email.txt

# Send to POP3 address
mail -s "[TEST] POP3 Test" tickets@poolsafe.com < test-email.txt

# Step 2: Trigger manual cron or wait 15 minutes
do_action( 'lgp_process_emails_cron' );

# Step 3: Check database
# SELECT * FROM wp_lgp_tickets WHERE source='pop3' ORDER BY created_at DESC LIMIT 1;
```

**Expected Results**:
- [ ] Email fetched from POP3
- [ ] Ticket created with `source=pop3`
- [ ] Email marked as deleted/processed in inbox
- [ ] Log shows "Processed X emails via POP3"

**Result**: _____ PASS / FAIL

---

### 1.3 Test Email with Subject Priority Tags

**Objective**: Verify priority is correctly detected from subject

**Test Cases**:

| Subject | Expected Priority | Result |
|---------|------------------|--------|
| `[URGENT] Server Down` | high | [ ] PASS / FAIL |
| `[CRITICAL] Login Issue` | high | [ ] PASS / FAIL |
| `[HIGH] Feature Request` | high | [ ] PASS / FAIL |
| `Access Problem` | medium | [ ] PASS / FAIL |
| `[LOW] Documentation Update` | low | [ ] PASS / FAIL |
| `Question about API` | low | [ ] PASS / FAIL |

**Steps**:
1. Send email with subject from test case
2. Wait for processing
3. Check `wp_lgp_service_requests.priority` field

**Example**:
```bash
mail -s "[URGENT] Test Ticket" support@loungenie.com << EOF
System is down.
EOF
```

---

## Section 2: Deduplication

### 2.1 Test Deduplication Hash Generation

**Objective**: Verify emails are hashed consistently

**Steps**:

```php
$email = 'jane.doe@poolsafeinc.com';
$subject = 'Test Ticket';
$timestamp = time();

$hash1 = LGP_Deduplication::generate_hash( $email, $subject, $timestamp );
$hash2 = LGP_Deduplication::generate_hash( $email, $subject, $timestamp + 30 );  // 30 sec later
$hash3 = LGP_Deduplication::generate_hash( $email, $subject, $timestamp + 120 ); // 2 min later

// All should match (rounded to minute)
echo "Hash 1: $hash1\n";
echo "Hash 2: $hash2\n";
echo "Hash 3: $hash3\n";
echo "All match: " . ( $hash1 === $hash2 && $hash2 === $hash3 ? "YES" : "NO" ) . "\n";
```

**Expected Results**:
- [ ] Hashes for same email/subject within same minute are identical
- [ ] Hash length is 64 characters (SHA256)
- [ ] Different subjects produce different hashes

**Result**: _____ PASS / FAIL

---

### 2.2 Test Duplicate Prevention (Hook + POP3)

**Objective**: Verify same email processed via both paths creates only one ticket

**Scenario**: Email sent to support address AND received via POP3

**Steps**:

```php
// Step 1: Manually trigger hook interception
$to = 'support@loungenie.com';
$subject = '[TEST-DEDUP] ' . date('Y-m-d H:i:s');
$message = "Email body for dedup test.";
wp_mail( $to, $subject, $message );

// This automatically:
// 1. Intercepts via hook
// 2. Creates ticket (e.g., ticket #100)
// 3. Registers in dedup table

// Step 2: Manually simulate POP3 receiving same email
// Simulate processing of email with identical hash

global $wpdb;
$duplicate_check = $wpdb->get_row(
    "SELECT ticket_id FROM {$wpdb->prefix}lgp_email_dedup 
     WHERE email_hash = '" . hash('sha256', strtolower($to) . '|' . strtolower($subject) . '|' . (time()/60)*60) . "'"
);

echo "Original ticket created: #" . $duplicate_check->ticket_id . "\n";

// Step 3: Verify no second ticket created
$ticket_count = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}lgp_tickets WHERE status='open' AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
);

echo "Tickets created in last 5 minutes: $ticket_count\n";
```

**Expected Results**:
- [ ] Only 1 ticket created despite dual processing
- [ ] Dedup table has record linking to original ticket
- [ ] Log shows "Email duplicate detected"

**Result**: _____ PASS / FAIL

---

### 2.3 Test Dedup Record Expiration

**Objective**: Verify old dedup records are cleaned up

**Steps**:

```php
// Step 1: Create old dedup record (manually insert)
global $wpdb;
$wpdb->insert(
    $wpdb->prefix . 'lgp_email_dedup',
    array(
        'email_hash' => hash('sha256', 'old-email@example.com'),
        'ticket_id' => 99,
        'company_id' => 1,
        'expires_at' => date('Y-m-d H:i:s', time() - 7200),  // 2 hours ago
    )
);

// Step 2: Run cleanup
LGP_Deduplication::cleanup_expired();

// Step 3: Verify record deleted
$count = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}lgp_email_dedup WHERE expires_at < NOW()"
);

echo "Expired records remaining: $count\n";  // Should be 0
```

**Expected Results**:
- [ ] Expired records removed
- [ ] Active records (< 1 hour) preserved
- [ ] Database cleaned up successfully

**Result**: _____ PASS / FAIL

---

## Section 3: Automatic User Creation

### 3.1 Test User Creation from Email Domain

**Objective**: Verify new user created when email from unknown sender received

**Prerequisites**:
- [ ] Company "Pool Safe Inc" mapped to domain `poolsafeinc.com`
- [ ] No user with email `newuser@poolsafeinc.com` exists

**Steps**:

```php
// Step 1: Send email from new user
$email = 'newuser@poolsafeinc.com';
$company_id = 1;  // Pool Safe Inc

// Simulate email receipt
$user_id = LGP_User_Creator::get_or_create_user( $email, $company_id, 'New User' );

// Step 2: Verify user created
if ( is_wp_error( $user_id ) ) {
    echo "Error: " . $user_id->get_error_message();
} else {
    $user = get_user_by( 'id', $user_id );
    echo "User created:\n";
    echo "  Login: {$user->user_login}\n";
    echo "  Email: {$user->user_email}\n";
    echo "  Roles: " . implode( ', ', $user->roles ) . "\n";
    echo "  Company ID: " . get_user_meta( $user_id, '_lgp_company_id', true ) . "\n";
}

// Step 3: Verify welcome email sent
// Check email logs or test account

// Step 4: Verify can log in
wp_signon( array(
    'user_login'    => $user->user_login,
    'user_password' => $password,  // From welcome email
    'remember'      => false,
) );
```

**Expected Results**:
- [ ] User created with `lgp_partner` role
- [ ] Username generated from email (e.g., `newuser` or `newuser-poolsafeinc`)
- [ ] User linked to correct company via `_lgp_company_id` meta
- [ ] Welcome email sent with password reset link
- [ ] User can log in after resetting password

**Result**: _____ PASS / FAIL

---

### 3.2 Test Duplicate User Prevention

**Objective**: Verify existing users not duplicated

**Steps**:

```php
// Step 1: Create user
$email = 'jane.doe@poolsafeinc.com';
$user_id_1 = LGP_User_Creator::get_or_create_user( $email, 1 );

// Step 2: Try to create again
$user_id_2 = LGP_User_Creator::get_or_create_user( $email, 1 );

// Step 3: Verify same user returned
echo "First call: $user_id_1\n";
echo "Second call: $user_id_2\n";
echo "Same user: " . ( $user_id_1 === $user_id_2 ? "YES" : "NO" ) . "\n";

// Verify no duplicate users with same email
$user = get_user_by( 'email', $email );
echo "Users with email '$email': 1\n";  // Only 1
```

**Expected Results**:
- [ ] Both calls return same user ID
- [ ] No duplicate users created
- [ ] Only one user with email address

**Result**: _____ PASS / FAIL

---

### 3.3 Test Display Name Extraction

**Objective**: Verify user display name set correctly

**Test Cases**:

| Email | Expected Display Name | Result |
|-------|------------------------|--------|
| `jane.doe@poolsafeinc.com` | Jane Doe | [ ] PASS / FAIL |
| `john-smith@poolsafeinc.com` | John Smith | [ ] PASS / FAIL |
| `alice_jones@poolsafeinc.com` | Alice Jones | [ ] PASS / FAIL |
| `support@poolsafeinc.com` | Support | [ ] PASS / FAIL |

---

## Section 4: Attachment Handling

### 4.1 Test File Upload & Storage

**Objective**: Verify attachments saved to correct company folder

**Prerequisites**:
- [ ] Company directories created: `/wp-content/uploads/lgp-attachments/poolsafe-com/`
- [ ] .htaccess in place with PHP blocking rules

**Steps**:

```php
// Step 1: Create test file
$test_content = "This is a test PDF attachment.";
$test_file = wp_tempnam();
file_put_contents( $test_file, $test_content );

// Step 2: Save attachment
$result = LGP_Attachment_Handler::save_attachment(
    $test_file,
    'test-document.pdf',
    123,  // ticket_id
    1,    // company_id (Pool Safe Inc)
    1     // uploaded_by user_id
);

// Step 3: Verify result
if ( is_array( $result ) ) {
    echo "Attachment saved:\n";
    echo "  ID: {$result['id']}\n";
    echo "  Path: {$result['path']}\n";
    echo "  Size: {$result['size']} bytes\n";
    echo "  MIME: {$result['mime_type']}\n";
} else {
    echo "Failed: " . print_r( $result, true ) . "\n";
}

// Step 4: Verify file in correct location
$expected_path = ABSPATH . 'wp-content/uploads/lgp-attachments/poolsafe-com/';
echo "File exists in company folder: " . ( file_exists( $result['path'] ) ? "YES" : "NO" ) . "\n";
echo "In correct folder: " . ( strpos( $result['path'], $expected_path ) === 0 ? "YES" : "NO" ) . "\n";

// Step 5: Verify not directly accessible
$url = $result['path'];
$response = wp_remote_get( str_replace( ABSPATH, site_url() . '/', $url ) );
echo "Direct HTTP access: " . ( is_wp_error( $response ) ? "BLOCKED (good)" : "ACCESSIBLE (bad)" ) . "\n";
```

**Expected Results**:
- [ ] File saved successfully
- [ ] Path contains company folder name
- [ ] File stored outside web root or protected by .htaccess
- [ ] Direct HTTP access blocked
- [ ] Database record created with metadata

**Result**: _____ PASS / FAIL

---

### 4.2 Test File Size & Type Validation

**Objective**: Verify only allowed files accepted, within size limits

**Test Cases**:

| File | Size | Type | Expected | Result |
|------|------|------|----------|--------|
| report.pdf | 5MB | application/pdf | ✅ ACCEPT | [ ] |
| image.jpg | 2MB | image/jpeg | ✅ ACCEPT | [ ] |
| video.mp4 | 50MB | video/mp4 | ❌ REJECT | [ ] |
| script.php | 10KB | application/x-php | ❌ REJECT | [ ] |
| data.exe | 500KB | application/x-dosexec | ❌ REJECT | [ ] |
| spreadsheet.xlsx | 3MB | application/vnd.ms-excel | ✅ ACCEPT | [ ] |

**Steps**:

```php
foreach ( $test_cases as $file_info ) {
    $result = LGP_Attachment_Handler::save_attachment(
        $file_path,
        $file_info['filename'],
        123,
        1
    );
    
    $passed = ( !$result === $file_info['should_fail'] );
    echo $file_info['filename'] . ": " . ( $passed ? "PASS" : "FAIL" ) . "\n";
}
```

**Expected Results**:
- [ ] PDF files accepted (< 10MB)
- [ ] Images accepted (JPEG, PNG, GIF)
- [ ] Video files rejected
- [ ] PHP/executable files rejected
- [ ] Files > 10MB rejected
- [ ] Clear error messages for rejected files

**Result**: _____ PASS / FAIL

---

### 4.3 Test Attachment Count Limit

**Objective**: Verify max 5 attachments per ticket enforced

**Steps**:

```php
$ticket_id = 123;

// Try to upload 6 attachments
for ( $i = 1; $i <= 6; $i++ ) {
    // Create test file
    $test_file = wp_tempnam();
    file_put_contents( $test_file, "Attachment $i" );
    
    // Try to save
    $result = LGP_Attachment_Handler::save_attachment(
        $test_file,
        "test-$i.txt",
        $ticket_id,
        1
    );
    
    if ( is_array( $result ) ) {
        echo "Attachment $i: SAVED\n";
    } else {
        echo "Attachment $i: REJECTED - " . $result . "\n";
    }
}

// Verify count
global $wpdb;
$count = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}lgp_ticket_attachments WHERE ticket_id = $ticket_id"
);

echo "Total attachments on ticket: $count (expected max 5)\n";
```

**Expected Results**:
- [ ] First 5 attachments saved successfully
- [ ] 6th attachment rejected with "maximum exceeded" message
- [ ] Database count shows exactly 5 attachments

**Result**: _____ PASS / FAIL

---

### 4.4 Test Chunked File Reading (Memory Safety)

**Objective**: Verify large files processed without memory exhaustion

**Steps**:

```php
// Create test file larger than normal chunk size
$large_file = wp_tempnam();
$handle = fopen( $large_file, 'w' );

// Write 50MB of data in chunks
for ( $i = 0; $i < 50; $i++ ) {
    fwrite( $handle, str_repeat( "x", 1024 * 1024 ) );  // 1MB at a time
}
fclose( $handle );

// Monitor memory usage before
$mem_before = memory_get_usage( true );

// Try to save (should handle chunking)
$result = LGP_Attachment_Handler::save_attachment(
    $large_file,
    'large-file.bin',
    123,
    1
);

// Monitor after
$mem_after = memory_get_usage( true );
$mem_increase = ( $mem_after - $mem_before ) / 1024 / 1024;

echo "Memory increase: " . round( $mem_increase, 2 ) . "MB (should be < 2MB)\n";
echo "File saved: " . ( is_array( $result ) ? "YES" : "NO" ) . "\n";

// Note: File should be rejected due to 10MB limit, but test proves chunking works
```

**Expected Results**:
- [ ] Chunked reading prevents memory spike
- [ ] Memory increase < 2MB
- [ ] Large files processed without timeout

**Result**: _____ PASS / FAIL

---

## Section 5: Notifications

### 5.1 Test Support Team Notification

**Objective**: Verify Support Team receives notifications on all events

**Steps**:

```php
// Step 1: Create test ticket
$ticket_id = 123;
$company_id = 1;
$user_id = 2;

// Step 2: Trigger notification
do_action( 'lgp_ticket_created', $ticket_id, $company_id, $user_id, array(
    'from' => 'sender@example.com',
    'body' => 'Test ticket content',
) );

// Step 3: Check notification received
// Look for email in:
// - Debug log
// - Server maillog
// - Test email account (if configured)

// Step 4: Verify content
// Email should contain:
// - "New support ticket has been created"
// - Ticket #123
// - Company name
// - Priority
// - Content snippet
```

**Expected Results**:
- [ ] Email sent to all `lgp_support` users
- [ ] Falls back to admin_email if no support users
- [ ] Email subject includes ticket number
- [ ] Email body includes company, priority, content
- [ ] Email footer has portal link

**Result**: _____ PASS / FAIL

---

### 5.2 Test Partner Company Notification

**Objective**: Verify Partner Company only receives notifications for their tickets

**Steps**:

```php
// Create ticket for Company A
$ticket_company_a = 123;
$company_a_id = 1;
$user_a_id = 2;

// Trigger notification
do_action( 'lgp_ticket_created', $ticket_company_a, $company_a_id, $user_a_id );

// Partner from Company B should NOT receive email
// Partner from Company A SHOULD receive email

global $wpdb;

// Get partners for Company A
$partners_a = $wpdb->get_results(
    "SELECT u.ID, u.user_email FROM {$wpdb->users} u
     INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
     WHERE um.meta_key = '_lgp_company_id' AND um.meta_value = $company_a_id"
);

// Get partners for Company B
$partners_b = $wpdb->get_results(
    "SELECT u.ID, u.user_email FROM {$wpdb->users} u
     INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
     WHERE um.meta_key = '_lgp_company_id' AND um.meta_value = 2"
);

echo "Partners from Company A (should receive): " . count( $partners_a ) . "\n";
echo "Partners from Company B (should NOT receive): " . count( $partners_b ) . "\n";
```

**Expected Results**:
- [ ] Partner Company receives notification only for their company's tickets
- [ ] Cross-company notifications NOT sent
- [ ] Portal alert created for Partner Company user (if enabled)

**Result**: _____ PASS / FAIL

---

### 5.3 Test Notification Status Change

**Objective**: Verify different notifications sent for status changes

**Test Cases**:

| Status | Event | Should Send | Result |
|--------|-------|-------------|--------|
| open | Created | ✅ | [ ] |
| Under Review | Updated | ✅ | [ ] |
| Resolved | Status Changed | ✅ | [ ] |
| Closed | Status Changed | ✅ | [ ] |

---

## Section 6: Role-Based Access Control

### 6.1 Test Support Team Permissions

**Objective**: Verify Support Team can see all tickets

**Steps**:

```php
// Login as Support Team user
wp_set_current_user( $support_user_id );

// REST API test
$response = wp_remote_get( home_url( 'wp-json/lgp/v1/tickets' ), array(
    'headers' => array(
        'Authorization' => 'Bearer ' . wp_create_nonce( 'lgp_api' ),
    ),
) );

// Parse response
$tickets = json_decode( wp_remote_retrieve_body( $response ) );

echo "Tickets visible to Support Team: " . count( $tickets ) . "\n";
echo "Should include all companies: ";

// Verify includes tickets from all companies
$company_ids = array_unique( array_column( $tickets, 'company_id' ) );
echo count( $company_ids ) > 1 ? "YES" : "NO\n";
```

**Expected Results**:
- [ ] Support Team can view all tickets
- [ ] Can see tickets from all companies
- [ ] Can access REST API endpoints
- [ ] Can create/edit/delete tickets

**Result**: _____ PASS / FAIL

---

### 6.2 Test Partner Company Permissions

**Objective**: Verify Partner Company sees only their tickets

**Steps**:

```php
// Login as Partner Company user
wp_set_current_user( $partner_user_id );

// Get partner's company
$partner_company = get_user_meta( $partner_user_id, '_lgp_company_id', true );

// REST API test
$response = wp_remote_get( home_url( 'wp-json/lgp/v1/tickets' ), array(
    'headers' => array(
        'Authorization' => 'Bearer ' . wp_create_nonce( 'lgp_api' ),
    ),
) );

// Parse response
$tickets = json_decode( wp_remote_retrieve_body( $response ) );

echo "Tickets visible to Partner: " . count( $tickets ) . "\n";

// Verify all are from their company
$for_their_company = array_filter( $tickets, function( $t ) use ( $partner_company ) {
    return $t->company_id == $partner_company;
} );

echo "All from their company: " . ( count( $for_their_company ) === count( $tickets ) ? "YES" : "NO\n" );
```

**Expected Results**:
- [ ] Partner Company sees only their company's tickets
- [ ] Cannot access other companies' tickets
- [ ] Cannot create tickets for other companies
- [ ] Cannot edit company data

**Result**: _____ PASS / FAIL

---

## Section 7: Shared Hosting Optimization

### 7.1 Test Cron Locking

**Objective**: Verify cron lock prevents parallel execution

**Steps**:

```php
// Step 1: Set lock
set_transient( 'lgp_email_processing_lock', time(), 300 );

// Step 2: Try to process emails
do_action( 'lgp_process_emails_cron' );

// Step 3: Verify lock prevented execution
// Check logs for "Email processing already running, skipping"

echo "Lock status: ";
echo get_transient( 'lgp_email_processing_lock' ) ? "LOCKED" : "FREE";

// Step 4: Manually clear lock (after test)
delete_transient( 'lgp_email_processing_lock' );
```

**Expected Results**:
- [ ] Cron skips if already running
- [ ] Log shows lock message
- [ ] No database errors from concurrent access
- [ ] Lock expires after timeout

**Result**: _____ PASS / FAIL

---

### 7.2 Test Batch Processing

**Objective**: Verify emails processed in batches (max 10/run)

**Steps**:

```php
// Step 1: Add 20 emails to POP3 inbox

// Step 2: Trigger cron
do_action( 'lgp_process_emails_cron' );

// Step 3: Check results
global $wpdb;
$processed = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}lgp_email_dedup 
     WHERE DATE(processed_at) = CURDATE()"
);

echo "Emails processed this batch: $processed (should be <= 10)\n";

// Step 4: Run cron again
do_action( 'lgp_process_emails_cron' );

$total_processed = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}lgp_email_dedup 
     WHERE DATE(processed_at) = CURDATE()"
);

echo "Total after second run: $total_processed\n";
```

**Expected Results**:
- [ ] First run processes max 10 emails
- [ ] Remaining emails processed on next run
- [ ] No timeout errors
- [ ] No memory exhaustion

**Result**: _____ PASS / FAIL

---

### 7.3 Test Cron Interval (15 minutes)

**Objective**: Verify cron scheduled for shared hosting (not 5 minutes)

**Steps**:

```php
// Check registered cron
$timestamp = wp_next_scheduled( 'lgp_process_emails_cron' );
$next_two = wp_get_schedules()['lgp_fifteen_minutes'];

echo "Cron interval: " . ( $next_two['interval'] / 60 ) . " minutes\n";
echo "Should be 15 minutes for shared hosting\n";

// If 5-minute interval found, increase to 15
if ( $next_two['interval'] < 600 ) {
    wp_clear_scheduled_hook( 'lgp_process_emails_cron' );
    wp_schedule_event( time(), 'lgp_fifteen_minutes', 'lgp_process_emails_cron' );
    echo "Updated to 15-minute schedule\n";
}
```

**Expected Results**:
- [ ] Cron interval is 15 minutes (900 seconds)
- [ ] Not 5 minutes (shared hosting optimization)
- [ ] Can be manually adjusted if needed

**Result**: _____ PASS / FAIL

---

## Section 8: Error Handling & Edge Cases

### 8.1 Test Invalid Email Address

**Objective**: Verify invalid emails are rejected

**Test Cases**:

| Email | Expected | Result |
|-------|----------|--------|
| `not-an-email` | ❌ REJECT | [ ] |
| `missing@domain` | ❌ REJECT | [ ] |
| `@example.com` | ❌ REJECT | [ ] |
| ` ` (space) | ❌ REJECT | [ ] |
| `valid@example.com` | ✅ ACCEPT | [ ] |

---

### 8.2 Test Unknown Company Domain

**Objective**: Verify emails from unknown domains are handled

**Steps**:

```php
// Send email from domain not in company mapping
$email = 'user@unknowndomain.com';
$company_id = LGP_User_Creator::find_company_by_email_domain( $email );

echo "Company found for unknown domain: " . ( $company_id ? "#$company_id" : "NONE (expected)" ) . "\n";
```

**Expected Results**:
- [ ] Returns false/no company found
- [ ] Email skipped or logged as unmatched
- [ ] No ticket created
- [ ] No error in logs (just info message)

**Result**: _____ PASS / FAIL

---

### 8.3 Test Connection Failures

**Objective**: Verify graceful handling of POP3 connection errors

**Steps**:

```php
// Simulate bad POP3 credentials
$bad_settings = array(
    'pop3_server'   => 'mail.example.com',
    'pop3_port'     => 110,
    'pop3_username' => 'wrong@example.com',
    'pop3_password' => 'wrongpassword',
);

// Try to connect
$connection = @imap_open(
    '{' . $bad_settings['pop3_server'] . ':' . $bad_settings['pop3_port'] . '/pop3}INBOX',
    $bad_settings['pop3_username'],
    $bad_settings['pop3_password']
);

if ( !$connection ) {
    echo "Connection failed as expected: " . imap_last_error() . "\n";
}

// Verify no crash
// Check logs for graceful error handling
```

**Expected Results**:
- [ ] Connection errors logged
- [ ] Process continues (doesn't crash)
- [ ] Retries on next cron run
- [ ] Admin notified if critical

**Result**: _____ PASS / FAIL

---

## Section 9: Database Integrity

### 9.1 Test Thread History JSON

**Objective**: Verify thread history stored and retrievable as JSON

**Steps**:

```php
global $wpdb;

// Get ticket with thread history
$ticket = $wpdb->get_row(
    "SELECT id, thread_history FROM {$wpdb->prefix}lgp_tickets WHERE id = 123"
);

// Verify JSON format
$thread = json_decode( $ticket->thread_history, true );

echo "Thread entries: " . count( $thread ) . "\n";

if ( is_array( $thread ) && count( $thread ) > 0 ) {
    $entry = $thread[0];
    echo "Entry structure:\n";
    echo "  - timestamp: " . ( isset( $entry['timestamp'] ) ? $entry['timestamp'] : "MISSING" ) . "\n";
    echo "  - user_id: " . ( isset( $entry['user_id'] ) ? $entry['user_id'] : "MISSING" ) . "\n";
    echo "  - email: " . ( isset( $entry['email'] ) ? $entry['email'] : "MISSING" ) . "\n";
    echo "  - type: " . ( isset( $entry['type'] ) ? $entry['type'] : "MISSING" ) . "\n";
    echo "  - content: " . ( isset( $entry['content'] ) ? "OK" : "MISSING" ) . "\n";
}
```

**Expected Results**:
- [ ] Thread history is valid JSON
- [ ] Contains array of entries
- [ ] Each entry has timestamp, user_id, email, type, content
- [ ] Attachments array present

**Result**: _____ PASS / FAIL

---

### 9.2 Test Data Consistency

**Objective**: Verify foreign key relationships intact

**Steps**:

```sql
-- Verify no orphaned tickets
SELECT COUNT(*) as orphaned_tickets
FROM wp_lgp_tickets t
LEFT JOIN wp_lgp_service_requests sr ON t.service_request_id = sr.id
WHERE sr.id IS NULL;

-- Should return 0

-- Verify no orphaned attachments
SELECT COUNT(*) as orphaned_attachments
FROM wp_lgp_ticket_attachments a
LEFT JOIN wp_lgp_tickets t ON a.ticket_id = t.id
WHERE t.id IS NULL;

-- Should return 0
```

**Expected Results**:
- [ ] No orphaned tickets
- [ ] No orphaned attachments
- [ ] All relationships intact
- [ ] Database is consistent

**Result**: _____ PASS / FAIL

---

## Section 10: Performance

### 10.1 Test Email Processing Speed

**Objective**: Measure email-to-ticket conversion time

**Steps**:

```php
$start = microtime( true );

do_action( 'lgp_process_emails_cron' );

$end = microtime( true );
$duration = $end - $start;

echo "Email processing time: " . round( $duration, 2 ) . " seconds\n";
echo "Expected: < 30 seconds (shared hosting timeout)\n";
```

**Expected Results**:
- [ ] Processes 10 emails in < 5 seconds
- [ ] Never exceeds 30-second timeout
- [ ] Batch approach prevents timeouts

**Result**: _____ PASS / FAIL

---

### 10.2 Test Database Query Performance

**Objective**: Verify queries use indexes efficiently

**Steps**:

```sql
-- Enable query profiling
SET SESSION sql_mode = '';
SET SESSION profiling = 1;

-- Run slow queries
SELECT * FROM wp_lgp_service_requests WHERE company_id = 1 AND status = 'open';
SELECT * FROM wp_lgp_email_dedup WHERE expires_at > NOW();
SELECT * FROM wp_lgp_ticket_attachments WHERE ticket_id = 123;

-- Check query execution
SHOW PROFILES;

-- Should show USING KEY in EXPLAIN
EXPLAIN SELECT * FROM wp_lgp_service_requests WHERE company_id = 1 AND status = 'open';
```

**Expected Results**:
- [ ] All queries use indexes
- [ ] Query time < 100ms
- [ ] No full table scans

**Result**: _____ PASS / FAIL

---

## Final Certification

**All sections completed**: [ ] YES / [ ] NO

**Overall result**: 

- [ ] **PASS**: All tests passed, ready for production
- [ ] **PASS WITH WARNINGS**: Minor issues noted below
- [ ] **FAIL**: Critical issues need resolution

**Issues Found**:

```
1. ____________________________________________________
2. ____________________________________________________
3. ____________________________________________________
```

**Recommendations**:

```
1. ____________________________________________________
2. ____________________________________________________
```

---

**Test Report Signed**:

Tester: ______________________  
Date: ________________________  
Signature: ____________________  

**Approved for Production**: [ ] YES [ ] NO

Manager: ____________________  
Date: ________________________  

---

This comprehensive testing checklist ensures the email-to-ticket system is production-ready, secure, and optimized for shared hosting environments.
