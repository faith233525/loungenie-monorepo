# Email Integration Test Cases

## Overview

This document outlines comprehensive test cases for the shared mailbox email integration.

## Test Environment Setup

### Prerequisites

- WordPress development installation
- Pool Safe Portal plugin installed
- Shared mailbox configured
- Azure AD application with proper permissions
- Test data (sample emails)

### Setup Steps

```bash
# 1. Create test mailbox (optional, can use existing)
wp option update lgp_shared_mailbox "test-support@poolsafe-local.test"
wp option update lgp_azure_tenant_id "test-tenant-id"
wp option update lgp_azure_client_id "test-client-id"
wp option update lgp_azure_client_secret "test-secret"

# 2. Create test tickets custom post type if not exists
wp post-type create ticket --porcelain

# 3. Create test contacts custom post type if not exists
wp post-type create contact --porcelain

# 4. Enable debug logging
define('LGP_EMAIL_DEBUG', true);
```

## Unit Tests

### Test Class: LGP_Graph_Client

#### Test 1.1: Connection and Token Management

**Test Case**: `test_graph_client_connection`

**Description**: Verify Graph Client can authenticate and get valid token

**Steps**:
1. Initialize LGP_Graph_Client
2. Verify token is obtained
3. Verify token expiration check

**Expected Result**: 
- Token is valid string
- Token expiration is in future

**Test Code**:
```php
public function test_graph_client_connection() {
    $graph = new LGP_Graph_Client();
    
    // Check token exists
    $token = $graph->get_access_token();
    $this->assertNotEmpty($token);
    
    // Check token format
    $this->assertStringStartsWith('eyJ', $token); // JWT format
}
```

#### Test 1.2: Get Folders

**Test Case**: `test_graph_client_get_folders`

**Description**: Verify client can retrieve mailbox folders

**Steps**:
1. Call get_folders()
2. Verify response contains folders

**Expected Result**:
- Response is array
- Contains 'value' key with folders
- Inbox folder present

**Test Code**:
```php
public function test_graph_client_get_folders() {
    $graph = new LGP_Graph_Client();
    $folders = $graph->get_folders();
    
    $this->assertIsArray($folders);
    $this->assertArrayHasKey('value', $folders);
    $this->assertGreater(count($folders['value']), 0);
    
    $folder_names = array_map(function($f) { return $f['displayName']; }, $folders['value']);
    $this->assertContains('Inbox', $folder_names);
}
```

### Test Class: LGP_Email_Ingest

#### Test 2.1: Email Sync - New Tickets

**Test Case**: `test_sync_creates_new_tickets`

**Description**: Verify emails create new tickets

**Setup**:
1. Add 3 test emails to shared mailbox
2. Clear any existing delta token

**Steps**:
1. Call sync_messages()
2. Verify stats show correct counts

**Expected Result**:
- stats['total'] = 3
- stats['created'] = 3
- stats['updated'] = 0
- stats['errors'] = 0
- 3 new ticket posts created

**Test Code**:
```php
public function test_sync_creates_new_tickets() {
    // Send test emails first
    $this->send_test_email('test1@example.com', 'Test Subject 1', 'Body 1');
    $this->send_test_email('test2@example.com', 'Test Subject 2', 'Body 2');
    $this->send_test_email('test3@example.com', 'Test Subject 3', 'Body 3');
    
    // Clear delta token
    delete_transient('lgp_email_delta_token');
    
    $ingest = new LGP_Email_Ingest();
    $stats = $ingest->sync_messages();
    
    $this->assertEquals(3, $stats['total']);
    $this->assertEquals(3, $stats['created']);
    $this->assertEquals(0, $stats['errors']);
    
    // Verify tickets were created
    $tickets = get_posts(array(
        'post_type'  => 'ticket',
        'meta_key'   => '_email_source',
        'meta_value' => 1,
        'numberposts' => -1
    ));
    
    $this->assertGreaterThanOrEqual(3, count($tickets));
}
```

#### Test 2.2: Email Sync - Idempotency

**Test Case**: `test_sync_idempotent`

**Description**: Verify same email isn't processed twice

**Setup**:
1. Create ticket from email
2. Run sync again without new emails

