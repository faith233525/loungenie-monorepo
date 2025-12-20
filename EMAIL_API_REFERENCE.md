# Email Integration API Documentation

## Overview

The Pool Safe Portal email integration provides REST API endpoints and PHP classes for managing shared mailbox email operations.

## REST API Endpoints

All endpoints require authentication (WordPress logged-in user).

### 1. Manual Email Sync

**Endpoint**: `POST /wp-json/lgp/v1/email/sync`

**Permissions Required**: `manage_options`

**Description**: Manually trigger email synchronization from the shared mailbox.

**Request**:
```bash
curl -X POST https://yoursite.com/wp-json/lgp/v1/email/sync \
  -H "X-WP-Nonce: $(wp eval 'echo wp_create_nonce("wp_rest");')" \
  -H "Content-Type: application/json"
```

**Response**:
```json
{
  "total": 5,
  "created": 3,
  "updated": 1,
  "skipped": 0,
  "errors": 1,
  "error_list": [
    "Failed to decode attachment for message AAMkADAwATM0..."
  ]
}
```

**Status Codes**:
- `200 OK`: Sync completed
- `403 Forbidden`: User doesn't have `manage_options` capability

### 2. Send Email Reply

**Endpoint**: `POST /wp-json/lgp/v1/email/send-reply`

**Permissions Required**: `edit_post` for the ticket

**Description**: Send a reply to a ticket via email through the shared mailbox.

**Request**:
```bash
curl -X POST https://yoursite.com/wp-json/lgp/v1/email/send-reply \
  -H "X-WP-Nonce: $(wp eval 'echo wp_create_nonce("wp_rest");')" \
  -H "Content-Type: application/json" \
  -d '{
    "ticket_id": 123,
    "content": "<p>Thank you for your inquiry.</p><p>We will get back to you shortly.</p>"
  }'
```

**Parameters**:
- `ticket_id` (required, integer): ID of the ticket to reply to
- `content` (required, string): HTML content of the reply

**Response**:
```json
{
  "comment_id": 456,
  "ticket_id": 123,
  "sent_to": "customer@example.com",
  "sent_at": "2024-01-15 14:35:00"
}
```

**Status Codes**:
- `200 OK`: Reply sent successfully
- `400 Bad Request`: Missing required parameters
- `403 Forbidden`: User doesn't have access to this ticket
- `400 Bad Request`: {error: "send_error", message: "Failed to get sender email for ticket"}

**Error Examples**:
```json
{
  "code": "send_error",
  "message": "Failed to get sender email for ticket"
}
```

### 3. Get Ticket Email Status

**Endpoint**: `GET /wp-json/lgp/v1/email/ticket-status/{ticket_id}`

**Permissions Required**: `read_post` for the ticket

**Description**: Get email integration status for a specific ticket.

**Request**:
```bash
curl -X GET https://yoursite.com/wp-json/lgp/v1/email/ticket-status/123 \
  -H "X-WP-Nonce: $(wp eval 'echo wp_create_nonce("wp_rest");')"
```

**Response**:
```json
{
  "is_email_ticket": true,
  "sender_email": "customer@example.com",
  "replies_via_outlook": 2,
  "replies_via_portal": 3,
  "total_replies": 5,
  "conversation_id": "AAQkADAwATM0MjE3AC...",
  "message_id": "AAMkADAwATM0MjE3AC...",
  "received_date": "2024-01-15 14:30:00",
  "last_reply_date": "2024-01-16 10:15:00"
}
```

**Status Codes**:
- `200 OK`: Status retrieved
- `403 Forbidden`: User doesn't have access to this ticket
- `404 Not Found`: Ticket not found

## PHP API

### LGP_Email_Ingest

**Namespace**: Global

**Purpose**: Fetch and process emails from shared mailbox.

#### Constructor

```php
$ingest = new LGP_Email_Ingest();
```

#### Methods

##### sync_messages()

**Signature**:
```php
public function sync_messages(): array
```

**Description**: Synchronize messages from shared mailbox. Uses delta sync for efficiency.

