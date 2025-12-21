# Email Integration Database Schema

## Overview

The email integration uses WordPress post meta and comment meta to store email-related information. This document describes the schema.

## Post Meta (Tickets)

### Email Source Indicator
- **Key**: `_email_source`
- **Type**: boolean (1 or 0)
- **Description**: Flag indicating ticket was created from email
- **Example**: `1`

### Sender Email Address
- **Key**: `_sender_email`
- **Type**: string
- **Description**: Email address of the original sender
- **Example**: `customer@example.com`

### Graph Message ID
- **Key**: `_email_message_id`
- **Type**: string
- **Description**: Microsoft Graph API message ID for the original email
- **Example**: `AAMkADAwATM0MjE3AC...`
- **Used for**: Idempotency, tracking processed messages

### Conversation ID
- **Key**: `_email_conversation_id`
- **Type**: string
- **Description**: Microsoft Graph conversation ID for email threading
- **Example**: `AAQkADAwATM0MjE3AC...`
- **Used for**: Threading, detecting replies to same conversation

### Internet Message ID
- **Key**: `_email_internet_message_id`
- **Type**: string
- **Description**: RFC 5322 internet message ID (In-Reply-To header)
- **Example**: `<AM5PR12MB1234.namprd12.prod.outlook.com@contoso.com>`
- **Used for**: Email client threading (Outlook, etc)

### Received Date
- **Key**: `_received_date`
- **Type**: datetime string (MySQL format)
- **Description**: When the email was received
- **Example**: `2024-01-15 14:30:00`

### Last Reply Date
- **Key**: `_last_reply_date`
- **Type**: datetime string (MySQL format)
- **Description**: Timestamp of last reply (email or portal)
- **Example**: `2024-01-15 15:45:00`

### Contact ID (Foreign Key)
- **Key**: `_contact_id`
- **Type**: integer (post ID)
- **Description**: References the contact post created/linked from email sender
- **Example**: `456`

### Company ID (Foreign Key)
- **Key**: `_company_id`
- **Type**: integer (post ID)
- **Description**: Company associated with the contact
- **Example**: `789`

## Comment Meta (Ticket Replies)

### Email Source Flag
- **Key**: `_email_source`
- **Type**: boolean (1 or 0)
- **Description**: Reply came from email
- **Example**: `1`

### Sent Via Portal
- **Key**: `_sent_via_portal`
- **Type**: boolean (1 or 0)
- **Description**: Reply was sent by portal user to email
- **Example**: `1`

### Sent Via Outlook
- **Key**: `_sent_via_outlook`
- **Type**: boolean (1 or 0)
- **Description**: Reply was sent via Outlook (detected from email)
- **Example**: `1`

### Email Message ID
- **Key**: `_email_message_id`
- **Type**: string
- **Description**: Graph message ID for reply email
- **Example**: `AAMkADAwATM0MjE3AC...`

### Email Sent Flag
- **Key**: `_email_sent`
- **Type**: boolean (1 or 0)
- **Description**: Portal reply was successfully sent via email
- **Example**: `1`

### Reply To Email Address
- **Key**: `_reply_to_email`
- **Type**: string
- **Description**: Email address this reply was sent to
- **Example**: `customer@example.com`

### Sent Timestamp
- **Key**: `_sent_timestamp`
- **Type**: datetime string
- **Description**: When portal reply was sent via email
- **Example**: `2024-01-15 14:35:00`

### Received Date
- **Key**: `_received_date`
- **Type**: datetime string
- **Description**: When email reply was received
- **Example**: `2024-01-15 14:32:00`

### Conversation ID
- **Key**: `_conversation_id`
- **Type**: string
- **Description**: Graph conversation ID
- **Example**: `AAQkADAwATM0MjE3AC...`

### Internet Message ID
- **Key**: `_internet_message_id`
- **Type**: string
- **Description**: RFC 5322 message ID
- **Example**: `<AM5PR12MB1234...@contoso.com>`

### Email Send Error
- **Key**: `_email_send_error`
- **Type**: string
- **Description**: Error message if email send failed
- **Example**: `Failed to get sender email for ticket`

### Attachment Files
- **Key**: `_attachment` (repeating)
- **Type**: serialized array
- **Description**: Local attachment references
- **Structure**:
  ```php
  array(
      'name'     => 'document.pdf',
      'path'     => '/wp-content/uploads/ticket-replies/123/document.pdf',
      'size'     => 45678,
      'mime'     => 'application/pdf',
      'saved_at' => '2024-01-15 14:30:00'
  )
  ```