**Steps**:
1. First sync - should create ticket
2. Second sync - should skip same email
3. Verify message ID is recorded

**Expected Result**:
- First sync: created = 1
- Second sync: created = 0, skipped = 1
- Ticket has _email_message_id meta

**Test Code**:
```php
public function test_sync_idempotent() {
    $test_email = 'idempotent@example.com';
    $this->send_test_email($test_email, 'Idempotent Test', 'Body');
    
    // First sync
    delete_transient('lgp_email_delta_token');
    $ingest = new LGP_Email_Ingest();
    $stats1 = $ingest->sync_messages();
    
    $this->assertEquals(1, $stats1['created']);
    $ticket_id = $this->get_last_created_ticket();
    $message_id = get_post_meta($ticket_id, '_email_message_id', true);
    
    // Second sync (same email)
    $stats2 = $ingest->sync_messages();
    
    $this->assertEquals(0, $stats2['created']);
    $this->assertEquals(1, $stats2['skipped']);
}
```

#### Test 2.3: Email Subject and Content

**Test Case**: `test_ticket_content_from_email`

**Description**: Verify ticket created with correct content

**Setup**:
1. Send email with specific subject and body

**Steps**:
1. Sync emails
2. Get created ticket
3. Verify post_title and post_content match

**Expected Result**:
- Ticket title matches email subject
- Ticket content contains email body
- Email body is escaped properly

**Test Code**:
```php
public function test_ticket_content_from_email() {
    $subject = 'Test Subject with Special <chars>';
    $body = '<p>Test body content</p>';
    
    $this->send_test_email('content@example.com', $subject, $body);
    delete_transient('lgp_email_delta_token');
    
    $ingest = new LGP_Email_Ingest();
    $ingest->sync_messages();
    
    $ticket = $this->get_last_created_ticket();
    $this->assertEquals($subject, $ticket->post_title);
    $this->assertStringContainsString('Test body content', $ticket->post_content);
}
```

#### Test 2.4: Attachment Processing

**Test Case**: `test_email_attachments_processed`

**Description**: Verify attachments are downloaded and attached to ticket

**Setup**:
1. Send email with attachments
2. Use test files (PDF, image, etc)

**Steps**:
1. Sync emails
2. Get created ticket
3. Verify attachments exist

**Expected Result**:
- Attachments downloaded to ticket folder
- Attachment posts created
- File accessible at path

**Test Code**:
```php
public function test_email_attachments_processed() {
    $this->send_test_email_with_attachment(
        'attach@example.com',
        'Attachment Test',
        'Body',
        'test-file.pdf'
    );
    
    delete_transient('lgp_email_delta_token');
    
    $ingest = new LGP_Email_Ingest();
    $ingest->sync_messages();
    
    $ticket = $this->get_last_created_ticket();
    
    // Check for attachment posts
    $attachments = get_children(array(
        'post_parent' => $ticket->ID,
        'post_type'   => 'attachment'
    ));
    
    $this->assertGreater(count($attachments), 0);
    
    // Check file exists
    $attachment = reset($attachments);
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/tickets/' . $ticket->ID . '/test-file.pdf';
    $this->assertFileExists($file_path);
}
```

#### Test 2.5: Contact Creation from Email

**Test Case**: `test_contact_created_from_email`

**Description**: Verify contact is created when processing email

**Setup**:
1. Send email from new sender

**Steps**:
1. Sync emails
2. Get created ticket
3. Verify contact was created
4. Verify ticket references contact

**Expected Result**:
- Contact post created
- Contact has email meta
- Ticket has _contact_id meta
- Contact is linkable

**Test Code**:
```php
public function test_contact_created_from_email() {
    $sender_email = 'newcontact@example.com';
    $this->send_test_email($sender_email, 'Contact Test', 'Body');
    
    delete_transient('lgp_email_delta_token');
    $ingest = new LGP_Email_Ingest();
    $ingest->sync_messages();
    
    $ticket = $this->get_last_created_ticket();
    $contact_id = get_post_meta($ticket->ID, '_contact_id', true);
    
    $this->assertNotEmpty($contact_id);
    
    $contact = get_post($contact_id);
    $this->assertEquals('contact', $contact->post_type);
    
    $contact_email = get_post_meta($contact_id, '_contact_email', true);
    $this->assertEquals($sender_email, $contact_email);
}
```