**Returns**: Array with sync statistics:
```php
array(
    'total'      => 5,        // Total messages processed
    'created'    => 3,        // New tickets created
    'updated'    => 1,        // Existing tickets updated
    'skipped'    => 0,        // Messages skipped
    'errors'     => 1,        // Processing errors
    'error_list' => array()   // Error messages
)
```

**Example**:
```php
$ingest = new LGP_Email_Ingest();
$stats = $ingest->sync_messages();

if ($stats['errors'] > 0) {
    error_log('Email sync had errors: ' . print_r($stats['error_list'], true));
}
```

**Triggers**:
- Scheduled via WordPress cron: `lgp_sync_emails` (every 5 minutes)
- Can also be triggered manually: `do_action('lgp_sync_emails')`

### LGP_Email_Reply

**Namespace**: Global

**Purpose**: Send email replies and detect Outlook responses.

#### Constructor

```php
$reply_handler = new LGP_Email_Reply();
```

#### Methods

##### send_reply()

**Signature**:
```php
public function send_reply(
    int $ticket_id, 
    string $reply_content, 
    int $author_id = 1, 
    array $attachments = array()
): int
```

**Description**: Send a reply to a ticket via email.

**Parameters**:
- `$ticket_id` (int, required): WordPress post ID of the ticket
- `$reply_content` (string, required): HTML content to send
- `$author_id` (int, optional): WordPress user ID of the reply author (default: 1)
- `$attachments` (array, optional): Array of attachments:
  ```php
  array(
      array('path' => '/path/to/file.pdf', 'name' => 'document.pdf'),
      array('path' => '/path/to/image.jpg', 'name' => 'image.jpg')
  )
  ```

**Returns**: Comment ID of the created reply

**Throws**: `Exception` if:
- Ticket not found
- No sender email metadata on ticket
- Graph API error

**Example**:
```php
$reply_handler = new LGP_Email_Reply();

try {
    $comment_id = $reply_handler->send_reply(
        123,  // ticket_id
        '<p>Thank you for contacting us!</p>',  // content
        45,   // author_id (WordPress user)
        array(
            array('path' => '/tmp/invoice.pdf', 'name' => 'invoice.pdf')
        )
    );
    
    echo "Reply sent as comment: $comment_id";
} catch (Exception $e) {
    error_log("Failed to send reply: " . $e->getMessage());
}
```

##### detect_outlook_replies()

**Signature**:
```php
public function detect_outlook_replies(): int
```

**Description**: Check all open email tickets for replies sent via Outlook.

**Returns**: Number of new replies detected and recorded

**Example**:
```php
$reply_handler = new LGP_Email_Reply();
$count = $reply_handler->detect_outlook_replies();

echo "Found $count replies from Outlook";
```

**Triggers**:
- Scheduled via WordPress cron: `lgp_detect_outlook_replies` (every 10 minutes)

##### delete_reply()

**Signature**:
```php
public function delete_reply(int $comment_id): bool
```

**Description**: Delete a reply (marks as deleted in portal, cannot delete from Outlook).

**Parameters**:
- `$comment_id` (int, required): WordPress comment ID

**Returns**: Boolean success

**Example**:
```php
$reply_handler = new LGP_Email_Reply();
$deleted = $reply_handler->delete_reply(456);
```

### LGP_Graph_Client

**Namespace**: Global

**Purpose**: Low-level Microsoft Graph API client.

#### Constructor

```php
$graph = new LGP_Graph_Client();
```

#### Methods

##### get_messages()

**Signature**:
```php
public function get_messages(string $delta_token = null): array
```

**Description**: Fetch messages with pagination and delta sync support.

**Parameters**:
- `$delta_token` (string, optional): Delta token from previous response

**Returns**: Array of messages from Graph API

**Example**:
```php
$graph = new LGP_Graph_Client();

$response = $graph->get_messages();
foreach ($response['value'] as $message) {
    echo $message['subject'];
}

// Next batch with delta token
if (!empty($response['@odata.deltaLink'])) {
    $next = $graph->get_messages($response['@odata.deltaLink']);
}
```