### Deleted In Portal
- **Key**: `_deleted_in_portal`
- **Type**: boolean (1 or 0)
- **Description**: Reply was deleted in portal but sent via email (can't delete from Outlook)
- **Example**: `1`

## Options (WordPress)

### Shared Mailbox Email
- **Key**: `lgp_shared_mailbox`
- **Type**: string
- **Description**: Email address of shared mailbox
- **Example**: `support@poolsafe.example.com`

### Azure Tenant ID
- **Key**: `lgp_azure_tenant_id`
- **Type**: string
- **Description**: Azure AD tenant ID
- **Example**: `12345678-1234-1234-1234-123456789012`

### Azure Client ID
- **Key**: `lgp_azure_client_id`
- **Type**: string
- **Description**: Azure AD application client ID
- **Example**: `aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee`

### Azure Client Secret
- **Key**: `lgp_azure_client_secret`
- **Type**: string (encrypted)
- **Description**: Azure AD application client secret
- **Example**: Stored securely, use env var in production

### Email Delta Token
- **Key**: `lgp_email_delta_token` (transient)
- **Type**: string (URL)
- **Description**: Delta sync token for efficient email fetching
- **Expiration**: 24 hours
- **Example**: `https://graph.microsoft.com/v1.0/users/...?$deltatoken=...`

## SQL Schema Reference

### Create Meta Indexes

For performance, add database indexes:

```sql
-- For email_ingest queries
ALTER TABLE wp_postmeta ADD INDEX idx_email_message_id 
  (meta_key(20), meta_value(100));

ALTER TABLE wp_postmeta ADD INDEX idx_conversation_id 
  (meta_key(25), meta_value(100));

-- For comment queries
ALTER TABLE wp_commentmeta ADD INDEX idx_comment_email_message_id 
  (meta_key(20), meta_value(100));

-- For contact queries
ALTER TABLE wp_postmeta ADD INDEX idx_contact_email 
  (meta_key(20), meta_value(100));
```

### Sample Queries

Get all email-sourced tickets:

```sql
SELECT post_id 
FROM wp_postmeta 
WHERE meta_key = '_email_source' AND meta_value = '1'
  AND post_id IN (SELECT ID FROM wp_posts WHERE post_type = 'ticket');
```

Get all replies for a ticket by email source:

```sql
SELECT comment_ID, meta_value 
FROM wp_commentmeta cm
JOIN wp_comments c ON cm.comment_id = c.comment_ID
WHERE cm.meta_key = '_email_source' 
  AND cm.meta_value = '1'
  AND c.comment_post_ID = 123;
```

Get tickets with replies sent via portal:

```sql
SELECT DISTINCT c.comment_post_ID as ticket_id
FROM wp_commentmeta cm
JOIN wp_comments c ON cm.comment_id = c.comment_ID
WHERE cm.meta_key = '_sent_via_portal' 
  AND cm.meta_value = '1'
ORDER BY c.comment_date DESC;
```

## Data Migration

If migrating from another system:

```php
// Programmatically add email metadata to existing tickets
$tickets = get_posts(array(
    'post_type'      => 'ticket',
    'posts_per_page' => -1,
    'post_status'    => 'any'
));

foreach ($tickets as $ticket) {
    // Check if already has email metadata
    if (!get_post_meta($ticket->ID, '_email_source', true)) {
        // Add migration flag
        update_post_meta($ticket->ID, '_email_source', 0);
        update_post_meta($ticket->ID, '_migration_source', 'legacy_system');
    }
}
```

## Data Export

Export email tickets and replies:

```php
$tickets = get_posts(array(
    'post_type'  => 'ticket',
    'meta_key'   => '_email_source',
    'meta_value' => 1
));

$export = array();
foreach ($tickets as $ticket) {
    $data = array(
        'id'              => $ticket->ID,
        'title'           => $ticket->post_title,
        'sender'          => get_post_meta($ticket->ID, '_sender_email', true),
        'created'         => $ticket->post_date,
        'received'        => get_post_meta($ticket->ID, '_received_date', true),
        'last_reply'      => get_post_meta($ticket->ID, '_last_reply_date', true),
        'replies'         => count(get_comments(array('post_id' => $ticket->ID)))
    );
    $export[] = $data;
}

// Convert to CSV or JSON
wp_send_json($export);
```

## Cleanup and Maintenance

### Archive Old Tickets

```php
$args = array(
    'post_type'      => 'ticket',
    'meta_key'       => '_email_source',
    'meta_value'     => 1,
    'posts_per_page' => -1,
    'date_query'     => array(
        'before' => date('Y-m-d', strtotime('-1 year'))
    )
);

$old_tickets = get_posts($args);
foreach ($old_tickets as $ticket) {
    // Archive or delete
    wp_update_post(array(
        'ID'          => $ticket->ID,
        'post_status' => 'archived'
    ));
}
```

### Cleanup Failed Email Sends

```php
$failed_comments = get_comments(array(
    'meta_key'    => '_email_send_error',
    'meta_compare' => 'EXISTS'
));

foreach ($failed_comments as $comment) {
    // Retry or notify admin
    $error = get_comment_meta($comment->comment_ID, '_email_send_error', true);
    error_log("Pending email failure for comment {$comment->comment_ID}: {$error}");
}
```