### Test Class: LGP_Email_Reply

#### Test 3.1: Send Reply via Portal

**Test Case**: `test_send_reply_via_portal`

**Description**: Verify reply is sent via email when posted to portal

**Setup**:
1. Create email-sourced ticket
2. Have test user ready

**Steps**:
1. Create reply comment on ticket
2. Call send_reply()
3. Verify email was sent
4. Verify metadata recorded

**Expected Result**:
- Email sent to original sender
- Comment created with proper metadata
- _email_sent = true
- Reply appears in Graph API sent items

**Test Code**:
```php
public function test_send_reply_via_portal() {
    $ticket_id = $this->create_email_ticket('sender@example.com');
    $author_id = $this->create_test_user();
    
    $reply_handler = new LGP_Email_Reply();
    $reply_content = '<p>Thank you for your message!</p>';
    
    $comment_id = $reply_handler->send_reply(
        $ticket_id,
        $reply_content,
        $author_id
    );
    
    $this->assertGreater($comment_id, 0);
    
    $comment = get_comment($comment_id);
    $this->assertEquals($reply_content, $comment->comment_content);
    
    $email_sent = get_comment_meta($comment_id, '_email_sent', true);
    $this->assertTrue((bool)$email_sent);
    
    // Verify sent to correct recipient
    $sent_to = get_comment_meta($comment_id, '_reply_to_email', true);
    $this->assertEquals('sender@example.com', $sent_to);
}
```

#### Test 3.2: Reply Threading

**Test Case**: `test_reply_uses_email_threading`

**Description**: Verify replies use proper email threading headers

**Setup**:
1. Create email-sourced ticket
2. Get conversation ID from ticket metadata

**Steps**:
1. Send reply
2. Check Graph sent messages
3. Verify threading headers present

**Expected Result**:
- Reply has In-Reply-To header
- Conversation ID preserved
- Email client threads conversation

**Test Code**:
```php
public function test_reply_uses_email_threading() {
    $ticket_id = $this->create_email_ticket('sender@example.com');
    $conversation_id = get_post_meta($ticket_id, '_email_conversation_id', true);
    
    $reply_handler = new LGP_Email_Reply();
    $reply_handler->send_reply($ticket_id, 'Reply message');
    
    // Get sent message and verify headers
    $graph = new LGP_Graph_Client();
    $sent_messages = $graph->request(
        'GET',
        '/users/' . rawurlencode(get_option('lgp_shared_mailbox')) . '/mailFolders/Sent Items/messages?$top=1'
    );
    
    $sent_msg = $sent_messages['value'][0];
    
    $this->assertStringContains('Re:', $sent_msg['subject']);
    $this->assertNotEmpty($sent_msg['internetMessageHeaders']);
}
```

#### Test 3.3: Detect Outlook Replies

**Test Case**: `test_detect_outlook_replies`

**Description**: Verify Outlook replies are detected and recorded

**Setup**:
1. Create email-sourced ticket
2. Send reply from Outlook client to shared mailbox

**Steps**:
1. Call detect_outlook_replies()
2. Verify reply is recorded as comment
3. Verify metadata is correct

**Expected Result**:
- Reply detected and added as comment
- _sent_via_outlook = true
- Comment shows sender name
- Timestamp recorded

**Test Code**:
```php
public function test_detect_outlook_replies() {
    $ticket_id = $this->create_email_ticket('sender@example.com');
    
    // Simulate Outlook reply
    $this->send_outlook_reply(
        $ticket_id,
        'User Name <user@example.com>',
        'Reply from Outlook'
    );
    
    // Detect replies
    $reply_handler = new LGP_Email_Reply();
    $count = $reply_handler->detect_outlook_replies();
    
    $this->assertGreater($count, 0);
    
    // Verify comment was created
    $comments = get_comments(array(
        'post_id' => $ticket_id,
        'meta_key' => '_sent_via_outlook'
    ));
    
    $this->assertGreater(count($comments), 0);
    $comment = $comments[0];
    $this->assertTrue((bool)get_comment_meta($comment->comment_ID, '_sent_via_outlook', true));
}
```

#### Test 3.4: Reply with Attachments