##### send_mail_message()

**Signature**:
```php
public function send_mail_message(
    string $mailbox, 
    array $message_payload
): array
```

**Description**: Send a message via Graph API with custom payload.

**Parameters**:
- `$mailbox` (string, required): Email address of mailbox
- `$message_payload` (array, required): Full Graph API message payload

**Returns**: Response from Graph API

**Example**:
```php
$payload = array(
    'message' => array(
        'subject' => 'Hello',
        'body' => array(
            'contentType' => 'HTML',
            'content' => '<p>Hello world</p>'
        ),
        'toRecipients' => array(
            array('emailAddress' => array('address' => 'user@example.com'))
        )
    ),
    'saveToSentItems' => true
);

$response = $graph->send_mail_message('support@company.com', $payload);
```

##### get_attachments_with_content()

**Signature**:
```php
public function get_attachments_with_content(string $message_id): array
```

**Description**: Get all attachments for a message including base64 content.

**Parameters**:
- `$message_id` (string, required): Graph message ID

**Returns**: Array of attachments with `contentBytes` base64-encoded

##### get_message()

**Signature**:
```php
public function get_message(string $message_id): array
```

**Description**: Get a specific message by ID.

**Parameters**:
- `$message_id` (string, required): Graph message ID

**Returns**: Full message object from Graph API

##### mark_as_read()

**Signature**:
```php
public function mark_as_read(string $message_id): bool
```

**Description**: Mark a message as read in the mailbox.

**Parameters**:
- `$message_id` (string, required): Graph message ID

**Returns**: Boolean success

##### get_folders()

**Signature**:
```php
public function get_folders(): array
```

**Description**: Get list of mailbox folders.

**Returns**: Array of folder objects from Graph API

## Hooks and Filters

### Actions

#### lgp_sync_emails

**Description**: Scheduled action to sync emails from shared mailbox.

**Frequency**: Every 5 minutes (via WordPress cron)

**Example**:
```php
add_action('lgp_sync_emails', function() {
    // Custom sync logic
});
```

#### lgp_detect_outlook_replies

**Description**: Scheduled action to detect replies sent via Outlook.

**Frequency**: Every 10 minutes (via WordPress cron)

#### comment_post

**Description**: Called when a new comment/reply is created on a ticket.

**Automatically triggers email sending** for email-sourced tickets.

**Parameters**: `$comment_id`, `$comment` (WP_Comment object)

### Filters

#### lgp_ticket_meta

**Description**: Filter to customize ticket metadata.

**Signature**:
```php
apply_filters('lgp_ticket_meta', $meta, $ticket_id)
```

**Parameters**:
- `$meta` (array): Ticket metadata
- `$ticket_id` (int): Ticket post ID

**Returns**: Modified metadata array

**Example**:
```php
add_filter('lgp_ticket_meta', function($meta, $ticket_id) {
    if (!empty($meta['email'])) {
        $meta['email']['custom_field'] = 'custom_value';
    }
    return $meta;
}, 10, 2);
```

#### lgp_get_ticket_email_status

**Description**: Get email status information for a ticket.

**Signature**:
```php
apply_filters('lgp_get_ticket_email_status', $ticket_id)
```

**Parameters**:
- `$ticket_id` (int): Ticket post ID

**Returns**: Status array

**Example**:
```php
$status = apply_filters('lgp_get_ticket_email_status', 123);
echo "Email replies: " . $status['total_replies'];
```

## Error Handling

### Common Error Codes

| Code | Message | Solution |
|------|---------|----------|
| `forbidden` | User lacks required permissions | Check user capabilities |
| `sync_error` | Email sync failed | Check Graph API token, logs |
| `send_error` | Email send failed | Check sender email, permissions |
| `invalid_params` | Missing required parameters | Check API documentation |
| `attachment_error` | Failed to process attachment | Check file permissions, size |

### Logging