**Test Case**: `test_send_reply_with_attachments`

**Description**: Verify attachments in portal replies are sent via email

**Setup**:
1. Create email-sourced ticket
2. Create test file

**Steps**:
1. Send reply with attachment
2. Verify email sent
3. Verify Graph API shows attachment

**Expected Result**:
- Email sent with attachment
- Attachment is base64 encoded
- File received correctly

**Test Code**:
```php
public function test_send_reply_with_attachments() {
    $ticket_id = $this->create_email_ticket('sender@example.com');
    
    // Create test file
    $file_path = $this->create_test_file('test-doc.txt', 'Test content');
    
    $reply_handler = new LGP_Email_Reply();
    $comment_id = $reply_handler->send_reply(
        $ticket_id,
        'See attached document',
        1,
        array(
            array('path' => $file_path, 'name' => 'test-doc.txt')
        )
    );
    
    $this->assertGreater($comment_id, 0);
    
    // Verify attachment metadata
    $attachments = get_comment_meta($comment_id, '_attachment', false);
    $this->assertGreater(count($attachments), 0);
    $this->assertEquals('test-doc.txt', $attachments[0]['name']);
}
```

## Integration Tests

### Test 4.1: Complete Email to Ticket to Reply Flow

**Test Case**: `test_complete_email_flow`

**Description**: Test entire lifecycle: email → ticket → reply → Outlook detection

**Steps**:
1. Send email to shared mailbox
2. Sync (create ticket)
3. Add portal reply
4. Detect reply back from Outlook
5. Verify all metadata

**Expected Result**:
- Ticket created with email source
- Reply sent via email
- Outlook reply detected
- All metadata preserved

**Test Code**:
```php
public function test_complete_email_flow() {
    // Step 1: Send email
    $sender = 'customer@example.com';
    $subject = 'Help with my account';
    $this->send_test_email($sender, $subject, 'I need help...');
    
    // Step 2: Sync to create ticket
    delete_transient('lgp_email_delta_token');
    $ingest = new LGP_Email_Ingest();
    $stats1 = $ingest->sync_messages();
    $this->assertEquals(1, $stats1['created']);
    
    $ticket = $this->get_last_created_ticket();
    $ticket_id = $ticket->ID;
    
    // Step 3: Verify ticket properties
    $this->assertTrue((bool)get_post_meta($ticket_id, '_email_source', true));
    $this->assertEquals($sender, get_post_meta($ticket_id, '_sender_email', true));
    $this->assertEquals($subject, $ticket->post_title);
    
    // Step 4: Send portal reply
    $reply_handler = new LGP_Email_Reply();
    $comment_id = $reply_handler->send_reply(
        $ticket_id,
        '<p>Thank you for contacting us. We will assist you shortly.</p>',
        1
    );
    
    $this->assertGreater($comment_id, 0);
    
    // Step 5: Simulate Outlook reply
    $this->send_outlook_reply($ticket_id, 'Customer Name', 'Thank you!');
    
    // Step 6: Detect Outlook reply
    $count = $reply_handler->detect_outlook_replies();
    $this->assertGreater($count, 0);
    
    // Step 7: Verify all replies visible
    $comments = get_comments(array('post_id' => $ticket_id));
    $this->assertGreaterThanOrEqual(2, count($comments)); // Portal + Outlook
}
```

### Test 4.2: Multiple Conversations

**Test Case**: `test_multiple_email_conversations`

**Description**: Test handling multiple independent email conversations

**Steps**:
1. Send 3 emails from different senders
2. Sync all
3. Reply to each
4. Verify separation

**Expected Result**:
- 3 tickets created
- Replies don't cross-thread
- Each has separate conversation ID