All operations are logged to `/wp-content/logs/`:
- `email-ingest.log` - Email ingestion operations
- `email-reply.log` - Reply sending operations
- WordPress `debug.log` - General errors

### Debug Mode

Enable detailed logging:
```php
// In wp-config.php
define('LGP_EMAIL_DEBUG', true);
```

## Rate Limiting

### Graph API Limits

Microsoft Graph API enforces rate limits:
- 1000 requests per minute per app
- 10,000 concurrent requests

**Current implementation**:
- Syncs every 5 minutes (12 sync calls/hour)
- Uses delta sync for efficiency
- Should easily stay within limits

### WordPress Cron Considerations

If your site has low traffic, WordPress cron may not execute regularly:

**Solution**: Set up real cron jobs:
```bash
# In system crontab
*/5 * * * * curl -X POST https://yoursite.com/wp-json/lgp/v1/email/sync
```

## Security Considerations

### API Authentication

All endpoints require:
- WordPress login (logged-in user)
- Nonce verification (for POST requests)
- Appropriate user capabilities

### Data Protection

- Store credentials in environment variables
- Use HTTPS for all API calls
- Implement audit logging for sensitive operations
- Validate and sanitize all inputs

### Attachment Security

- Validate file types
- Store attachments outside web root
- Implement virus scanning
- Limit file sizes

## Examples

### Complete Ticket to Email Flow

```php
// 1. Sync emails (automatic, runs via cron)
$ingest = new LGP_Email_Ingest();
$stats = $ingest->sync_messages();
// Creates ticket from incoming email

// 2. Get ticket status
$status = apply_filters('lgp_get_ticket_email_status', $ticket_id);
echo "Ticket has " . $status['total_replies'] . " replies";

// 3. Send reply via portal
$reply_handler = new LGP_Email_Reply();
$comment_id = $reply_handler->send_reply(
    $ticket_id,
    'Thank you for your message!',
    get_current_user_id()
);

// 4. Detect Outlook replies
$count = $reply_handler->detect_outlook_replies();
echo "Detected $count new replies from Outlook";
```

### Custom Ticket Creation from Email

```php
// Custom processing of email before ticket creation
add_action('lgp_sync_emails', function() {
    $graph = new LGP_Graph_Client();
    $messages = $graph->get_messages();
    
    foreach ($messages['value'] as $message) {
        // Custom business logic
        if (strpos($message['subject'], 'URGENT') !== false) {
            // Set higher priority
            $priority = 'high';
        }
        
        // Process as normal...
    }
});
```

### Monitor Failed Sends

```php
// Check for failed email sends
$failed = get_comments(array(
    'meta_key' => '_email_send_error',
    'meta_compare' => 'EXISTS'
));

foreach ($failed as $comment) {
    $ticket_id = $comment->comment_post_ID;
    $error = get_comment_meta($comment->comment_ID, '_email_send_error', true);
    
    // Notify admin or retry
    wp_mail(
        get_option('admin_email'),
        "Failed to send email reply on ticket $ticket_id",
        "Error: $error"
    );
}
```

## Troubleshooting

### Check Logs

```bash
# View recent errors
tail -f /wp-content/logs/email-ingest.log
tail -f /wp-content/logs/email-reply.log
```

### Test Graph Connection

```php
$graph = new LGP_Graph_Client();
try {
    $response = $graph->get_folders();
    echo "Connection OK, folders: " . count($response);
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

### Manual Email Sync

```bash
# Via wp-cli
wp eval 'do_action("lgp_sync_emails");'

# Via cURL
curl -X POST https://yoursite.com/wp-json/lgp/v1/email/sync
```

## Versioning

**Current Version**: 1.0.0

**API Version**: 1.0 (via `/wp-json/lgp/v1/`)

**Graph API Version**: v1.0 (stable)

## Support and Contribution

For issues, improvements, or questions:
1. Check the logs in `/wp-content/logs/`
2. Review this documentation
3. Test using Microsoft Graph Explorer
4. Check Azure AD app configuration