**Test Code**:
```php
public function test_multiple_email_conversations() {
    // Create 3 conversations
    $emails = array(
        array('support1@example.com', 'Issue 1', 'Problem 1'),
        array('support2@example.com', 'Issue 2', 'Problem 2'),
        array('support3@example.com', 'Issue 3', 'Problem 3')
    );
    
    foreach ($emails as $email_data) {
        $this->send_test_email(...$email_data);
    }
    
    // Sync all
    delete_transient('lgp_email_delta_token');
    $ingest = new LGP_Email_Ingest();
    $stats = $ingest->sync_messages();
    $this->assertEquals(3, $stats['created']);
    
    // Get all tickets
    $tickets = get_posts(array(
        'post_type'  => 'ticket',
        'meta_key'   => '_email_source',
        'meta_value' => 1,
        'numberposts' => 3
    ));
    
    $this->assertEquals(3, count($tickets));
    
    // Verify conversation IDs are different
    $conversation_ids = array_map(
        fn($t) => get_post_meta($t->ID, '_email_conversation_id', true),
        $tickets
    );
    
    $this->assertEquals(3, count(array_unique($conversation_ids)));
}
```

## Performance Tests

### Test 5.1: Bulk Email Sync

**Test Case**: `test_bulk_email_sync_performance`

**Description**: Test sync performance with many emails

**Setup**:
1. Create 100 test emails

**Steps**:
1. Measure sync time
2. Verify all processed
3. Check memory usage

**Expected Result**:
- All 100 emails synced in < 30 seconds
- Memory usage stable
- No timeout

**Test Code**:
```php
public function test_bulk_email_sync_performance() {
    // Create 100 emails
    for ($i = 0; $i < 100; $i++) {
        $this->send_test_email(
            "bulk{$i}@example.com",
            "Bulk Email {$i}",
            "Body {$i}"
        );
    }
    
    delete_transient('lgp_email_delta_token');
    
    $start = microtime(true);
    $ingest = new LGP_Email_Ingest();
    $stats = $ingest->sync_messages();
    $duration = microtime(true) - $start;
    
    $this->assertLessThan(30, $duration); // Should complete in 30 seconds
    $this->assertEquals(100, $stats['total']);
    $this->assertEquals(0, $stats['errors']);
}
```

## Error Handling Tests

### Test 6.1: Missing Sender Email

**Test Case**: `test_error_missing_sender`

**Description**: Test handling of email without sender

**Setup**:
1. Create malformed email (no sender)

**Steps**:
1. Sync emails
2. Verify error is recorded
3. Verify sync continues

**Expected Result**:
- Error recorded in stats
- Sync completes
- Other emails still processed

### Test 6.2: Invalid Graph Response

**Test Case**: `test_error_invalid_graph_response`

**Description**: Test handling of Graph API errors

**Setup**:
1. Mock Graph API to return error
2. Try to sync

**Steps**:
1. Trigger sync
2. Verify error handling
3. Verify retry logic

**Expected Result**:
- Error caught and logged
- No crash
- Can retry

## Test Utilities

```php
// Helper functions for tests

protected function send_test_email($to, $subject, $body) {
    // Send via Graph API
}

protected function send_test_email_with_attachment($to, $subject, $body, $filename) {
    // Send with attachment
}

protected function send_outlook_reply($ticket_id, $from_name, $reply_text) {
    // Simulate Outlook reply
}

protected function create_email_ticket($sender_email) {
    // Create pre-configured email ticket
}

protected function create_test_user() {
    // Create WordPress user for testing
}

protected function create_test_file($filename, $content) {
    // Create temporary test file
}

protected function get_last_created_ticket() {
    // Get most recently created ticket
}
```

## Continuous Integration

### GitHub Actions Test Workflow

```yaml
name: Email Integration Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: wordpress
          MYSQL_ROOT_PASSWORD: root
        options: --health-cmd="mysqladmin ping" --health-interval=10s

    steps:
      - uses: actions/checkout@v2
      
      - name: Setup WordPress
        run: |
          # Setup WP test environment
          
      - name: Run Tests
        run: |
          wp eval 'PHPUnit_TextUI_Command::main(...);'
```

## Test Coverage Goals

- **Unit Tests**: 80%+ coverage
- **Integration Tests**: Critical paths covered
- **Error Scenarios**: All major error conditions
- **Performance**: Bulk operations verified

## Test Execution

```bash
# Run all tests
wp eval 'PHPUnit_TextUI_Command::main(array("--configuration=phpunit.xml"));'

# Run specific test class
wp eval 'PHPUnit_TextUI_Command::main(array("tests/TestEmailIngest.php"));'

# Run with coverage
wp eval 'PHPUnit_TextUI_Command::main(array("--coverage-html=coverage/"));'
```
